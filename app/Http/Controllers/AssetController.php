<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class AssetController extends Controller
{
    public function index(Project $project)
    {
        Gate::authorize('view', $project);

        return $project->assets()
            ->where('type', 'image')
            ->orderByDesc('created_at')
            ->get(['id', 'url', 'meta', 'created_at']);
    }

    public function store(Request $request, Project $project)
    {
        Gate::authorize('update', $project);

        $request->validate([
            'image' => ['required', 'image', 'max:5120'],
        ]);

        $file = $request->file('image');
        $path = $file->store("assets/{$project->id}", 'public');

        $asset = $project->assets()->create([
            'url' => '/storage/' . $path,
            'type' => 'image',
            'meta' => [
                'original_name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'mime' => $file->getMimeType(),
            ],
        ]);

        return response()->json([
            'id' => $asset->id,
            'url' => $asset->url,
            'meta' => $asset->meta,
        ]);
    }

    public function destroy(Project $project, Asset $asset)
    {
        Gate::authorize('update', $project);

        abort_if($asset->project_id !== $project->id, 403);

        // Delete file from disk
        $diskPath = str_replace('/storage/', '', $asset->url);
        Storage::disk('public')->delete($diskPath);

        $asset->delete();

        return response()->json(['ok' => true]);
    }
}
