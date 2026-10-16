<?php

namespace App\Services;

use App\Services\Ai\BlockCatalog;
use ZipArchive;

class ExportService
{
    public function toHtml(array $schema, ?string $beacon = null): string
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
        ])->render();
    }

    public function toZip(array $schema): string
    {
        $html = $this->toHtml($schema);

        $dir = storage_path('app/temp');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $zipPath = $dir . '/' . uniqid('export_') . '.zip';

        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE);
        $zip->addFromString('index.html', $html);
        $zip->close();

        return $zipPath;
    }
}
