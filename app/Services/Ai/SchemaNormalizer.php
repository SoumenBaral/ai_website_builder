<?php

namespace App\Services\Ai;

/**
 * Makes any AI output safe and consistent before it reaches the editor or a
 * published page. It never throws on bad values — it repairs or drops them —
 * and records what it changed in fixes() for logging and tests.
 *
 *  - props are coerced to the types the Vue/Blade blocks expect; lists always exist
 *  - unknown section types and props are dropped
 *  - fonts are limited to the editor's list, colours to valid hex
 *  - text/background, section and button colours are kept readable (WCAG)
 *  - ids are unique, navbar first / footer last, links point at real sections
 */
final class SchemaNormalizer
{
    private const DEFAULT_THEME = [
        'primaryColor' => '#2563eb',
        'secondaryColor' => '#0f172a',
        'backgroundColor' => '#ffffff',
        'textColor' => '#0f172a',
        'fontHeading' => 'Poppins',
        'fontBody' => 'Inter',
        'radius' => 'lg',
        'mode' => 'light',
    ];

    /** Common link targets → section type. */
    private const LINK_SYNONYMS = [
        'contact' => ['contact', 'contact-us', 'contactus', 'get-in-touch', 'getintouch', 'book', 'booking', 'bookings', 'reserve', 'reservation', 'reservations', 'appointment', 'appointments', 'quote', 'enquire', 'inquire', 'hire', 'hire-me', 'location', 'visit', 'find-us'],
        'about' => ['about', 'about-us', 'aboutus', 'story', 'our-story', 'who-we-are', 'mission', 'me', 'about-me', 'bio'],
        'services' => ['services', 'service', 'what-we-do', 'offerings', 'offer', 'menu', 'products', 'shop', 'classes', 'treatments', 'solutions', 'packages'],
        'features' => ['features', 'feature', 'benefits', 'why-us', 'why', 'highlights', 'how-it-works'],
        'pricing' => ['pricing', 'prices', 'price', 'plans', 'plan', 'rates', 'membership', 'tickets'],
        'gallery' => ['gallery', 'work', 'our-work', 'portfolio', 'projects', 'photos', 'showcase', 'images'],
        'team' => ['team', 'our-team', 'people', 'staff', 'coaches', 'doctors', 'artists'],
        'testimonials' => ['testimonials', 'reviews', 'review', 'clients', 'love', 'stories'],
        'faq' => ['faq', 'faqs', 'questions', 'help'],
        'stats' => ['stats', 'numbers', 'results', 'impact'],
        'cta' => ['cta', 'join', 'start', 'get-started', 'signup', 'sign-up'],
    ];

    /** @var string[] */
    private array $fixes = [];

    /** @return string[] Human-readable list of repairs made during the last call. */
    public function fixes(): array
    {
        return $this->fixes;
    }

    /* ==================================================================
     | Whole site
     * ================================================================== */

    /**
     * @param bool $ensureFrame Add a navbar/footer if missing (new sites only — edits may remove them on purpose).
     */
    public function normalizeSite(array $schema, bool $ensureFrame = false): array
    {
        $this->fixes = [];

        $meta = is_array($schema['meta'] ?? null) ? $schema['meta'] : [];
        $theme = $this->normalizeTheme(is_array($schema['theme'] ?? null) ? $schema['theme'] : []);

        $sections = [];
        foreach ((array) ($schema['sections'] ?? []) as $section) {
            if (!is_array($section) || !BlockCatalog::has((string) ($section['type'] ?? ''))) {
                $this->fixes[] = 'Dropped section of unknown type: ' . (is_array($section) ? ($section['type'] ?? '?') : '?');
                continue;
            }
            $sections[] = $this->normalizeSectionInternal($section, $theme);
        }

        $brand = $this->brandName($sections, $meta);
        $sections = $this->arrangeFrame($sections, $brand, $ensureFrame);
        $sections = $this->uniqueIds($sections);
        $sections = $this->fixLinks($sections);

        return [
            'version' => 1,
            'meta' => $this->normalizeMeta($meta, $brand),
            'theme' => $theme,
            'sections' => array_values($sections),
        ];
    }

    /** One section (from a section edit). Keeps the given id and type. */
    public function normalizeSection(array $section, array $theme): array
    {
        $this->fixes = [];
        $theme = $this->normalizeTheme($theme);

        return $this->normalizeSectionInternal($section, $theme);
    }

    /* ==================================================================
     | Meta & theme
     * ================================================================== */

    private function normalizeMeta(array $meta, string $brand): array
    {
        $title = $this->cleanString($meta['title'] ?? null) ?? $brand;
        $description = $this->cleanString($meta['description'] ?? null) ?? '';
        $language = strtolower((string) ($meta['language'] ?? 'en'));
        if (!preg_match('/^[a-z]{2}(-[a-z]{2})?$/', $language)) {
            $language = 'en';
        }

        return [
            'title' => mb_substr($title, 0, 70),
            'description' => mb_substr($description, 0, 200),
            'language' => $language,
        ];
    }

    public function normalizeTheme(array $theme): array
    {
        $out = [];
        foreach (['primaryColor', 'secondaryColor', 'backgroundColor', 'textColor'] as $key) {
            $color = Color::normalize($theme[$key] ?? null);
            if ($color === null) {
                $this->fixes[] = "Theme {$key} was not a valid colour";
                $color = self::DEFAULT_THEME[$key];
            }
            $out[$key] = $color;
        }

        $out['fontHeading'] = $this->font($theme['fontHeading'] ?? null, self::DEFAULT_THEME['fontHeading']);
        $out['fontBody'] = $this->font($theme['fontBody'] ?? null, self::DEFAULT_THEME['fontBody']);
        $out['radius'] = in_array($theme['radius'] ?? null, BlockCatalog::RADII, true) ? $theme['radius'] : self::DEFAULT_THEME['radius'];

        // Mode follows the actual background so dark/light switches stay consistent.
        $mode = Color::isDark($out['backgroundColor']) ? 'dark' : 'light';
        if (($theme['mode'] ?? null) !== $mode && isset($theme['mode'])) {
            $this->fixes[] = "Theme mode set to {$mode} to match the background";
        }
        $out['mode'] = $mode;

        // Body text must be readable on the page background (WCAG AA 4.5:1).
        if (Color::contrast($out['textColor'], $out['backgroundColor']) < 4.5) {
            $out['textColor'] = Color::readableOn($out['backgroundColor']);
            $this->fixes[] = 'Adjusted text colour for readable contrast';
        }

        // Buttons use primary with white text; keep that legible (3:1 for large UI text).
        $safePrimary = Color::ensureWhiteTextContrast($out['primaryColor']);
        if ($safePrimary !== $out['primaryColor']) {
            $out['primaryColor'] = $safePrimary;
            $this->fixes[] = 'Darkened primary colour so button text stays readable';
        }

        return $out;
    }

    private function font(mixed $value, string $default): string
    {
        if (is_string($value)) {
            foreach (BlockCatalog::FONTS as $font) {
                if (strcasecmp($font, trim($value)) === 0) {
                    return $font;
                }
            }
            if (stripos($value, 'source sans') !== false) {
                return 'Source Sans 3';
            }
        }
        $this->fixes[] = 'Replaced unsupported font: ' . (is_string($value) ? $value : 'none');

        return $default;
    }

    /* ==================================================================
     | Sections & props
     * ================================================================== */

    private function normalizeSectionInternal(array $section, array $theme): array
    {
        $type = $section['type'];
        $props = is_array($section['props'] ?? null) ? $section['props'] : [];
        if ($type === 'navbar') {
            $props = $this->rescueLogo($props);
        }
        if (isset($props['animation'])) {
            // Accept older/natural names ("slide-left", "zoom-in", "Fade Up").
            $props['animation'] = BlockCatalog::canonicalAnimation($props['animation']) ?? $props['animation'];
        }
        $clean = [];

        foreach (BlockCatalog::props($type) as $key => $spec) {
            $value = $this->coerce($props[$key] ?? null, $spec, "{$type}.{$key}");
            if ($value !== null) {
                $clean[$key] = $value;
            }
        }

        // Details-page buttons are actions; "View details" on the details page itself makes no sense.
        if ($list = BlockCatalog::ITEM_PAGE_LISTS[$type] ?? null) {
            foreach ($clean[$list] ?? [] as $n => $item) {
                if (isset($item['ctaText']) && preg_match('/^(view|see|explore|discover|learn more|read more|details)\b/i', $item['ctaText'])) {
                    unset($clean[$list][$n]['ctaText']);
                }
            }
        }

        // AI-designed motion (data only, clamped).
        if ($effect = MotionSpec::effect($props['bgEffect'] ?? null)) {
            $clean['bgEffect'] = $effect;
        }
        if ($phrases = MotionSpec::phrases($props['typingPhrases'] ?? null)) {
            $clean['typingPhrases'] = $phrases;
        }
        if ($custom = MotionSpec::entrance($props['customAnimation'] ?? null)) {
            $clean['customAnimation'] = $custom;
        } elseif (($clean['animation'] ?? null) === 'custom') {
            $clean['animation'] = 'fade-up'; // "custom" without a recipe
        }

        $clean = $this->sectionRules($type, $clean);
        $clean = $this->sectionColours($clean, $theme, $type);

        return [
            'id' => is_string($section['id'] ?? null) && $section['id'] !== '' ? $section['id'] : null,
            'type' => $type,
            'props' => $clean,
        ];
    }

    /** Coerce one value to its spec; null means "omit" (the block uses its own default). */
    private function coerce(mixed $value, array $spec, string $path): mixed
    {
        switch ($spec['kind']) {
            case 'string':
                // Long descriptions keep their paragraph breaks (blank lines).
                return $this->cleanString($value, keepParagraphs: str_ends_with($path, '.details'));

            case 'image':
                $str = $this->cleanString($value);
                if ($str === null) {
                    return null;
                }
                // User uploads (logos) can't be stock photos: drop anything that isn't a real URL.
                if (!($spec['generated'] ?? true) && !preg_match('#^(https?:)?//|^/#i', $str)) {
                    $this->fixes[] = "Ignored invented {$path}";

                    return null;
                }
                if (str_starts_with($str, 'PLACEHOLDER:') || preg_match('#^(https?:)?//#i', $str) || str_starts_with($str, '/')) {
                    return $str;
                }

                // Plain text like "smiling chef" → treat it as a search query.
                return 'PLACEHOLDER:' . $str;

            case 'color':
                return Color::normalize($value);

            case 'int':
                if (!is_numeric($value)) {
                    return null;
                }
                $int = (int) round((float) $value);

                return max($spec['min'], min($spec['max'], $int));

            case 'bool':
                return is_bool($value) ? $value : (is_numeric($value) ? (bool) $value : null);

            case 'enum':
                if (is_string($value) && in_array(strtolower($value), $spec['values'], true)) {
                    return strtolower($value);
                }
                if ($value !== null) {
                    $this->fixes[] = "Ignored invalid {$path}";
                }

                return null;

            case 'strings':
                $list = [];
                foreach (is_array($value) ? $value : [] as $item) {
                    $str = is_scalar($item) ? $this->cleanString((string) $item) : null;
                    if ($str !== null) {
                        $list[] = $str;
                    }
                }

                return array_slice($list, 0, $spec['max']);

            case 'list':
                $list = [];
                foreach (is_array($value) ? $value : [] as $item) {
                    if (!is_array($item)) {
                        continue;
                    }
                    $clean = [];
                    foreach ($spec['fields'] as $field => $fieldSpec) {
                        $fieldValue = $this->coerce($item[$field] ?? null, $fieldSpec, "{$path}.{$field}");
                        if ($fieldValue !== null) {
                            $clean[$field] = $fieldValue;
                        }
                    }
                    if ($this->hasContent($clean)) {
                        $list[] = $clean;
                    }
                }
                if (count($list) > $spec['max']) {
                    $this->fixes[] = "Trimmed {$path} to {$spec['max']} items";
                }

                return array_slice($list, 0, $spec['max']);
        }

        return null;
    }

    private function hasContent(array $item): bool
    {
        foreach ($item as $value) {
            if ((is_string($value) && $value !== '') || (is_array($value) && $value) || is_bool($value) || is_int($value)) {
                return true;
            }
        }

        return false;
    }

    private function cleanString(mixed $value, bool $keepParagraphs = false): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }
        $raw = (string) $value;
        // Props are plain text; blocks escape them, so HTML would show up as literal tags.
        if (str_contains($raw, '<') && preg_match('/<\/?[a-z][^>]*>/i', $raw)) {
            $raw = strip_tags($raw);
            $this->fixes[] = 'Removed HTML from a text field';
        }
        if ($keepParagraphs) {
            $paragraphs = preg_split('/\R\s*\R|\\n\\n/u', $raw); // real or escaped blank lines
            $str = implode("\n\n", array_filter(array_map(fn ($p) => trim(preg_replace('/\s+/u', ' ', $p) ?? ''), $paragraphs)));
        } else {
            $str = trim(preg_replace('/\s+/u', ' ', $raw) ?? '');
        }

        return $str === '' ? null : $str;
    }

    /**
     * Models sometimes put "<img src=… alt=…>" into the brand when asked for a logo.
     * Move a real image URL into `logo` and keep the alt text as the brand name.
     */
    private function rescueLogo(array $props): array
    {
        $brand = $props['brand'] ?? null;
        if (!is_string($brand) || !preg_match('/<img\b[^>]*>/i', $brand, $tag)) {
            return $props;
        }
        if (preg_match('/\bsrc\s*=\s*["\']([^"\']+)["\']/i', $tag[0], $src) && preg_match('#^(https?:)?//|^/#i', $src[1]) && empty($props['logo'])) {
            $props['logo'] = $src[1];
        }
        $alt = preg_match('/\balt\s*=\s*["\']([^"\']*)["\']/i', $tag[0], $m) ? $m[1] : '';
        $alt = trim(preg_replace('/\s*\blogo\b\s*$/i', '', $alt));
        $text = trim(strip_tags(str_replace($tag[0], '', $brand)));
        $props['brand'] = $text !== '' ? $text : $alt;
        $this->fixes[] = 'Moved a logo out of the brand text';

        return $props;
    }

    /** Layout sanity rules that make generated sections look right. */
    private function sectionRules(string $type, array $p): array
    {
        // Never more grid columns than items (2 features in a 3-column grid look broken).
        $listKey = match ($type) {
            'features', 'services', 'testimonials', 'stats' => 'items',
            'gallery' => 'images',
            'team' => 'members',
            'pricing' => 'plans',
            default => null,
        };
        if ($listKey && isset($p['columns']) && count($p[$listKey] ?? []) > 0) {
            $min = $type === 'gallery' || $type === 'stats' ? 2 : 1;
            $p['columns'] = max($min, min($p['columns'], count($p[$listKey])));
        }

        if ($type === 'pricing' && !empty($p['plans'])) {
            $highlighted = array_keys(array_filter($p['plans'], fn ($plan) => ($plan['highlighted'] ?? false) === true));
            foreach ($p['plans'] as $i => $plan) {
                $p['plans'][$i]['highlighted'] = false;
            }
            // Exactly one recommended plan: the AI's choice, or the middle of three.
            $pick = $highlighted[0] ?? (count($p['plans']) === 3 ? 1 : null);
            if ($pick !== null) {
                $p['plans'][$pick]['highlighted'] = true;
            }
        }

        if ($type === 'hero') {
            $background = in_array($p['layout'] ?? 'center', ['center', 'left', 'right'], true);
            // Text over a photo needs enough overlay to stay readable.
            if ($background && !empty($p['image']) && ($p['overlayOpacity'] ?? 50) < 35) {
                $p['overlayOpacity'] = 45;
                $this->fixes[] = 'Raised hero overlay for readable text';
            }
        }

        if ($type === 'navbar' && isset($p['links'])) {
            $p['links'] = array_values(array_filter($p['links'], fn ($l) => isset($l['label'])));
        }

        return $p;
    }

    /** Section colour overrides must stay readable. */
    private function sectionColours(array $p, array $theme, string $type): array
    {
        $bg = $p['sectionBg'] ?? null;
        $text = $p['sectionText'] ?? null;

        if ($bg) {
            $effectiveText = $text ?? $theme['textColor'];
            if (Color::contrast($effectiveText, $bg) < 4.5) {
                $p['sectionText'] = Color::readableOn($bg);
                $this->fixes[] = "Set readable text colour on {$type} background";
            }
        } elseif ($text && Color::contrast($text, $theme['backgroundColor']) < 4.5) {
            unset($p['sectionText']);
            $this->fixes[] = "Removed unreadable {$type} text colour";
        }

        return $p;
    }

    /* ==================================================================
     | Structure
     * ================================================================== */

    private function brandName(array $sections, array $meta): string
    {
        foreach ($sections as $section) {
            if ($section['type'] === 'navbar' && !empty($section['props']['brand'])) {
                return $section['props']['brand'];
            }
        }
        $title = $this->cleanString($meta['title'] ?? null);

        return $title ? trim(explode('|', explode(' - ', $title)[0])[0]) : 'My Website';
    }

    private function arrangeFrame(array $sections, string $brand, bool $ensureFrame): array
    {
        $navbars = array_values(array_filter($sections, fn ($s) => $s['type'] === 'navbar'));
        $footers = array_values(array_filter($sections, fn ($s) => $s['type'] === 'footer'));
        $body = array_values(array_filter($sections, fn ($s) => !in_array($s['type'], ['navbar', 'footer'], true)));

        if (count($navbars) > 1 || count($footers) > 1) {
            $this->fixes[] = 'Removed duplicate navbar/footer';
        }

        $navbar = $navbars[0] ?? null;
        $footer = $footers[0] ?? null;

        if ($ensureFrame && !$navbar) {
            $navbar = ['id' => null, 'type' => 'navbar', 'props' => ['brand' => $brand, 'links' => []]];
            $this->fixes[] = 'Added missing navbar';
        }
        if ($ensureFrame && !$footer) {
            $footer = ['id' => null, 'type' => 'footer', 'props' => ['text' => '© ' . date('Y') . " {$brand}. All rights reserved."]];
            $this->fixes[] = 'Added missing footer';
        }

        // A navbar without links gets links to the main sections.
        if ($navbar && empty($navbar['props']['links'])) {
            $anchors = BlockCatalog::anchors($body);
            $links = [];
            foreach ($body as $i => $section) {
                if (in_array($section['type'], ['about', 'services', 'features', 'gallery', 'pricing', 'contact'], true) && $anchors[$i]) {
                    $links[] = ['label' => ucfirst($section['type']), 'href' => '#' . $anchors[$i]];
                }
            }
            $navbar['props']['links'] = array_slice($links, 0, 5);
        }

        return array_values(array_filter([$navbar, ...$body, $footer]));
    }

    private function uniqueIds(array $sections): array
    {
        $seen = [];
        $counter = [];
        foreach ($sections as $i => $section) {
            $id = $section['id'];
            if (!$id || isset($seen[$id]) || !preg_match('/^[A-Za-z0-9_-]+$/', $id)) {
                $type = $section['type'];
                do {
                    $counter[$type] = ($counter[$type] ?? 0) + 1;
                    $id = "blk_{$type}_" . $counter[$type];
                } while (isset($seen[$id]));
            }
            $seen[$id] = true;
            $sections[$i]['id'] = $id;
        }

        return $sections;
    }

    /** Point "#…" links at sections that exist (anchors are added by the export renderer). */
    private function fixLinks(array $sections): array
    {
        $anchors = BlockCatalog::anchors($sections);
        $byType = [];
        foreach ($sections as $i => $section) {
            if ($anchors[$i] && !isset($byType[$section['type']])) {
                $byType[$section['type']] = $anchors[$i];
            }
        }
        $all = array_flip(array_filter($anchors));

        $resolve = function (?string $href) use ($byType, $all): string {
            if ($href === null || $href === '') {
                return '#';
            }
            if (!str_starts_with($href, '#')) {
                return $href; // external URL, mailto:, tel:
            }
            $key = strtolower(trim(substr($href, 1)));
            if ($key === '' || in_array($key, ['top', 'home', 'hero', 'start-here'], true)) {
                return isset($all['home']) ? '#home' : '#';
            }
            if (isset($all[$key])) {
                return "#{$key}";
            }
            if (isset($byType[$key])) {
                return '#' . $byType[$key];
            }
            foreach (self::LINK_SYNONYMS as $type => $words) {
                if (in_array($key, $words, true) && isset($byType[$type])) {
                    return '#' . $byType[$type];
                }
            }
            // Calls to action fall back to contact when it exists.
            return isset($byType['contact']) ? '#' . $byType['contact'] : '#';
        };

        foreach ($sections as $i => $section) {
            $p = &$sections[$i]['props'];
            foreach (['ctaHref'] as $key) {
                if (array_key_exists($key, $p) || in_array($section['type'], ['hero', 'cta'], true)) {
                    if (isset($p['ctaText']) || isset($p[$key])) {
                        $p[$key] = $resolve($p[$key] ?? null);
                    }
                }
            }
            foreach (['links'] as $listKey) {
                foreach ($p[$listKey] ?? [] as $j => $link) {
                    $p[$listKey][$j]['href'] = $resolve($link['href'] ?? null);
                }
            }
            if ($section['type'] === 'custom') {
                foreach ($p['elements'] ?? [] as $j => $el) {
                    if (($el['type'] ?? '') === 'button') {
                        $p['elements'][$j]['href'] = $resolve($el['href'] ?? null);
                    }
                }
            }
            unset($p);
        }

        return $sections;
    }
}
