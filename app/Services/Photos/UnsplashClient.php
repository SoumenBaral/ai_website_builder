<?php

namespace App\Services\Photos;

use Illuminate\Support\Facades\Http;

/**
 * Unsplash search API (https://unsplash.com/documentation). Photos are hot-linked
 * from images.unsplash.com, which the API guidelines require (with attribution).
 * Demo keys allow 50 requests/hour; the import pauses when the quota runs out.
 */
class UnsplashClient implements PhotoSource
{
    public function __construct(private string $accessKey) {}

    public function name(): string
    {
        return 'unsplash';
    }

    public function search(string $query, int $perPage = 20, int $page = 1, ?string $orientation = null): array
    {
        $params = ['query' => $query, 'per_page' => min(30, $perPage), 'page' => $page, 'content_filter' => 'high'];
        if ($orientation) {
            $params['orientation'] = $orientation === 'portrait' ? 'portrait' : 'landscape';
        }

        $response = Http::timeout(20)
            ->withHeaders(['Authorization' => 'Client-ID ' . $this->accessKey, 'Accept-Version' => 'v1'])
            ->get('https://api.unsplash.com/search/photos', $params);

        if (!$response->ok()) {
            throw new \RuntimeException("Unsplash HTTP {$response->status()}: " . mb_strimwidth($response->body(), 0, 200, '…'));
        }

        return [
            'photos' => array_map([$this, 'normalize'], $response->json('results', [])),
            'total' => (int) $response->json('total', 0),
            'remaining' => $response->header('X-Ratelimit-Remaining') !== '' ? (int) $response->header('X-Ratelimit-Remaining') : null,
        ];
    }

    private function normalize(array $p): array
    {
        $w = (int) ($p['width'] ?? 0);
        $h = (int) ($p['height'] ?? 0);
        $ratio = $h > 0 ? $w / $h : 1;
        $raw = $p['urls']['raw'] ?? '';
        // Keep the bare photo URL; placement sizes are added at render time.
        $base = preg_replace('/[?&](ixid|ixlib|w|h|q|auto|fit|crop)=[^&]*/', '', $raw);
        $base = rtrim(str_replace('?&', '?', $base), '?&');
        $tags = array_values(array_filter(array_map(fn ($t) => $t['title'] ?? null, $p['tags'] ?? [])));

        return [
            'source' => 'unsplash',
            'source_id' => (string) $p['id'],
            'url' => $base,
            'thumb_url' => $p['urls']['small'] ?? ($base . '?w=400&q=70&auto=format&fit=crop'),
            'width' => min(65535, $w),
            'height' => min(65535, $h),
            'orientation' => $ratio > 1.15 ? 'landscape' : ($ratio < 0.87 ? 'portrait' : 'square'),
            'alt' => mb_strimwidth(trim(implode(' · ', array_filter([(string) ($p['alt_description'] ?? $p['description'] ?? ''), $tags ? implode(', ', array_slice($tags, 0, 6)) : null]))), 0, 300, ''),
            'avg_color' => $p['color'] ?? null,
            'photographer' => $p['user']['name'] ?? null,
            'photographer_url' => isset($p['user']['links']['html']) ? $p['user']['links']['html'] . '?utm_source=vibeweb&utm_medium=referral' : null,
        ];
    }

    /** Sized Unsplash URL for a placement (same params the curated library uses). */
    public static function sized(string $base, string $placement): string
    {
        $params = match ($placement) {
            'wide' => 'w=1600&q=80&auto=format&fit=crop',
            'portrait' => 'w=400&h=400&q=80&auto=format&fit=crop&crop=faces',
            'gallery' => 'w=900&h=900&q=80&auto=format&fit=crop',
            'thumb' => 'w=400&h=300&q=60&auto=format&fit=crop',
            default => 'w=900&q=80&auto=format&fit=crop',
        };

        return $base . (str_contains($base, '?') ? '&' : '?') . $params;
    }
}
