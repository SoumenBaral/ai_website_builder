<?php

use App\Services\DemoSite;
use App\Services\ExportService;

it('serves published pages with the prebuilt stylesheet, not the Tailwind CDN', function () {
    expect(is_file(public_path('site/site.css')))->toBeTrue('Run: npm run build:site');

    $html = $this->get('/demo')->assertOk()->getContent();
    expect($html)->not->toContain('cdn.tailwindcss.com')
        ->toMatch('#<link rel="stylesheet" href="/site/site\.css\?v=[0-9a-f]{10}">#')
        ->toContain('loading="lazy" decoding="async"')   // images below the hero load lazily
        ->not->toMatch('#<img loading="lazy"[^>]*maison-atelier-logo#'); // …but never the logo
});

it('inlines the stylesheet in ZIP downloads so they work offline', function () {
    $zip = new ZipArchive();
    $zip->open($path = app(ExportService::class)->toZip(DemoSite::schema()));
    $index = $zip->getFromName('index.html');
    expect($index)->not->toContain('cdn.tailwindcss.com')->not->toContain('/site/site.css')
        ->toContain(substr(file_get_contents(public_path('site/site.css')), 0, 60));
    $zip->close();
    @unlink($path);

    // …and the next live render links the file again
    expect(app(ExportService::class)->toHtml(DemoSite::schema()))->toContain('/site/site.css?v=');
});

it('sizes header logos', function () {
    $html = $this->get('/demo')->getContent();
    expect($html)->toContain('--logo-h: 3.25rem'); // demo logo: large
});
