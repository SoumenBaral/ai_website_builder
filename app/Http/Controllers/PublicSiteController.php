<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\ExportService;

class PublicSiteController extends Controller
{
    public function show(string $slug, ExportService $export)
    {
        $project = Project::where('slug', $slug)
            ->where('status', 'published')
            ->firstOrFail();

        $page = $project->homePage;
        if (!$page) {
            abort(404);
        }

        $beacon = '<script>
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

        return response($export->toHtml($page->schema, $beacon))
            ->header('Content-Type', 'text/html');
    }
}
