<?php

use App\Models\User;
use App\Services\Ai\MotionSpec;
use App\Services\Ai\SchemaNormalizer;
use App\Services\ExportService;
use OpenAI\Laravel\Facades\OpenAI;
use OpenAI\Responses\Chat\CreateResponse;

function fx_reply(array $content): CreateResponse
{
    return CreateResponse::fake([
        'choices' => [['index' => 0, 'message' => ['role' => 'assistant', 'content' => json_encode($content)], 'finish_reason' => 'stop']],
        'usage' => ['prompt_tokens' => 500, 'completion_tokens' => 200, 'total_tokens' => 700],
    ]);
}

function fx_stars(): array
{
    return ['kind' => 'particles', 'name' => 'Twinkling stars', 'shape' => 'star', 'emoji' => null, 'colors' => ['#ffffff', '#fde68a'], 'count' => 45, 'sizeMin' => 3, 'sizeMax' => 8, 'motion' => 'twinkle', 'speed' => 'normal', 'opacity' => 85, 'glow' => true];
}

function fx_site(array $sections): array
{
    return [
        'meta' => ['title' => 'Nova', 'description' => '', 'language' => 'en'],
        'theme' => ['primaryColor' => '#6d28d9', 'secondaryColor' => '#0f172a', 'backgroundColor' => '#0b0d12', 'textColor' => '#f8fafc', 'fontHeading' => 'Inter', 'fontBody' => 'Inter', 'radius' => 'md', 'mode' => 'dark'],
        'sections' => $sections,
    ];
}

it('clamps AI-designed background effects to safe data', function () {
    $fx = MotionSpec::effect(['kind' => 'particles', 'name' => '<b>Stars</b>{x}', 'shape' => 'star', 'colors' => ['#fff', 'javascript:alert(1)', 'red;background:url(x)'], 'count' => 5000, 'sizeMin' => -4, 'sizeMax' => 900, 'motion' => 'explode', 'speed' => 'warp', 'opacity' => 300, 'glow' => 'yes']);

    expect($fx)->toMatchArray(['name' => 'Starsx', 'colors' => ['#ffffff'], 'count' => 120, 'sizeMin' => 2.0, 'sizeMax' => 120.0, 'motion' => 'twinkle', 'speed' => 'normal', 'opacity' => 100, 'glow' => true])
        ->and(MotionSpec::effect(['kind' => 'lasers']))->toBeNull()
        ->and(MotionSpec::effect(['kind' => 'particles', 'shape' => 'emoji', 'emoji' => '🍂']))->toMatchArray(['shape' => 'emoji', 'emoji' => '🍂'])
        ->and(MotionSpec::effect(['kind' => 'particles', 'shape' => 'emoji', 'emoji' => '<img src=x>']))->toMatchArray(['shape' => 'star', 'emoji' => null]);
});

it('clamps custom entrance moves', function () {
    expect(MotionSpec::entrance(['name' => 'Spin in', 'opacity' => 0, 'rotate' => -9000, 'scale' => 0.6, 'easing' => 'spring', 'x' => 'abc']))
        ->toMatchArray(['rotate' => -720, 'scale' => 0.6, 'x' => 0, 'easing' => 'spring'])
        ->and(MotionSpec::entrance(['opacity' => 1, 'scale' => 1]))->toBeNull(); // moves nothing
});

it('publishes effects and custom moves as escaped data the runtime renders', function () {
    $html = app(ExportService::class)->toHtml(fx_site([
        ['id' => 'h', 'type' => 'hero', 'props' => ['heading' => 'Hi', 'animation' => 'custom', 'customAnimation' => ['name' => 'Spin', 'opacity' => 0, 'rotate' => -180, 'scale' => 0.6, 'easing' => 'spring'], 'bgEffect' => fx_stars()]],
        ['id' => 'c', 'type' => 'cta', 'props' => ['heading' => 'Go', 'animation' => 'custom']], // custom without a recipe
        ['id' => 'f', 'type' => 'footer', 'props' => ['text' => 'x', 'bgEffect' => ['kind' => 'particles', 'name' => "'><script>alert(1)</script>", 'colors' => ['#fff']]]],
    ]));

    expect(with_site_assets($html))->toContain('data-anim="custom" data-anim-custom=\'{&quot;name&quot;:&quot;Spin&quot;')
        ->toContain('data-fx=\'{&quot;kind&quot;:&quot;particles&quot;')
        ->toContain('<div data-anim="fade-up">')     // falls back safely
        ->toContain('mountFx')                        // runtime inlined
        ->toContain('.fx-layer')
        ->not->toContain('<script>alert(1)');
});

it('keeps stars and custom moves when the AI edits the site text', function () {
    $original = fx_site([
        ['id' => 'h', 'type' => 'hero', 'props' => ['heading' => 'Old', 'animation' => 'custom', 'customAnimation' => ['name' => 'Spin', 'rotate' => -180, 'opacity' => 0], 'bgEffect' => fx_stars()]],
    ]);
    $edited = fx_site([['id' => 'h', 'type' => 'hero', 'props' => ['heading' => 'New heading', 'animation' => 'fade-up']]]);
    OpenAI::fake([fx_reply(['summary' => 'New heading.'] + $edited)]);

    $res = $this->actingAs(User::factory()->create())->postJson(route('ai.edit'), ['schema' => $original, 'instruction' => 'new heading'])->assertOk();

    expect($res->json('schema.sections.0.props'))->toMatchArray(['heading' => 'New heading', 'animation' => 'custom'])
        ->and($res->json('schema.sections.0.props.bgEffect.name'))->toBe('Twinkling stars')
        ->and($res->json('schema.sections.0.props.customAnimation.rotate'))->toBe(-180);
});

it('lets the AI design a background effect and a custom move', function () {
    OpenAI::fake([fx_reply([
        'handled' => true,
        'summary' => 'Added twinkling stars and a bouncy spin-in to the hero.',
        'sections' => [[
            'id' => 'h', 'animation' => 'custom', 'animationSpeed' => null, 'animationStagger' => true, 'hoverEffect' => null,
            'customAnimation' => ['name' => 'Bouncy spin-in', 'opacity' => 0, 'x' => 0, 'y' => 0, 'scale' => 0.5, 'rotate' => -180, 'rotateX' => 0, 'rotateY' => 0, 'skewX' => 0, 'blur' => 0, 'easing' => 'bounce'],
            'bgEffect' => fx_stars(),
        ], [
            'id' => 'c', 'animation' => null, 'animationSpeed' => null, 'animationStagger' => null, 'hoverEffect' => null, 'customAnimation' => null,
            'bgEffect' => ['kind' => 'none', 'name' => '', 'shape' => 'dot', 'emoji' => null, 'colors' => [], 'count' => 3, 'sizeMin' => 2, 'sizeMax' => 2, 'motion' => 'twinkle', 'speed' => 'normal', 'opacity' => 5, 'glow' => false],
        ]],
    ])]);

    $changes = $this->actingAs(User::factory()->create())->postJson(route('ai.animate'), [
        'sections' => [['id' => 'h', 'type' => 'hero', 'props' => ['heading' => 'Hi']], ['id' => 'c', 'type' => 'cta', 'props' => ['heading' => 'Go', 'bgEffect' => fx_stars()]]],
        'instruction' => 'little stars over the hero background, spin it in with a bounce, and remove the cta effect',
        'theme' => ['primaryColor' => '#6d28d9'],
    ])->assertOk()->json('changes');

    expect($changes[0]['props'])->toMatchArray(['animation' => 'custom', 'animationStagger' => true])
        ->and($changes[0]['props']['customAnimation'])->toMatchArray(['easing' => 'bounce', 'rotate' => -180])
        ->and($changes[0]['props']['bgEffect'])->toMatchArray(['shape' => 'star', 'motion' => 'twinkle', 'glow' => true])
        ->and($changes[1]['props'])->toBe(['bgEffect' => null]); // removed
});

it('applies only the motion part in extract mode', function () {
    OpenAI::fake([fx_reply(['handled' => false, 'summary' => '', 'sections' => [['id' => 'h', 'animation' => 'zoom', 'animationSpeed' => null, 'animationStagger' => null, 'hoverEffect' => null, 'customAnimation' => null, 'bgEffect' => null]]])]);

    $res = $this->actingAs(User::factory()->create())->postJson(route('ai.animate'), [
        'sections' => [['id' => 'h', 'type' => 'hero', 'props' => ['heading' => 'Hi']]],
        'instruction' => 'make it red and zoom it in',
        'extract' => true,
    ])->assertOk();

    expect($res->json('handled'))->toBeTrue()->and($res->json('changes.0.props'))->toBe(['animation' => 'zoom']);
    OpenAI::assertSent(\OpenAI\Resources\Chat::class, fn ($m, $p) => str_starts_with($p['messages'][1]['content'], 'MODE: extract'));
});

it('normalizes custom without a recipe to a plain animation', function () {
    $section = app(SchemaNormalizer::class)->normalizeSection(['id' => 'x', 'type' => 'about', 'props' => ['heading' => 'A', 'body' => 'B', 'animation' => 'custom']], fx_site([])['theme']);

    expect($section['props']['animation'])->toBe('fade-up');
});

it('publishes button effects and lets the AI set them', function () {
    $html = app(ExportService::class)->toHtml(fx_site([
        ['id' => 'h', 'type' => 'hero', 'props' => ['heading' => 'Hi', 'ctaText' => 'Shop now', 'buttonEffect' => 'shine']],
        ['id' => 'c', 'type' => 'cta', 'props' => ['heading' => 'Go', 'buttonEffect' => 'explode']],
    ]));
    expect(with_site_assets($html))->toContain('<div data-btn-fx="shine">')
        ->toContain('markButtons')
        ->toContain('@keyframes btn-shine')
        ->and(substr_count($html, 'data-btn-fx="'))->toBe(1); // invalid value ignored

    OpenAI::fake([fx_reply(['handled' => true, 'summary' => 'Your buttons now pulse.', 'sections' => [
        ['id' => 'h', 'animation' => null, 'animationSpeed' => null, 'animationStagger' => null, 'hoverEffect' => null, 'buttonEffect' => 'pulse', 'customAnimation' => null, 'bgEffect' => null],
    ]])]);
    $changes = $this->actingAs(User::factory()->create())->postJson(route('ai.animate'), [
        'sections' => [['id' => 'h', 'type' => 'hero', 'props' => ['heading' => 'Hi']]],
        'instruction' => 'make the buttons pulse',
    ])->assertOk()->json('changes');

    expect($changes)->toBe([['id' => 'h', 'props' => ['buttonEffect' => 'pulse']]]);
});

it('publishes heading effects with clean typing phrases', function () {
    $html = app(ExportService::class)->toHtml(fx_site([
        ['id' => 'h', 'type' => 'hero', 'props' => ['heading' => 'Websites that sell', 'textEffect' => 'typing-loop', 'textSpeed' => 'slow', 'typingPhrases' => ['Brands that <b>stand out</b>', 'Stores that grow', 'Stores that grow', '']]],
        ['id' => 'a', 'type' => 'about', 'props' => ['heading' => 'Us', 'body' => 'x', 'textEffect' => 'typing']],
        ['id' => 'c', 'type' => 'cta', 'props' => ['heading' => 'Go', 'textEffect' => 'matrix']],
    ]));

    expect(with_site_assets($html))->toContain('data-text-fx="typing-loop" data-text-speed="slow" data-text-phrases=\'[&quot;Brands that stand out&quot;,&quot;Stores that grow&quot;]\'')
        ->toContain('<div data-text-fx="typing">')
        ->toContain('Websites that sell')          // real text stays in the HTML (SEO, no-JS)
        ->toContain('textFx')
        ->toContain('.tx-caret')
        ->and(substr_count($html, 'data-text-fx="'))->toBe(2); // invalid "matrix" ignored
});

it('lets the AI add a typing effect with rotating phrases', function () {
    OpenAI::fake([fx_reply(['handled' => true, 'summary' => 'Your hero heading now types out and rotates.', 'sections' => [[
        'id' => 'h', 'animation' => null, 'animationSpeed' => null, 'animationStagger' => null, 'hoverEffect' => null, 'buttonEffect' => null,
        'textEffect' => 'typing-loop', 'textSpeed' => 'normal', 'typingPhrases' => ['Everyday menswear', 'Built to last'],
        'customAnimation' => null, 'bgEffect' => null,
    ]]])]);

    $changes = $this->actingAs(User::factory()->create())->postJson(route('ai.animate'), [
        'sections' => [['id' => 'h', 'type' => 'hero', 'props' => ['heading' => 'Elevate your style']]],
        'instruction' => 'add a typing effect to the hero heading that rotates through a couple of phrases',
    ])->assertOk()->json('changes');

    expect($changes[0]['props'])->toBe(['textEffect' => 'typing-loop', 'textSpeed' => 'normal', 'typingPhrases' => ['Everyday menswear', 'Built to last']]);
});
