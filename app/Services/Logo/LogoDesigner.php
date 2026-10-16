<?php

namespace App\Services\Logo;

use App\Services\Ai\BlockCatalog;
use App\Services\Ai\Color;

/**
 * Turns AI logo concepts into safe, renderable specs and SVGs.
 * The AI only chooses from fixed options (layout, icon, shape, font…);
 * this class validates every value and keeps colours readable.
 */
class LogoDesigner
{
    public const LAYOUTS = ['icon-wordmark', 'monogram-wordmark', 'wordmark', 'monogram', 'icon'];
    public const SHAPES = ['rounded', 'circle', 'square', 'hexagon', 'shield', 'diamond', 'none'];
    public const MARK_STYLES = ['solid', 'outline', 'soft'];
    public const WEIGHTS = ['500', '600', '700', '800'];
    public const CASES = ['upper', 'title', 'asis', 'lower'];
    public const TRACKING = ['tight', 'normal', 'wide'];
    public const ACCENTS = ['none', 'dot', 'underline', 'split'];

    public function __construct(private LogoRenderer $renderer) {}

    /** Strict JSON schema for 4 concepts. */
    public static function schema(): array
    {
        $enum = fn (array $values, string $desc) => ['type' => 'string', 'enum' => array_values($values), 'description' => $desc];
        $hex = ['type' => 'string', 'description' => 'Hex colour like "#1f6feb"'];

        $concept = [
            'type' => 'object',
            'additionalProperties' => false,
            'properties' => [
                'name' => ['type' => 'string', 'description' => '2–3 word style name shown to the user, e.g. "Bold monogram"'],
                'layout' => $enum(self::LAYOUTS, 'icon-wordmark = icon in a shape + name; monogram-wordmark = initials in a shape + name; wordmark = styled name only; monogram = initials badge only; icon = icon badge only'),
                'monogram' => ['type' => 'string', 'description' => '1–3 letters, usually the brand initials'],
                'icon' => $enum(array_keys(LogoIcons::ICONS), 'Icon for icon layouts'),
                'shape' => $enum(self::SHAPES, 'Container of the mark'),
                'markStyle' => $enum(self::MARK_STYLES, 'solid = filled shape; outline = stroked shape; soft = tinted shape'),
                'font' => $enum(BlockCatalog::FONTS, 'Typeface'),
                'weight' => $enum(self::WEIGHTS, 'Font weight'),
                'case' => $enum(self::CASES, 'Letter case of the name'),
                'tracking' => $enum(self::TRACKING, 'Letter spacing'),
                'accent' => $enum(self::ACCENTS, 'dot = coloured full stop; underline = short bar; split = later words in accent colour'),
                'markColor' => ['description' => 'Colour of the mark'] + $hex,
                'textColor' => ['description' => 'Colour of the name; must be readable on the background'] + $hex,
                'accentColor' => ['description' => 'Accent colour for dot/underline/split'] + $hex,
            ],
        ];
        $concept['required'] = array_keys($concept['properties']);

        return [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => ['summary', 'concepts'],
            'properties' => [
                'summary' => ['type' => 'string', 'description' => 'One short sentence to the user about the direction of these logos'],
                'concepts' => ['type' => 'array', 'items' => $concept, 'description' => 'Exactly 4 clearly different concepts'],
            ],
        ];
    }

    public static function prompt(): string
    {
        $icons = implode('; ', array_map(fn ($k, $v) => "{$k} ({$v})", array_keys(LogoIcons::HINTS), LogoIcons::HINTS));
        $fonts = implode(', ', BlockCatalog::FONTS);

        return <<<PROMPT
You are a senior brand identity designer. Design 4 distinct, professional logo concepts for a website navbar.

You choose the design; the system draws it precisely (text is set in the chosen font, icons are clean line icons).

## Make the 4 concepts genuinely different
- Mix layouts: at least one icon-wordmark, one monogram-wordmark and one pure wordmark. The fourth can be any layout.
- Vary shapes, mark styles and fonts. Prefer the site's own fonts for at least two concepts.

## Fit the brand
- Pick icons that express what the business does. Icons: {$icons}.
- Fonts available: {$fonts}. Condensed bold (Oswald) suits sport and energy; serif (Playfair Display, Merriweather) suits luxury, editorial, law; geometric sans (Poppins, Montserrat, DM Sans) suits modern brands; rounded (Nunito) suits friendly, kids, wellness.
- Monogram: the brand's initials (max 3 letters), e.g. "Rohit Sharma" → "RS".
- Use the site's palette. textColor must read clearly on the given navbar background; markColor should usually be the primary brand colour.
- Uppercase + wide tracking feels premium/sporty; title case feels friendly; tight tracking feels bold and modern.
- Keep it simple and timeless — logos must work small.

## Follow the user's direction
If the user asks for something specific (an icon, a style, colours, letters), apply it to all concepts while keeping them different.
PROMPT;
    }

    /** Validate a concept from the AI (or the browser) into a safe spec. */
    public function normalize(array $c, string $brand, array $theme, string $background): array
    {
        $pick = fn (string $key, array $allowed, string $default) => in_array($c[$key] ?? null, $allowed, true) ? $c[$key] : $default;
        $bg = Color::normalize($background) ?? '#ffffff';

        $spec = [
            'name' => mb_substr(trim((string) ($c['name'] ?? 'Logo')), 0, 32) ?: 'Logo',
            'layout' => $pick('layout', self::LAYOUTS, 'monogram-wordmark'),
            'monogram' => $this->monogram((string) ($c['monogram'] ?? ''), $brand),
            'icon' => $pick('icon', array_keys(LogoIcons::ICONS), 'sparkles'),
            'shape' => $pick('shape', self::SHAPES, 'rounded'),
            'markStyle' => $pick('markStyle', self::MARK_STYLES, 'solid'),
            'font' => $pick('font', BlockCatalog::FONTS, $theme['fontHeading'] ?? 'Poppins'),
            'weight' => $pick('weight', self::WEIGHTS, '700'),
            'case' => $pick('case', self::CASES, 'asis'),
            'tracking' => $pick('tracking', self::TRACKING, 'normal'),
            'accent' => $pick('accent', self::ACCENTS, 'none'),
        ];

        $primary = Color::normalize($theme['primaryColor'] ?? null) ?? '#2563eb';
        $mark = Color::normalize($c['markColor'] ?? null) ?? $primary;
        $text = Color::normalize($c['textColor'] ?? null) ?? (Color::normalize($theme['textColor'] ?? null) ?? Color::readableOn($bg));
        $accent = Color::normalize($c['accentColor'] ?? null) ?? $mark;

        // Readability on the navbar background.
        if (Color::contrast($text, $bg) < 4.5) {
            $text = Color::readableOn($bg);
        }
        $markNeeds = $spec['markStyle'] === 'solid' ? 1.6 : 3.0; // outline/soft marks are thin or tinted
        if (Color::contrast($mark, $bg) < $markNeeds) {
            $mark = Color::contrast($primary, $bg) >= $markNeeds ? $primary : Color::readableOn($bg);
        }
        if (Color::contrast($accent, $bg) < 3.0) {
            $accent = $mark;
        }

        return $spec + ['markColor' => $mark, 'textColor' => $text, 'accentColor' => $accent];
    }

    /**
     * Render concepts. Returns [{spec, svg}] — invalid or duplicate concepts are skipped.
     *
     * @param array<int, array> $concepts
     */
    public function build(array $concepts, string $brand, array $theme, string $background): array
    {
        $out = [];
        $seen = [];
        foreach ($concepts as $concept) {
            if (!is_array($concept)) {
                continue;
            }
            $spec = $this->normalize($concept, $brand, $theme, $background);
            $fingerprint = md5(json_encode(array_diff_key($spec, ['name' => 1])));
            if (isset($seen[$fingerprint])) {
                continue;
            }
            $seen[$fingerprint] = true;
            $out[] = ['spec' => $spec, 'svg' => $this->renderer->render($spec, $brand)];
        }

        return array_slice($out, 0, 4);
    }

    /** Good-looking concepts without AI (used if the AI is unavailable). */
    public function fallbackConcepts(array $theme): array
    {
        $heading = $theme['fontHeading'] ?? 'Poppins';
        $primary = $theme['primaryColor'] ?? '#2563eb';
        $secondary = $theme['secondaryColor'] ?? '#0f172a';

        return [
            ['name' => 'Classic monogram', 'layout' => 'monogram-wordmark', 'shape' => 'rounded', 'markStyle' => 'solid', 'font' => $heading, 'weight' => '700', 'case' => 'asis', 'tracking' => 'normal', 'accent' => 'none', 'markColor' => $primary, 'accentColor' => $primary],
            ['name' => 'Clean wordmark', 'layout' => 'wordmark', 'font' => $heading, 'weight' => '800', 'case' => 'asis', 'tracking' => 'tight', 'accent' => 'dot', 'accentColor' => $primary],
            ['name' => 'Spark badge', 'layout' => 'icon-wordmark', 'icon' => 'sparkles', 'shape' => 'circle', 'markStyle' => 'soft', 'font' => 'Montserrat', 'weight' => '700', 'case' => 'upper', 'tracking' => 'wide', 'accent' => 'none', 'markColor' => $primary],
            ['name' => 'Bold split', 'layout' => 'wordmark', 'font' => 'Oswald', 'weight' => '700', 'case' => 'upper', 'tracking' => 'wide', 'accent' => 'split', 'accentColor' => $primary, 'textColor' => $secondary],
        ];
    }

    private function monogram(string $value, string $brand): string
    {
        $letters = preg_replace('/[^\p{L}\p{N}&]/u', '', $value) ?? '';
        if ($letters === '') {
            $words = preg_split('/\s+/u', trim($brand), -1, PREG_SPLIT_NO_EMPTY);
            $letters = implode('', array_map(fn ($w) => mb_substr($w, 0, 1), array_slice($words, 0, 2))) ?: 'A';
        }

        return mb_strtoupper(mb_substr($letters, 0, 3));
    }
}
