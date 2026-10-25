<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\PageVersion;
use App\Models\Project;
use App\Services\PublishedSiteCache;
use App\Services\SchemaValidator;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public function autosave(Request $request, Project $project, Page $page, SchemaValidator $validator)
    {
        if ($project->user_id !== $request->user()->id) {
            abort(403);
        }
        // The page id comes from the URL too: it must be one of this project's pages.
        abort_unless($page->project_id === $project->id, 404);

        $request->validate([
            'schema' => 'required|array',
        ]);

        try {
            $validator->validate($request->schema);
        } catch (\InvalidArgumentException $e) {
            // A broken schema would take the published site down with a 500.
            return response()->json(['saved' => false, 'message' => 'That change could not be saved: ' . $e->getMessage()], 422);
        }

        $this->snapshot($page, $request->user()->id);
        $page->update(['schema' => $request->schema]);
        PublishedSiteCache::forget($project);

        return response()->json(['saved' => true, 'updated_at' => $page->updated_at->toIso8601String()]);
    }

    /** Minutes of editing between automatic restore points. */
    public const SNAPSHOT_MINUTES = 10;

    /** Automatic restore points kept per page (manual/AI versions are never pruned). */
    public const SNAPSHOT_KEEP = 30;

    /**
     * Before overwriting the page, keep the previous content as an "autosave" version when the
     * newest version is older than SNAPSHOT_MINUTES — so hand edits can be restored, not only
     * AI generations, without recording every keystroke.
     */
    private function snapshot(Page $page, int $userId): void
    {
        $latest = PageVersion::where('page_id', $page->id)->max('created_at');
        if ($latest && \Illuminate\Support\Carbon::parse($latest)->gt(now()->subMinutes(self::SNAPSHOT_MINUTES))) {
            return;
        }
        if (empty($page->schema['sections'])) {
            return; // nothing worth restoring
        }

        PageVersion::create(['page_id' => $page->id, 'schema' => $page->schema, 'label' => 'autosave', 'created_by' => $userId, 'created_at' => now()]);

        PageVersion::where('page_id', $page->id)->where('label', 'autosave')
            ->orderByDesc('created_at')->skip(self::SNAPSHOT_KEEP)->take(1000)->pluck('id')
            ->whenNotEmpty(fn ($ids) => PageVersion::whereIn('id', $ids)->delete());
    }
}
