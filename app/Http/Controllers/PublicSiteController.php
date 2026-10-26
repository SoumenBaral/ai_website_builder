<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\ExportService;
use App\Services\ItemPages;
use App\Services\PublishedSiteCache;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Serves published sites at /s/{slug}. These routes run without a session or
 * CSRF middleware (see routes/web.php): visitors get cached HTML with public
 * cache headers and an ETag, so repeat visits and CDNs cost nothing.
 */
class PublicSiteController extends Controller
{
    public function show(Request $request, string $slug, ExportService $export)
    {
        [$project, $page] = $this->site($slug);

        $html = PublishedSiteCache::remember($project, 'home', fn () => $export->toHtml(
            $page->schema,
            $this->beacon($project),
            $project->favicon,
            null,
            $this->itemUrl($project),
            $this->shop($project),
            route('site.show', $project->slug),
        ));

        return $this->respond($request, $html, $project);
    }

    /** A card's details page: /s/{slug}/{section}/{item} */
    public function item(Request $request, string $slug, string $section, string $item, ExportService $export)
    {
        [$project, $page] = $this->site($slug);

        $html = PublishedSiteCache::remember($project, "item:{$section}/{$item}", fn () => $export->itemHtml(
            $page->schema, $section, $item, route('site.show', $project->slug), $this->itemUrl($project), $this->beacon($project), $project->favicon, null, $this->shop($project)
        ) ?? '');
        abort_if($html === '', 404);

        return $this->respond($request, $html, $project);
    }

    /** /s/{slug}/sitemap.xml — the home page and every details page. */
    public function sitemap(string $slug)
    {
        [$project, $page] = $this->site($slug);

        $xml = PublishedSiteCache::remember($project, 'sitemap', function () use ($project, $page) {
            $modified = $page->updated_at?->toAtomString();
            $urls = [['loc' => route('site.show', $project->slug), 'priority' => '1.0']];
            foreach (ItemPages::map($page->schema) as $group) {
                foreach ($group['slugs'] as $itemSlug) {
                    $urls[] = ['loc' => route('site.item', [$project->slug, $group['anchor'], $itemSlug]), 'priority' => '0.7'];
                }
            }
            $out = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
            foreach ($urls as $u) {
                $out .= '  <url><loc>' . e($u['loc']) . '</loc>' . ($modified ? "<lastmod>{$modified}</lastmod>" : '') . "<priority>{$u['priority']}</priority></url>\n";
            }

            return $out . '</urlset>';
        });

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=utf-8', 'Cache-Control' => 'public, max-age=3600']);
    }

    /** /s/{slug}/robots.txt — allow everything, point at the sitemap. */
    public function robots(string $slug)
    {
        [$project] = $this->site($slug);

        return response("User-agent: *\nAllow: /\nSitemap: " . route('site.sitemap', $project->slug) . "\n", 200, ['Content-Type' => 'text/plain; charset=utf-8', 'Cache-Control' => 'public, max-age=86400']);
    }

    /** Public, revalidating cache headers; 304 when the visitor's copy is current. */
    private function respond(Request $request, string $html, Project $project): Response
    {
        $etag = '"' . md5($html) . '"';
        $headers = [
            'Content-Type' => 'text/html; charset=utf-8',
            // Revalidate on every visit (a 304 via the ETag costs almost nothing) so an owner who just
            // edited the site never sees a stale copy; the server-side page cache does the heavy lifting.
            'Cache-Control' => 'public, no-cache',
            'ETag' => $etag,
            'Last-Modified' => ($project->homePage?->updated_at ?? $project->updated_at)->toRfc7231String(),
            'Vary' => 'Accept-Encoding',
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
        ];

        if (trim((string) $request->header('If-None-Match')) === $etag) {
            return response('', 304, $headers);
        }

        return response($html, 200, $headers);
    }

    private function site(string $slug): array
    {
        $project = Project::where('slug', $slug)
            ->where('status', 'published')
            ->with('homePage')
            ->firstOrFail();

        $page = $project->homePage;
        abort_if(!$page, 404);

        return [$project, $page];
    }

    private function itemUrl(Project $project): \Closure
    {
        return fn (string $anchor, string $slug) => route('site.item', [$project->slug, $anchor, $slug]);
    }

    private function shop(Project $project): array
    {
        return ['mode' => 'live', 'orderUrl' => route('site.order', $project->slug), 'key' => $project->slug];
    }

    /** Page-view beacon: sendBeacon survives navigation and skips obvious bots. */
    private function beacon(Project $project): string
    {
        return '<script>
(function(){
    if (/bot|crawl|spider|slurp|headless|lighthouse/i.test(navigator.userAgent)) return;
    var body = JSON.stringify({
        project_id: ' . $project->id . ',
        event: "pageview",
        path: location.pathname.slice(0, 255),
        referrer: document.referrer.slice(0, 255),
        device: /Mobile/.test(navigator.userAgent) ? "mobile" : "desktop"
    });
    var sent = navigator.sendBeacon && navigator.sendBeacon("/track", new Blob([body], {type: "application/json"}));
    if (!sent) fetch("/track", {method: "POST", headers: {"Content-Type": "application/json"}, body: body, keepalive: true}).catch(function(){});
})();
</script>';
    }
}
