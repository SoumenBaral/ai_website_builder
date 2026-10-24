<?php

namespace App\Services\Ai\Providers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Puku (puku.sh) — an Anthropic-Messages-compatible gateway at api-cli.puku.sh.
 *
 * Auth is the `pk_live_…` user key in the `x-api-key` header. Models: `opus`
 * (Claude Opus), `puku-ai-2.8`, `puku-ai-2.7`.
 *
 * JSON is enforced with Anthropic structured outputs. Because a gateway may lag
 * behind the upstream API, the request is tried in three modes, falling back on
 * a 400 that names the unsupported field:
 *   1. `output_config.format`  (current GA shape)
 *   2. `output_format` + beta header (older shape)
 *   3. prompt-only: instruct JSON and prefill the reply with "{"
 */
class PukuProvider implements ChatProvider
{
    public const DEFAULT_BASE_URL = 'https://api-cli.puku.sh';

    private const MODES = ['output_config', 'output_format', 'prompt'];

    /** @param array{generate: string, edit: string} $models */
    public function __construct(
        private string $apiKey,
        private array $models,
        private string $baseUrl = self::DEFAULT_BASE_URL,
        private int $timeout = 150,
    ) {
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    public function name(): string
    {
        return 'puku';
    }

    public function label(): string
    {
        return 'Puku';
    }

    public function model(string $purpose): string
    {
        return $this->models[$purpose] ?? $this->models['edit'];
    }

    public function complete(ChatRequest $request): ChatResult
    {
        return $this->send($request, null);
    }

    public function stream(ChatRequest $request, callable $onDelta): ChatResult
    {
        return $this->send($request, $onDelta);
    }

    /* ------------------------------------------------------------------ */

    private function send(ChatRequest $request, ?callable $onDelta): ChatResult
    {
        $lastError = null;

        foreach (self::MODES as $mode) {
            [$body, $headers, $prefill] = $this->build($request, $mode, $onDelta !== null);

            try {
                $response = $this->http($headers, $onDelta !== null)->post('/v1/messages', $body);
            } catch (ConnectionException $e) {
                throw new ProviderException(ProviderException::TRANSPORT, $e->getMessage(), null, $e);
            }

            if ($response->successful()) {
                return $onDelta
                    ? $this->readStream($response, $onDelta, $prefill)
                    : $this->readJson($response, $prefill);
            }

            $lastError = $this->failure($response);
            if (!$this->shouldFallBack($lastError, $mode)) {
                throw $lastError;
            }
            Log::info('Puku structured output mode not accepted, falling back', ['mode' => $mode, 'error' => $lastError->getMessage()]);
        }

        throw $lastError ?? new ProviderException(ProviderException::TRANSPORT, 'Puku request failed');
    }

    /** @return array{0: array, 1: array<string, string>, 2: string} body, extra headers, prefilled text */
    private function build(ChatRequest $request, string $mode, bool $stream): array
    {
        $system = $request->systemPrompt();
        $messages = array_map(fn ($m) => ['role' => $m['role'], 'content' => self::content($m['content'])], $request->conversation());
        $headers = [];
        $prefill = '';

        $body = [
            'model' => $request->model,
            'max_tokens' => $request->maxTokens,
            'temperature' => $request->temperature,
            'messages' => $messages,
        ];

        $format = ['type' => 'json_schema', 'schema' => AnthropicSchema::sanitize($request->schema)];

        if ($mode === 'output_config') {
            $body['output_config'] = ['format' => $format];
        } elseif ($mode === 'output_format') {
            $body['output_format'] = $format;
            $headers['anthropic-beta'] = 'structured-outputs-2025-11-13';
        } else {
            // No schema enforcement available: ask firmly and start the answer for the model.
            $system = ($system ? $system . "\n\n" : '')
                . "Respond with a single JSON object and nothing else — no prose, no markdown fences. It must match this JSON schema exactly:\n"
                . json_encode($request->schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $prefill = '{';
            $messages[] = ['role' => 'assistant', 'content' => $prefill];
            $body['messages'] = $messages;
        }

        if ($system !== null) {
            $body['system'] = $system;
        }
        if ($stream) {
            $body['stream'] = true;
        }

        return [$body, $headers, $prefill];
    }

    /** OpenAI-style content parts → Anthropic content blocks (strings pass through). */
    private static function content(string|array $content): string|array
    {
        if (is_string($content)) {
            return $content;
        }
        $blocks = [];
        foreach ($content as $part) {
            if (($part['type'] ?? '') === 'image_url') {
                $url = is_array($part['image_url']) ? $part['image_url']['url'] : $part['image_url'];
                $blocks[] = str_starts_with($url, 'data:')
                    ? ['type' => 'image', 'source' => ['type' => 'base64', 'media_type' => explode(';', substr($url, 5))[0], 'data' => substr($url, strpos($url, ',') + 1)]]
                    : ['type' => 'image', 'source' => ['type' => 'url', 'url' => $url]];
            } elseif (($part['type'] ?? '') === 'text') {
                $blocks[] = ['type' => 'text', 'text' => $part['text']];
            }
        }

        return $blocks;
    }

    private function http(array $headers, bool $stream): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)
            ->withHeaders([
                'x-api-key' => $this->apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ] + $headers)
            ->timeout($this->timeout)
            ->connectTimeout(15)
            ->withOptions($stream ? ['stream' => true] : []);
    }

    private function readJson(Response $response, string $prefill): ChatResult
    {
        $json = $response->json();
        if (!is_array($json)) {
            throw new ProviderException(ProviderException::TRANSPORT, 'Puku returned a non-JSON response', $response->status());
        }

        $text = '';
        foreach ($json['content'] ?? [] as $block) {
            if (($block['type'] ?? '') === 'text') {
                $text .= $block['text'] ?? '';
            }
        }

        return new ChatResult(
            $prefill . $text,
            $this->finish($json['stop_reason'] ?? null),
            ['input' => $json['usage']['input_tokens'] ?? null, 'output' => $json['usage']['output_tokens'] ?? null],
        );
    }

    /** Parse Anthropic's SSE stream (message_start → content_block_delta* → message_delta → message_stop). */
    private function readStream(Response $response, callable $onDelta, string $prefill): ChatResult
    {
        $stream = $response->toPsrResponse()->getBody();
        $buffer = $prefill;
        $finish = null;
        $usage = ['input' => null, 'output' => null];
        $pending = '';
        $event = null;

        if ($prefill !== '') {
            $onDelta($prefill);
        }

        $handle = function (string $line) use (&$event, &$buffer, &$finish, &$usage, $onDelta) {
            if (str_starts_with($line, 'event:')) {
                $event = trim(substr($line, 6));

                return;
            }
            if (!str_starts_with($line, 'data:')) {
                return;
            }
            $data = json_decode(trim(substr($line, 5)), true);
            if (!is_array($data)) {
                return;
            }
            $type = $data['type'] ?? $event;

            switch ($type) {
                case 'message_start':
                    $usage['input'] = $data['message']['usage']['input_tokens'] ?? $usage['input'];
                    break;
                case 'content_block_delta':
                    if (($data['delta']['type'] ?? '') === 'text_delta') {
                        $delta = $data['delta']['text'] ?? '';
                        if ($delta !== '') {
                            $buffer .= $delta;
                            $onDelta($delta);
                        }
                    }
                    break;
                case 'message_delta':
                    $finish = $data['delta']['stop_reason'] ?? $finish;
                    $usage['output'] = $data['usage']['output_tokens'] ?? $usage['output'];
                    $usage['input'] = $data['usage']['input_tokens'] ?? $usage['input'];
                    break;
                case 'error':
                    throw new ProviderException(ProviderException::TRANSPORT, $data['error']['message'] ?? 'Puku stream error');
            }
        };

        while (!$stream->eof()) {
            $pending .= $stream->read(8192);
            while (($nl = strpos($pending, "\n")) !== false) {
                $line = rtrim(substr($pending, 0, $nl), "\r");
                $pending = substr($pending, $nl + 1);
                $handle($line);
            }
        }
        if (trim($pending) !== '') {
            $handle(trim($pending));
        }

        return new ChatResult($buffer, $this->finish($finish), $usage);
    }

    private function finish(?string $stopReason): ?string
    {
        return match ($stopReason) {
            null => null,
            'max_tokens' => ChatResult::FINISH_LENGTH,
            'end_turn', 'stop_sequence' => ChatResult::FINISH_STOP,
            default => $stopReason,
        };
    }

    private function failure(Response $response): ProviderException
    {
        $status = $response->status();
        $error = $response->json('error') ?? [];
        $message = is_array($error) ? ($error['message'] ?? '') : (string) $error;
        $code = is_array($error) ? ($error['code'] ?? $error['type'] ?? '') : '';
        $message = $message !== '' ? $message : (trim($response->body()) ?: "HTTP {$status}");
        $message = "Puku {$status}" . ($code ? " [{$code}]" : '') . ": {$message}";

        $lower = strtolower($message);
        $kind = match (true) {
            // puku.sh reports an exhausted subscription as 429 chat_req_limit_exceeded "… Upgrade your plan".
            $status === 429 && (str_contains($code, 'quota') || str_contains($code, 'limit_exceeded') || str_contains($lower, 'quota') || str_contains($lower, 'upgrade your plan')) => ProviderException::QUOTA,
            $status === 429 => ProviderException::RATE_LIMITED,
            $status === 402 => ProviderException::QUOTA,
            $status >= 500 => ProviderException::TRANSPORT,
            default => ProviderException::REJECTED,
        };

        return new ProviderException($kind, $message, $status);
    }

    /** A 400 that complains about the structured-output field means "try the next shape". */
    private function shouldFallBack(ProviderException $e, string $mode): bool
    {
        if ($mode === 'prompt' || $e->status !== 400) {
            return false;
        }
        $lower = strtolower($e->getMessage());

        return str_contains($lower, 'output_config') || str_contains($lower, 'output_format')
            || str_contains($lower, 'structured') || str_contains($lower, 'beta') || str_contains($lower, 'schema');
    }
}
