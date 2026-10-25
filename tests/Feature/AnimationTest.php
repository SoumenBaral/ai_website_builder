<?php

use App\Services\Ai\BlockCatalog;
use App\Services\Ai\SchemaNormalizer;
use App\Models\User;
use App\Services\ExportService;
use OpenAI\Laravel\Facades\OpenAI;
use OpenAI\Responses\Chat\CreateResponse;

function anim_reply(array $content): CreateResponse
{
    return CreateResponse::fake([
        'choices' => [['index' => 0, 'message' => ['role' => 'assistant', 'content' => json_encode($content)], 'finish_reason' => 'stop']],
        'usage' => ['prompt_tokens' => 400, 'completion_tokens' => 120, 'total_tokens' => 520],
    ]);
}

function anim_sections(): array
{
    return [
        ['id' => 'n1', 'type' => 'navbar', 'props' => ['brand' => 'Studio']],
        ['id' => 'h1', 'type' => 'hero', 'props' => ['heading' => 'Hello', 'animation' => 'slide-left']],
        ['id' => 'p1', 'type' => 'pricing', 'props' => ['heading' => 'Plans']],
    ];
}

function anim_site(array $sections): array
{
    return [
        'meta' => ['title' => 'Studio', 'description' => '', 'language' => 'en'],
        'theme' => ['primaryColor' => '#4f46e5', 'secondaryColor' => '#111827', 'backgroundColor' => '#ffffff', 'textColor' => '#111827', 'fontHeading' => 'Inter', 'fontBody' => 'Inter', 'radius' => 'md', 'mode' => 'light'],
        'sections' => $sections,
    ];
}

it('lets the AI set animations on every section type', function () {
    foreach (BlockCatalog::types() as $type) {
        $props = BlockCatalog::props($type);
        expect($props)->toHaveKeys(['animation', 'animationSpeed', 'animationStagger', 'hoverEffect']);
        expect($props['animation']['values'])->toBe(BlockCatalog::ANIMATIONS);
    }
    expect(BlockCatalog::promptReference())->toContain('animationStagger');
});

it('keeps valid animations and drops invented ones', function () {
    $normalizer = app(SchemaNormalizer::class);
    $ok = $normalizer->normalizeSection(['id' => 'a', 'type' => 'about', 'props' => ['heading' => 'Hi', 'body' => 'Text', 'animation' => 'From Left', 'animationSpeed' => 'slow', 'animationStagger' => true]], anim_site([])['theme']);
    $bad = $normalizer->normalizeSection(['id' => 'b', 'type' => 'about', 'props' => ['heading' => 'Hi', 'body' => 'Text', 'animation' => 'spin-wildly', 'animationSpeed' => 'warp']], anim_site([])['theme']);

    expect($ok['props'])->toMatchArray(['animation' => 'from-left', 'animationSpeed' => 'slow', 'animationStagger' => true])
        ->and($bad['props']['animation'] ?? null)->toBeNull()
        ->and($bad['props']['animationSpeed'] ?? null)->toBeNull();
});

it('publishes animated sections with the animation script', function () {
    $html = app(ExportService::class)->toHtml(anim_site([
        ['id' => 'h', 'type' => 'hero', 'props' => ['headline' => 'Hello', 'animation' => 'zoom', 'animationSpeed' => 'slow']],
        ['id' => 'f', 'type' => 'features', 'props' => ['heading' => 'Why us', 'items' => [['title' => 'A', 'description' => 'B']], 'animation' => 'fade-up', 'animationStagger' => true]],
        ['id' => 'c', 'type' => 'cta', 'props' => ['heading' => 'Go', 'animation' => 'none']],
    ]));

    expect(with_site_assets($html))->toContain('data-anim="zoom" data-anim-speed="slow"')
        ->toContain('data-anim="fade-up" data-anim-stagger')
        ->toContain('IntersectionObserver')
        ->toContain('prefers-reduced-motion')
        ->toContain('.anim-on [data-anim] [data-at="0"]')  // minified: attribute quotes normalised
        ->and(substr_count($html, '<div data-anim="'))->toBe(2); // "none" isn't wrapped
});

it('adds no animation code to sites without animations', function () {
    $html = app(ExportService::class)->toHtml(anim_site([
        ['id' => 'h', 'type' => 'hero', 'props' => ['headline' => 'Hello']],
    ]));

    expect($html)->not->toContain('data-anim')->not->toContain('IntersectionObserver')->not->toContain('anim-clip');
});

it('never lets animation values inject markup', function () {
    $html = app(ExportService::class)->toHtml(anim_site([
        ['id' => 'h', 'type' => 'hero', 'props' => ['headline' => 'Hello', 'animation' => '"><script>alert(1)</script>', 'animationSpeed' => '"><b>']],
    ]));

    expect($html)->not->toContain('<script>alert(1)')->not->toContain('<b>');
});

it('maps old and natural animation names', function () {
    expect(BlockCatalog::canonicalAnimation('slide-left'))->toBe('from-right') // it moved left = came from the right
        ->and(BlockCatalog::canonicalAnimation('slide-right'))->toBe('from-left')
        ->and(BlockCatalog::canonicalAnimation('Zoom In'))->toBe('zoom')
        ->and(BlockCatalog::canonicalAnimation('fade_in'))->toBe('fade')
        ->and(BlockCatalog::canonicalAnimation('wobble'))->toBeNull();
});

it('publishes hover effects, whole-navbar motion and legacy names', function () {
    $html = app(ExportService::class)->toHtml(anim_site([
        ['id' => 'n', 'type' => 'navbar', 'props' => ['brand' => 'S', 'animation' => 'fade-down']],
        ['id' => 'a', 'type' => 'about', 'props' => ['heading' => 'Us', 'body' => 'x', 'animation' => 'slide-right']],
        ['id' => 'f', 'type' => 'features', 'props' => ['heading' => 'F', 'items' => [['title' => 'A', 'description' => 'B']], 'hoverEffect' => 'lift']],
    ]));

    expect(with_site_assets($html))->toContain('<div data-anim="fade-down" data-anim-whole>')
        ->toContain('<div data-anim="from-left">')
        ->toContain('<div data-hover="lift">')       // hover works without an entrance animation
        ->toContain('window.SiteAnim')               // the same runtime the editor uses
        ->toContain('[data-hover=lift]');            // minified selector
});

it('applies animation-only AI edits without touching content', function () {
    OpenAI::fake([anim_reply([
        'handled' => true,
        'summary' => 'Pricing cards now flip up one by one.',
        'sections' => [
            ['id' => 'p1', 'animation' => 'flip', 'animationSpeed' => null, 'animationStagger' => true, 'hoverEffect' => 'glow'],
        ],
    ])]);

    $res = $this->actingAs(User::factory()->create())->postJson(route('ai.animate'), [
        'sections' => anim_sections(),
        'selected' => 'p1',
        'instruction' => 'make these cards flip up one by one and glow on hover',
    ])->assertOk();

    expect($res->json('handled'))->toBeTrue()
        ->and($res->json('summary'))->toBe('Pricing cards now flip up one by one.')
        ->and($res->json('changes'))->toBe([['id' => 'p1', 'props' => ['animation' => 'flip', 'animationStagger' => true, 'hoverEffect' => 'glow']]]);

    OpenAI::assertSent(\OpenAI\Resources\Chat::class, function (string $method, array $params) {
        $user = $params['messages'][1]['content'];

        return $params['response_format']['json_schema']['strict'] === true
            && str_contains($user, 'Selected section: p1 (pricing)')
            && str_contains($user, '"animation":"from-right"'); // legacy value shown to the AI in current terms
    });
});

it('ignores invented section ids and hands mixed requests back', function () {
    OpenAI::fake([
        anim_reply(['handled' => true, 'summary' => 'Done', 'sections' => [['id' => 'ghost', 'animation' => 'zoom', 'animationSpeed' => null, 'animationStagger' => null, 'hoverEffect' => null]]]),
        anim_reply(['handled' => false, 'summary' => '', 'sections' => []]),
    ]);
    $user = User::factory()->create();

    expect($this->actingAs($user)->postJson(route('ai.animate'), ['sections' => anim_sections(), 'instruction' => 'animate it'])->json('changes'))->toBe([]);
    expect($this->actingAs($user)->postJson(route('ai.animate'), ['sections' => anim_sections(), 'instruction' => 'add fade and make the heading red'])->json())
        ->toMatchArray(['handled' => false, 'changes' => []]);
});
