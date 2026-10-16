<?php

namespace App\Services;

use App\Models\AiGeneration;
use App\Models\User;
use App\Services\Ai\AiException;
use App\Services\Ai\BlockCatalog;
use App\Services\Ai\Prompts;
use App\Services\Ai\SchemaNormalizer;
use Illuminate\Support\Facades\Log;
use OpenAI\Exceptions\ErrorException;
use OpenAI\Exceptions\RateLimitException;
use OpenAI\Exceptions\ServerException;
use OpenAI\Exceptions\TransporterException;
use OpenAI\Exceptions\UnserializableResponse;
use OpenAI\Laravel\Facades\OpenAI;

/**
 * Generates and edits websites with OpenAI structured outputs.
 *
 * Pipeline for every request:
 *   strict JSON schema (BlockCatalog) → OpenAI (streamed when a progress
 *   callback is given) → truncation / JSON checks with one retry →
 *   SchemaValidator (structure) → SchemaNormalizer (repair & polish) →
 *   ImageResolver (real photos).
 */
class AiWebsiteService
{
    public function __construct(
        private SchemaValidator $validator,
        private ImageResolver $imageResolver,
        private SchemaNormalizer $normalizer,
    ) {}

    /**
     * Generate a complete website from a prompt.
     *
     * @param callable|null $onProgress fn(array $event) — receives {stage, message, progress}
     */
    public function generate(string $userPrompt, ?int $userId = null, ?int $projectId = null, ?callable $onProgress = null): array
    {
        if ($userId) {
            $this->enforceGenerationCap($userId);
        }

        $model = config('ai.models.generate');
        $result = $this->requestJson(
            model: $model,
            messages: [
                ['role' => 'system', 'content' => Prompts::generate()],
                ['role' => 'user', 'content' => trim($userPrompt)],
            ],
            schemaName: 'website',
            schema: BlockCatalog::siteSchema('plan'),
            maxTokens: config('ai.max_tokens.generate'),
            temperature: config('ai.temperature.generate'),
            onProgress: $onProgress,
            expectedTokens: 2400,
        );

        $data = $result['data'];
        $plan = is_array($data['plan'] ?? null) ? $data['plan'] : [];
        unset($data['plan']);

        $schema = $this->finalizeSite($data, ensureFrame: true, hint: implode(' ', array_filter([
            $plan['industry'] ?? null, $plan['brand'] ?? null, $plan['visualDirection'] ?? null, $userPrompt,
        ])));

        if ($userId) {
            $this->recordUsage($userId, $projectId, $userPrompt, $model, $result['usage']);
        }

        Log::info('AI website generated', [
            'project_id' => $projectId,
            'sections' => count($schema['sections']),
            'fixes' => $this->normalizer->fixes(),
            'output_tokens' => $result['usage']['output'] ?? null,
        ]);

        return $schema;
    }

    /**
     * Apply an instruction to the whole site.
     *
     * @return array{schema: array, summary: string}
     */
    public function editSchema(array $currentSchema, string $instruction, ?int $userId = null, ?callable $onProgress = null): array
    {
        $result = $this->requestJson(
            model: config('ai.models.edit'),
            messages: [
                ['role' => 'system', 'content' => Prompts::editSite()],
                ['role' => 'user', 'content' => "Instruction: {$instruction}\n\nCurrent website JSON:\n" . json_encode($this->forModel($currentSchema), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)],
            ],
            schemaName: 'website_edit',
            schema: BlockCatalog::siteSchema('summary'),
            maxTokens: config('ai.max_tokens.edit_site'),
            temperature: config('ai.temperature.edit'),
            onProgress: $onProgress,
            expectedTokens: max(500, (int) (strlen(json_encode($currentSchema)) / 3.5)),
        );

        $data = $result['data'];
        $summary = $this->summary($data['summary'] ?? null);
        unset($data['summary']);

        return [
            'schema' => $this->finalizeSite($data, ensureFrame: false, hint: $currentSchema['meta']['title'] ?? ''),
            'summary' => $summary,
        ];
    }

    /**
     * Apply an instruction to one section. The section keeps its id and type.
     *
     * @return array{section: array, summary: string}
     */
    public function editSection(array $section, string $instruction, array $theme, ?int $userId = null): array
    {
        $type = (string) ($section['type'] ?? '');
        if (!BlockCatalog::has($type)) {
            throw new AiException('This section type cannot be edited with AI yet.', 422);
        }

        $result = $this->requestJson(
            model: config('ai.models.edit'),
            messages: [
                ['role' => 'system', 'content' => Prompts::editSection($type)],
                ['role' => 'user', 'content' => "Instruction: {$instruction}\n\nTheme: " . json_encode($theme, JSON_UNESCAPED_SLASHES)
                    . "\n\nCurrent props:\n" . json_encode($section['props'] ?? new \stdClass(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)],
            ],
            schemaName: 'section_edit',
            schema: BlockCatalog::sectionEditSchema($type),
            maxTokens: config('ai.max_tokens.edit_section'),
            temperature: config('ai.temperature.edit'),
        );

        $updated = $this->normalizer->normalizeSection(
            ['id' => $section['id'] ?? null, 'type' => $type, 'props' => $result['data']['props'] ?? []],
            $theme,
        );
        $updated['id'] = $section['id'] ?? $updated['id'];

        // Resolve new photos without repeating ones already in this section.
        $updated = $this->imageResolver->resolvePlaceholders(['sections' => [$updated]], $type)['sections'][0];

        return ['section' => $updated, 'summary' => $this->summary($result['data']['summary'] ?? null)];
    }

    /* ==================================================================
     | Internals
     * ================================================================== */

    private function finalizeSite(array $data, bool $ensureFrame, string $hint): array
    {
        $data['version'] = 1;
        $schema = $this->normalizer->normalizeSite($data, $ensureFrame);
        $this->validator->validate($schema);

        if (!$schema['sections']) {
            throw AiException::unusable();
        }

        return $this->imageResolver->resolvePlaceholders($schema, $hint);
    }

    /** Strip noise the model doesn't need (keeps token use and latency down). */
    private function forModel(array $schema): array
    {
        unset($schema['version']);

        return $schema;
    }

    private function summary(mixed $value): string
    {
        $text = is_string($value) ? trim($value) : '';

        return $text !== '' ? mb_substr($text, 0, 200) : 'Done — your changes are applied.';
    }

    /**
     * Call OpenAI with a strict JSON schema. Retries once on truncated/invalid
     * output. When $onProgress is given the response is streamed and progress
     * events are emitted as the JSON arrives.
     *
     * @return array{data: array, usage: array{input: ?int, output: ?int}}
     */
    private function requestJson(
        string $model,
        array $messages,
        string $schemaName,
        array $schema,
        int $maxTokens,
        float $temperature,
        ?callable $onProgress = null,
        int $expectedTokens = 3000,
    ): array {
        $params = [
            'model' => $model,
            'messages' => $messages,
            'temperature' => $temperature,
            'max_completion_tokens' => $maxTokens,
            'response_format' => [
                'type' => 'json_schema',
                'json_schema' => ['name' => $schemaName, 'strict' => true, 'schema' => $schema],
            ],
        ];

        $attempts = 1 + max(0, (int) config('ai.retries'));
        $lastError = null;

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            try {
                [$content, $finish, $usage] = $onProgress
                    ? $this->stream($params, $onProgress, $expectedTokens)
                    : $this->complete($params);

                if ($finish === 'length') {
                    throw new \RuntimeException('Output truncated at max_completion_tokens');
                }

                $data = json_decode($content ?? '', true);
                if (!is_array($data)) {
                    throw new \RuntimeException('Model output was not valid JSON');
                }

                return ['data' => $data, 'usage' => $usage];
            } catch (RateLimitException $e) {
                Log::warning('OpenAI rate limited', ['model' => $model]);

                throw AiException::unavailable($e);
            } catch (ErrorException $e) {
                // API rejected the request (bad key, quota, invalid schema): retrying won't help.
                Log::error('OpenAI request rejected', ['model' => $model, 'error' => $e->getMessage()]);

                throw str_contains(strtolower($e->getMessage()), 'quota') || str_contains(strtolower($e->getMessage()), 'rate')
                    ? AiException::unavailable($e)
                    : new AiException('The AI request was rejected. Please try again, or contact support if it keeps happening.', 502, $e);
            } catch (TransporterException|ServerException|UnserializableResponse $e) {
                Log::warning('OpenAI transport error', ['model' => $model, 'attempt' => $attempt, 'error' => $e->getMessage()]);
                $lastError = AiException::unavailable($e);
            } catch (\RuntimeException $e) {
                Log::warning('Unusable AI output', ['model' => $model, 'attempt' => $attempt, 'error' => $e->getMessage()]);
                $lastError = AiException::unusable($e);
            }

            if ($attempt < $attempts && $onProgress) {
                $onProgress(['stage' => 'retry', 'message' => 'Polishing the result…', 'progress' => 0.1]);
            }
        }

        throw $lastError ?? AiException::unusable();
    }

    /** @return array{0: ?string, 1: ?string, 2: array} */
    private function complete(array $params): array
    {
        $response = OpenAI::chat()->create($params);
        $choice = $response->choices[0] ?? null;

        return [
            $choice?->message->content,
            $choice?->finishReason,
            ['input' => $response->usage?->promptTokens, 'output' => $response->usage?->completionTokens],
        ];
    }

    /** @return array{0: string, 1: ?string, 2: array} */
    private function stream(array $params, callable $onProgress, int $expectedTokens): array
    {
        $params['stream_options'] = ['include_usage' => true];
        $stream = OpenAI::chat()->createStreamed($params);

        $buffer = '';
        $finish = null;
        $usage = ['input' => null, 'output' => null];
        $chunks = 0;
        $lastStage = null;
        $sectionsSeen = 0;

        $onProgress(['stage' => 'start', 'message' => 'Understanding your request…', 'progress' => 0.03]);

        foreach ($stream as $response) {
            if ($response->usage) {
                $usage = ['input' => $response->usage->promptTokens, 'output' => $response->usage->completionTokens];
            }
            $choice = $response->choices[0] ?? null;
            if (!$choice) {
                continue;
            }
            $buffer .= $choice->delta->content ?? '';
            $finish = $choice->finishReason ?? $finish;
            $chunks++;

            // Every few chunks, describe what the model is writing right now.
            if ($chunks % 8 !== 0) {
                continue;
            }
            $progress = min(0.95, 0.05 + (strlen($buffer) / 4) / $expectedTokens * 0.9);
            $event = $this->describeProgress($buffer, $sectionsSeen);
            if ($event['stage'] !== $lastStage || $chunks % 40 === 0) {
                $lastStage = $event['stage'];
                $onProgress($event + ['progress' => round($progress, 3)]);
            }
        }

        return [$buffer, $finish, $usage];
    }

    /** Turn the partial JSON into a human status ("Writing the pricing section…"). */
    private function describeProgress(string $buffer, int &$sectionsSeen): array
    {
        if (preg_match_all('/"type"\s*:\s*"([a-z]+)"/', $buffer, $m) && $m[1]) {
            $sectionsSeen = count($m[1]);
            $type = end($m[1]);
            $label = match ($type) {
                'navbar' => 'navigation',
                'hero' => 'hero',
                'cta' => 'call to action',
                'faq' => 'FAQ',
                default => $type,
            };

            return ['stage' => "section:{$sectionsSeen}", 'message' => "Writing the {$label} section…", 'section' => $type];
        }
        if (str_contains($buffer, '"theme"')) {
            return ['stage' => 'theme', 'message' => 'Choosing colors and fonts…'];
        }
        if (str_contains($buffer, '"meta"')) {
            return ['stage' => 'meta', 'message' => 'Writing your page title and description…'];
        }
        if (str_contains($buffer, '"summary"')) {
            return ['stage' => 'summary', 'message' => 'Working out the changes…'];
        }

        return ['stage' => 'plan', 'message' => 'Planning the brand, audience and layout…'];
    }

    private function recordUsage(int $userId, ?int $projectId, string $prompt, string $model, array $usage): void
    {
        AiGeneration::create([
            'user_id' => $userId,
            'project_id' => $projectId,
            'prompt' => $prompt,
            'model' => $model,
            'input_tokens' => $usage['input'],
            'output_tokens' => $usage['output'],
            'cost_usd' => $this->estimateCost($model, $usage['input'] ?? 0, $usage['output'] ?? 0),
            'status' => 'completed',
        ]);
    }

    private function generationLimit(User $user): int
    {
        return match ($user->plan) {
            'pro' => 100,
            'team' => 500,
            default => 10, // free
        };
    }

    private function enforceGenerationCap(int $userId): void
    {
        $usage = $this->getRemainingGenerations($userId);

        if ($usage['remaining'] <= 0) {
            throw AiException::limitReached($usage['limit']);
        }
    }

    public function getRemainingGenerations(int $userId): array
    {
        $user = User::findOrFail($userId);
        $limit = $this->generationLimit($user);

        $used = AiGeneration::where('user_id', $userId)
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();

        return [
            'used' => $used,
            'limit' => $limit,
            'remaining' => max(0, $limit - $used),
            'plan' => $user->plan,
        ];
    }

    private function estimateCost(string $model, int $inputTokens, int $outputTokens): float
    {
        // Model names contain dots (gpt-4.1), so read the array directly instead of dot-notation config().
        [$in, $out] = config('ai.pricing')[$model] ?? [2.00, 8.00];

        return ($inputTokens * $in / 1_000_000) + ($outputTokens * $out / 1_000_000);
    }
}
