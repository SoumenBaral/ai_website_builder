<?php

namespace App\Services\Photos;

use App\Models\StockPhoto;
use App\Services\Ai\Providers\ChatProvider;
use App\Services\Ai\Providers\ChatRequest;
use App\Services\Ai\Providers\ProviderException;

/**
 * Looks at photos with a vision model and writes what is actually in them —
 * the ground truth the resolver and the picker search over.
 *
 * Several photos go in one request (small thumbnails, low detail) so the whole
 * catalog costs a few dollars, and every result is validated before it is saved.
 */
class PhotoAnalyzer
{
    public const BATCH = 6;

    public const PLACEMENTS = ['hero', 'card', 'gallery', 'portrait', 'background'];

    public function __construct(private ChatProvider $provider) {}

    /**
     * Analyze up to BATCH photos. Returns the number saved.
     *
     * @param  \Illuminate\Support\Collection<int, StockPhoto> $photos
     * @throws ProviderException when the provider fails (caller decides whether to continue)
     * @throws \RuntimeException when the model output is unusable
     */
    public function analyze($photos, ?string $model = null): int
    {
        $photos = collect($photos)->values()->take(self::BATCH);
        if ($photos->isEmpty()) {
            return 0;
        }

        $content = [['type' => 'text', 'text' => 'Describe each of the ' . $photos->count() . ' photos below, in order. Photo numbers start at 1.']];
        foreach ($photos as $i => $photo) {
            $content[] = ['type' => 'text', 'text' => 'Photo ' . ($i + 1) . ($photo->alt ? " (source caption: {$photo->alt})" : '')];
            $content[] = ['type' => 'image_url', 'image_url' => ['url' => $photo->thumb_url, 'detail' => 'low']];
        }

        $request = new ChatRequest(
            model: $model ?? $this->provider->model('edit'),
            messages: [
                ['role' => 'system', 'content' => self::prompt()],
                ['role' => 'user', 'content' => $content],
            ],
            schemaName: 'photo_analysis',
            schema: self::schema(),
            maxTokens: 220 * $photos->count() + 200,
            temperature: 0.1,
        );

        $result = $this->provider->complete($request);
        if ($result->truncated()) {
            throw new \RuntimeException('Photo analysis output truncated');
        }
        $data = json_decode((string) $result->content, true);
        if (!is_array($data) || !isset($data['photos']) || !is_array($data['photos'])) {
            throw new \RuntimeException('Photo analysis output was not valid JSON');
        }

        $saved = 0;
        foreach ($data['photos'] as $row) {
            $index = (int) ($row['number'] ?? 0) - 1;
            $photo = $photos->get($index);
            if (!$photo || !$this->plausible($row)) {
                continue;
            }
            $this->apply($photo, $row);
            $saved++;
        }

        return $saved;
    }

    public function apply(StockPhoto $photo, array $row): void
    {
        $clean = fn ($list, $max) => array_values(array_unique(array_slice(array_filter(array_map(
            fn ($t) => mb_strtolower(trim(preg_replace('/[^\p{L}\p{N} \-]/u', '', (string) $t))),
            is_array($list) ? $list : []
        ), fn ($t) => $t !== '' && mb_strlen($t) <= 40), 0, $max)));

        $photo->ai_description = mb_strimwidth(trim((string) $row['description']), 0, 400, '');
        $photo->ai_tags = $clean($row['tags'] ?? [], 14);
        $photo->ai_subjects = $clean($row['subjects'] ?? [], 3);
        $photo->people = (bool) ($row['people'] ?? false);
        $photo->person = in_array($row['person'] ?? null, ['man', 'woman'], true) && $photo->people ? $row['person'] : null;
        $photo->mood = mb_strimwidth(mb_strtolower(trim((string) ($row['mood'] ?? ''))), 0, 40, '') ?: null;
        $photo->placements = array_values(array_intersect(self::PLACEMENTS, array_map('strval', $row['placements'] ?? [])));
        $photo->search_text = $photo->buildSearchText();
        $photo->analyzed_at = now();
        $photo->save();
    }

    private function plausible(array $row): bool
    {
        return isset($row['description']) && is_string($row['description']) && mb_strlen(trim($row['description'])) >= 10
            && isset($row['tags']) && is_array($row['tags']) && count($row['tags']) >= 3;
    }

    public static function prompt(): string
    {
        return <<<'TXT'
You catalogue stock photos for a website builder. For each photo, write exactly what is visible so it can be matched to search queries like "dentist smiling with patient" or "latte art on wooden table".

Rules:
- description: one factual sentence (12–30 words) about the main subject, setting and action. No opinions, no "this image shows".
- subjects: 1–3 nouns for the main things in the picture, most important first (e.g. ["barber", "haircut"]). Lowercase, singular.
- tags: 8–14 lowercase search words: subjects, objects, setting, activity, industry it suits (e.g. "restaurant", "healthcare"), style ("minimal", "close-up"). Singular nouns. No colours unless striking, no camera jargon.
- people: true only if a person is clearly visible.
- person: "man" or "woman" if ONE person is the clear subject and their presentation is unambiguous; otherwise null.
- mood: one or two words (e.g. "calm", "energetic", "luxury", "cozy").
- placements: where this photo works on a website — "hero" (wide, uncluttered area for text, strong scene), "card" (clear single subject), "gallery" (attractive on its own), "portrait" (a face, head-and-shoulders), "background" (texture/abstract/scene without a focal subject). Choose every one that fits.
Return a result for every photo number given.
TXT;
    }

    public static function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'photos' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'number' => ['type' => 'integer'],
                            'description' => ['type' => 'string'],
                            'subjects' => ['type' => 'array', 'items' => ['type' => 'string']],
                            'tags' => ['type' => 'array', 'items' => ['type' => 'string']],
                            'people' => ['type' => 'boolean'],
                            'person' => ['type' => ['string', 'null'], 'enum' => ['man', 'woman', null]],
                            'mood' => ['type' => 'string'],
                            'placements' => ['type' => 'array', 'items' => ['type' => 'string', 'enum' => self::PLACEMENTS]],
                        ],
                        'required' => ['number', 'description', 'subjects', 'tags', 'people', 'person', 'mood', 'placements'],
                        'additionalProperties' => false,
                    ],
                ],
            ],
            'required' => ['photos'],
            'additionalProperties' => false,
        ];
    }
}
