<?php

namespace App\Services\Ai;

/**
 * AI-designed motion that isn't a fixed preset:
 *  - bgEffect:        an ambient background layer (twinkling stars, snow, bubbles, aurora…)
 *  - customAnimation: a custom entrance move (used when animation = "custom")
 *
 * Both are DATA, never code: resources/js/site/animRuntime.js renders them from these
 * clamped numbers and enums, so AI output can never inject CSS or JavaScript.
 */
class MotionSpec
{
    public const KINDS = ['particles', 'gradient', 'blobs'];

    public const SHAPES = ['star', 'sparkle', 'dot', 'ring', 'heart', 'snowflake', 'bubble', 'petal', 'leaf', 'square', 'triangle', 'line', 'confetti', 'emoji'];

    public const MOTIONS = ['twinkle', 'pulse', 'drift', 'fall', 'sway-fall', 'float-up', 'rise-sway', 'rain', 'shoot', 'spin'];

    public const SPEEDS = ['slow', 'normal', 'fast'];

    public const EASINGS = ['smooth', 'spring', 'bounce', 'elastic', 'sharp', 'linear'];

    /** Clean an AI/user background effect. null = no effect. */
    public static function effect(mixed $spec): ?array
    {
        if (!is_array($spec) || !in_array($spec['kind'] ?? null, self::KINDS, true)) {
            return null;
        }

        $colors = [];
        foreach (is_array($spec['colors'] ?? null) ? $spec['colors'] : [] as $c) {
            if (is_string($c) && ($hex = Color::normalize($c))) {
                $colors[] = $hex;
            }
        }
        $colors = array_slice(array_values(array_unique($colors)), 0, 4) ?: ['#ffffff'];

        $shape = in_array($spec['shape'] ?? null, self::SHAPES, true) ? $spec['shape'] : 'dot';
        $emoji = null;
        if ($shape === 'emoji') {
            $emoji = self::emoji($spec['emoji'] ?? null);
            if ($emoji === null) {
                $shape = 'star';
            }
        }

        $sizeMin = self::num($spec['sizeMin'] ?? null, 2, 80, 4);
        $sizeMax = self::num($spec['sizeMax'] ?? null, 2, 120, max($sizeMin, 10));

        return [
            'kind' => $spec['kind'],
            'name' => self::label($spec['name'] ?? null) ?? 'Background effect',
            'shape' => $shape,
            'emoji' => $emoji,
            'colors' => $colors,
            'count' => (int) self::num($spec['count'] ?? null, 3, 120, 40),
            'sizeMin' => $sizeMin,
            'sizeMax' => max($sizeMin, $sizeMax),
            'motion' => in_array($spec['motion'] ?? null, self::MOTIONS, true) ? $spec['motion'] : 'twinkle',
            'speed' => in_array($spec['speed'] ?? null, self::SPEEDS, true) ? $spec['speed'] : 'normal',
            'opacity' => (int) self::num($spec['opacity'] ?? null, 5, 100, 80),
            'glow' => (bool) ($spec['glow'] ?? false),
        ];
    }

    /** Clean a custom entrance animation. null = invalid. */
    public static function entrance(mixed $spec): ?array
    {
        if (!is_array($spec)) {
            return null;
        }

        $clean = [
            'name' => self::label($spec['name'] ?? null) ?? 'Custom',
            'opacity' => round(self::num($spec['opacity'] ?? null, 0, 1, 0), 2),
            'x' => (int) self::num($spec['x'] ?? null, -400, 400, 0),
            'y' => (int) self::num($spec['y'] ?? null, -400, 400, 0),
            'scale' => round(self::num($spec['scale'] ?? null, 0.1, 3, 1), 2),
            'rotate' => (int) self::num($spec['rotate'] ?? null, -720, 720, 0),
            'rotateX' => (int) self::num($spec['rotateX'] ?? null, -180, 180, 0),
            'rotateY' => (int) self::num($spec['rotateY'] ?? null, -180, 180, 0),
            'skewX' => (int) self::num($spec['skewX'] ?? null, -45, 45, 0),
            'blur' => (int) self::num($spec['blur'] ?? null, 0, 30, 0),
            'easing' => in_array($spec['easing'] ?? null, self::EASINGS, true) ? $spec['easing'] : 'smooth',
        ];

        // A "custom" move that doesn't move anything is just a fade.
        $moves = $clean['x'] || $clean['y'] || $clean['scale'] != 1 || $clean['rotate'] || $clean['rotateX'] || $clean['rotateY'] || $clean['skewX'] || $clean['blur'];

        return $moves || $clean['opacity'] < 1 ? $clean : null;
    }

    /** Alternative headings for the typing-loop effect: plain text, max 5 × 80 chars. */
    public static function phrases(mixed $list): ?array
    {
        $clean = [];
        foreach (is_array($list) ? $list : [] as $item) {
            if (!is_string($item)) {
                continue;
            }
            $text = trim(preg_replace('/\s+/u', ' ', strip_tags($item)));
            if ($text !== '' && !in_array($text, $clean, true)) {
                $clean[] = mb_substr($text, 0, 80);
            }
        }

        return $clean ? array_slice($clean, 0, 5) : null;
    }

    private static function num(mixed $v, float $min, float $max, float $default): float
    {
        return is_numeric($v) ? max($min, min($max, (float) $v)) : $default;
    }

    private static function label(mixed $v): ?string
    {
        if (!is_string($v)) {
            return null;
        }
        $v = trim(preg_replace('/[<>{}\\\\]/u', '', strip_tags($v)));

        return $v === '' ? null : mb_substr($v, 0, 40);
    }

    /** One emoji / pictograph (max 2 graphemes), nothing else. */
    private static function emoji(mixed $v): ?string
    {
        if (!is_string($v)) {
            return null;
        }
        $v = trim($v);
        if ($v === '' || preg_match('/[\p{L}\p{N}<>&"\'\\\\{}]/u', $v)) {
            return null;
        }
        $v = function_exists('grapheme_substr') ? (grapheme_substr($v, 0, 2) ?: $v) : mb_substr($v, 0, 4);

        return strlen($v) <= 32 ? $v : null;
    }
}
