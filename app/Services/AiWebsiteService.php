<?php

namespace App\Services;

use App\Models\AiGeneration;
use App\Models\User;
use OpenAI\Laravel\Facades\OpenAI;

class AiWebsiteService
{
    public function __construct(
        private SchemaValidator $validator,
        private ImageResolver $imageResolver,
    ) {}

    public function generate(string $userPrompt, ?int $userId = null, ?int $projectId = null): array
    {
        // Enforce generation caps
        if ($userId) {
            $this->enforceGenerationCap($userId);
        }

        $response = OpenAI::chat()->create([
            'model' => 'gpt-4.1',
            'messages' => [
                ['role' => 'system', 'content' => $this->systemPrompt()],
                ['role' => 'user', 'content' => $userPrompt],
            ],
            'response_format' => [
                'type' => 'json_schema',
                'json_schema' => [
                    'name' => 'website_schema',
                    'strict' => true,
                    'schema' => $this->jsonSchema(),
                ],
            ],
            'max_completion_tokens' => 4000,
        ]);

        $json = $response->choices[0]->message->content;
        $schema = json_decode($json, true);

        $schema = $this->validator->validate($schema);
        $schema = $this->imageResolver->resolvePlaceholders($schema);

        if ($userId) {
            AiGeneration::create([
                'user_id' => $userId,
                'project_id' => $projectId,
                'prompt' => $userPrompt,
                'model' => 'gpt-4.1',
                'input_tokens' => $response->usage->promptTokens ?? null,
                'output_tokens' => $response->usage->completionTokens ?? null,
                'cost_usd' => $this->estimateCost($response->usage->promptTokens ?? 0, $response->usage->completionTokens ?? 0),
                'status' => 'completed',
            ]);
        }

        return $schema;
    }

    public function editSchema(array $currentSchema, string $instruction, ?int $userId = null): array
    {
        $response = OpenAI::chat()->create([
            'model' => 'gpt-4.1-mini',
            'messages' => [
                ['role' => 'system', 'content' => $this->editSystemPrompt()],
                ['role' => 'user', 'content' => "Instruction: {$instruction}\n\nCurrent schema:\n" . json_encode($currentSchema)],
            ],
            'response_format' => [
                'type' => 'json_schema',
                'json_schema' => [
                    'name' => 'website_schema',
                    'strict' => true,
                    'schema' => $this->jsonSchema(),
                ],
            ],
            'max_completion_tokens' => 4000,
        ]);

        $json = $response->choices[0]->message->content;
        $schema = json_decode($json, true);

        $schema = $this->validator->validate($schema);
        $schema = $this->imageResolver->resolvePlaceholders($schema);

        return $schema;
    }

    private function systemPrompt(): string
    {
        return <<<'PROMPT'
You are a senior web designer that outputs ONLY a website layout as JSON.

Rules:
- Return a JSON object matching the provided schema EXACTLY. No prose, no HTML.
- Choose 5–8 sections that fit the user's purpose and industry.
- Always start with a "navbar" and end with a "footer".
- Pick a tasteful theme: a primary color suited to the brand, readable fonts, and a light/dark mode that fits the mood.
- Write realistic, specific marketing copy — never "Lorem ipsum".
- For images, use the string "PLACEHOLDER:<short description>"; the server will replace these with real image URLs.
- Keep headings short and punchy; body copy concise.
- Each section must have a unique id in the format "blk_<type>_<number>".
- Available section types: navbar, hero, features, gallery, about, services, testimonials, pricing, stats, faq, team, cta, contact, footer.
- For the "features" type, props.items should be an array of objects with icon (emoji), title, and description.
- For the "gallery" type, props.images should be an array of objects with src and caption.
- For the "contact" type, props.fields should be an array of strings like ["name", "email", "message"].
- For the "navbar" type, props.links should be an array of objects with label and href.

You will be given a user request describing the website they want.
PROMPT;
    }

    private function editSystemPrompt(): string
    {
        return <<<'PROMPT'
You edit an existing website JSON schema. Apply the user's requested change and return the full updated JSON matching the schema. Change only what is requested — preserve everything else exactly as-is.

Rules:
- Return the complete updated JSON schema.
- Only modify what the user's instruction asks for.
- Keep all section IDs unchanged unless adding/removing sections.
- For images, use "PLACEHOLDER:<short description>" for any new images.
PROMPT;
    }

    private function jsonSchema(): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => ['version', 'meta', 'theme', 'sections'],
            'properties' => [
                'version' => ['type' => 'integer'],
                'meta' => [
                    'type' => 'object',
                    'additionalProperties' => false,
                    'required' => ['title', 'description', 'language'],
                    'properties' => [
                        'title' => ['type' => 'string'],
                        'description' => ['type' => 'string'],
                        'language' => ['type' => 'string'],
                    ],
                ],
                'theme' => [
                    'type' => 'object',
                    'additionalProperties' => false,
                    'required' => ['primaryColor', 'secondaryColor', 'backgroundColor', 'textColor', 'fontHeading', 'fontBody', 'radius', 'mode'],
                    'properties' => [
                        'primaryColor' => ['type' => 'string'],
                        'secondaryColor' => ['type' => 'string'],
                        'backgroundColor' => ['type' => 'string'],
                        'textColor' => ['type' => 'string'],
                        'fontHeading' => ['type' => 'string'],
                        'fontBody' => ['type' => 'string'],
                        'radius' => ['type' => 'string', 'enum' => ['none', 'sm', 'md', 'lg', 'xl', 'full']],
                        'mode' => ['type' => 'string', 'enum' => ['light', 'dark']],
                    ],
                ],
                'sections' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'required' => ['id', 'type', 'props'],
                        'properties' => [
                            'id' => ['type' => 'string'],
                            'type' => [
                                'type' => 'string',
                                'enum' => ['navbar', 'hero', 'features', 'gallery', 'about', 'services', 'testimonials', 'pricing', 'stats', 'faq', 'team', 'cta', 'contact', 'footer'],
                            ],
                            'props' => ['type' => 'object'],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function enforceGenerationCap(int $userId): void
    {
        $user = User::findOrFail($userId);
        $limit = match ($user->plan) {
            'pro' => 100,
            'team' => 500,
            default => 10, // free
        };

        $monthlyCount = AiGeneration::where('user_id', $userId)
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();

        if ($monthlyCount >= $limit) {
            throw new \Exception("Monthly generation limit reached ({$limit}). Upgrade your plan for more.");
        }
    }

    public function getRemainingGenerations(int $userId): array
    {
        $user = User::findOrFail($userId);
        $limit = match ($user->plan) {
            'pro' => 100,
            'team' => 500,
            default => 10,
        };

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

    private function estimateCost(int $inputTokens, int $outputTokens): float
    {
        // GPT-4.1 pricing: ~$2/1M input, ~$8/1M output
        return ($inputTokens * 2 / 1_000_000) + ($outputTokens * 8 / 1_000_000);
    }
}
