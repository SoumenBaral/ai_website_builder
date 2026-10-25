<?php

namespace App\Services\Photos;

use App\Models\StockPhoto;
use App\Services\Ai\ImageLibrary;
use Illuminate\Support\Facades\Cache;

/**
 * Search over the stock photo catalog (stock_photos table).
 *
 * The whole catalog (a few thousand compact rows) is held in cache and scored in
 * PHP, which is faster and far more controllable than SQL LIKEs for this size:
 * subjects outrank tags outrank description words, common words count less,
 * the site's industry nudges on-topic photos up, and each placement wants a
 * suitable shape (wide bands, square cards, faces for portraits).
 */
class PhotoCatalog
{
    public const CACHE_KEY = 'photo-catalog:index';

    /** @var array<int, array>|null */
    private ?array $index = null;

    /**
     * Is there a catalog worth using (otherwise callers fall back to the hand-curated ImageLibrary)?
     * Only photos the vision model has described count: dataset keywords alone match too loosely
     * to be trusted for a site's hero image.
     */
    public function ready(): bool
    {
        return count(array_filter($this->index(), fn ($p) => $p['analyzed'])) >= 50;
    }

    public function count(): int
    {
        return count($this->index());
    }

    public static function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Best photos for a query.
     *
     * @param string[] $exclude photo keys ("pexels:123") already used
     * @return array<int, array{photo: array, score: float}> sorted best first
     */
    public function search(string $query, string $hint = '', string $placement = 'card', array $exclude = [], int $limit = 12, ?string $category = null): array
    {
        $analyzedOnly = $this->ready();
        $qTokens = $this->tokens($query);
        $qPhrases = $this->phrases($query);
        $hTokens = array_diff($this->tokens($hint), $qTokens);
        $wants = self::wantedGender($query);
        $excluded = array_flip($exclude);
        $df = $this->documentFrequency();
        $n = max(1, $this->count());

        $scored = [];
        foreach ($this->index() as $p) {
            if (isset($excluded[$p['key']]) || ($analyzedOnly && !$p['analyzed'])) {
                continue;
            }
            if ($category && $p['category'] !== $category) {
                continue;
            }
            if ($placement === 'portrait') {
                if (!$p['people'] || ($wants && $p['person'] && $p['person'] !== $wants)) {
                    continue;
                }
            }

            $score = 0.0;
            foreach ($qTokens as $t) {
                // Words that describe a third of the catalog ("woman", "white") say little.
                $idf = 1.0 - min(0.6, (($df[$t] ?? 0) / $n) * 1.5);
                if (($p['subjects'][0] ?? null) === $t) {
                    $score += 5 * $idf;
                } elseif (in_array($t, $p['subjects'], true)) {
                    $score += 4 * $idf;
                } elseif (in_array($t, $p['tags'], true)) {
                    $score += 2.5 * $idf;
                } elseif (str_contains($p['text'], " {$t}")) {
                    $score += 1 * $idf;
                }
            }
            foreach ($qPhrases as $ph) {
                if (str_contains($p['text'], $ph)) {
                    $score += 2;
                }
            }
            if ($qTokens && $score === 0.0) {
                continue; // nothing in common with the request
            }

            // The business's own context: a dentist site prefers clinical photos even for "team".
            $hintHits = 0;
            foreach ($hTokens as $t) {
                if (in_array($t, $p['tags'], true) || in_array($t, $p['subjects'], true) || $p['category'] === $t) {
                    $hintHits++;
                }
            }
            $score += min(2.0, 0.6 * $hintHits);

            // Shape and purpose fit.
            if ($placement === 'wide') {
                $score += $p['orientation'] === 'landscape' ? 1.0 : ($p['orientation'] === 'portrait' ? -2.5 : 0);
            } elseif ($placement === 'portrait' && $p['people']) {
                $score += in_array('portrait', $p['placements'], true) ? 1.5 : 0;
            } elseif ($placement === 'gallery' && $p['orientation'] === 'portrait') {
                $score -= 0.5;
            }
            if (in_array($placement === 'wide' ? 'hero' : $placement, $p['placements'], true)) {
                $score += 0.75;
            }
            // Portrait photos of people are rarely what a card/hero wants unless asked for.
            if ($placement !== 'portrait' && $p['people'] && $p['orientation'] === 'portrait' && !in_array('portrait', $qTokens, true)) {
                $score -= 1.0;
            }

            $scored[] = ['photo' => $p, 'score' => round($score, 3)];
        }

        usort($scored, function ($a, $b) use ($query) {
            if ($b['score'] !== $a['score']) {
                return $b['score'] <=> $a['score'];
            }
            // Deterministic variety among equals: depends on the query, not insertion order.
            return crc32($query . $a['photo']['key']) <=> crc32($query . $b['photo']['key']);
        });

        return array_slice($scored, 0, $limit);
    }

    /** Photos for the picker's category browse (no query). */
    public function browse(?string $category, int $limit = 60): array
    {
        $rows = array_values(array_filter($this->index(), fn ($p) => !$category || $p['category'] === $category));
        usort($rows, fn ($a, $b) => strcmp($a['key'], $b['key']));

        return array_slice($rows, 0, $limit);
    }

    /** Picker categories present in the catalog, with counts. */
    public function categories(): array
    {
        $counts = [];
        foreach ($this->index() as $p) {
            $counts[$p['category']] = ($counts[$p['category']] ?? 0) + 1;
        }
        ksort($counts);

        return $counts;
    }

    public function url(array $photo, string $placement): string
    {
        return match ($photo['source']) {
            'pexels' => PexelsClient::sized($photo['url'], $placement),
            'unsplash' => UnsplashClient::sized($photo['url'], $placement),
            default => $photo['url'],
        };
    }

    public function thumb(array $photo): string
    {
        return match ($photo['source']) {
            'pexels' => PexelsClient::sized($photo['url'], 'thumb'),
            'unsplash' => UnsplashClient::sized($photo['url'], 'thumb'),
            default => $photo['thumb'],
        };
    }

    /** "pexels:123" / "unsplash:abc" for a catalog URL already on the page, or null. */
    public static function keyFromUrl(string $url): ?string
    {
        if (preg_match('#images\.pexels\.com/photos/(\d+)/#', $url, $m)) {
            return "pexels:{$m[1]}";
        }
        if (preg_match('#images\.unsplash\.com/photo-([\w-]+)#', $url, $m)) {
            return "unsplash:photo-{$m[1]}";
        }

        return null;
    }

    /* ------------------------------------------------------------------ */

    /** @return array<int, array> compact rows, cached */
    public function index(): array
    {
        if ($this->index !== null) {
            return $this->index;
        }

        try {
            return $this->index = Cache::remember(self::CACHE_KEY, 3600, fn () => $this->load());
        } catch (\Illuminate\Database\QueryException $e) {
            // Table not migrated yet (or no database at all): behave as an empty catalog.
            return $this->index = [];
        }
    }

    private function load(): array
    {
        {
            return StockPhoto::query()
                ->orderBy('id')
                ->get()
                ->map(fn (StockPhoto $p) => [
                    'id' => $p->id,
                    'key' => $p->key(),
                    'source' => $p->source,
                    'source_id' => $p->source_id,
                    'url' => $p->url,
                    'thumb' => $p->thumb_url,
                    'orientation' => $p->orientation,
                    'category' => $p->category,
                    'tags' => $p->tagList(),
                    'subjects' => array_map('mb_strtolower', $p->ai_subjects ?? []),
                    'text' => ' ' . ($p->search_text ?: $p->buildSearchText()) . ' ',
                    'people' => (bool) $p->people,
                    'person' => $p->person,
                    'placements' => $p->placements ?? [],
                    'label' => $p->ai_subjects[0] ?? ($p->alt ?: $p->topic),
                    'credit' => $p->photographer,
                    'color' => $p->avg_color,
                    'analyzed' => $p->analyzed_at !== null,
                ])
                ->all();
        }
    }

    /** @return array<string, int> how many photos carry each tag */
    private function documentFrequency(): array
    {
        static $df = null;
        if ($df !== null) {
            return $df;
        }
        $df = [];
        foreach ($this->index() as $p) {
            foreach (array_unique(array_merge($p['tags'], $p['subjects'])) as $t) {
                $df[$t] = ($df[$t] ?? 0) + 1;
            }
        }

        return $df;
    }

    /** @return string[] lowercase words, singularised, with known synonyms added */
    public function tokens(string $text): array
    {
        $words = preg_split('/[^a-z0-9]+/', mb_strtolower($text), -1, PREG_SPLIT_NO_EMPTY);
        $stop = ['a', 'an', 'the', 'of', 'in', 'on', 'at', 'with', 'and', 'or', 'for', 'to', 'photo', 'image', 'picture', 'shot', 'closeup', 'close', 'up', 'professional', 'high', 'quality'];
        $out = [];
        foreach ($words as $w) {
            if (in_array($w, $stop, true) || strlen($w) < 2) {
                continue;
            }
            $out[] = $w;
            if (strlen($w) > 3 && str_ends_with($w, 'ies')) {
                $out[] = substr($w, 0, -3) . 'y';
            } elseif (strlen($w) > 3 && str_ends_with($w, 's') && !str_ends_with($w, 'ss')) {
                $out[] = substr($w, 0, -1);
            }
            if (isset(ImageLibrary::SYNONYMS[$w])) {
                $out[] = ImageLibrary::SYNONYMS[$w];
            }
        }

        return array_values(array_unique($out));
    }

    /** @return string[] two-word phrases ("latte art") */
    private function phrases(string $text): array
    {
        $words = preg_split('/[^a-z0-9]+/', mb_strtolower($text), -1, PREG_SPLIT_NO_EMPTY);
        $out = [];
        for ($i = 0; $i < count($words) - 1; $i++) {
            $out[] = $words[$i] . ' ' . $words[$i + 1];
        }

        return $out;
    }

    public static function wantedGender(string $query): ?string
    {
        return preg_match('/\b(woman|female|girl|lady|she|her|mrs|ms|businesswoman)\b/i', $query) ? 'woman'
            : (preg_match('/\b(man|male|guy|boy|he|his|mr|businessman)\b/i', $query) ? 'man' : null);
    }
}
