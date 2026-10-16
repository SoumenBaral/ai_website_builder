<?php

use App\Services\Ai\Color;
use App\Services\Ai\SchemaNormalizer;

function site(array $sections, array $theme = []): array
{
    return [
        'meta' => ['title' => 'Lumen Studio', 'description' => 'Wedding photography', 'language' => 'en'],
        'theme' => $theme + [
            'primaryColor' => '#2563eb', 'secondaryColor' => '#0f172a', 'backgroundColor' => '#ffffff',
            'textColor' => '#0f172a', 'fontHeading' => 'Poppins', 'fontBody' => 'Inter', 'radius' => 'lg', 'mode' => 'light',
        ],
        'sections' => $sections,
    ];
}

beforeEach(fn () => $this->n = new SchemaNormalizer());

it('keeps custom sections and drops unknown types', function () {
    $out = $this->n->normalizeSite(site([
        ['id' => 'a', 'type' => 'custom', 'props' => ['elements' => [['type' => 'heading', 'text' => 'Hi']]]],
        ['id' => 'b', 'type' => 'carousel', 'props' => []],
    ]));

    expect(array_column($out['sections'], 'type'))->toBe(['custom']);
});

it('always provides list props as arrays so published templates never crash', function () {
    $out = $this->n->normalizeSite(site([
        ['id' => 'f', 'type' => 'features', 'props' => ['heading' => 'Why us', 'items' => 'oops']],
        ['id' => 'p', 'type' => 'pricing', 'props' => ['plans' => [['name' => 'Pro', 'price' => '$9', 'features' => 'one']]]],
    ]));

    expect($out['sections'][0]['props']['items'])->toBe([])
        ->and($out['sections'][1]['props']['plans'][0]['features'])->toBe([]);
});

it('drops unknown props and null values, coerces numbers and enums', function () {
    $out = $this->n->normalizeSite(site([
        ['id' => 'h', 'type' => 'hero', 'props' => [
            'heading' => '  Big   moments ', 'layout' => 'SPLIT-RIGHT', 'overlayOpacity' => '150',
            'minHeight' => 'giant', 'madeUp' => 'x', 'subheading' => null,
        ]],
    ]));
    $p = $out['sections'][0]['props'];

    expect($p['heading'])->toBe('Big moments')
        ->and($p['layout'])->toBe('split-right')
        ->and($p['overlayOpacity'])->toBe(90)
        ->and($p)->not->toHaveKey('minHeight')
        ->and($p)->not->toHaveKey('madeUp')
        ->and($p)->not->toHaveKey('subheading');
});

it('turns plain-text image values into photo search placeholders', function () {
    $out = $this->n->normalizeSite(site([
        ['id' => 'a', 'type' => 'about', 'props' => ['heading' => 'Story', 'image' => 'smiling chef in kitchen']],
        ['id' => 'b', 'type' => 'about', 'props' => ['heading' => 'Story', 'image' => 'https://images.unsplash.com/photo-1?w=1']],
    ]));

    expect($out['sections'][0]['props']['image'])->toBe('PLACEHOLDER:smiling chef in kitchen')
        ->and($out['sections'][1]['props']['image'])->toBe('https://images.unsplash.com/photo-1?w=1');
});

it('repairs the theme: fonts, colours, mode and contrast', function () {
    $out = $this->n->normalizeSite(site([], [
        'fontHeading' => 'Comic Sans', 'fontBody' => 'source sans pro',
        'primaryColor' => 'blue', 'backgroundColor' => '#0b0d12', 'textColor' => '#111111', 'mode' => 'light',
    ]));
    $t = $out['theme'];

    expect($t['fontHeading'])->toBe('Poppins')
        ->and($t['fontBody'])->toBe('Source Sans 3')
        ->and($t['primaryColor'])->toBe('#2563eb')
        ->and($t['mode'])->toBe('dark')
        ->and(Color::contrast($t['textColor'], $t['backgroundColor']))->toBeGreaterThanOrEqual(4.5);
});

it('keeps white button text readable on the primary colour', function () {
    $out = $this->n->normalizeSite(site([], ['primaryColor' => '#fde68a']));

    expect(Color::contrast('#ffffff', $out['theme']['primaryColor']))->toBeGreaterThanOrEqual(3.0);
});

it('makes section colour overrides readable', function () {
    $out = $this->n->normalizeSite(site([
        ['id' => 's', 'type' => 'stats', 'props' => ['sectionBg' => '#0f172a', 'items' => [['value' => '12', 'label' => 'Years']]]],
        ['id' => 'c', 'type' => 'cta', 'props' => ['heading' => 'Go', 'sectionText' => '#fefefe']],
    ]));

    $stats = $out['sections'][0]['props'];
    expect(Color::contrast($stats['sectionText'], $stats['sectionBg']))->toBeGreaterThanOrEqual(4.5)
        ->and($out['sections'][1]['props'])->not->toHaveKey('sectionText');
});

it('puts navbar first and footer last, adding them for new sites', function () {
    $out = $this->n->normalizeSite(site([
        ['id' => 'f', 'type' => 'footer', 'props' => ['text' => '©']],
        ['id' => 'h', 'type' => 'hero', 'props' => ['heading' => 'Hi']],
        ['id' => 'a', 'type' => 'about', 'props' => ['heading' => 'About']],
    ]), ensureFrame: true);

    expect(array_column($out['sections'], 'type'))->toBe(['navbar', 'hero', 'about', 'footer'])
        ->and($out['sections'][0]['props']['brand'])->toBe('Lumen Studio')
        ->and($out['sections'][0]['props']['links'][0]['href'])->toBe('#about');
});

it('does not add a navbar or footer when editing', function () {
    $out = $this->n->normalizeSite(site([['id' => 'h', 'type' => 'hero', 'props' => ['heading' => 'Hi']]]), ensureFrame: false);

    expect(array_column($out['sections'], 'type'))->toBe(['hero']);
});

it('keeps existing ids and makes duplicates unique', function () {
    $out = $this->n->normalizeSite(site([
        ['id' => 'blk_hero_x', 'type' => 'hero', 'props' => ['heading' => 'A']],
        ['id' => 'blk_hero_x', 'type' => 'about', 'props' => ['heading' => 'B']],
        ['type' => 'faq', 'props' => ['items' => []]],
    ]));
    $ids = array_column($out['sections'], 'id');

    expect($ids[0])->toBe('blk_hero_x')
        ->and(count(array_unique($ids)))->toBe(3);
});

it('points links at sections that exist', function () {
    $out = $this->n->normalizeSite(site([
        ['id' => 'n', 'type' => 'navbar', 'props' => ['brand' => 'B', 'ctaText' => 'Book', 'ctaHref' => '#booking', 'links' => [
            ['label' => 'Story', 'href' => '#our-story'],
            ['label' => 'Work', 'href' => '#portfolio'],
            ['label' => 'Blog', 'href' => '#blog'],
            ['label' => 'Insta', 'href' => 'https://instagram.com/x'],
        ]]],
        ['id' => 'h', 'type' => 'hero', 'props' => ['heading' => 'Hi', 'ctaText' => 'Go', 'ctaHref' => '#top']],
        ['id' => 'a', 'type' => 'about', 'props' => ['heading' => 'About']],
        ['id' => 'g', 'type' => 'gallery', 'props' => ['images' => []]],
        ['id' => 'c', 'type' => 'contact', 'props' => ['heading' => 'Contact']],
    ]));
    $nav = $out['sections'][0]['props'];

    expect(array_column($nav['links'], 'href'))->toBe(['#about', '#gallery', '#contact', 'https://instagram.com/x'])
        ->and($nav['ctaHref'])->toBe('#contact')
        ->and($out['sections'][1]['props']['ctaHref'])->toBe('#home');
});

it('highlights exactly one pricing plan', function () {
    $plan = fn ($name, $hl) => ['name' => $name, 'price' => '$1', 'period' => '/mo', 'features' => ['a'], 'ctaText' => 'Buy', 'highlighted' => $hl];

    $none = $this->n->normalizeSite(site([['id' => 'p', 'type' => 'pricing', 'props' => ['plans' => [$plan('A', false), $plan('B', false), $plan('C', false)]]]]));
    $many = $this->n->normalizeSite(site([['id' => 'p', 'type' => 'pricing', 'props' => ['plans' => [$plan('A', true), $plan('B', true)]]]]));

    expect(array_column($none['sections'][0]['props']['plans'], 'highlighted'))->toBe([false, true, false])
        ->and(array_column($many['sections'][0]['props']['plans'], 'highlighted'))->toBe([true, false]);
});

it('never uses more grid columns than items and keeps hero text readable over photos', function () {
    $out = $this->n->normalizeSite(site([
        ['id' => 'f', 'type' => 'features', 'props' => ['columns' => 4, 'items' => [['title' => 'A', 'description' => 'a', 'icon' => '⭐'], ['title' => 'B', 'description' => 'b', 'icon' => '⭐']]]],
        ['id' => 'h', 'type' => 'hero', 'props' => ['heading' => 'Hi', 'layout' => 'center', 'image' => 'PLACEHOLDER:beach', 'overlayOpacity' => 10]],
    ]));

    expect($out['sections'][0]['props']['columns'])->toBe(2)
        ->and($out['sections'][1]['props']['overlayOpacity'])->toBe(45);
});

it('normalises a single edited section with its own rules', function () {
    $section = $this->n->normalizeSection(
        ['id' => 'blk_team_1', 'type' => 'team', 'props' => ['members' => [['name' => 'Ana', 'role' => 'Chef', 'image' => 'portrait of woman chef']], 'columns' => 4]],
        ['primaryColor' => '#2563eb', 'secondaryColor' => '#0f172a', 'backgroundColor' => '#fff', 'textColor' => '#000', 'fontHeading' => 'Inter', 'fontBody' => 'Inter', 'radius' => 'lg', 'mode' => 'light'],
    );

    expect($section['id'])->toBe('blk_team_1')
        ->and($section['props']['columns'])->toBe(1)
        ->and($section['props']['members'][0]['image'])->toBe('PLACEHOLDER:portrait of woman chef');
});

it('rescues a logo the model stuffed into the brand text', function () {
    $out = $this->n->normalizeSite(site([
        ['id' => 'n1', 'type' => 'navbar', 'props' => ['links' => [], 'brand' => '<img src="PLACEHOLDER:logo transparent background" alt="Rohit Sharma Logo" style="height:40px;">']],
        ['id' => 'n2', 'type' => 'navbar', 'props' => ['links' => [], 'brand' => '<img src="https://cdn.example.com/logo.png" alt="Lumen"> Lumen Studio']],
    ]));
    // (only the first navbar survives; check each case separately)
    $first = $out['sections'][0]['props'];
    $second = $this->n->normalizeSection(['id' => 'n2', 'type' => 'navbar', 'props' => ['brand' => '<img src="https://cdn.example.com/logo.png" alt="Lumen"> Lumen Studio']], site([])['theme'])['props'];

    expect($first['brand'])->toBe('Rohit Sharma')
        ->and($first)->not->toHaveKey('logo')                 // a placeholder is not a logo
        ->and($second['brand'])->toBe('Lumen Studio')
        ->and($second['logo'])->toBe('https://cdn.example.com/logo.png');
});

it('never invents logos but keeps uploaded ones', function () {
    $theme = site([])['theme'];
    $invented = $this->n->normalizeSection(['id' => 'n', 'type' => 'navbar', 'props' => ['brand' => 'B', 'logo' => 'PLACEHOLDER:minimal logo']], $theme)['props'];
    $uploaded = $this->n->normalizeSection(['id' => 'n', 'type' => 'navbar', 'props' => ['brand' => 'B', 'logo' => '/storage/assets/7/logo.svg']], $theme)['props'];

    expect($invented)->not->toHaveKey('logo')
        ->and($uploaded['logo'])->toBe('/storage/assets/7/logo.svg');
});

it('keeps text fields as plain text', function () {
    $out = $this->n->normalizeSite(site([
        ['id' => 'h', 'type' => 'hero', 'props' => ['heading' => '<strong>Big</strong> moments<script>x</script>']],
    ]));

    expect($out['sections'][0]['props']['heading'])->toBe('Big momentsx');
});

it('tells the model the logo is a user upload', function () {
    $schema = \App\Services\Ai\BlockCatalog::sectionEditSchema('navbar');
    expect($schema['properties']['props']['properties']['logo']['description'])->toContain('never invent')
        ->and($schema['properties']['props']['properties']['logo']['description'])->not->toContain('PLACEHOLDER');
});
