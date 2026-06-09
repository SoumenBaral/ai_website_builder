<?php

namespace App\Services;

use ZipArchive;

class ExportService
{
    public function toHtml(array $schema, ?string $beacon = null): string
    {
        $body = collect($schema['sections'])
            ->map(function ($block) use ($schema) {
                $viewName = "export.blocks.{$block['type']}";
                if (!view()->exists($viewName)) {
                    return "<!-- Unknown block type: {$block['type']} -->";
                }
                return view($viewName, [
                    'props' => $block['props'],
                    'theme' => $schema['theme'],
                ])->render();
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
