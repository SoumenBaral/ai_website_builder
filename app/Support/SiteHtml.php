<?php

namespace App\Support;

/**
 * Escaping helpers for the published-site templates. Everything a visitor sees
 * came from a user (or the AI), so it is text, never markup.
 */
final class SiteHtml
{
    /** A link target that can never run script: javascript:/data:/vbscript: become "#". */
    public static function href(mixed $href): string
    {
        $href = trim((string) $href);
        if ($href === '') {
            return '#';
        }
        // Strip control characters and whitespace the browser would ignore in a scheme ("java\tscript:").
        $scheme = preg_replace('/[\x00-\x20]/', '', $href);
        if (preg_match('/^(javascript|data|vbscript|file):/i', $scheme)) {
            return '#';
        }

        return $href;
    }

    /** Widths offered to the browser for stock photos (Unsplash / Pexels resize on the fly). */
    public const WIDTHS = [480, 768, 1024, 1400, 1920];

    /**
     * A srcset for a stock-photo URL, or null for any other image. Phones then download a
     * 480px file instead of the 1600px one.
     */
    public static function srcset(mixed $url, array $widths = self::WIDTHS): ?string
    {
        if (!is_string($url) || !preg_match('#^https://(images\.unsplash\.com|images\.pexels\.com)/#', $url) || !preg_match('/([?&])w=\d+/', $url)) {
            return null;
        }
        $parts = [];
        foreach ($widths as $w) {
            $parts[] = preg_replace('/([?&])w=\d+/', '${1}w=' . $w, $url) . " {$w}w";
        }

        return implode(', ', $parts);
    }

    /**
     * src + srcset + sizes attributes for an <img> (escaped). $sizes describes the slot,
     * e.g. "(min-width: 1024px) 33vw, 100vw".
     */
    public static function img(mixed $url, string $sizes = '100vw', array $widths = self::WIDTHS): string
    {
        $attrs = 'src="' . e((string) $url) . '"';
        if ($srcset = self::srcset($url, $widths)) {
            $attrs .= ' srcset="' . e($srcset) . '" sizes="' . e($sizes) . '"';
        }

        return $attrs;
    }

    /** Plain text with blank lines → escaped <p> elements. */
    /**
     * Inline CSS for imageRadius / imageShadow / imageBorder — the same look the editor
     * gets from useImageStyle.js (Tailwind rounded-* / shadow-* / border classes).
     */
    public static function imageStyle(array $props): string
    {
        $radius = ['none' => '0', 'sm' => '0.125rem', 'md' => '0.375rem', 'lg' => '0.5rem', 'xl' => '0.75rem', 'full' => '9999px'][$props['imageRadius'] ?? ''] ?? null;
        $shadow = [
            'sm' => '0 1px 2px 0 rgb(0 0 0 / 0.05)',
            'md' => '0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1)',
            'lg' => '0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1)',
            'xl' => '0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1)',
        ][$props['imageShadow'] ?? ''] ?? null;
        $border = ['thin' => '1px solid #e5e7eb', 'thick' => '2px solid #d1d5db'][$props['imageBorder'] ?? ''] ?? null;

        return ($radius !== null ? "border-radius: {$radius};" : '')
            . ($shadow !== null ? " box-shadow: {$shadow};" : '')
            . ($border !== null ? " border: {$border};" : '');
    }

    public static function paragraphs(mixed $text, string $class = ''): string
    {
        if (!is_string($text) || trim($text) === '') {
            return '';
        }
        $attr = $class !== '' ? ' class="' . e($class) . '"' : '';

        return implode("\n", array_map(
            fn ($p) => "<p{$attr}>" . nl2br(e($p)) . '</p>',
            array_values(array_filter(array_map('trim', preg_split('/\R{2,}/u', trim($text))))),
        ));
    }
}
