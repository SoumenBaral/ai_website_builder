<?php

namespace App\Services\Logo;

use App\Services\Ai\Color;

/**
 * Draws a logo concept as a self-contained SVG (48px tall, width fits content).
 * Text is converted to vector paths from the real font, so the logo looks the
 * same everywhere and the brand name is always spelled exactly.
 */
class LogoRenderer
{
    private const H = 48;

    public function __construct(private FontStore $fonts) {}

    /** @param array $spec a normalised concept (see LogoDesigner::normalize) */
    public function render(array $spec, string $brand): string
    {
        $font = $this->fonts->font($spec['font'], (int) $spec['weight']);
        $text = $this->applyCase($brand, $spec['case']);
        $parts = [];
        $x = 0.0;

        $hasMark = in_array($spec['layout'], ['icon-wordmark', 'monogram-wordmark', 'monogram', 'icon'], true);
        $hasText = in_array($spec['layout'], ['icon-wordmark', 'monogram-wordmark', 'wordmark'], true) && $text !== '';

        if ($hasMark) {
            $parts[] = $this->mark($spec, $font);
            $x = self::H + ($spec['shape'] === 'none' ? 8 : 12);
        }

        if ($hasText) {
            $size = $hasMark ? 24.0 : 30.0;
            [$svg, $end] = $this->wordmark($text, $spec, $font, $x, $size);
            $parts[] = $svg;
            $x = $end;
        } elseif ($hasMark) {
            $x = self::H;
        }

        $width = (int) ceil(max($x, 1) + 2);
        $title = htmlspecialchars($brand, ENT_QUOTES | ENT_XML1);

        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $width . ' ' . self::H . '" width="' . $width . '" height="' . self::H . '" role="img" aria-label="' . $title . '">'
            . "<title>{$title}</title>" . implode('', $parts) . '</svg>';
    }

    /* ---------------- Mark (shape + icon/monogram) ---------------- */

    private function mark(array $spec, ?TrueTypeFont $font): string
    {
        $shape = $spec['shape'];
        $style = $spec['markStyle'];
        $mark = $spec['markColor'];
        $inner = match (true) {
            $shape === 'none' => $mark,
            $style === 'solid' => Color::readableOn($mark) === '#0f172a' ? '#0f172a' : '#ffffff',
            default => $mark,
        };

        $svg = '';
        if ($shape !== 'none') {
            $geometry = $this->shapeGeometry($shape);
            $svg .= match ($style) {
                'outline' => str_replace('/>', ' fill="none" stroke="' . $mark . '" stroke-width="2.5" stroke-linejoin="round"/>', $geometry),
                'soft' => str_replace('/>', ' fill="' . $mark . '" fill-opacity="0.15"/>', $geometry),
                default => str_replace('/>', ' fill="' . $mark . '"/>', $geometry),
            };
        }

        $compact = in_array($shape, ['diamond', 'hexagon', 'shield'], true);
        $usesIcon = in_array($spec['layout'], ['icon-wordmark', 'icon'], true);

        if ($usesIcon) {
            $size = $shape === 'none' ? 40.0 : ($compact ? 20.0 : 24.0);
            $offset = (self::H - $size) / 2 + ($shape === 'shield' ? 1 : 0);
            $scale = round($size / 24, 4);
            $path = LogoIcons::ICONS[$spec['icon']] ?? LogoIcons::ICONS['sparkles'];
            $svg .= '<path d="' . $path . '" transform="translate(' . $offset . ' ' . $offset . ') scale(' . $scale . ')" fill="none" stroke="' . $inner . '" stroke-width="' . ($shape === 'none' ? 1.5 : 1.7) . '" stroke-linecap="round" stroke-linejoin="round"/>';
        } else {
            $letters = $this->applyCase($spec['monogram'], 'upper');
            $count = max(1, mb_strlen($letters));
            $size = $shape === 'none' ? 36.0 : [1 => 26.0, 2 => 20.0, 3 => 15.0][min(3, $count)];
            if ($compact) {
                $size *= 0.85;
            }
            $svg .= $this->centeredText($letters, $font, $size, self::H / 2 + ($shape === 'shield' ? -1 : 0), $inner, $spec['weight']);
        }

        return $svg;
    }

    private function shapeGeometry(string $shape): string
    {
        return match ($shape) {
            'circle' => '<circle cx="24" cy="24" r="22.5"/>',
            'square' => '<rect x="1.5" y="1.5" width="45" height="45" rx="5"/>',
            'hexagon' => '<path d="M24 1.75 43.27 12.875v22.25L24 46.25 4.73 35.125v-22.25Z"/>',
            'shield' => '<path d="M24 1.75 43.5 8.5V22c0 12-8.25 20.5-19.5 24.25C12.75 42.5 4.5 34 4.5 22V8.5Z"/>',
            'diamond' => '<path d="M24 1.5 46.5 24 24 46.5 1.5 24Z"/>',
            default => '<rect x="1.5" y="1.5" width="45" height="45" rx="13"/>', // rounded
        };
    }

    /* ---------------- Wordmark ---------------- */

    /** @return array{0: string, 1: float} svg and the x where the logo ends */
    private function wordmark(string $text, array $spec, ?TrueTypeFont $font, float $x, float $size): array
    {
        $tracking = match ($spec['tracking']) {
            'tight' => -0.02,
            'wide' => $spec['case'] === 'upper' ? 0.14 : 0.06,
            default => $spec['case'] === 'upper' ? 0.05 : 0.0,
        } * $size;

        if (!$font || !$font->supports($text)) {
            return $this->fallbackText($text, $spec, $x, $size, $tracking);
        }

        $scale = $size / $font->unitsPerEm;
        $baseline = round(self::H / 2 + $font->capHeight * $scale / 2, 2);
        $svg = '';

        $words = preg_split('/(\s+)/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
        $split = $spec['accent'] === 'split' && count(array_filter($words, fn ($w) => trim($w) !== '')) > 1;
        $cursor = $x;
        $wordIndex = 0;
        foreach ($words as $chunk) {
            if ($chunk === '') {
                continue;
            }
            $isSpace = trim($chunk) === '';
            $result = $font->textPath($chunk, $cursor, $baseline, $size, $tracking);
            if (!$isSpace) {
                $color = $split && $wordIndex > 0 ? $spec['accentColor'] : $spec['textColor'];
                $svg .= '<path d="' . $result['d'] . '" fill="' . $color . '"/>';
                $wordIndex++;
            }
            $cursor += $result['width'] + $tracking;
        }
        $end = $cursor - $tracking;
        $textWidth = $end - $x;

        if ($spec['accent'] === 'dot') {
            $r = round($size * 0.13, 2);
            $svg .= '<circle cx="' . round($end + $r + 3, 2) . '" cy="' . round($baseline - $r, 2) . '" r="' . $r . '" fill="' . $spec['accentColor'] . '"/>';
            $end += $r * 2 + 4;
        } elseif ($spec['accent'] === 'underline') {
            $svg .= '<rect x="' . round($x, 2) . '" y="' . round($baseline + 5, 2) . '" width="' . round(min($textWidth, max(28, $textWidth * 0.3)), 2) . '" height="3" rx="1.5" fill="' . $spec['accentColor'] . '"/>';
        }

        return [$svg, $end];
    }

    private function centeredText(string $text, ?TrueTypeFont $font, float $size, float $centerY, string $color, int|string $weight): string
    {
        if (!$font || !$font->supports($text)) {
            $esc = htmlspecialchars($text, ENT_QUOTES | ENT_XML1);

            return '<text x="24" y="' . round($centerY + $size * 0.35, 2) . '" text-anchor="middle" font-family="Helvetica Neue, Arial, sans-serif" font-weight="' . $weight . '" font-size="' . $size . '" fill="' . $color . '">' . $esc . '</text>';
        }
        $scale = $size / $font->unitsPerEm;
        $tracking = mb_strlen($text) > 1 ? 0.02 * $size : 0;
        $width = $font->measure($text, $tracking / $scale) * $scale;
        $baseline = round($centerY + $font->capHeight * $scale / 2, 2);
        $result = $font->textPath($text, round(24 - $width / 2, 2), $baseline, $size, $tracking);

        return '<path d="' . $result['d'] . '" fill="' . $color . '"/>';
    }

    /** Text the font can't draw (e.g. non-Latin brand names): plain SVG text with a system font stack. */
    private function fallbackText(string $text, array $spec, float $x, float $size, float $tracking): array
    {
        $esc = htmlspecialchars($text, ENT_QUOTES | ENT_XML1);
        $estimate = mb_strlen($text) * $size * 0.6 + max(0, mb_strlen($text) - 1) * $tracking;
        $svg = '<text x="' . round($x, 2) . '" y="' . round(self::H / 2 + $size * 0.35, 2) . '" font-family="' . htmlspecialchars($spec['font'], ENT_QUOTES | ENT_XML1) . ', Noto Sans Bengali, Noto Sans, Helvetica Neue, Arial, sans-serif" font-weight="' . $spec['weight'] . '" font-size="' . $size . '" letter-spacing="' . round($tracking, 2) . '" fill="' . $spec['textColor'] . '">' . $esc . '</text>';

        return [$svg, $x + $estimate];
    }

    private function applyCase(string $text, string $case): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? '');

        return match ($case) {
            'upper' => mb_strtoupper($text),
            'lower' => mb_strtolower($text),
            'title' => mb_convert_case($text, MB_CASE_TITLE),
            default => $text,
        };
    }
}
