<?php

namespace App\Http\Controllers;

use App\Models\AnalyticsEvent;
use App\Models\Page;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $projects = $user->projects()
            ->with('homePage:id,project_id,schema')
            ->withCount('pages')
            ->latest('updated_at')
            ->get();

        // Page views over the last 30 days, one grouped query for all projects.
        $views = AnalyticsEvent::whereIn('project_id', $projects->pluck('id'))
            ->where('event', 'pageview')
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('project_id, COUNT(*) as views')
            ->groupBy('project_id')
            ->pluck('views', 'project_id');

        // Orders per website (all + new), one grouped query.
        $orders = \App\Models\Order::whereIn('project_id', $projects->pluck('id'))
            ->selectRaw("project_id, COUNT(*) as total, SUM(CASE WHEN status = 'new' THEN 1 ELSE 0 END) as fresh")
            ->groupBy('project_id')
            ->get()->keyBy('project_id');

        return Inertia::render('Dashboard', [
            'projects' => $projects->map(fn (Project $project) => [
                'id' => $project->id,
                'name' => $project->name,
                'slug' => $project->slug,
                'description' => $project->description,
                'status' => $project->status,
                'pages_count' => $project->pages_count,
                'views' => (int) ($views[$project->id] ?? 0),
                'created_at' => $project->created_at,
                'updated_at' => $project->updated_at,
                'preview' => $this->previewFor($project),
                'shop' => \App\Services\Shop\Shop::enabled($project->homePage?->schema ?? []),
                'orders' => (int) ($orders[$project->id]->total ?? 0),
                'newOrders' => (int) ($orders[$project->id]->fresh ?? 0),
            ])->values(),
        ]);
    }

    /**
     * A lightweight summary of the home page (theme + first sections) so the
     * dashboard can draw a miniature of each site without loading full schemas.
     */
    private function previewFor(Project $project): array
    {
        $schema = $project->homePage?->schema ?? [];
        $theme = $schema['theme'] ?? [];
        $sections = collect($schema['sections'] ?? []);
        $hero = $sections->firstWhere('type', 'hero')['props'] ?? [];
        $navbar = $sections->firstWhere('type', 'navbar')['props'] ?? [];

        return [
            'primary' => $theme['primaryColor'] ?? '#6366f1',
            'background' => $theme['backgroundColor'] ?? '#ffffff',
            'text' => $theme['textColor'] ?? '#0f172a',
            'font' => $theme['fontHeading'] ?? null,
            'brand' => is_string($navbar['brand'] ?? null) ? $navbar['brand'] : null,
            'heading' => is_string($hero['heading'] ?? null) ? Str::limit($hero['heading'], 80) : null,
            'cta' => is_string($hero['ctaText'] ?? null) ? $hero['ctaText'] : null,
            'image' => is_string($hero['image'] ?? null) ? $hero['image'] : null,
            'sections' => $sections->pluck('type')->filter()->values()->all(),
        ];
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
