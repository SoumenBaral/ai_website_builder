<?php

use App\Models\AnalyticsEvent;
use App\Models\Page;
use App\Models\Project;
use App\Models\User;
use App\Services\ExportService;
use App\Services\PublishedSiteCache;
use App\Support\SiteHtml;
use Illuminate\Support\Facades\Cache;

function published_site(array $sectionOverrides = []): array
{
    $user = User::factory()->create();
    $project = Project::create(['user_id' => $user->id, 'name' => 'Forma', 'slug' => 'forma-' . uniqid(), 'status' => 'published']);
    $page = Page::create(['project_id' => $project->id, 'title' => 'Home', 'path' => '/', 'is_home' => true, 'schema' => [
        'version' => 1, 'meta' => ['title' => 'Forma Men', 'description' => 'Crisp staples.', 'language' => 'en'],
        'theme' => ['primaryColor' => '#1e293b', 'secondaryColor' => '#0f172a', 'backgroundColor' => '#ffffff', 'textColor' => '#111827', 'fontHeading' => 'Inter', 'fontBody' => 'Inter', 'radius' => 'md', 'mode' => 'light'],
        'sections' => $sectionOverrides ?: [
            ['id' => 'n', 'type' => 'navbar', 'props' => ['brand' => 'Forma', 'links' => [['label' => 'About', 'href' => '#about']], 'ctaText' => 'Shop', 'ctaHref' => '#services']],
            ['id' => 'h', 'type' => 'hero', 'props' => ['heading' => 'Dress well', 'subheading' => 'Every day.', 'ctaText' => 'Shop', 'ctaHref' => '#services', 'image' => 'https://images.unsplash.com/photo-hero?w=1600&q=80&auto=format&fit=crop', 'layout' => 'center']],
            ['id' => 'a', 'type' => 'about', 'props' => ['heading' => 'Our story', 'body' => "We started in 2020.\n\nWe make shirts.", 'image' => 'https://images.pexels.com/photos/12/pexels-photo-12.jpeg?auto=compress&w=900']],
            ['id' => 'f', 'type' => 'footer', 'props' => ['text' => '© Forma']],
        ],
    ]]);

    return [$user, $project, $page];
}

/* ------------------------------------------------------------ security */

it('refuses to autosave a page that belongs to another project', function () {
    [$user, $project] = published_site();
    [$other, $otherProject, $otherPage] = published_site();

    $this->actingAs($user)
        ->postJson(route('pages.autosave', [$project, $otherPage]), ['schema' => $otherPage->schema])
        ->assertNotFound();

    expect($otherPage->fresh()->schema['meta']['title'])->toBe('Forma Men');
});

it('rejects an autosave whose schema would break the published site', function () {
    [$user, $project, $page] = published_site();
    $broken = $page->schema;
    unset($broken['theme']);

    $this->actingAs($user)
        ->postJson(route('pages.autosave', [$project, $page]), ['schema' => $broken])
        ->assertStatus(422)
        ->assertJsonPath('saved', false);

    expect($page->fresh()->schema)->toHaveKey('theme');
    $this->get(route('site.show', $project->slug))->assertOk(); // still serves
});

it('escapes user text and neutralises script links on published pages', function () {
    [, $project] = published_site([
        ['id' => 'n', 'type' => 'navbar', 'props' => ['brand' => 'Forma', 'links' => [['label' => 'Evil', 'href' => 'javascript:alert(1)']], 'ctaText' => 'Go', 'ctaHref' => " JavaScript:alert(2)"]],
        ['id' => 'h', 'type' => 'hero', 'props' => ['heading' => 'Hi', 'ctaText' => 'Go', 'ctaHref' => 'data:text/html,evil']],
        ['id' => 'a', 'type' => 'about', 'props' => ['heading' => 'About', 'body' => '<img src=x onerror=alert(1)>']],
        ['id' => 'c', 'type' => 'custom', 'props' => ['elements' => [['type' => 'icon', 'emoji' => '<script>alert(3)</script>'], ['type' => 'button', 'text' => 'B', 'href' => 'vbscript:x']]]],
        ['id' => 'f', 'type' => 'footer', 'props' => ['text' => '<b onmouseover=alert(4)>x</b>']],
    ]);

    $html = $this->get(route('site.show', $project->slug))->assertOk()->getContent();

    expect($html)->not->toContain('javascript:')->not->toContain('JavaScript:')->not->toContain('data:text/html')->not->toContain('vbscript:')
        ->not->toContain('<img src=x')->not->toContain('<script>alert(3)')->not->toContain('<b onmouseover')
        ->toContain('&lt;img src=x onerror=alert(1)&gt;')
        ->toContain('&lt;script&gt;alert(3)&lt;/script&gt;');
});

it('renders the about body as escaped paragraphs', function () {
    [, $project] = published_site();
    $html = $this->get(route('site.show', $project->slug))->getContent();

    expect($html)->toContain('<p>We started in 2020.</p>')->toContain('<p>We make shirts.</p>');
    expect(SiteHtml::href('javascript:alert(1)'))->toBe('#')
        ->and(SiteHtml::href("\tjava\nscript:alert(1)"))->toBe('#')
        ->and(SiteHtml::href('https://example.com/a?b=1'))->toBe('https://example.com/a?b=1')
        ->and(SiteHtml::href('#contact'))->toBe('#contact')
        ->and(SiteHtml::href(''))->toBe('#');
});

it('rate limits and bounds the analytics beacon', function () {
    [, $project] = published_site();

    $this->postJson('/track', ['project_id' => $project->id, 'event' => 'pageview', 'path' => str_repeat('a', 256)])->assertStatus(422);
    $this->postJson('/track', ['project_id' => $project->id, 'event' => 'pageview', 'path' => '/'])->assertOk();
    expect(AnalyticsEvent::count())->toBe(1);
    expect(collect(app('router')->getRoutes()->getByName('analytics.track')->middleware())->contains('throttle:60,1'))->toBeTrue();
});

/* ------------------------------------------------------------ serving & caching */

it('serves published pages without a session, with public cache headers and an ETag', function () {
    [, $project] = published_site();

    $res = $this->get(route('site.show', $project->slug))->assertOk();
    $res->assertHeader('Cache-Control', 'max-age=300, public, s-maxage=86400, stale-while-revalidate=86400');
    expect($res->headers->get('ETag'))->toStartWith('"')
        ->and($res->headers->get('Last-Modified'))->not->toBeEmpty()
        ->and($res->headers->getCookies())->toBe([]);          // no session / XSRF cookies

    // A visitor with a current copy gets 304 and no body.
    $this->get(route('site.show', $project->slug), ['If-None-Match' => $res->headers->get('ETag')])
        ->assertStatus(304);
});

it('caches the rendered page and drops it when the page is saved', function () {
    [$user, $project, $page] = published_site();
    $url = route('site.show', $project->slug);

    $first = $this->get($url)->getContent();
    expect(Cache::has(PublishedSiteCache::key($project->fresh(), 'home')))->toBeTrue();

    // Editing the title in the database alone is invisible — the cache answers…
    Page::whereKey($page->id)->update(['schema' => array_replace_recursive($page->schema, ['meta' => ['title' => 'Changed']])]);
    expect($this->get($url)->getContent())->toBe($first);

    // …until a real save (autosave bumps updated_at and the version).
    $schema = $page->fresh()->schema;
    $schema['meta']['title'] = 'Saved title';
    $this->actingAs($user)->postJson(route('pages.autosave', [$project, $page]), ['schema' => $schema])->assertOk();
    expect($this->get($url)->getContent())->toContain('<title>Saved title</title>');
});

it('links the hashed runtime files instead of inlining them, and inlines for ZIP downloads', function () {
    [, $project, $page] = published_site([
        ['id' => 'h', 'type' => 'hero', 'props' => ['heading' => 'Hi', 'animation' => 'fade-up']],
        ['id' => 's', 'type' => 'services', 'props' => ['heading' => 'Shop', 'itemPages' => true, 'items' => [['title' => 'Shirt', 'price' => '$45', 'description' => 'Crisp.']]]],
    ]);

    $html = $this->get(route('site.show', $project->slug))->getContent();
    expect($html)->toMatch('#<script defer src="/site/anim\.js\?v=[a-f0-9]{10}">#')
        ->toMatch('#<link rel="stylesheet" href="/site/anim\.css\?v=[a-f0-9]{10}">#')
        ->not->toContain('IntersectionObserver')               // not inlined
        ->and(strlen($html))->toBeLessThan(40000);

    $zip = app(ExportService::class)->toZip($page->schema, $project);
    $archive = new ZipArchive();
    $archive->open($zip);
    $index = $archive->getFromName('index.html');
    expect($index)->toContain('IntersectionObserver')->not->toContain('/site/anim.js');
    @unlink($zip);
});

it('ships responsive, prioritised images and SEO tags', function () {
    [, $project] = published_site();
    $html = $this->get(route('site.show', $project->slug))->getContent();

    expect($html)
        // hero: a real <img>, fetched first, preloaded, with widths for phones
        ->toContain('fetchpriority="high"')
        ->toMatch('#<link rel="preload" as="image" href="https://images\.unsplash\.com/photo-hero\?w=1600[^"]*" imagesrcset="[^"]*w=480[^"]*" imagesizes="100vw" fetchpriority="high">#')
        ->toContain('photo-hero?w=480&amp;q=80&amp;auto=format&amp;fit=crop 480w')
        // about: Pexels URL gets a srcset too, and a stable box
        ->toContain('pexels-photo-12.jpeg?auto=compress&amp;w=480 480w')
        ->toContain('aspect-ratio: 4 / 3')
        // fonts: one family, non-blocking
        ->toContain('rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&amp;display=swap"')
        ->and(substr_count($html, 'family=Inter'))->toBe(2) // preload + noscript, not once per role
        // SEO
        ->and($html)->toContain('<link rel="canonical" href="' . route('site.show', $project->slug) . '">')
        ->toContain('<meta property="og:title" content="Forma Men">')
        ->toContain('<meta property="og:image" content="https://images.unsplash.com/photo-hero?w=1600')
        ->toContain('"@type":"WebSite"');

    expect(SiteHtml::srcset('https://example.com/photo.jpg'))->toBeNull();
});

it('serves a sitemap and robots.txt per site', function () {
    [, $project] = published_site([
        ['id' => 'h', 'type' => 'hero', 'props' => ['heading' => 'Hi']],
        ['id' => 's', 'type' => 'services', 'props' => ['heading' => 'Shop', 'itemPages' => true, 'items' => [['title' => 'Oxford Shirt', 'description' => 'Crisp.', 'details' => 'Long.']]]],
    ]);

    $xml = $this->get(route('site.sitemap', $project->slug))->assertOk()->assertHeader('Content-Type', 'application/xml; charset=utf-8')->getContent();
    expect($xml)->toContain('<loc>' . route('site.show', $project->slug) . '</loc>')
        ->toContain('<loc>' . route('site.item', [$project->slug, 'services', 'oxford-shirt']) . '</loc>');

    $this->get(route('site.robots', $project->slug))->assertOk()
        ->assertSee('Sitemap: ' . route('site.sitemap', $project->slug), false);

    $this->get(route('site.sitemap', 'nope'))->assertNotFound();
});

it('never lets theme values inject CSS', function () {
    [, $project, $page] = published_site();
    $schema = $page->schema;
    $schema['theme']['primaryColor'] = 'red;background:url(https://evil.test/x)';
    $schema['theme']['fontHeading'] = "Inter', serif; } body { display: none } .x { font-family: '";
    Page::whereKey($page->id)->update(['schema' => $schema]);
    PublishedSiteCache::forget($project);

    $html = $this->get(route('site.show', $project->slug))->getContent();
    expect($html)->not->toContain('evil.test')->not->toContain('display: none')
        ->toContain('--primary: #2563eb')->toContain("--font-heading: 'Inter', sans-serif");
});

it('keeps automatic restore points while editing, without recording every keystroke', function () {
    [$user, $project, $page] = published_site();
    $save = function (string $title) use ($user, $project, $page) {
        $schema = $page->fresh()->schema;
        $schema['meta']['title'] = $title;
        $this->actingAs($user)->postJson(route('pages.autosave', [$project, $page]), ['schema' => $schema])->assertOk();
    };

    $save('One');
    $save('Two');
    $save('Three');
    expect(\App\Models\PageVersion::where('page_id', $page->id)->count())->toBe(1) // first save snapshots the original…
        ->and(\App\Models\PageVersion::first()->label)->toBe('autosave')
        ->and(\App\Models\PageVersion::first()->schema['meta']['title'])->toBe('Forma Men');

    $this->travel(11)->minutes();
    $save('Four');
    expect(\App\Models\PageVersion::where('page_id', $page->id)->count())->toBe(2) // …the next one ten minutes later
        ->and(\App\Models\PageVersion::latest('id')->first()->schema['meta']['title'])->toBe('Three');

    // Old automatic snapshots are pruned, manual ones never.
    \App\Models\PageVersion::create(['page_id' => $page->id, 'schema' => $page->schema, 'label' => 'AI generation', 'created_at' => now()->subYear()]);
    foreach (range(1, 40) as $i) {
        \App\Models\PageVersion::create(['page_id' => $page->id, 'schema' => $page->schema, 'label' => 'autosave', 'created_at' => now()->subDays($i)]);
    }
    $this->travel(11)->minutes();
    $save('Five');
    expect(\App\Models\PageVersion::where('page_id', $page->id)->where('label', 'autosave')->count())->toBe(\App\Http\Controllers\PageController::SNAPSHOT_KEEP)
        ->and(\App\Models\PageVersion::where('label', 'AI generation')->exists())->toBeTrue();
});
