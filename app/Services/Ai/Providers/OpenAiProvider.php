<?php

namespace App\Services\Ai\Providers;

use OpenAI\Exceptions\ErrorException;
use OpenAI\Exceptions\RateLimitException;
use OpenAI\Exceptions\ServerException;
use OpenAI\Exceptions\TransporterException;
use OpenAI\Exceptions\UnserializableResponse;
use OpenAI\Laravel\Facades\OpenAI;

/**
 * OpenAI Chat Completions with strict JSON-schema structured outputs.
 * Uses the openai-php/laravel facade so OpenAI::fake() keeps working in tests.
 */
class OpenAiProvider implements ChatProvider
{
    /** @param array{generate: string, edit: string} $models */
    public function __construct(private array $models) {}

    public function name(): string
    {
        return 'openai';
    }

    public function label(): string
    {
        return 'OpenAI';
    }

    public function model(string $purpose): string
    {
        return $this->models[$purpose] ?? $this->models['edit'];
    }

    public function complete(ChatRequest $request): ChatResult
    {
        try {
            $response = OpenAI::chat()->create($this->params($request));
        } catch (\Throwable $e) {
            throw $this->translate($e);
        }

        $choice = $response->choices[0] ?? null;

        return new ChatResult(
            $choice?->message->content,
            $choice?->finishReason,
            ['input' => $response->usage?->promptTokens, 'output' => $response->usage?->completionTokens],
        );
    }

    public function stream(ChatRequest $request, callable $onDelta): ChatResult
    {
        $params = $this->params($request);
        $params['stream_options'] = ['include_usage' => true];

        $buffer = '';
        $finish = null;
        $usage = ['input' => null, 'output' => null];

        try {
            $stream = OpenAI::chat()->createStreamed($params);

            foreach ($stream as $response) {
                if ($response->usage) {
                    $usage = ['input' => $response->usage->promptTokens, 'output' => $response->usage->completionTokens];
                }
                $choice = $response->choices[0] ?? null;
                if (!$choice) {
                    continue;
                }
                $delta = $choice->delta->content ?? '';
                if ($delta !== '') {
                    $buffer .= $delta;
                    $onDelta($delta);
                }
                $finish = $choice->finishReason ?? $finish;
            }
        } catch (\Throwable $e) {
            throw $this->translate($e);
        }

        return new ChatResult($buffer, $finish, $usage);
    }

    private function params(ChatRequest $request): array
    {
        return [
            'model' => $request->model,
            'messages' => $request->messages,
            'temperature' => $request->temperature,
            'max_completion_tokens' => $request->maxTokens,
            'response_format' => [
                'type' => 'json_schema',
                'json_schema' => ['name' => $request->schemaName, 'strict' => true, 'schema' => $request->schema],
            ],
        ];
    }

    private function translate(\Throwable $e): ProviderException
    {
        if ($e instanceof ProviderException) {
            return $e;
        }
        if ($e instanceof RateLimitException) {
            return new ProviderException(ProviderException::RATE_LIMITED, $e->getMessage(), 429, $e);
        }
        if ($e instanceof ErrorException) {
            $lower = strtolower($e->getMessage());
            $kind = str_contains($lower, 'quota') || str_contains($lower, 'billing') ? ProviderException::QUOTA
                : (str_contains($lower, 'rate') ? ProviderException::RATE_LIMITED : ProviderException::REJECTED);

            return new ProviderException($kind, $e->getMessage(), null, $e);
        }
        if ($e instanceof TransporterException || $e instanceof ServerException || $e instanceof UnserializableResponse) {
            return new ProviderException(ProviderException::TRANSPORT, $e->getMessage(), null, $e);
        }

        return new ProviderException(ProviderException::TRANSPORT, $e->getMessage(), null, $e);
    }
}
