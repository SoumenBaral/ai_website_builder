<?php

namespace App\Services;

use App\Models\Project;
use App\Services\Ai\BlockCatalog;
use App\Services\Logo\FaviconService;
use App\Services\Shop\Shop;
use App\Services\Ai\Color;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class ExportService
{
    /** ZIP exports inline the stylesheet so the site works from any folder (and offline). */
    private bool $inlineCss = false;

    /**
     * @param array|null    $favicon     the project's favicon set (null = initials icon in the site's colours)
     * @param string|null   $faviconBase prefix for favicon files; "" when they sit next to index.html (ZIP)
     * @param callable|null $itemUrl     fn(string $anchor, string $slug): string — where card details pages live
     * @param array|null    $shop        ['mode' => live|preview|static, 'orderUrl' => ?string, 'key' => string] — cart setup
     */
    public function toHtml(array $schema, ?string $beacon = null, ?array $favicon = null, ?string $faviconBase = null, ?callable $itemUrl = null, ?array $shop = null): string
    {
        // Section ids make in-page links ("#about", "#contact") work on published sites.
        $sections = array_values($schema['sections']);
        $anchors = BlockCatalog::anchors($sections);
        $pages = $itemUrl ? ItemPages::map($schema) : [];

        $body = collect($sections)
            ->map(function ($block, $index) use ($schema, $anchors, $pages, $itemUrl) {
                $links = isset($pages[$index])
                    ? array_map(fn ($slug) => $itemUrl($pages[$index]['anchor'], $slug), $pages[$index]['slugs'])
                    : [];

                return $this->renderSection($block, $schema, $anchors[$index] ?? null, $links);
            })
            ->implode("\n");

        return $this->page($schema, $schema['meta'], $body, $beacon, app(FaviconService::class)->headTags($favicon, $schema, $faviconBase), self::usesAnimations($schema), $this->shopConfig($schema, $itemUrl, $shop));
    }

    /**
     * A card's own details page, or null when the card doesn't exist / has no page.
     *
     * @param string   $homeUrl the site's home page (navbar/footer "#about" links point there)
     * @param callable $itemUrl fn(string $anchor, string $slug): string
     */
    public function itemHtml(array $schema, string $anchor, string $slug, string $homeUrl, callable $itemUrl, ?string $beacon = null, ?array $favicon = null, ?string $faviconBase = null, ?array $shop = null): ?string
    {
        $found = ItemPages::find($schema, $anchor, $slug);
        if (!$found) {
            return null;
        }

        $sections = array_values($schema['sections']);
        $item = $found['item'];
        $section = $found['section'];
        $type = $section['type'];
        $title = ItemPages::title($item);
        $siteTitle = $schema['meta']['title'] ?? '';

        // The site's own navbar and footer, with in-page links pointing back home.
        $chrome = function (string $type) use ($sections, $schema, $homeUrl) {
            $block = collect($sections)->firstWhere('type', $type);
            if (!$block) {
                return '';
            }
            $html = $this->renderSection($block, $schema, null, []);

            return preg_replace('/href="#([^"]*)"/', 'href="' . e($homeUrl) . '#$1"', $html);
        };

        $count = count($found['items']);
        $n = $found['itemIndex'];
        $urlOf = fn (int $i) => $itemUrl($anchor, $found['slugs'][$i]);
        $related = [];
        for ($k = 1; $k < $count && count($related) < 3; $k++) {
            $i = ($n + $k) % $count;
            $related[] = ['item' => $found['items'][$i], 'url' => $urlOf($i)];
        }

        $photos = array_values(array_unique(array_filter([
            $item['image'] ?? null,
            ...(is_array($item['photos'] ?? null) ? $item['photos'] : []),
        ], fn ($u) => is_string($u) && $u !== '')));

        $cta = $this->itemCta($item, $section, $sections, $homeUrl);
        $shopConfig = $this->shopConfig($schema, $itemUrl, $shop);
        $product = $shopConfig['products']["{$anchor}/{$slug}"] ?? null;
        $summary = trim((string) ($item['description'] ?? $item['bio'] ?? $item['role'] ?? ''));

        $body = $chrome('navbar')
            . view('export.item', [
                'type' => $type,
                'item' => $item,
                'title' => $title,
                'summary' => $summary,
                'paragraphs' => self::paragraphs($item['details'] ?? ''),
                'highlights' => array_values(array_filter((array) ($item['highlights'] ?? []), 'is_string')),
                'photos' => $photos,
                'cta' => $cta,
                'sectionHeading' => $section['props']['heading'] ?? ucfirst($type),
                'sectionUrl' => $homeUrl . '#' . $anchor,
                'homeUrl' => $homeUrl,
                'siteTitle' => $siteTitle,
                'related' => $related,
                'prev' => $count > 1 ? ['title' => ItemPages::title($found['items'][($n - 1 + $count) % $count]), 'url' => $urlOf(($n - 1 + $count) % $count)] : null,
                'next' => $count > 1 ? ['title' => ItemPages::title($found['items'][($n + 1) % $count]), 'url' => $urlOf(($n + 1) % $count)] : null,
                'position' => $n + 1,
                'count' => $count,
                'product' => $product,
            ])->render()
            . $chrome('footer');

        $description = mb_substr($summary !== '' ? $summary : strip_tags((string) ($item['details'] ?? '')), 0, 160);
        $meta = [
            'title' => $title . ($siteTitle ? " — {$siteTitle}" : ''),
            'description' => $description,
            'language' => $schema['meta']['language'] ?? 'en',
        ];

        $head = app(FaviconService::class)->headTags($favicon, $schema, $faviconBase)
            . "\n" . '<meta property="og:title" content="' . e($meta['title']) . '">'
            . "\n" . '<meta property="og:description" content="' . e($description) . '">'
            . "\n" . '<meta property="og:type" content="' . ($type === 'team' ? 'profile' : 'product') . '">'
            . ($photos ? "\n" . '<meta property="og:image" content="' . e($photos[0]) . '">' : '')
            . "\n" . '<script type="application/ld+json">' . json_encode(self::structuredData($type, $item, $title, $description, $photos), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) . '</script>';

        return $this->page($schema, $meta, $body, $beacon, $head, true, $shopConfig);
    }

    /** The prebuilt site stylesheet (public/site/site.css), linked or inlined. */
    private function tailwind(): array
    {
        $path = public_path('site/site.css');
        if (!is_file($path)) {
            return [];
        }

        return $this->inlineCss
            ? ['inline' => file_get_contents($path)]
            : ['href' => '/site/site.css?v=' . substr(md5_file($path), 0, 10)];
    }

    /** Cart setup for the browser, or null when the site doesn't sell anything. */
    private function shopConfig(array $schema, ?callable $itemUrl, ?array $shop): ?array
    {
        if (!$itemUrl) {
            return null;
        }
        $products = Shop::products($schema, $itemUrl);
        if (!$products) {
            return null;
        }
        $mode = $shop['mode'] ?? 'preview';
        $contact = Shop::contact($schema);

        return [
            'key' => $shop['key'] ?? substr(md5($schema['meta']['title'] ?? 'site'), 0, 10),
            'mode' => $mode,
            'orderUrl' => $mode === 'live' ? ($shop['orderUrl'] ?? null) : null,
            'siteName' => $schema['meta']['title'] ?? 'the shop',
            'currency' => Shop::currency($products),
            'whatsapp' => $contact['whatsapp'],
            'email' => $contact['email'],
            'products' => array_map(fn ($p) => array_intersect_key($p, array_flip(['id', 'title', 'price', 'amount', 'image', 'url'])), $products),
        ];
    }

    /** One section, wrapped with its colour overrides, motion attributes and anchor. */
    private function renderSection(array $block, array $schema, ?string $anchor, array $links): string
    {
        $viewName = "export.blocks.{$block['type']}";
        if (!view()->exists($viewName)) {
            return "<!-- Unknown block type: {$block['type']} -->";
        }
        $html = view($viewName, [
            'props' => $block['props'],
            'theme' => $schema['theme'],
            'links' => $links, // card index => details page URL
        ])->render();

        // Images further down the page load only when the visitor gets near them.
        if (!in_array($block['type'], ['navbar', 'hero'], true)) {
            $html = preg_replace('/<img(?![^>]*\bloading=)/i', '<img loading="lazy" decoding="async"', $html);
        }

        $hasBg = !empty($block['props']['sectionBg']);
        $hasText = !empty($block['props']['sectionText']);
        if ($hasBg || $hasText) {
            $vars = '';
            if ($hasBg) {
                $bg = e($block['props']['sectionBg']);
                $vars .= '--bg: ' . $bg;
                $primary = Color::normalize($schema['theme']['primaryColor'] ?? null);
                $bgHex = Color::normalize($block['props']['sectionBg']);
                $bandText = Color::normalize($block['props']['sectionText'] ?? null);
                if ($bgHex) {
                    // Cards and hairlines must stand off this band, not disappear into it.
                    foreach (Color::surfaceVars($bgHex) as $name => $value) {
                        $vars .= "; --{$name}: {$value}";
                    }
                }
                if ($block['type'] === 'cta') {
                    $vars .= '; --primary: ' . $bg;
                    if ($bgHex) $vars .= '; --on-primary: ' . ($bandText ?? Color::readableOn($bgHex));
                }
                if ($block['type'] === 'footer') $vars .= '; --secondary: ' . $bg;
                // Brand-coloured accents (prices, ticks, ribbons, buttons) would vanish on a band painted
                // the brand colour: swap them for the band's text colour, and put the band colour on top
                // of them so "white on primary" never becomes white on white.
                if ($block['type'] !== 'cta' && $primary && $bgHex && Color::contrast($primary, $bgHex) < 2.5) {
                    $vars .= '; --primary: ' . e($bandText ?? Color::readableOn($bgHex));
                    $vars .= '; --on-primary: ' . e($bgHex);
                }
            }
            if ($hasText) {
                $txt = e($block['props']['sectionText']);
                if ($vars) $vars .= '; ';
                $vars .= '--text: ' . $txt . '; color: ' . $txt;
            }
            $html = '<div style="' . $vars . ';">' . $html . '</div>';
        }

        // Bands painted in the brand colour (CTA, or a matching sectionBg): heading shimmer uses a
        // light/dark sheen of the text colour instead of the (invisible) brand colour.
        if (($block['props']['textEffect'] ?? null) === 'gradient') {
            $primary = Color::normalize($schema['theme']['primaryColor'] ?? null);
            $bandBg = Color::normalize($block['props']['sectionBg'] ?? null) ?? ($block['type'] === 'cta' ? $primary : null);
            if ($primary && $bandBg && Color::contrast($primary, $bandBg) < 2.5) {
                $text = Color::normalize($block['props']['sectionText'] ?? null) ?? Color::readableOn($bandBg);
                $accent = Color::mix($text, Color::isDark($bandBg) ? '#ffffff' : '#000000', 0.45);
                $html = '<div style="--tx-accent: ' . e($accent) . ';">' . $html . '</div>';
            }
        }

        // Entrance animation + hover effect (see resources/js/site/animRuntime.js)
        if ($attrs = self::animationAttributes($block)) {
            $html = '<div' . $attrs . '>' . $html . '</div>';
        }

        if ($anchor) {
            $html = '<div id="' . e($anchor) . '" style="scroll-margin-top: 72px;">' . $html . '</div>';
        }

        return $html;
    }

    private function page(array $schema, array $meta, string $body, ?string $beacon, string $head, bool $motion, ?array $shop = null): string
    {
        return view('export.shell', [
            'tailwind' => $this->tailwind(),
            'shop' => $shop ? [
                'css' => file_get_contents(resource_path('css/site-shop.css')),
                'config' => json_encode($shop, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP),
                'runtime' => file_get_contents(resource_path('js/site/cartRuntime.js')),
            ] : null,
            'meta' => $meta,
            'theme' => $schema['theme'],
            'body' => $body,
            'beacon' => $beacon,
            'head' => $head,
            'animations' => $motion ? file_get_contents(resource_path('css/site-animations.css')) : null,
            'animationRuntime' => $motion ? file_get_contents(resource_path('js/site/animRuntime.js')) : null,
        ])->render();
    }

    /** The details page button: the card's own, else the site's contact section, else its main CTA. */
    private function itemCta(array $item, array $section, array $sections, string $homeUrl): array
    {
        $types = array_column($sections, 'type');
        $hero = collect($sections)->firstWhere('type', 'hero')['props'] ?? [];
        $contact = collect($sections)->firstWhere('type', 'contact')['props'] ?? [];

        $href = trim((string) ($item['ctaHref'] ?? ''));
        if ($href === '') {
            $href = in_array('contact', $types, true) ? '#contact'
                : (!empty($hero['ctaHref']) ? $hero['ctaHref'] : (!empty($contact['email']) ? 'mailto:' . $contact['email'] : '#'));
        }
        if (preg_match('/^\s*(javascript|data|vbscript):/i', $href)) {
            $href = '#'; // never a script link
        }
        if (str_starts_with($href, '#')) {
            $href = $homeUrl . $href;
        }

        $text = trim((string) ($item['ctaText'] ?? ''));
        if ($text === '') {
            $text = match (true) {
                $section['type'] === 'team' => 'Get in touch',
                !empty($item['price']) => 'Order now',
                !empty($hero['ctaText']) => $hero['ctaText'],
                default => 'Get in touch',
            };
        }

        return ['text' => $text, 'href' => $href];
    }

    /** Blank lines (or single newlines) separate paragraphs. */
    private static function paragraphs(mixed $text): array
    {
        if (!is_string($text) || trim($text) === '') {
            return [];
        }

        $paragraphs = array_values(array_filter(array_map('trim', preg_split('/\R+/u', trim($text)))));

        // One long block reads badly: break it at sentence ends into ~45-word paragraphs.
        if (count($paragraphs) === 1 && str_word_count($paragraphs[0]) > 60) {
            $sentences = preg_split('/(?<=[.!?।])\s+/u', $paragraphs[0]);
            $paragraphs = [''];
            foreach ($sentences as $sentence) {
                $last = count($paragraphs) - 1;
                if ($paragraphs[$last] !== '' && str_word_count($paragraphs[$last]) >= 40) {
                    $paragraphs[] = '';
                    $last++;
                }
                $paragraphs[$last] = trim($paragraphs[$last] . ' ' . $sentence);
            }
        }

        return $paragraphs;
    }

    /** schema.org data so search engines understand the page (Product with price, Service, Person). */
    private static function structuredData(string $type, array $item, string $title, string $description, array $photos): array
    {
        if ($type === 'team') {
            return array_filter(['@context' => 'https://schema.org', '@type' => 'Person', 'name' => $title, 'jobTitle' => $item['role'] ?? null, 'description' => $description, 'image' => $photos[0] ?? null]);
        }

        $data = array_filter(['@context' => 'https://schema.org', '@type' => !empty($item['price']) ? 'Product' : 'Service', 'name' => $title, 'description' => $description, 'image' => $photos ?: null]);
        if (!empty($item['price']) && preg_match('/([$€£₹৳¥])?\s*([\d][\d,]*(?:\.\d+)?)/u', (string) $item['price'], $m)) {
            $currency = ['$' => 'USD', '€' => 'EUR', '£' => 'GBP', '₹' => 'INR', '৳' => 'BDT', '¥' => 'JPY'][$m[1] ?? ''] ?? null;
            $data['offers'] = array_filter(['@type' => 'Offer', 'price' => str_replace(',', '', $m[2]), 'priceCurrency' => $currency, 'availability' => 'https://schema.org/InStock']);
        }

        return $data;
    }

    /** data-* attributes for a section's animation and hover effect ('' when it has neither). */
    public static function animationAttributes(array $block): string
    {
        $props = $block['props'] ?? [];
        $attrs = '';
        $anim = BlockCatalog::canonicalAnimation($props['animation'] ?? null);
        $custom = $anim === 'custom' ? \App\Services\Ai\MotionSpec::entrance($props['customAnimation'] ?? null) : null;
        if ($anim === 'custom' && !$custom) {
            $anim = 'fade-up';
        }
        if ($anim && $anim !== 'none') {
            $attrs .= ' data-anim="' . $anim . '"';
            if ($custom) {
                $attrs .= " data-anim-custom='" . e(json_encode($custom, JSON_UNESCAPED_UNICODE)) . "'";
            }
            if (in_array($props['animationSpeed'] ?? null, ['fast', 'slow'], true)) {
                $attrs .= ' data-anim-speed="' . $props['animationSpeed'] . '"';
            }
            if (!empty($props['animationStagger'])) {
                $attrs .= ' data-anim-stagger';
            }
            if (($block['type'] ?? '') === 'navbar') {
                $attrs .= ' data-anim-whole'; // the bar moves as one piece
            }
        }
        if ($effect = \App\Services\Ai\MotionSpec::effect($props['bgEffect'] ?? null)) {
            $attrs .= " data-fx='" . e(json_encode($effect, JSON_UNESCAPED_UNICODE)) . "'";
        }
        $text = $props['textEffect'] ?? null;
        if (in_array($text, BlockCatalog::TEXT_EFFECTS, true) && $text !== 'none') {
            $attrs .= ' data-text-fx="' . $text . '"';
            if (in_array($props['textSpeed'] ?? null, ['fast', 'slow'], true)) {
                $attrs .= ' data-text-speed="' . $props['textSpeed'] . '"';
            }
            if ($text === 'typing-loop' && ($phrases = \App\Services\Ai\MotionSpec::phrases($props['typingPhrases'] ?? null))) {
                $attrs .= " data-text-phrases='" . e(json_encode($phrases, JSON_UNESCAPED_UNICODE)) . "'";
            }
        }
        $buttons = $props['buttonEffect'] ?? null;
        if (in_array($buttons, BlockCatalog::BUTTON_EFFECTS, true) && $buttons !== 'none') {
            $attrs .= ' data-btn-fx="' . $buttons . '"';
        }
        $hover = $props['hoverEffect'] ?? null;
        if (in_array($hover, BlockCatalog::HOVER_EFFECTS, true) && $hover !== 'none') {
            $attrs .= ' data-hover="' . $hover . '"';
        }

        return $attrs;
    }

    private static function usesAnimations(array $schema): bool
    {
        foreach ($schema['sections'] ?? [] as $section) {
            if (self::animationAttributes($section) !== '') {
                return true;
            }
        }

        return false;
    }

    public function toZip(array $schema, ?Project $project = null): string
    {
        $favicon = $project?->favicon;
        $this->inlineCss = true;
        // Details pages live in folders next to index.html: services/oxford-shirt.html
        $shop = ['mode' => 'static', 'key' => $project?->slug ?? 'site'];
        $html = $this->toHtml($schema, null, $favicon, '', fn ($anchor, $slug) => "{$anchor}/{$slug}.html", $shop);

        $dir = storage_path('app/temp');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $zipPath = $dir . '/' . uniqid('export_') . '.zip';

        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE);

        // Every page of the site: path in the ZIP => [html, prefix back to the ZIP root]
        $pages = ['index.html' => [$html, '']];
        foreach (ItemPages::map($schema) as $entry) {
            foreach ($entry['slugs'] as $slug) {
                $page = $this->itemHtml($schema, $entry['anchor'], $slug, '../index.html', fn ($a, $s) => "../{$a}/{$s}.html", null, $favicon, '../', $shop);
                if ($page) {
                    $pages["{$entry['anchor']}/{$slug}.html"] = [$page, '../'];
                }
            }
        }

        // Uploaded files (logo, photos…) live on this server as /storage/…; a downloaded site
        // can't reach them, so copy each one into media/ and point every page at the copy.
        $media = [];
        foreach ($pages as [$page]) {
            preg_match_all('#/storage/((?:assets|favicons)/[A-Za-z0-9_\-./]+\.(?:png|jpe?g|gif|webp|avif|svg|ico|mp4|webm|pdf))#i', $page, $m);
            foreach ($m[1] as $path) {
                if (!isset($media[$path]) && !str_contains($path, '..') && Storage::disk('public')->exists($path)) {
                    $media[$path] = 'media/' . $path;
                    $zip->addFromString('media/' . $path, Storage::disk('public')->get($path));
                }
            }
        }
        foreach ($pages as $name => [$page, $prefix]) {
            foreach ($media as $path => $local) {
                $page = str_replace('/storage/' . $path, $prefix . $local, $page);
            }
            $zip->addFromString($name, $page);
        }

        $zip->addFromString('README.txt', $this->zipReadme($schema, $project));
        $this->inlineCss = false;

        // Favicon files sit next to index.html so the site works from any host.
        if ($project && !empty($favicon['files'])) {
            foreach ($favicon['files'] as $name => $url) {
                if ($name === 'manifest.json') {
                    continue;
                }
                $path = ltrim(str_replace('/storage/', '', $url), '/');
                if (Storage::disk('public')->exists($path)) {
                    $zip->addFromString($name, Storage::disk('public')->get($path));
                }
            }
            $zip->addFromString('manifest.json', app(FaviconService::class)->manifest($project, $favicon['files'], ''));
        }

        $zip->close();

        return $zipPath;
    }

    private function zipReadme(array $schema, ?Project $project): string
    {
        $title = $schema['meta']['title'] ?? 'Your website';
        $shop = Shop::enabled($schema);

        return "{$title}\n" . str_repeat('=', mb_strlen($title)) . "\n\n"
            . "Open index.html to view the site, or upload this whole folder to any web host\n"
            . "(Netlify, Vercel, GitHub Pages, cPanel…). Keep the folders together.\n\n"
            . "• media/ holds your uploaded logo and photos.\n"
            . "• Fonts and styling load from the internet (Google Fonts, Tailwind CDN), so the\n"
            . "  pages look best online.\n"
            . "• The contact form opens the visitor's email app (or WhatsApp) with their message.\n"
            . ($shop ? "• Shop: this copy has no server, so checkout sends the order by WhatsApp or email.\n"
                . "  Orders placed on your published site (" . ($project ? url("/s/{$project->slug}") : 'your live link') . ") still arrive in your Orders page.\n" : '')
            . "\nMade with AiWebBuilder.\n";
    }
}
