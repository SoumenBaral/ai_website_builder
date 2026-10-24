<?php

use App\Models\Page;
use App\Models\Project;
use App\Models\User;
use App\Services\ExportService;

beforeEach(function () {
    $this->schema = [
        'version' => 1,
        'meta' => ['title' => 'Export Test Site', 'description' => 'Testing export', 'language' => 'en'],
        'theme' => [
            'primaryColor' => '#2563eb', 'secondaryColor' => '#0f172a',
            'backgroundColor' => '#ffffff', 'textColor' => '#0f172a',
            'fontHeading' => 'Poppins', 'fontBody' => 'Inter',
            'radius' => 'lg', 'mode' => 'light',
        ],
        'sections' => [
            ['id' => 'blk_hero_01', 'type' => 'hero', 'props' => ['heading' => 'Welcome Hero']],
        ],
    ];
});

it('returns valid HTML containing the meta title', function () {
    $export = app(ExportService::class);

    $html = $export->toHtml($this->schema);

    expect($html)->toBeString()
        ->and($html)->toContain('Export Test Site');
});

it('includes block content in exported HTML', function () {
    $export = app(ExportService::class);

    $html = $export->toHtml($this->schema);

    expect($html)->toContain('Welcome Hero');
});

it('allows authenticated user to download export zip', function () {
    $user = User::factory()->create();

    $project = Project::create([
        'user_id' => $user->id,
        'name' => 'Zip Export',
        'slug' => 'zip-export-abc123',
        'status' => 'draft',
    ]);

    Page::create([
        'project_id' => $project->id,
        'title' => 'Home',
        'path' => '/',
        'schema' => $this->schema,
        'is_home' => true,
    ]);

    $response = $this->actingAs($user)->get("/projects/{$project->id}/export");

    $response->assertStatus(200);
    $response->assertDownload();
});

it('keeps pricing readable on a section painted in the brand colour', function () {
    // The AI often paints a band in the primary colour. Every accent that used to be "white on
    // primary" must flip, and cards must stand off the band instead of melting into it.
    $this->schema['theme']['primaryColor'] = '#9333ea';
    $this->schema['sections'] = [[
        'id' => 'blk_pricing_01', 'type' => 'pricing', 'props' => [
            'heading' => 'Membership Pricing', 'sectionBg' => '#9333ea', 'columns' => 3,
            'plans' => [
                ['name' => 'Basic', 'price' => '$9', 'period' => 'month', 'features' => ['HD videos'], 'ctaText' => 'Start free trial', 'highlighted' => false],
                ['name' => 'Premium', 'price' => '$19', 'period' => 'month', 'features' => ['All Basic'], 'ctaText' => 'Start free trial', 'highlighted' => true],
            ],
        ],
    ]];

    $html = app(ExportService::class)->toHtml($this->schema);

    // Root: text on --primary is defined once, next to the theme.
    expect($html)->toContain('--on-primary: #f8fafc')->toContain('--surface:')->toContain('--line:');

    // Band: accents become the band's text colour, with the band colour on top of them.
    preg_match('/<div style="--bg: #9333ea;([^"]*)">/', $html, $m);
    expect($m)->not->toBeEmpty();
    expect($m[1])->toContain('--primary: #f8fafc')
        ->toContain('--on-primary: #9333ea')
        ->toContain('--surface: #9e47ec')
        ->toContain('--line: #');

    // Nothing in the block hard-codes white or the band colour where it could vanish.
    $block = substr($html, strpos($html, 'Membership Pricing'));
    expect($block)->not->toContain('color: #fff')->not->toContain('color: white')->not->toContain('text-white')
        ->and($block)->toContain('background-color: var(--surface)')
        ->and($block)->toContain('color: var(--on-primary)');
});

it('derives surface and line colours that stand off any background', function () {
    $light = \App\Services\Ai\Color::surfaceVars('#ffffff');
    $dark = \App\Services\Ai\Color::surfaceVars('#0f172a');
    $brand = \App\Services\Ai\Color::surfaceVars('#9333ea');

    foreach ([$light, $dark, $brand] as $vars) {
        expect($vars['surface'])->toMatch('/^#[0-9a-f]{6}$/')->and($vars['line'])->toMatch('/^#[0-9a-f]{6}$/');
    }
    expect($light['surface'])->toBe('#f5f5f5')          // a whisper darker than white
        ->and($dark['surface'])->not->toBe('#0f172a')     // lighter than the dark band
        ->and(\App\Services\Ai\Color::luminance($dark['surface']))->toBeGreaterThan(\App\Services\Ai\Color::luminance('#0f172a'))
        ->and(\App\Services\Ai\Color::luminance($brand['surface']))->toBeGreaterThan(\App\Services\Ai\Color::luminance('#9333ea'));
});
