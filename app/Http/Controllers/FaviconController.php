<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\Logo\FaviconService;
use Illuminate\Http\Request;

class FaviconController extends Controller
{
    /** Build the favicon set from one of the project's images (normally the navbar logo). */
    public function store(Request $request, Project $project, FaviconService $favicons)
    {
        abort_if($project->user_id !== $request->user()->id, 403);
        $data = $request->validate([
            'url' => 'required|string|max:500',
            'followsLogo' => 'sometimes|boolean',
        ]);

        return response()->json([
            'favicon' => $favicons->fromAssetUrl($project, $data['url'], $data['followsLogo'] ?? true),
        ]);
    }

    /** Back to the automatic initials icon. */
    public function destroy(Request $request, Project $project, FaviconService $favicons)
    {
        abort_if($project->user_id !== $request->user()->id, 403);
        $favicons->remove($project);

        return response()->json(['favicon' => null]);
    }
}
