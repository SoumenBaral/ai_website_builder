<?php

namespace App\Services\Logo;

use App\Services\Ai\Color;

/**
 * Builds favicon artwork from a logo spec. A favicon is the logo's *mark*
 * (icon or monogram) redrawn for tiny sizes: solid shape, bolder glyph,
 * thicker strokes — the full wordmark would be unreadable at 16px.
 *
 * Produces a vector "scene" (see Rasterizer) that is exported both as SVG
 * and as PNG, so every format is drawn from the same geometry.
 */
class FaviconDesigner
{
    public const CANVAS = 48;

    public function __construct(private FontStore $fonts) {}

    /**
     * @param string $variant "tab" = shape with transparent corners (browser tabs);
     *                        "app" = full-bleed square for home screens / PWA (glyph inside the safe zone)
     * @return array{scene: array, background: ?string}
     */
    public function scene(array $spec, string $brand, string $variant = 'tab'): array
    {
        $mark = Color::normalize($spec['markColor'] ?? null) ?? '#2563eb';
        $glyphColor = Color::readableOn($mark) === '#0f172a' ? '#0f172a' : '#ffffff';
        $shape = in_array($spec['shape'] ?? '', ['circle', 'square', 'hexagon', 'shield', 'diamond', 'rounded'], true) ? $spec['shape'] : 'rounded';
        $app = $variant === 'app';

        $scene = [];
        if (!$app) {
            $scene[] = ['fill', 'd' => $this->shapePath($shape), 'color' => $mark];
        }

        // Inner area available to the glyph (compact shapes leave less room).
        $compact = !$app && in_array($shape, ['diamond', 'hexagon', 'shield'], true);
        $box = $app ? 26.0 : ($compact ? 25.0 : 31.0);

        $glyph = $this->monogramGlyph($spec, $brand, $box, $glyphColor, $shape, $app);
        if (!$glyph) {
            $icon = LogoIcons::ICONS[$spec['icon'] ?? ''] ?? LogoIcons::ICONS['sparkles'];
            $s = $box / 24;
            $offset = (self::CANVAS - $box) / 2 + (!$app && $shape === 'shield' ? 1 : 0);
            // ~2.3 icon units ≈ 1px at 16px — thick enough to stay crisp in a tab.
            $glyph = ['stroke', 'd' => $icon, 'color' => $glyphColor, 'width' => 2.3, 'transform' => [$s, $offset, $offset]];
        }
        $scene[] = $glyph;

        return ['scene' => $scene, 'background' => $app ? $mark : null];
    }

    public function svg(array $built): string
    {
        $c = self::CANVAS;
        $body = $built['background'] ? '<rect width="' . $c . '" height="' . $c . '" fill="' . $built['background'] . '"/>' : '';
        foreach ($built['scene'] as $p) {
            [$s, $tx, $ty] = $p['transform'] ?? [1, 0, 0];
            $transform = ($s != 1 || $tx || $ty) ? ' transform="translate(' . round($tx, 3) . ' ' . round($ty, 3) . ') scale(' . round($s, 4) . ')"' : '';
            $body .= $p[0] === 'fill'
                ? '<path d="' . $p['d'] . '" fill="' . $p['color'] . '"' . $transform . '/>'
                : '<path d="' . $p['d'] . '" fill="none" stroke="' . $p['color'] . '" stroke-width="' . $p['width'] . '" stroke-linecap="round" stroke-linejoin="round"' . $transform . '/>';
        }

        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $c . ' ' . $c . '">' . $body . '</svg>';
    }

    public function png(array $built, int $size): string
    {
        return Rasterizer::png($built['scene'], self::CANVAS, $size, $built['background']);
    }

    /* ------------------------------------------------------------------ */

    private function monogramGlyph(array $spec, string $brand, float $box, string $color, string $shape, bool $app): ?array
    {
        $usesIcon = in_array($spec['layout'] ?? '', ['icon-wordmark', 'icon'], true);
        if ($usesIcon) {
            return null;
        }

        // Two letters at most — three are illegible at 16px.
        $letters = mb_strtoupper(mb_substr(preg_replace('/[^\p{L}\p{N}&]/u', '', (string) ($spec['monogram'] ?? '')) ?: $this->initials($brand), 0, 2));
        $font = $this->fonts->font($spec['font'] ?? 'Poppins', max(700, (int) ($spec['weight'] ?? 700)));
        if ($letters === '' || !$font || !$font->supports($letters)) {
            return null; // fall back to the icon
        }

        // Fit the letters inside the box: by cap height, and by width for wide pairs like "MW".
        $scale1 = 1 / $font->unitsPerEm;
        $size = $box * (mb_strlen($letters) === 1 ? 1.0 : 0.82) / ($font->capHeight * $scale1);
        $width = $font->measure($letters) * $scale1 * $size;
        if ($width > $box * 1.05) {
            $size *= ($box * 1.05) / $width;
            $width = $box * 1.05;
        }
        $centerY = self::CANVAS / 2 + (!$app && $shape === 'shield' ? -1 : 0);
        $baseline = $centerY + $font->capHeight * $scale1 * $size / 2;
        $path = $font->textPath($letters, (self::CANVAS - $width) / 2, $baseline, $size);

        return ['fill', 'd' => $path['d'], 'color' => $color];
    }

    private function initials(string $brand): string
    {
        $words = preg_split('/\s+/u', trim($brand), -1, PREG_SPLIT_NO_EMPTY);

        return implode('', array_map(fn ($w) => mb_substr($w, 0, 1), array_slice($words, 0, 2)));
    }

    /** Shapes fill the full canvas — favicons need every pixel. */
    private function shapePath(string $shape): string
    {
        return match ($shape) {
            'circle' => 'M24 0A24 24 0 1 1 24 48A24 24 0 1 1 24 0Z',
            'square' => 'M5 0H43A5 5 0 0 1 48 5V43A5 5 0 0 1 43 48H5A5 5 0 0 1 0 43V5A5 5 0 0 1 5 0Z',
            'hexagon' => 'M24 0 44.78 12V36L24 48 3.22 36V12Z',
            'shield' => 'M24 0 46 7V22C46 35 37 44.5 24 48 11 44.5 2 35 2 22V7Z',
            'diamond' => 'M24 0 48 24 24 48 0 24Z',
            default => 'M11 0H37A11 11 0 0 1 48 11V37A11 11 0 0 1 37 48H11A11 11 0 0 1 0 37V11A11 11 0 0 1 11 0Z',
        };
    }
}
