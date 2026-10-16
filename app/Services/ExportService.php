<?php

namespace App\Services;

use App\Models\Project;
use App\Services\Ai\BlockCatalog;
use App\Services\Logo\FaviconService;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class ExportService
{
    /**
     * @param array|null  $favicon     the project's favicon set (null = initials icon in the site's colours)
     * @param string|null $faviconBase prefix for favicon files; "" when they sit next to index.html (ZIP)
     */
    public function toHtml(array $schema, ?string $beacon = null, ?array $favicon = null, ?string $faviconBase = null): string
    {
        // Section ids make in-page links ("#about", "#contact") work on published sites.
        $anchors = BlockCatalog::anchors($schema['sections']);

        $body = collect(array_values($schema['sections']))
            ->map(function ($block, $index) use ($schema, $anchors) {
                $viewName = "export.blocks.{$block['type']}";
                if (!view()->exists($viewName)) {
                    return "<!-- Unknown block type: {$block['type']} -->";
                }
                $html = view($viewName, [
                    'props' => $block['props'],
                    'theme' => $schema['theme'],
                ])->render();

                $hasBg = !empty($block['props']['sectionBg']);
                $hasText = !empty($block['props']['sectionText']);
                if ($hasBg || $hasText) {
                    $vars = '';
                    if ($hasBg) {
                        $bg = e($block['props']['sectionBg']);
                        $vars .= '--bg: ' . $bg;
                        if ($block['type'] === 'cta') $vars .= '; --primary: ' . $bg;
                        if ($block['type'] === 'footer') $vars .= '; --secondary: ' . $bg;
                    }
                    if ($hasText) {
                        $txt = e($block['props']['sectionText']);
                        if ($vars) $vars .= '; ';
                        $vars .= '--text: ' . $txt . '; color: ' . $txt;
                    }
                    $html = '<div style="' . $vars . ';">' . $html . '</div>';
                }

                // Entrance animation + hover effect (see resources/js/site/animRuntime.js)
                if ($attrs = self::animationAttributes($block)) {
                    $html = '<div' . $attrs . '>' . $html . '</div>';
                }

                if (!empty($anchors[$index])) {
                    $html = '<div id="' . e($anchors[$index]) . '" style="scroll-margin-top: 72px;">' . $html . '</div>';
                }

                return $html;
            })
            ->implode("\n");

        return view('export.shell', [
            'meta' => $schema['meta'],
            'theme' => $schema['theme'],
            'body' => $body,
            'beacon' => $beacon,
            'head' => app(FaviconService::class)->headTags($favicon, $schema, $faviconBase),
            'animations' => self::usesAnimations($schema) ? file_get_contents(resource_path('css/site-animations.css')) : null,
            'animationRuntime' => self::usesAnimations($schema) ? file_get_contents(resource_path('js/site/animRuntime.js')) : null,
        ])->render();
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
        $html = $this->toHtml($schema, null, $favicon, '');

        $dir = storage_path('app/temp');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $zipPath = $dir . '/' . uniqid('export_') . '.zip';

        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE);
        $zip->addFromString('index.html', $html);

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
}
