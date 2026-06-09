<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $projects = $request->user()
            ->projects()
            ->latest()
            ->get();

        return Inertia::render('Dashboard', [
            'projects' => $projects,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        $slug = Str::slug($request->name) . '-' . Str::random(6);

        $project = Project::create([
            'user_id' => $request->user()->id,
            'name' => $request->name,
            'slug' => $slug,
            'description' => $request->description,
            'status' => 'draft',
        ]);

        // Create a default home page with empty schema
        Page::create([
            'project_id' => $project->id,
            'title' => 'Home',
            'path' => '/',
            'schema' => [
                'version' => 1,
                'meta' => ['title' => $request->name, 'description' => $request->description ?? '', 'language' => 'en'],
                'theme' => [
                    'primaryColor' => '#2563eb',
                    'secondaryColor' => '#0f172a',
                    'backgroundColor' => '#ffffff',
                    'textColor' => '#0f172a',
                    'fontHeading' => 'Poppins',
                    'fontBody' => 'Inter',
                    'radius' => 'lg',
                    'mode' => 'light',
                ],
                'sections' => [],
            ],
            'is_home' => true,
        ]);

        return redirect()->route('editor.show', $project);
    }

    public function destroy(Request $request, Project $project)
    {
        if ($project->user_id !== $request->user()->id) {
            abort(403);
        }

        $project->delete();

        return redirect()->route('dashboard');
    }
}
