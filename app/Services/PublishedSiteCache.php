<?php

namespace App\Services;

use App\Models\Project;
use Closure;
use Illuminate\Support\Facades\Cache;

/**
 * Rendered HTML of published sites, so a visit costs a cache read instead of
 * two queries and fifteen Blade renders.
 *
 * Keys carry the home page's updated_at (autosave bumps it) and a per-project
 * version number that forget() increments (publish, unpublish, settings, orders
 * UI changes…), so stale pages are never served and nothing needs tag support.
 */
final class PublishedSiteCache
{
    public const TTL_SECONDS = 86400;

    public static function remember(Project $project, string $key, Closure $render): string
    {
        return Cache::remember(self::key($project, $key), self::TTL_SECONDS, $render);
    }

    public static function forget(Project $project): void
    {
        $versionKey = self::versionKey($project);
        Cache::forever($versionKey, (int) Cache::get($versionKey, 1) + 1);
    }

    public static function key(Project $project, string $key): string
    {
        $version = (int) Cache::get(self::versionKey($project), 1);
        $stamp = $project->homePage?->updated_at?->getTimestamp() ?? 0;

        return "site:{$project->id}:{$version}:{$stamp}:" . md5($key);
    }

    private static function versionKey(Project $project): string
    {
        return "site:version:{$project->id}";
    }
}
