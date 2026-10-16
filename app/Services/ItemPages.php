<?php

namespace App\Services;

use App\Services\Ai\BlockCatalog;
use Illuminate\Support\Str;

/**
 * Details pages for cards (products, services, team members…).
 *
 * A card's page lives at  {base}/{section anchor}/{item slug}, e.g. /s/my-shop/services/oxford-shirt.
 * Slugs come from the card's title, are unique inside their section and fall back to
 * "item-3" for titles that don't transliterate (e.g. Bangla).
 */
class ItemPages
{
    /**
     * Every card that has a page, keyed by section index.
     *
     * @return array<int, array{anchor: string, list: string, slugs: array<int, string>}>
     */
    public static function map(array $schema): array
    {
        $sections = array_values($schema['sections'] ?? []);
        $anchors = BlockCatalog::anchors($sections);
        $map = [];

        foreach ($sections as $i => $section) {
            $list = BlockCatalog::ITEM_PAGE_LISTS[$section['type'] ?? ''] ?? null;
            if (!$list || empty($section['props']['itemPages']) || empty($anchors[$i])) {
                continue;
            }
            $slugs = [];
            foreach (array_values($section['props'][$list] ?? []) as $n => $item) {
                $base = Str::slug(self::title($item)) ?: 'item-' . ($n + 1);
                $slug = $base;
                for ($k = 2; in_array($slug, $slugs, true); $k++) {
                    $slug = "{$base}-{$k}";
                }
                $slugs[$n] = $slug;
            }
            $map[$i] = ['anchor' => $anchors[$i], 'list' => $list, 'slugs' => $slugs];
        }

        return $map;
    }

    /**
     * Find the card behind a URL.
     *
     * @return array{sectionIndex: int, itemIndex: int, section: array, item: array, items: array, slugs: array, anchor: string}|null
     */
    public static function find(array $schema, string $anchor, string $slug): ?array
    {
        $sections = array_values($schema['sections'] ?? []);
        foreach (self::map($schema) as $i => $entry) {
            if ($entry['anchor'] !== $anchor) {
                continue;
            }
            $n = array_search($slug, $entry['slugs'], true);
            if ($n === false) {
                return null;
            }
            $items = array_values($sections[$i]['props'][$entry['list']] ?? []);

            return [
                'sectionIndex' => $i,
                'itemIndex' => $n,
                'section' => $sections[$i],
                'item' => $items[$n],
                'items' => $items,
                'slugs' => $entry['slugs'],
                'anchor' => $anchor,
            ];
        }

        return null;
    }

    public static function title(array $item): string
    {
        return trim((string) ($item['title'] ?? $item['name'] ?? ''));
    }
}
