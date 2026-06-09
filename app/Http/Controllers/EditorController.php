<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EditorController extends Controller
{
    public function show(Request $request, Project $project)
    {
        if ($project->user_id !== $request->user()->id) {
            abort(403);
        }

        $page = $project->homePage;

        return Inertia::render('Editor', [
            'project' => $project,
            'page' => $page,
        ]);
    }
}
