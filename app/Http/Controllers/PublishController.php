<?php

namespace App\Http\Controllers;

use App\Models\Deployment;
use App\Models\Project;
use Illuminate\Http\Request;

class PublishController extends Controller
{
    public function publish(Request $request, Project $project)
    {
        if ($project->user_id !== $request->user()->id) {
            abort(403);
        }

        $project->update([
            'status' => 'published',
            'published_url' => url("/s/{$project->slug}"),
        ]);

        Deployment::create([
            'project_id' => $project->id,
            'provider' => 'subdomain',
            'target_url' => $project->published_url,
            'status' => 'live',
        ]);

        return back()->with('success', 'Site published!');
    }

    public function unpublish(Request $request, Project $project)
    {
        if ($project->user_id !== $request->user()->id) {
            abort(403);
        }

        $project->update([
            'status' => 'draft',
            'published_url' => null,
        ]);

        return back()->with('success', 'Site unpublished.');
    }
}
