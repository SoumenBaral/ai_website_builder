<?php

namespace App\Services;

use App\Services\Ai\ImageLibrary;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Turns the AI's "PLACEHOLDER:<search query>" values into real photo URLs.
 *
 * - With UNSPLASH_ACCESS_KEY set: searches Unsplash (cached) for the query.
 * - Otherwise: scores a curated, verified photo library against the query.
 * Photos are never repeated within one site, and each placement gets a
 * suitable size/crop (wide hero backgrounds, cards, square portraits).
 */
class ImageResolver
{
    private const PREFIX = 'PLACEHOLDER:';

    /** Image props whose photo fills a whole section (wide crop). */
    private const WIDE_SECTIONS = ['hero', 'cta', 'stats', 'about', 'faq', 'features', 'services', 'pricing', 'contact'];

    /** Sections whose own `image` is essential; other section images are decorative and optional. */
    private const REQUIRED_SECTION_IMAGES = ['hero', 'about'];

    /** List fields that hold people. */
    private const PEOPLE_FIELDS = ['avatar'];
    private const PEOPLE_LISTS = ['members'];

    /** @var array<string, true> ids already used in the current site */
    private array $used = [];

    private string $siteHint = '';

    /**
     * @param string $siteHint Extra context (industry, brand…) used when a query is vague.
     */
    public function resolvePlaceholders(array $schema, string $siteHint = ''): array
    {
        $this->used = [];
        $this->siteHint = strtolower($siteHint);

        // Existing photos count as used, so new ones don't duplicate them.
        array_walk_recursive($schema, function ($value) {
            if (is_string($value) && preg_match('#images\.unsplash\.com/photo-([\w-]+)#', $value, $m)) {
                $this->used[$m[1]] = true;
            }
        });

        if (isset($schema['sections']) && is_array($schema['sections'])) {
            foreach ($schema['sections'] as $i => $section) {
                $schema['sections'][$i] = $this->resolveSection($section);
            }
        }

        return $schema;
    }

    /**
     * Photos for a list of search queries (e.g. product titles), without repeating
     * each other or any photo in $exclude. Costs no AI tokens.
     *
     * @param string[] $queries
     * @param string[] $exclude URLs already on the page
     * @return array<int, string|null> one URL per query (null when nothing relevant was found)
     */
    public function photosFor(array $queries, string $siteHint = '', array $exclude = [], string $placement = 'card'): array
    {
        $this->used = [];
        $this->siteHint = strtolower($siteHint);
        foreach ($exclude as $url) {
            if (is_string($url) && preg_match('#images\.unsplash\.com/photo-([\w-]+)#', $url, $m)) {
                $this->used[$m[1]] = true;
            }
        }

        return array_map(
            fn ($q) => is_string($q) && trim($q) !== '' ? $this->resolve(strtolower(trim($q)), $placement, optional: true, minFresh: 0.75) : null,
            array_values($queries),
        );
    }

    public function resolveSection(array $section): array
    {
        $type = $section['type'] ?? '';
        if (!isset($section['props']) || !is_array($section['props'])) {
            return $section;
        }

        foreach ($section['props'] as $key => $value) {
            if (is_string($value) && $this->isPlaceholder($value)) {
                $placement = in_array($type, self::WIDE_SECTIONS, true) ? 'wide' : 'card';
                $url = $this->resolve($this->query($value), $placement, optional: !in_array($type, self::REQUIRED_SECTION_IMAGES, true));
                if ($url === null) {
                    unset($section['props'][$key]); // no relevant photo: better none than an unrelated one
                } else {
                    $section['props'][$key] = $url;
                }
            } elseif (is_array($value)) {
                $peopleList = in_array($key, self::PEOPLE_LISTS, true);
                foreach ($value as $j => $item) {
                    if (!is_array($item)) {
                        continue;
                    }
                    foreach ($item as $field => $fieldValue) {
                        if (is_string($fieldValue) && $this->isPlaceholder($fieldValue)) {
                            $placement = ($peopleList || in_array($field, self::PEOPLE_FIELDS, true)) ? 'portrait'
                                : ($key === 'images' ? 'gallery' : 'card');
                            $optional = $field === 'image' && in_array($type, ['features', 'services'], true);
                            $url = $this->resolve($this->query($fieldValue), $placement, $optional);
                            if ($url === null) {
                                unset($section['props'][$key][$j][$field]);
                            } else {
                                $section['props'][$key][$j][$field] = $url;
                            }
                        }
                    }
                }
            }
        }

        return $section;
    }

    private function isPlaceholder(string $value): bool
    {
        return str_starts_with($value, self::PREFIX);
    }

    private function query(string $value): string
    {
        return strtolower(trim(substr($value, strlen(self::PREFIX))));
    }

    /** @param bool $optional Return null instead of an unrelated fallback photo. */
    private function resolve(string $query, string $placement, bool $optional = false, float $minFresh = 2.5): ?string
    {
        if ($placement !== 'portrait' && config('ai.unsplash_access_key')) {
            $url = $this->searchUnsplash($query, $placement);
            if ($url) {
                return $url;
            }
        }

        $id = $placement === 'portrait' ? $this->pickPortrait($query) : $this->pickPhoto($query, $placement, $optional, $minFresh);
        if ($id === null) {
            return null;
        }
        $this->used[$id] = true;

        return $this->url($id, $placement);
    }

    private function url(string $id, string $placement): string
    {
        $params = match ($placement) {
            'wide' => 'w=1600&q=80&auto=format&fit=crop',
            'portrait' => 'w=400&h=400&q=80&auto=format&fit=crop&crop=faces',
            'gallery' => 'w=900&h=900&q=80&auto=format&fit=crop',
            default => 'w=900&q=80&auto=format&fit=crop',
        };

        return "https://images.unsplash.com/photo-{$id}?{$params}";
    }

    /* ---------------- Curated library ---------------- */

    /** @return string[] */
    private function tokens(string $text): array
    {
        $words = preg_split('/[^a-z0-9]+/', strtolower($text), -1, PREG_SPLIT_NO_EMPTY);
        $tokens = [];
        foreach ($words as $w) {
            $tokens[] = $w;
            if (isset(ImageLibrary::SYNONYMS[$w])) {
                $tokens[] = ImageLibrary::SYNONYMS[$w];
            }
            if (strlen($w) > 3 && str_ends_with($w, 's')) {
                $tokens[] = substr($w, 0, -1);
            }
        }
        // Two-word phrases (e.g. "real estate", "hair salon")
        for ($i = 0; $i < count($words) - 1; $i++) {
            $tokens[] = $words[$i] . ' ' . $words[$i + 1];
        }

        return array_values(array_unique($tokens));
    }

    /** How well a photo's tags match the query (primary subject counts most). */
    private function queryScore(array $tags, array $queryTokens): float
    {
        $score = 0.0;
        foreach ($tags as $rank => $tag) {
            if (in_array($tag, $queryTokens, true)) {
                $score += $rank === 0 ? 3.0 : 2.0;
            }
        }

        return $score;
    }

    /** How many of a photo's tags relate to the business itself (from the site hint). */
    private function hintOverlap(array $tags, array $hintTokens): int
    {
        return count(array_intersect($tags, $hintTokens));
    }

    private function pickPhoto(string $query, string $placement, bool $optional = false, float $minFresh = 2.5): ?string
    {
        $queryTokens = $this->tokens($query);
        $hintTokens = $this->tokens($this->siteHint);

        // Off-topic photos are only wrong when the query is about the business itself
        // ("modern dental clinic interior" on a dental site) — not for specific products ("sneakers").
        $queryAboutBusiness = (bool) array_intersect($queryTokens, $hintTokens);

        // Does the library cover this business's domain at all (e.g. dental, restaurant)?
        $domainKnown = false;
        foreach (ImageLibrary::PHOTOS as $tags) {
            if ($this->hintOverlap($tags, $hintTokens) >= 2) {
                $domainKnown = true;
                break;
            }
        }

        $scores = [];
        foreach (ImageLibrary::PHOTOS as $id => $tags) {
            $overlap = $this->hintOverlap($tags, $hintTokens);
            $score = $this->queryScore($tags, $queryTokens) + 0.75 * $overlap;
            // A photo unrelated to the business (a living room on a dental site) is a poor match
            // even if one query word fits.
            if ($domainKnown && $queryAboutBusiness && $overlap === 0) {
                $score *= 0.5;
            }
            if ($score > 0) {
                $scores[$id] = $score;
            }
        }

        $pickTop = function (array $candidates) use ($query): string {
            $top = max($candidates);
            // Among equally good matches, vary deterministically by query.
            $ties = array_keys(array_filter($candidates, fn ($s) => $s === $top));

            return $ties[crc32($query) % count($ties)];
        };

        $fresh = array_filter($scores, fn ($s, $id) => !isset($this->used[$id]), ARRAY_FILTER_USE_BOTH);

        // 1. A good photo not yet used on this site.
        if ($fresh && max($fresh) >= $minFresh) {
            return $pickTop($fresh);
        }
        // Decorative images: never repeat, never settle — leave them out.
        if ($optional) {
            return null;
        }
        // 2. A relevant repeat beats an unrelated photo.
        if ($scores && max($scores) >= 3) {
            return $pickTop($scores);
        }
        // 3. Any partial match.
        if ($fresh) {
            return $pickTop($fresh);
        }

        $pool = array_values(array_filter(ImageLibrary::GENERIC[$placement === 'wide' ? 'wide' : 'card'], fn ($id) => !isset($this->used[$id])))
            ?: ImageLibrary::GENERIC[$placement === 'wide' ? 'wide' : 'card'];

        return $pool[crc32($query) % count($pool)];
    }

    private function pickPortrait(string $query): string
    {
        $wants = preg_match('/\b(woman|female|girl|lady|she|her|mrs|ms)\b/', $query) ? 'woman'
            : (preg_match('/\b(man|male|guy|boy|he|his|mr)\b/', $query) ? 'man' : null);

        $candidates = array_keys(array_filter(ImageLibrary::PORTRAITS, fn ($g) => $wants === null || $g === $wants));
        $fresh = array_values(array_filter($candidates, fn ($id) => !isset($this->used[$id])));
        $pool = $fresh ?: $candidates;

        return $pool[crc32($query) % count($pool)];
    }

    /* ---------------- Optional Unsplash search ---------------- */

    private function searchUnsplash(string $query, string $placement): ?string
    {
        $orientation = $placement === 'wide' ? 'landscape' : ($placement === 'gallery' ? 'squarish' : 'landscape');

        try {
            $results = Cache::remember('unsplash:' . md5("{$query}|{$orientation}"), now()->addDays(7), function () use ($query, $orientation) {
                $response = Http::timeout(6)
                    ->withHeaders(['Authorization' => 'Client-ID ' . config('ai.unsplash_access_key'), 'Accept-Version' => 'v1'])
                    ->get('https://api.unsplash.com/search/photos', [
                        'query' => $query,
                        'per_page' => 10,
                        'orientation' => $orientation,
                        'content_filter' => 'high',
                    ]);

                if (!$response->ok()) {
                    throw new \RuntimeException('Unsplash HTTP ' . $response->status());
                }

                return collect($response->json('results', []))
                    ->map(fn ($r) => ['id' => $r['id'] ?? null, 'raw' => $r['urls']['raw'] ?? null])
                    ->filter(fn ($r) => $r['id'] && $r['raw'])
                    ->values()
                    ->all();
            });
        } catch (\Throwable $e) {
            Log::warning('Unsplash search failed, using curated library', ['query' => $query, 'error' => $e->getMessage()]);

            return null;
        }

        foreach ($results as $photo) {
            if (!isset($this->used['api:' . $photo['id']])) {
                $this->used['api:' . $photo['id']] = true;
                $size = $placement === 'wide' ? 'w=1600' : 'w=900';

                return $photo['raw'] . (str_contains($photo['raw'], '?') ? '&' : '?') . "{$size}&q=80&auto=format&fit=crop";
            }
        }

        return null;
    }
}
