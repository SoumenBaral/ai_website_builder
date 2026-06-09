<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\PageVersion;
use App\Models\Project;
use Illuminate\Http\Request;

class VersionController extends Controller
{
    public function index(Request $request, Project $project)
    {
        if ($project->user_id !== $request->user()->id) {
            abort(403);
        }

        $page = $project->homePage;
        if (!$page) {
            return response()->json(['versions' => []]);
        }

        $versions = $page->versions()
            ->orderByDesc('created_at')
            ->limit(50)
            ->get(['id', 'label', 'created_by', 'created_at']);

        return response()->json(['versions' => $versions]);
    }

    public function restore(Request $request, PageVersion $version)
    {
        $page = $version->page;
        $project = $page->project;

        if ($project->user_id !== $request->user()->id) {
            abort(403);
        }

        // Save current as a version before restoring
        PageVersion::create([
            'page_id' => $page->id,
            'schema' => $page->schema,
            'label' => 'Before restore',
            'created_by' => $request->user()->id,
            'created_at' => now(),
        ]);

        $page->update(['schema' => $version->schema]);

        return response()->json(['schema' => $version->schema]);
    }
}
