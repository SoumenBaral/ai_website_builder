<?php

namespace App\Http\Controllers;

use App\Models\AnalyticsEvent;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AnalyticsController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'project_id' => 'required|exists:projects,id',
            'event' => 'required|string|in:pageview,click',
            'path' => 'nullable|string|max:500',
            'referrer' => 'nullable|string|max:500',
            'device' => 'nullable|string|max:50',
        ]);

        AnalyticsEvent::create([
            'project_id' => $request->project_id,
            'event' => $request->event,
            'path' => $request->path,
            'referrer' => $request->referrer,
            'device' => $request->device,
            'country' => null,
            'session_id' => $request->ip() . '-' . substr(md5($request->userAgent()), 0, 8),
            'created_at' => now(),
        ]);

        return response()->json(['ok' => true]);
    }

    public function dashboard(Request $request, Project $project)
    {
        if ($project->user_id !== $request->user()->id) {
            abort(403);
        }

        $thirtyDaysAgo = now()->subDays(30);

        $totalViews = AnalyticsEvent::where('project_id', $project->id)
            ->where('event', 'pageview')
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->count();

        $uniqueSessions = AnalyticsEvent::where('project_id', $project->id)
            ->where('event', 'pageview')
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->distinct('session_id')
            ->count('session_id');

        $dailyViews = AnalyticsEvent::where('project_id', $project->id)
            ->where('event', 'pageview')
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as views')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $topReferrers = AnalyticsEvent::where('project_id', $project->id)
            ->where('event', 'pageview')
            ->whereNotNull('referrer')
            ->where('referrer', '!=', '')
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->selectRaw('referrer, COUNT(*) as count')
            ->groupBy('referrer')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        $deviceBreakdown = AnalyticsEvent::where('project_id', $project->id)
            ->where('event', 'pageview')
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->selectRaw('device, COUNT(*) as count')
            ->groupBy('device')
            ->get();

        return Inertia::render('Analytics', [
            'project' => $project,
            'stats' => [
                'totalViews' => $totalViews,
                'uniqueSessions' => $uniqueSessions,
                'dailyViews' => $dailyViews,
                'topReferrers' => $topReferrers,
                'deviceBreakdown' => $deviceBreakdown,
            ],
        ]);
    }
}
