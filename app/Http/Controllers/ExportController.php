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

        $zipPath = $export->toZip($page->schema);

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

        return response($export->toHtml($page->schema))
            ->header('Content-Type', 'text/html');
    }
}
