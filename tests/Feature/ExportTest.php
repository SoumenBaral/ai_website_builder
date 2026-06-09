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
