<?php

namespace App\Services\Ai;

/**
 * Small colour helpers (WCAG 2.x relative luminance & contrast).
 */
final class Color
{
    /** Normalise "#abc" / "abc" / "#AABBCC" to "#aabbcc"; null when not a hex colour. */
    public static function normalize(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }
        $hex = ltrim(trim($value), '#');
        if (preg_match('/^[0-9a-fA-F]{3}$/', $hex)) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        if (!preg_match('/^[0-9a-fA-F]{6}$/', $hex)) {
            return null;
        }

        return '#' . strtolower($hex);
    }

    /** @return array{0:int,1:int,2:int} */
    public static function rgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
    }

    public static function luminance(string $hex): float
    {
        [$r, $g, $b] = array_map(function (int $c) {
            $c /= 255;

            return $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        }, self::rgb($hex));

        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }

    public static function contrast(string $a, string $b): float
    {
        $la = self::luminance($a);
        $lb = self::luminance($b);

        return (max($la, $lb) + 0.05) / (min($la, $lb) + 0.05);
    }

    public static function isDark(string $hex): bool
    {
        return self::luminance($hex) < 0.18;
    }

    /** Near-black or near-white, whichever reads best on $background. */
    public static function readableOn(string $background): string
    {
        return self::contrast('#0f172a', $background) >= self::contrast('#f8fafc', $background) ? '#0f172a' : '#f8fafc';
    }

    /** Mix $hex towards $target by $amount (0–1). */
    public static function mix(string $hex, string $target, float $amount): string
    {
        $a = self::rgb($hex);
        $b = self::rgb($target);
        $out = array_map(fn ($x, $y) => (int) round($x + ($y - $x) * $amount), $a, $b);

        return sprintf('#%02x%02x%02x', ...$out);
    }

    /** Darken until white text on it reaches $ratio (used for button colours). */
    public static function ensureWhiteTextContrast(string $hex, float $ratio = 3.0): string
    {
        $color = $hex;
        for ($i = 0; $i < 10 && self::contrast('#ffffff', $color) < $ratio; $i++) {
            $color = self::mix($color, '#000000', 0.12);
        }

        return $color;
    }

    /**
     * Derived variables every block relies on, for a given background:
     * surface = card/panel fill that stands off it, line = visible hairline borders.
     * Mirrors surfaceVars() in resources/js/utils/color.js.
     *
     * @return array{surface: string, line: string}
     */
    public static function surfaceVars(string $background): array
    {
        $dark = self::isDark($background);

        return [
            'surface' => self::mix($background, $dark ? '#ffffff' : '#000000', $dark ? 0.10 : 0.04),
            'line' => self::mix($background, self::readableOn($background), 0.16),
        ];
    }
}
