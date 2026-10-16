<?php

namespace App\Services\Shop;

use App\Services\ItemPages;

/**
 * The site's shop: every card in a section with details pages AND "Add to cart" switched on.
 * The server is the source of truth for names and prices — the browser's cart only holds ids.
 */
class Shop
{
    /**
     * @param callable|null $itemUrl fn(anchor, slug): string — product page links
     * @return array<string, array{id: string, title: string, price: ?string, amount: ?float, image: ?string, url: ?string, money: ?array}>
     */
    public static function products(array $schema, ?callable $itemUrl = null): array
    {
        $sections = array_values($schema['sections'] ?? []);
        $products = [];
        foreach (ItemPages::map($schema) as $i => $entry) {
            if (($sections[$i]['type'] ?? '') !== 'services' || empty($sections[$i]['props']['cart'])) {
                continue;
            }
            $items = array_values($sections[$i]['props'][$entry['list']] ?? []);
            foreach ($entry['slugs'] as $n => $slug) {
                $item = $items[$n] ?? [];
                $id = "{$entry['anchor']}/{$slug}";
                $money = Price::parse($item['price'] ?? null);
                $products[$id] = [
                    'id' => $id,
                    'title' => ItemPages::title($item),
                    'price' => $item['price'] ?? null,
                    'amount' => $money['amount'] ?? null,
                    'money' => $money,
                    'image' => $item['image'] ?? null,
                    'url' => $itemUrl ? $itemUrl($entry['anchor'], $slug) : null,
                ];
            }
        }

        return $products;
    }

    public static function enabled(array $schema): bool
    {
        return (bool) self::products($schema);
    }

    /** The currency the totals are written in: taken from the first priced product. */
    public static function currency(array $products): array
    {
        foreach ($products as $p) {
            if ($p['money']) {
                return ['symbol' => $p['money']['symbol'], 'prefix' => $p['money']['prefix'], 'space' => $p['money']['space']];
            }
        }

        return ['symbol' => '', 'prefix' => true, 'space' => false];
    }

    /** Contact details for checkout alternatives (WhatsApp / email). */
    public static function contact(array $schema): array
    {
        $contact = collect($schema['sections'] ?? [])->firstWhere('type', 'contact')['props'] ?? [];
        $digits = preg_replace('/\D+/', '', (string) ($contact['phone'] ?? ''));

        return [
            'whatsapp' => strlen($digits) >= 8 ? $digits : null,
            'email' => filter_var($contact['email'] ?? null, FILTER_VALIDATE_EMAIL) ?: null,
        ];
    }
}
