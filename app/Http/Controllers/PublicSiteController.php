<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\ExportService;

class PublicSiteController extends Controller
{
    public function show(string $slug, ExportService $export)
    {
        [$project, $page] = $this->site($slug);

        return response($export->toHtml($page->schema, $this->beacon($project), $project->favicon, null, $this->itemUrl($project), $this->shop($project)))
            ->header('Content-Type', 'text/html');
    }

    /** A card's details page: /s/{slug}/{section}/{item} */
    public function item(string $slug, string $section, string $item, ExportService $export)
    {
        [$project, $page] = $this->site($slug);

        $html = $export->itemHtml($page->schema, $section, $item, route('site.show', $project->slug), $this->itemUrl($project), $this->beacon($project), $project->favicon, null, $this->shop($project));
        abort_if($html === null, 404);

        return response($html)->header('Content-Type', 'text/html');
    }

    private function site(string $slug): array
    {
        $project = Project::where('slug', $slug)
            ->where('status', 'published')
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

    private function beacon(Project $project): string
    {
        return '<script>
fetch("/track", {
    method: "POST",
    headers: {"Content-Type": "application/json"},
    body: JSON.stringify({
        project_id: ' . $project->id . ',
        event: "pageview",
        path: location.pathname,
        referrer: document.referrer,
        device: /Mobile/.test(navigator.userAgent) ? "mobile" : "desktop"
    })
}).catch(function(){});
</script>';
    }
}
