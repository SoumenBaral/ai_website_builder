<?php

use App\Models\Page;
use App\Models\Project;
use App\Models\User;
use App\Services\Ai\Color;
use App\Services\Logo\LogoDesigner;
use App\Services\Logo\LogoRenderer;
use App\Services\Logo\TrueTypeFont;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use OpenAI\Laravel\Facades\OpenAI;
use OpenAI\Responses\Chat\CreateResponse;

beforeEach(function () {
    // Fonts come from the fixture folder only — no network in tests.
    config(['ai.font_path' => base_path('tests/Fixtures/fonts')]);
    Http::fake(['*' => Http::response('', 404)]);
    Storage::fake('public');

    $this->theme = ['primaryColor' => '#6366f1', 'secondaryColor' => '#0b1220', 'backgroundColor' => '#0b1220', 'textColor' => '#e5e7eb', 'fontHeading' => 'Oswald', 'fontBody' => 'Inter', 'radius' => 'lg', 'mode' => 'dark'];
    $this->user = User::factory()->create();
    $this->project = Project::create(['user_id' => $this->user->id, 'name' => 'Rohit Sharma', 'slug' => 'rohit-abc', 'status' => 'draft', 'description' => 'Cricketer fan site']);
});

function concept(array $overrides = []): array
{
    return $overrides + [
        'name' => 'Sport crest', 'layout' => 'icon-wordmark', 'monogram' => 'RS', 'icon' => 'trophy', 'shape' => 'shield',
        'markStyle' => 'solid', 'font' => 'Oswald', 'weight' => '700', 'case' => 'upper', 'tracking' => 'wide',
        'accent' => 'none', 'markColor' => '#6366f1', 'textColor' => '#ffffff', 'accentColor' => '#6366f1',
    ];
}

it('reads TrueType outlines and metrics', function () {
    $font = TrueTypeFont::fromFile(base_path('tests/Fixtures/fonts/oswald-700.ttf'));
    $path = $font->textPath('Rohit', 0, 40, 32);

    expect($font->unitsPerEm)->toBe(1000)
        ->and($font->supports('Rohit Sharma É&'))->toBeTrue()
        ->and($font->supports('রোহিত'))->toBeFalse()
        ->and($path['width'])->toBeGreaterThan(50)
        ->and($path['d'])->toStartWith('M')->toContain('Q')->toContain('Z');
});

it('renders brand text as vector paths in the chosen font', function () {
    $svg = app(LogoRenderer::class)->render(app(LogoDesigner::class)->normalize(concept(), 'Rohit Sharma', $this->theme, '#0b1220'), 'Rohit Sharma');

    expect($svg)->toStartWith('<svg xmlns="http://www.w3.org/2000/svg"')
        ->and($svg)->toContain('<title>Rohit Sharma</title>')
        ->and($svg)->not->toContain('<text')      // text became paths
        ->and($svg)->not->toContain('<script')
        ->and(substr_count($svg, '<path'))->toBeGreaterThan(3);
});

it('renders every layout and falls back to SVG text for fonts or scripts it cannot draw', function () {
    $renderer = app(LogoRenderer::class);
    $designer = app(LogoDesigner::class);
    foreach (LogoDesigner::LAYOUTS as $layout) {
        $svg = $renderer->render($designer->normalize(concept(['layout' => $layout]), 'Rohit Sharma', $this->theme, '#0b1220'), 'Rohit Sharma');
        expect($svg)->toContain('viewBox="0 0 ');
    }

    $bangla = $renderer->render($designer->normalize(concept(['layout' => 'wordmark']), 'স্মাইলকেয়ার', $this->theme, '#fff'), 'স্মাইলকেয়ার');
    $unavailableFont = $renderer->render($designer->normalize(concept(['layout' => 'wordmark', 'font' => 'Lato']), 'Lumen', $this->theme, '#fff'), 'Lumen');

    expect($bangla)->toContain('<text')->toContain('স্মাইলকেয়ার')
        ->and($unavailableFont)->toContain('<text');
});

it('validates concepts and keeps colours readable on the navbar', function () {
    $spec = app(LogoDesigner::class)->normalize([
        'layout' => 'evil', 'icon' => '<script>', 'font' => 'Comic Sans', 'monogram' => 'r$s!x y',
        'textColor' => '#0b1220', 'markColor' => '#0c1322', 'markStyle' => 'outline',
    ], 'Rohit Sharma', $this->theme, '#0b1220');

    expect($spec['layout'])->toBe('monogram-wordmark')
        ->and($spec['icon'])->toBe('sparkles')
        ->and($spec['font'])->toBe('Oswald')
        ->and($spec['monogram'])->toBe('RSX')
        ->and(Color::contrast($spec['textColor'], '#0b1220'))->toBeGreaterThanOrEqual(4.5)
        ->and(Color::contrast($spec['markColor'], '#0b1220'))->toBeGreaterThanOrEqual(3.0);
});

it('designs four logo concepts with AI', function () {
    OpenAI::fake([CreateResponse::fake(['choices' => [['index' => 0, 'finish_reason' => 'stop', 'message' => ['role' => 'assistant', 'content' => json_encode([
        'summary' => 'Sporty, confident marks built around a trophy.',
        'concepts' => [concept(), concept(['layout' => 'monogram-wordmark', 'shape' => 'hexagon']), concept(['layout' => 'wordmark', 'accent' => 'split']), concept(['layout' => 'monogram', 'shape' => 'circle'])],
    ])]]]])]);

    $response = $this->actingAs($this->user)->postJson(route('ai.logo'), [
        'project_id' => $this->project->id, 'brand' => 'Rohit Sharma', 'theme' => $this->theme, 'background' => '#0b1220', 'instruction' => 'add a logo with a trophy',
    ]);

    $response->assertOk()->assertJsonPath('summary', 'Sporty, confident marks built around a trophy.');
    expect($response->json('concepts'))->toHaveCount(4)
        ->and($response->json('concepts.0.svg'))->toStartWith('<svg');
    OpenAI::assertSent(\OpenAI\Resources\Chat::class, fn ($m, $p) => $p['response_format']['json_schema']['strict'] === true
        && str_contains($p['messages'][1]['content'], 'add a logo with a trophy'));
});

it('still offers logos when the AI is unavailable', function () {
    OpenAI::fake([
        CreateResponse::fake(['choices' => [['index' => 0, 'finish_reason' => 'length', 'message' => ['role' => 'assistant', 'content' => '{']]]]),
        CreateResponse::fake(['choices' => [['index' => 0, 'finish_reason' => 'length', 'message' => ['role' => 'assistant', 'content' => '{']]]]),
    ]);

    $this->actingAs($this->user)
        ->postJson(route('ai.logo'), ['project_id' => $this->project->id, 'brand' => 'Rohit Sharma', 'theme' => $this->theme])
        ->assertOk()
        ->assertJsonCount(4, 'concepts');
});

it('saves the chosen logo by re-rendering it on the server', function () {
    $response = $this->actingAs($this->user)->postJson(route('projects.logo', $this->project), [
        'spec' => concept() + ['svg' => '<svg onload="alert(1)">'], // anything extra from the browser is ignored
        'brand' => 'Rohit Sharma', 'theme' => $this->theme, 'background' => '#0b1220',
    ]);

    $response->assertOk()->assertJsonPath('containsName', true);
    $path = str_replace('/storage/', '', $response->json('url'));
    Storage::disk('public')->assertExists($path);
    expect(Storage::disk('public')->get($path))->toStartWith('<svg xmlns')->not->toContain('onload')
        ->and($this->project->assets()->count())->toBe(1);
});

it("does not design or save logos for someone else's project", function () {
    $other = User::factory()->create();
    OpenAI::fake([]);

    $this->actingAs($other)->postJson(route('ai.logo'), ['project_id' => $this->project->id, 'brand' => 'X', 'theme' => $this->theme])->assertForbidden();
    $this->actingAs($other)->postJson(route('projects.logo', $this->project), ['spec' => concept(), 'brand' => 'X', 'theme' => $this->theme])->assertForbidden();
});

it('shows the logo on published pages and can hide the duplicate brand text', function () {
    $schema = ['meta' => ['title' => 'R', 'description' => '', 'language' => 'en'], 'theme' => $this->theme, 'sections' => [
        ['id' => 'n', 'type' => 'navbar', 'props' => ['brand' => 'Rohit Sharma', 'logo' => '/storage/assets/1/logo.svg', 'hideBrandText' => true, 'links' => []]],
    ]];
    $html = app(\App\Services\ExportService::class)->toHtml($schema);

    expect($html)->toContain('src="/storage/assets/1/logo.svg"')->toContain('alt="Rohit Sharma"')
        ->and($html)->not->toContain('<span class="text-xl font-bold" style="font-family: var(--font-heading);">Rohit Sharma');
});
