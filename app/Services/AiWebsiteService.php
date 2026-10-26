<?php

namespace App\Services;

use App\Models\AiGeneration;
use App\Models\User;
use App\Services\Ai\AiException;
use App\Services\Ai\BlockCatalog;
use App\Services\Ai\MotionSpec;
use App\Services\Ai\Prompts;
use App\Services\Ai\Providers\ChatProvider;
use App\Services\Ai\Providers\ChatRequest;
use App\Services\Ai\Providers\ProviderException;
use App\Services\Ai\SchemaNormalizer;
use App\Services\Logo\LogoDesigner;
use Illuminate\Support\Facades\Log;

/**
 * Generates and edits websites with schema-constrained AI output.
 *
 * The vendor (OpenAI or Puku, see config/ai.php) is behind the ChatProvider
 * interface; this class only deals in prompts, schemas and results.
 *
 * Pipeline for every request:
 *   strict JSON schema (BlockCatalog) → provider (streamed when a progress
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
        private LogoDesigner $logoDesigner,
        private ChatProvider $provider,
    ) {}

    /** The configured backend, e.g. for showing "Powered by …" or in ai:doctor. */
    public function provider(): ChatProvider
    {
        return $this->provider;
    }

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

        $model = $this->provider->model('generate');
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
            model: $this->provider->model('edit'),
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

        $schema = $this->finalizeSite($data, ensureFrame: false, hint: $currentSchema['meta']['title'] ?? '');
        $originals = collect($currentSchema['sections'] ?? [])->keyBy('id');
        $schema['sections'] = array_map(fn ($s) => $this->keepCardVisual($this->keepMotion($s, $originals[$s['id']] ?? null), $originals[$s['id']] ?? null, $instruction), $schema['sections']);

        return ['schema' => $schema, 'summary' => $summary];
    }

    /**
     * Apply an instruction to one section. The section keeps its id and type.
     *
     * @return array{section: array, summary: string}
     */
    public function editSection(array $section, string $instruction, array $theme, ?int $userId = null, ?array $site = null): array
    {
        $type = (string) ($section['type'] ?? '');
        if (!BlockCatalog::has($type)) {
            throw new AiException('This section type cannot be edited with AI yet.', 422);
        }

        $outline = $site ? $this->siteOutline($site, $section['id'] ?? null) : '';
        $props = $this->forModel(['sections' => [$section]])['sections'][0]['props'] ?? [];

        $result = $this->requestJson(
            model: $this->provider->model('edit'),
            messages: [
                ['role' => 'system', 'content' => Prompts::editSection($type)],
                ['role' => 'user', 'content' => "Instruction: {$instruction}\n\n"
                    . ($outline !== '' ? "Site outline:\n{$outline}\n\n" : '')
                    . 'Theme: ' . json_encode($theme, JSON_UNESCAPED_SLASHES)
                    . "\n\nCurrent props of the {$type} section:\n" . json_encode($props ?: new \stdClass(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                    . "\n\nBefore answering, check each part of the instruction against the props above: anything this section has no prop for, or that is already the case, must be named plainly in `summary`."],
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
        $updated = $this->keepMotion($updated, $section);
        $updated = $this->keepCardVisual($updated, $section, $instruction);

        // Resolve new photos without repeating ones already in this section.
        $updated = $this->imageResolver->resolvePlaceholders(['sections' => [$updated]], $type)['sections'][0];

        return ['section' => $updated, 'summary' => $this->summary($result['data']['summary'] ?? null)];
    }

    /**
     * Animation-only edit: a small, fast request that can only touch animation and hover
     * settings, so it can never change the site's text, images or layout.
     * Returns handled=false when the request also asks for other changes (caller falls back
     * to a normal edit).
     *
     * @param array $sections [{id, type, props}]
     * @return array{handled: bool, summary: string, changes: array<int, array{id: string, props: array}>}
     */
    public function animate(array $sections, string $instruction, ?string $selectedId = null, ?int $userId = null, bool $extract = false, array $theme = []): array
    {
        $ids = [];
        $page = [];
        foreach ($sections as $s) {
            if (!is_array($s) || !isset($s['id'], $s['type']) || !BlockCatalog::has((string) $s['type'])) {
                continue;
            }
            $p = is_array($s['props'] ?? null) ? $s['props'] : [];
            $ids[(string) $s['id']] = (string) $s['type'];
            $page[] = [
                'id' => (string) $s['id'],
                'type' => (string) $s['type'],
                'title' => mb_substr((string) ($p['heading'] ?? $p['headline'] ?? $p['brand'] ?? $p['text'] ?? ''), 0, 60),
                'animation' => BlockCatalog::canonicalAnimation($p['animation'] ?? null) ?? 'none',
                'animationSpeed' => $p['animationSpeed'] ?? 'normal',
                'animationStagger' => (bool) ($p['animationStagger'] ?? false),
                'hoverEffect' => $p['hoverEffect'] ?? 'none',
                'buttonEffect' => $p['buttonEffect'] ?? 'none',
                'textEffect' => $p['textEffect'] ?? 'none',
                'textSpeed' => $p['textSpeed'] ?? 'normal',
                'typingPhrases' => MotionSpec::phrases($p['typingPhrases'] ?? null),
                'customAnimation' => ($p['animation'] ?? null) === 'custom' ? MotionSpec::entrance($p['customAnimation'] ?? null) : null,
                'bgEffect' => MotionSpec::effect($p['bgEffect'] ?? null),
                'sectionBg' => $p['sectionBg'] ?? null,
            ];
        }
        if (!$page) {
            throw new AiException('Add a section first, then ask for animations.', 422);
        }

        $nullable = fn (array $values) => ['type' => ['string', 'null'], 'enum' => [...$values, null]];
        $schema = [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => ['handled', 'summary', 'sections'],
            'properties' => [
                'handled' => ['type' => 'boolean'],
                'summary' => ['type' => 'string'],
                'sections' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'required' => ['id', 'animation', 'animationSpeed', 'animationStagger', 'hoverEffect', 'buttonEffect', 'textEffect', 'textSpeed', 'typingPhrases', 'customAnimation', 'bgEffect'],
                        'properties' => [
                            'id' => ['type' => 'string', 'enum' => array_keys($ids)],
                            'animation' => $nullable(BlockCatalog::ANIMATIONS),
                            'animationSpeed' => $nullable(['fast', 'normal', 'slow']),
                            'animationStagger' => ['type' => ['boolean', 'null']],
                            'hoverEffect' => $nullable(BlockCatalog::HOVER_EFFECTS),
                            'buttonEffect' => $nullable(BlockCatalog::BUTTON_EFFECTS),
                            'textEffect' => $nullable(BlockCatalog::TEXT_EFFECTS),
                            'textSpeed' => $nullable(['fast', 'normal', 'slow']),
                            'typingPhrases' => [
                                'type' => ['array', 'null'],
                                'items' => ['type' => 'string'],
                                'description' => 'Only for typing-loop: 2–4 alternative headings typed after the real one, in the site\'s language',
                            ],
                            'customAnimation' => [
                                'description' => 'Only with animation "custom": the starting pose the content animates FROM into its normal place',
                                'anyOf' => [['type' => 'null'], [
                                    'type' => 'object',
                                    'additionalProperties' => false,
                                    'required' => ['name', 'opacity', 'x', 'y', 'scale', 'rotate', 'rotateX', 'rotateY', 'skewX', 'blur', 'easing'],
                                    'properties' => [
                                        'name' => ['type' => 'string', 'description' => '2–4 word name, e.g. "Bouncy spin-in"'],
                                        'opacity' => ['type' => 'number', 'description' => 'start opacity 0–1 (usually 0)'],
                                        'x' => ['type' => 'number', 'description' => 'start offset px, -400..400 (negative = from the left)'],
                                        'y' => ['type' => 'number', 'description' => 'start offset px, -400..400 (positive = from below)'],
                                        'scale' => ['type' => 'number', 'description' => 'start scale 0.1–3 (1 = normal)'],
                                        'rotate' => ['type' => 'number', 'description' => 'start rotation deg -720..720'],
                                        'rotateX' => ['type' => 'number', 'description' => '3D tilt deg -180..180'],
                                        'rotateY' => ['type' => 'number', 'description' => '3D turn deg -180..180'],
                                        'skewX' => ['type' => 'number', 'description' => 'skew deg -45..45'],
                                        'blur' => ['type' => 'number', 'description' => 'start blur px 0–30'],
                                        'easing' => ['type' => 'string', 'enum' => MotionSpec::EASINGS],
                                    ],
                                ]],
                            ],
                            'bgEffect' => [
                                'description' => 'Background effect for this section. null = keep as is; kind "none" = remove',
                                'anyOf' => [['type' => 'null'], [
                                    'type' => 'object',
                                    'additionalProperties' => false,
                                    'required' => ['kind', 'name', 'shape', 'emoji', 'colors', 'count', 'sizeMin', 'sizeMax', 'motion', 'speed', 'opacity', 'glow'],
                                    'properties' => [
                                        'kind' => ['type' => 'string', 'enum' => [...MotionSpec::KINDS, 'none']],
                                        'name' => ['type' => 'string', 'description' => '2–4 word name, e.g. "Twinkling gold stars"'],
                                        'shape' => ['type' => 'string', 'enum' => MotionSpec::SHAPES],
                                        'emoji' => ['type' => ['string', 'null'], 'description' => 'one emoji, only when shape is "emoji"'],
                                        'colors' => ['type' => 'array', 'items' => ['type' => 'string'], 'description' => '1–4 hex colours'],
                                        'count' => ['type' => 'integer', 'description' => 'particles 3–120'],
                                        'sizeMin' => ['type' => 'number', 'description' => 'px'],
                                        'sizeMax' => ['type' => 'number', 'description' => 'px'],
                                        'motion' => ['type' => 'string', 'enum' => MotionSpec::MOTIONS],
                                        'speed' => ['type' => 'string', 'enum' => MotionSpec::SPEEDS],
                                        'opacity' => ['type' => 'integer', 'description' => '5–100'],
                                        'glow' => ['type' => 'boolean'],
                                    ],
                                ]],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $model = $this->provider->model('edit');
        $result = $this->requestJson(
            model: $model,
            messages: [
                ['role' => 'system', 'content' => Prompts::animate()],
                ['role' => 'user', 'content' => ($extract ? "MODE: extract — the other parts of this request were already handled. Apply ONLY its motion/effect parts and set handled true (no sections if there are none).\n\n" : '')
                    . 'Selected section: ' . ($selectedId && isset($ids[$selectedId]) ? "{$selectedId} ({$ids[$selectedId]})" : 'none')
                    . "\n\nSite colours: " . json_encode(array_intersect_key($theme, array_flip(['primaryColor', 'secondaryColor', 'backgroundColor', 'textColor', 'mode'])), JSON_UNESCAPED_SLASHES)
                    . "\n\nSections:\n" . json_encode($page, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                    . "\n\nRequest: {$instruction}"],
            ],
            schemaName: 'animation_edit',
            schema: $schema,
            maxTokens: 4000,
            temperature: 0.3,
        );
        if ($userId) {
            $this->recordUsage($userId, null, "[animate] {$instruction}", $model, $result['usage']);
        }

        $data = $result['data'];
        if (empty($data['handled']) && !$extract) {
            return ['handled' => false, 'summary' => '', 'changes' => []];
        }

        $changes = [];
        foreach ($data['sections'] ?? [] as $row) {
            $id = (string) ($row['id'] ?? '');
            if (!isset($ids[$id])) {
                continue;
            }
            $props = [];
            if (($a = BlockCatalog::canonicalAnimation($row['animation'] ?? null)) !== null) {
                $props['animation'] = $a;
            }
            if (in_array($row['animationSpeed'] ?? null, ['fast', 'normal', 'slow'], true)) {
                $props['animationSpeed'] = $row['animationSpeed'];
            }
            if (is_bool($row['animationStagger'] ?? null)) {
                $props['animationStagger'] = $row['animationStagger'];
            }
            if (in_array($row['hoverEffect'] ?? null, BlockCatalog::HOVER_EFFECTS, true)) {
                $props['hoverEffect'] = $row['hoverEffect'];
            }
            if (in_array($row['buttonEffect'] ?? null, BlockCatalog::BUTTON_EFFECTS, true)) {
                $props['buttonEffect'] = $row['buttonEffect'];
            }
            if (in_array($row['textEffect'] ?? null, BlockCatalog::TEXT_EFFECTS, true)) {
                $props['textEffect'] = $row['textEffect'];
            }
            if (in_array($row['textSpeed'] ?? null, ['fast', 'normal', 'slow'], true)) {
                $props['textSpeed'] = $row['textSpeed'];
            }
            if ($phrases = MotionSpec::phrases($row['typingPhrases'] ?? null)) {
                $props['typingPhrases'] = $phrases;
            }
            if (($props['animation'] ?? null) === 'custom') {
                $custom = MotionSpec::entrance($row['customAnimation'] ?? null);
                if ($custom) {
                    $props['customAnimation'] = $custom;
                } else {
                    unset($props['animation']); // no usable recipe: leave the animation as it was
                }
            }
            if (is_array($row['bgEffect'] ?? null)) {
                // kind "none" → remove the effect (null); otherwise a clamped, data-only spec
                $props['bgEffect'] = ($row['bgEffect']['kind'] ?? null) === 'none' ? null : MotionSpec::effect($row['bgEffect']);
                if ($props['bgEffect'] === null && ($row['bgEffect']['kind'] ?? null) !== 'none') {
                    unset($props['bgEffect']);
                }
            }
            if ($props) {
                $changes[] = ['id' => $id, 'props' => $props];
            }
        }

        return [
            'handled' => true,
            'summary' => $this->summary($data['summary'] ?? null) ?: ($changes ? 'Animations updated.' : 'Nothing to change — your animations already match that.'),
            'changes' => $changes,
        ];
    }

    /**
     * Design 4 logo concepts for the navbar and render them as SVG.
     * Falls back to built-in concepts if the AI is unavailable, so the user always gets options.
     *
     * @return array{concepts: array<int, array{spec: array, svg: string}>, summary: string}
     */
    public function logoConcepts(string $brand, array $theme, string $background, string $instruction = '', string $context = ''): array
    {
        $brief = [
            'brand' => $brand,
            'about the business' => $context,
            'site theme' => array_intersect_key($theme, array_flip(['primaryColor', 'secondaryColor', 'textColor', 'fontHeading', 'fontBody', 'mode'])),
            'navbar background' => $background,
            'user direction' => $instruction !== '' ? $instruction : 'none — use your judgement',
        ];

        try {
            $result = $this->requestJson(
                model: $this->provider->model('edit'),
                messages: [
                    ['role' => 'system', 'content' => LogoDesigner::prompt()],
                    ['role' => 'user', 'content' => json_encode($brief, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)],
                ],
                schemaName: 'logo_concepts',
                schema: LogoDesigner::schema(),
                maxTokens: 2000,
                temperature: 0.9,
            );
            $concepts = $result['data']['concepts'] ?? [];
            $summary = $this->summary($result['data']['summary'] ?? null);
        } catch (AiException $e) {
            Log::warning('Logo concepts fell back to defaults', ['error' => $e->getMessage()]);
            $concepts = [];
            $summary = 'Here are a few clean starting points.';
        }

        $built = $this->logoDesigner->build($concepts, $brand, $theme, $background);
        if (count($built) < 4) {
            $built = array_slice(array_merge($built, $this->logoDesigner->build($this->logoDesigner->fallbackConcepts($theme), $brand, $theme, $background)), 0, 4);
        }

        return ['concepts' => $built, 'summary' => $summary];
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

    /**
     * The edit models can't output AI-designed motion (bgEffect / customAnimation), so carry
     * it over from the section as it was — an edit about text must never wipe the stars.
     */
    private function keepMotion(array $section, ?array $original): array
    {
        if (!$original) {
            return $section;
        }
        $before = $original['props'] ?? [];
        foreach (BlockCatalog::MOTION_PROPS as $key) {
            if (!isset($section['props'][$key]) && !empty($before[$key])) {
                $section['props'][$key] = $before[$key];
            }
        }
        // Keep a custom entrance unless the edit chose a different animation on purpose.
        if (($before['animation'] ?? null) === 'custom' && !empty($before['customAnimation'])
            && in_array($section['props']['animation'] ?? null, [null, 'custom', 'fade-up'], true)) {
            $section['props']['animation'] = 'custom';
        }

        return $section;
    }

    /**
     * Cards show a photo, an icon or nothing — the user's choice. An edit that isn't about
     * icons must not sprinkle emoji onto photo cards (or strip icons the user picked).
     */
    private function keepCardVisual(array $section, ?array $original, string $instruction): array
    {
        $list = ['features' => 'items', 'services' => 'items'][$section['type'] ?? ''] ?? null;
        if (!$list || !$original || preg_match('/\b(icons?|emojis?)\b|আইকন/iu', $instruction)) {
            return $section;
        }
        $before = $original['props'][$list] ?? [];
        $hadIcons = (bool) array_filter($before, fn ($item) => !empty($item['icon']));
        if (!$hadIcons) {
            foreach ($section['props'][$list] ?? [] as $n => $item) {
                unset($section['props'][$list][$n]['icon']);
            }
        }

        return $section;
    }

    /**
     * A few lines describing the site around one section: who it is for and which
     * anchors exist, so a section edit writes copy that fits and links that resolve.
     * $site is {meta, sections: [{id, type, heading?}]} — full sections work too.
     */
    private function siteOutline(array $site, ?string $currentId): string
    {
        $meta = (array) ($site['meta'] ?? []);
        $sections = array_values(array_filter((array) ($site['sections'] ?? []), 'is_array'));
        $brand = collect($sections)->firstWhere('type', 'navbar')['props']['brand']
            ?? collect($sections)->firstWhere('type', 'navbar')['heading']
            ?? null;

        $lines = [];
        foreach (['Brand' => $brand, 'Site title' => $meta['title'] ?? null, 'About' => $meta['description'] ?? null, 'Language' => $meta['language'] ?? null] as $label => $value) {
            if (is_string($value) && trim($value) !== '') {
                $lines[] = "{$label}: " . mb_substr(trim($value), 0, 200);
            }
        }

        $anchors = BlockCatalog::anchors($sections);
        $list = [];
        foreach ($sections as $i => $s) {
            $heading = $s['heading'] ?? $s['props']['heading'] ?? null;
            $list[] = '- ' . ($s['type'] ?? '?')
                . ($anchors[$i] ? " (#{$anchors[$i]})" : '')
                . (is_string($heading) && $heading !== '' ? ': "' . mb_substr($heading, 0, 80) . '"' : '')
                . (($s['id'] ?? null) === $currentId && $currentId !== null ? '  ← the section you are editing' : '');
        }
        if ($list) {
            $lines[] = "Sections, top to bottom:\n" . implode("\n", array_slice($list, 0, 30));
        }

        return implode("\n", $lines);
    }

    /** Strip noise the model doesn't need (keeps token use and latency down). */
    private function forModel(array $schema): array
    {
        unset($schema['version']);
        foreach ($schema['sections'] ?? [] as $i => $section) {
            foreach (BlockCatalog::MOTION_PROPS as $key) {
                unset($schema['sections'][$i]['props'][$key]);
            }
        }

        return $schema;
    }

    private function summary(mixed $value): string
    {
        $text = is_string($value) ? trim($value) : '';

        if ($text === '') {
            return 'Done — your changes are applied.';
        }
        if (mb_strlen($text) <= 320) {
            return $text;
        }
        // Cut on a word boundary so the toast never ends mid-word.
        $cut = mb_substr($text, 0, 320);

        return rtrim(mb_substr($cut, 0, (int) mb_strrpos($cut, ' ')), ' ,;:—') . '…';
    }

    /**
     * Ask the provider for JSON matching a strict schema. Retries once on
     * truncated/invalid output or transport errors. When $onProgress is given
     * the response is streamed and progress events are emitted as the JSON arrives.
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
        $request = new ChatRequest($model, $messages, $schemaName, $schema, $maxTokens, $temperature);
        $vendor = $this->provider->label();

        $attempts = 1 + max(0, (int) config('ai.retries'));
        $lastError = null;

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            try {
                $result = $onProgress
                    ? $this->provider->stream($request, $this->progressReporter($onProgress, $expectedTokens))
                    : $this->provider->complete($request);

                if ($result->truncated()) {
                    throw new \RuntimeException('Output truncated at max tokens');
                }

                $data = json_decode($result->content ?? '', true);
                if (!is_array($data)) {
                    throw new \RuntimeException('Model output was not valid JSON');
                }

                return ['data' => $data, 'usage' => $result->usage];
            } catch (ProviderException $e) {
                switch ($e->kind) {
                    case ProviderException::RATE_LIMITED:
                        Log::warning("{$vendor} rate limited", ['model' => $model, 'error' => $e->getMessage()]);

                        throw AiException::unavailable($e);
                    case ProviderException::QUOTA:
                        Log::error("{$vendor} plan quota exhausted", ['model' => $model, 'error' => $e->getMessage()]);

                        throw AiException::providerQuota($vendor, $e);
                    case ProviderException::REJECTED:
                        // Bad key, invalid schema, unknown model: retrying won't help.
                        Log::error("{$vendor} request rejected", ['model' => $model, 'error' => $e->getMessage()]);

                        throw new AiException('The AI request was rejected. Please try again, or contact support if it keeps happening.', 502, $e);
                    default:
                        Log::warning("{$vendor} transport error", ['model' => $model, 'attempt' => $attempt, 'error' => $e->getMessage()]);
                        $lastError = AiException::unavailable($e);
                }
            } catch (\RuntimeException $e) {
                Log::warning('Unusable AI output', ['model' => $model, 'provider' => $vendor, 'attempt' => $attempt, 'error' => $e->getMessage()]);
                $lastError = AiException::unusable($e);
            }

            if ($attempt < $attempts && $onProgress) {
                $onProgress(['stage' => 'retry', 'message' => 'Polishing the result…', 'progress' => 0.1]);
            }
        }

        throw $lastError ?? AiException::unusable();
    }

    /**
     * Turns raw streamed text into progress events: every few chunks, describe
     * what the model is writing right now ("Writing the pricing section…").
     *
     * @return callable(string): void
     */
    private function progressReporter(callable $onProgress, int $expectedTokens): callable
    {
        $buffer = '';
        $chunks = 0;
        $lastStage = null;
        $sectionsSeen = 0;

        $onProgress(['stage' => 'start', 'message' => 'Understanding your request…', 'progress' => 0.03]);

        return function (string $delta) use (&$buffer, &$chunks, &$lastStage, &$sectionsSeen, $onProgress, $expectedTokens): void {
            $buffer .= $delta;
            $chunks++;

            if ($chunks % 8 !== 0) {
                return;
            }
            $progress = min(0.95, 0.05 + (strlen($buffer) / 4) / $expectedTokens * 0.9);
            $event = $this->describeProgress($buffer, $sectionsSeen);
            if ($event['stage'] !== $lastStage || $chunks % 40 === 0) {
                $lastStage = $event['stage'];
                $onProgress($event + ['progress' => round($progress, 3)]);
            }
        };
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
