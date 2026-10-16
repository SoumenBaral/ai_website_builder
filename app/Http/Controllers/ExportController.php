<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\ExportService;
use Illuminate\Http\Request;

class ExportController extends Controller
{
    public function download(Request $request, Project $project, ExportService $export)
    {
        if ($project->user_id !== $request->user()->id) {
            abort(403);
        }

        $page = $project->homePage;
        if (!$page) {
            abort(404, 'No home page found.');
        }

        $zipPath = $export->toZip($page->schema, $project);

        return response()->download($zipPath, "{$project->slug}.zip")->deleteFileAfterSend();
    }

    public function preview(Request $request, Project $project, ExportService $export)
    {
        if ($project->user_id !== $request->user()->id) {
            abort(403);
        }

        $page = $project->homePage;
        if (!$page) {
            abort(404);
        }

        return response($export->toHtml($page->schema, null, $project->favicon, null, $this->previewItemUrl($project), ['mode' => 'preview', 'key' => 'preview-' . $project->slug]))
            ->header('Content-Type', 'text/html');
    }

    /** Preview a card's details page from the editor (uses the latest saved draft). */
    public function previewItem(Request $request, Project $project, string $section, string $item, ExportService $export)
    {
        if ($project->user_id !== $request->user()->id) {
            abort(403);
        }
        $page = $project->homePage;
        abort_if(!$page, 404);

        $html = $export->itemHtml($page->schema, $section, $item, route('export.preview', $project), $this->previewItemUrl($project), null, $project->favicon, null, ['mode' => 'preview', 'key' => 'preview-' . $project->slug]);
        abort_if($html === null, 404, 'This card has no details page yet — turn on "Details pages" for its section.');

        return response($html)->header('Content-Type', 'text/html');
    }

    /** Editor shortcut: open the details page of card #{index} in section {sectionId}. */
    public function previewCard(Request $request, Project $project, string $sectionId, int $index)
    {
        if ($project->user_id !== $request->user()->id) {
            abort(403);
        }
        $schema = $project->homePage?->schema;
        abort_if(!$schema, 404);

        $position = array_search($sectionId, array_column(array_values($schema['sections']), 'id'), true);
        $entry = $position === false ? null : (\App\Services\ItemPages::map($schema)[$position] ?? null);
        abort_if(!$entry || !isset($entry['slugs'][$index]), 404, 'This card has no details page yet — turn on "Details pages" for its section.');

        return redirect()->route('export.previewItem', [$project, $entry['anchor'], $entry['slugs'][$index]]);
    }

    private function previewItemUrl(Project $project): \Closure
    {
        return fn (string $anchor, string $slug) => route('export.previewItem', [$project, $anchor, $slug]);
    }
}
