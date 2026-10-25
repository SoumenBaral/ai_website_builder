<?php

use App\Models\Page;
use App\Models\Project;
use App\Models\User;
use App\Services\ExportService;
use App\Services\ItemPages;

function shop_schema(bool $pages = true): array
{
    return [
        'version' => 1,
        'meta' => ['title' => 'Forma Men', 'description' => 'Menswear', 'language' => 'en'],
        'theme' => ['primaryColor' => '#1e293b', 'secondaryColor' => '#0f172a', 'backgroundColor' => '#ffffff', 'textColor' => '#111827', 'fontHeading' => 'Inter', 'fontBody' => 'Inter', 'radius' => 'md', 'mode' => 'light'],
        'sections' => [
            ['id' => 'n', 'type' => 'navbar', 'props' => ['brand' => 'Forma', 'links' => [['label' => 'Shop', 'href' => '#services'], ['label' => 'Contact', 'href' => '#contact']]]],
            ['id' => 's', 'type' => 'services', 'props' => ['heading' => 'Shop the collection', 'itemPages' => $pages, 'items' => [
                ['title' => 'Oxford Shirt', 'description' => 'A crisp staple.', 'price' => '$45', 'image' => 'https://images.unsplash.com/photo-a?w=900', 'photos' => ['https://images.unsplash.com/photo-b?w=900'], 'details' => "Breathable cotton.\n\nCut slim.", 'highlights' => ['100% cotton', 'Easy care']],
                ['title' => 'Oxford Shirt', 'description' => 'Same name, other colour.', 'price' => '$49'],
                ['title' => 'বাংলা শার্ট', 'description' => 'Bangla title.', 'ctaText' => 'Ask us', 'ctaHref' => 'javascript:alert(1)'],
            ]]],
            ['id' => 't', 'type' => 'team', 'props' => ['heading' => 'Our team', 'itemPages' => $pages, 'members' => [['name' => 'Ana Silva', 'role' => 'Founder', 'image' => 'https://images.unsplash.com/photo-c?w=400', 'details' => 'Ana started the shop in 2019.']]]],
            ['id' => 'c', 'type' => 'contact', 'props' => ['heading' => 'Say hi', 'email' => 'hi@forma.com']],
            ['id' => 'f', 'type' => 'footer', 'props' => ['text' => '© 2026 Forma', 'links' => [['label' => 'Top', 'href' => '#home']]]],
        ],
    ];
}

it('gives every card a unique, readable URL', function () {
    $map = ItemPages::map(shop_schema());

    expect($map[1]['anchor'])->toBe('services')
        ->and($map[1]['slugs'])->toHaveCount(3)
        ->and($map[1]['slugs'][0])->toBe('oxford-shirt')
        ->and($map[1]['slugs'][1])->toBe('oxford-shirt-2')               // duplicate titles stay unique
        ->and(array_unique($map[1]['slugs']))->toHaveCount(3)
        ->and($map[2]['slugs'])->toBe(['ana-silva'])
        ->and(ItemPages::map(shop_schema(false)))->toBe([]);             // off = no pages
});

it('links the cards on the home page only when details pages are on', function () {
    $ex = app(ExportService::class);
    $on = $ex->toHtml(shop_schema(), null, null, null, fn ($a, $s) => "/s/forma/{$a}/{$s}");
    $off = $ex->toHtml(shop_schema(false), null, null, null, fn ($a, $s) => "/s/forma/{$a}/{$s}");

    expect($on)->toContain('href="/s/forma/services/oxford-shirt"')
        ->toContain('href="/s/forma/services/oxford-shirt-2"')
        ->toContain('href="/s/forma/team/ana-silva"')
        ->toContain('View details')->toContain('View profile')
        ->and($off)->not->toContain('/s/forma/services/')->not->toContain('View details');
});

it('renders a complete, safe details page', function () {
    $html = app(ExportService::class)->itemHtml(shop_schema(), 'services', 'oxford-shirt', '/s/forma', fn ($a, $s) => "/s/forma/{$a}/{$s}");

    expect($html)->toContain('<title>Oxford Shirt — Forma Men</title>')
        ->toContain('<h1')->toContain('$45')
        ->toContain('Breathable cotton.')->toContain('Cut slim.')               // paragraphs
        ->toContain('100% cotton')                                             // highlights
        ->toContain('data-thumb="https://images.unsplash.com/photo-b?w=900"') // gallery
        ->toContain('href="/s/forma#contact"')                                // default CTA → contact section
        ->toContain('Order now')
        ->toContain('href="/s/forma#services"')                               // navbar + breadcrumb point home
        ->toContain('You may also like')
        ->toContain('href="/s/forma/services/oxford-shirt-2"')               // related / next
        ->toContain('"@type":"Product"')->toContain('"price":"45"')->toContain('"priceCurrency":"USD"')
        ->toContain('og:image')
        ->toContain('window.SiteAnim')->and(with_site_assets($html))->toContain('IntersectionObserver');

    $bangla = app(ExportService::class)->itemHtml(shop_schema(), 'services', ItemPages::map(shop_schema())[1]['slugs'][2], '/s/forma', fn ($a, $s) => "/s/forma/{$a}/{$s}");
    expect($bangla)->toContain('বাংলা শার্ট')->toContain('Ask us')->not->toContain('javascript:alert');

    expect(app(ExportService::class)->itemHtml(shop_schema(), 'services', 'missing', '/', fn () => ''))->toBeNull()
        ->and(app(ExportService::class)->itemHtml(shop_schema(false), 'services', 'oxford-shirt', '/', fn () => ''))->toBeNull();
});

it('serves details pages on the published site', function () {
    $user = User::factory()->create();
    $project = Project::create(['user_id' => $user->id, 'name' => 'Forma', 'slug' => 'forma-x1', 'status' => 'published']);
    Page::create(['project_id' => $project->id, 'title' => 'Home', 'path' => '/', 'schema' => shop_schema(), 'is_home' => true]);

    $this->get('/s/forma-x1')->assertOk()->assertSee('/s/forma-x1/services/oxford-shirt', false);
    $this->get('/s/forma-x1/services/oxford-shirt')->assertOk()->assertSee('Oxford Shirt')->assertSee('fetch("/track"', false);
    $this->get('/s/forma-x1/team/ana-silva')->assertOk()->assertSee('About Ana');
    $this->get('/s/forma-x1/services/nope')->assertNotFound();

    $project->update(['status' => 'draft']);
    $this->get('/s/forma-x1/services/oxford-shirt')->assertNotFound();
});

it('previews a card from the editor and keeps it private', function () {
    $user = User::factory()->create();
    $project = Project::create(['user_id' => $user->id, 'name' => 'Forma', 'slug' => 'forma-x2', 'status' => 'draft']);
    Page::create(['project_id' => $project->id, 'title' => 'Home', 'path' => '/', 'schema' => shop_schema(), 'is_home' => true]);

    $this->actingAs($user)->get(route('export.previewCard', [$project, 's', 1]))
        ->assertRedirect(route('export.previewItem', [$project, 'services', 'oxford-shirt-2']));
    $this->actingAs($user)->get(route('export.previewItem', [$project, 'services', 'oxford-shirt']))->assertOk()->assertSee('Oxford Shirt');
    $this->actingAs(User::factory()->create())->get(route('export.previewItem', [$project, 'services', 'oxford-shirt']))->assertForbidden();
});

it('exports details pages in the ZIP with working relative links', function () {
    $zipPath = app(ExportService::class)->toZip(shop_schema());
    $zip = new ZipArchive();
    $zip->open($zipPath);

    $index = $zip->getFromName('index.html');
    $page = $zip->getFromName('services/oxford-shirt.html');
    expect($index)->toContain('href="services/oxford-shirt.html"')
        ->and($page)->not->toBeFalse()
        ->and($page)->toContain('href="../index.html#contact"')
        ->toContain('href="../services/oxford-shirt-2.html"')
        ->and($zip->getFromName('team/ana-silva.html'))->toContain('Ana Silva');

    $zip->close();
    @unlink($zipPath);
});

it('drops "View details" style button labels and splits long text', function () {
    $section = app(\App\Services\Ai\SchemaNormalizer::class)->normalizeSection(['id' => 's', 'type' => 'services', 'props' => ['heading' => 'Shop', 'itemPages' => true, 'items' => [
        ['title' => 'A', 'description' => 'x', 'ctaText' => 'View Oxford Shirt'],
        ['title' => 'B', 'description' => 'y', 'ctaText' => 'Order now'],
    ]]], shop_schema()['theme']);

    expect($section['props']['items'][0])->not->toHaveKey('ctaText')
        ->and($section['props']['items'][1]['ctaText'])->toBe('Order now');

    $schema = shop_schema();
    $schema['sections'][1]['props']['items'][0]['details'] = trim(str_repeat('This shirt is soft and easy to wear every single day. ', 12));
    $html = app(ExportService::class)->itemHtml($schema, 'services', 'oxford-shirt', '/', fn ($a, $s) => "/{$a}/{$s}");
    preg_match('#<h2[^>]*>Details</h2>\s*<div[^>]*>(.*?)</div>#s', $html, $m);
    expect(substr_count($m[1], '<p>'))->toBeGreaterThan(1);
});

it('keeps paragraph breaks in AI-written details', function () {
    $section = app(\App\Services\Ai\SchemaNormalizer::class)->normalizeSection(['id' => 's', 'type' => 'services', 'props' => ['heading' => 'Shop', 'itemPages' => true, 'items' => [
        ['title' => 'A', 'description' => "One\nline", 'details' => "First   paragraph.\n\nSecond\nparagraph."],
    ]]], shop_schema()['theme']);

    expect($section['props']['items'][0]['details'])->toBe("First paragraph.\n\nSecond paragraph.")
        ->and($section['props']['items'][0]['description'])->toBe('One line');
});

it('packs uploaded logos and photos into the ZIP and points every page at them', function () {
    \Illuminate\Support\Facades\Storage::fake('public');
    \Illuminate\Support\Facades\Storage::disk('public')->put('assets/9/logo.svg', '<svg/>');
    \Illuminate\Support\Facades\Storage::disk('public')->put('assets/9/shirt.jpg', 'jpg');

    $schema = shop_schema();
    $schema['sections'][0]['props']['logo'] = '/storage/assets/9/logo.svg';
    $schema['sections'][1]['props']['items'][0]['photos'] = ['/storage/assets/9/shirt.jpg'];

    $zip = new ZipArchive();
    $zip->open($path = app(ExportService::class)->toZip($schema));
    $index = $zip->getFromName('index.html');
    $page = $zip->getFromName('services/oxford-shirt.html');

    expect($zip->getFromName('media/assets/9/logo.svg'))->toBe('<svg/>')
        ->and($zip->getFromName('media/assets/9/shirt.jpg'))->toBe('jpg')
        ->and($index)->toContain('src="media/assets/9/logo.svg"')->not->toContain('/storage/')
        ->and($page)->toContain('src="../media/assets/9/logo.svg"')
        ->toContain('data-thumb="../media/assets/9/shirt.jpg"')
        ->not->toContain('/storage/')
        ->and($zip->getFromName('README.txt'))->toContain('media/ holds your uploaded logo');
    $zip->close();
    @unlink($path);
});

it('makes the contact form actually send (email app or WhatsApp)', function () {
    $html = app(ExportService::class)->toHtml(shop_schema());
    expect($html)->toContain('data-contact-form data-email="hi@forma.com"')
        ->toContain("window.location.href = 'mailto:'");
});
