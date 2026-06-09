<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\Project;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public function autosave(Request $request, Project $project, Page $page)
    {
        if ($project->user_id !== $request->user()->id) {
            abort(403);
        }

        $request->validate([
            'schema' => 'required|array',
        ]);

        $page->update(['schema' => $request->schema]);

        return response()->json(['saved' => true]);
    }
}
