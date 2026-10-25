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
