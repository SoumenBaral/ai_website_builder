<?php

namespace App\Services\Photos;

use Illuminate\Support\Facades\Http;

/**
 * Minimal Pexels API client (https://www.pexels.com/api/documentation/).
 * Photos are hot-linked from images.pexels.com, which Pexels permits with attribution.
 */
class PexelsClient implements PhotoSource
{
    public function __construct(private string $apiKey) {}

    public function name(): string
    {
        return 'pexels';
    }

    /**
     * @return array{photos: array<int, array>, total: int, remaining: ?int}
     * @throws \RuntimeException on HTTP failure (message includes the status)
     */
    public function search(string $query, int $perPage = 20, int $page = 1, ?string $orientation = null): array
    {
        $params = ['query' => $query, 'per_page' => min(80, $perPage), 'page' => $page];
        if ($orientation) {
            $params['orientation'] = $orientation;
        }

        $response = Http::timeout(20)
            ->withHeaders(['Authorization' => $this->apiKey])
            ->get('https://api.pexels.com/v1/search', $params);

        if (!$response->ok()) {
            throw new \RuntimeException("Pexels HTTP {$response->status()}: " . mb_strimwidth($response->body(), 0, 200, '…'));
        }

        return [
            'photos' => array_map([$this, 'normalize'], $response->json('photos', [])),
            'total' => (int) $response->json('total_results', 0),
            'remaining' => $response->header('X-Ratelimit-Remaining') !== '' ? (int) $response->header('X-Ratelimit-Remaining') : null,
        ];
    }

    /** The subset of a Pexels photo we keep, in StockPhoto column names. */
    private function normalize(array $p): array
    {
        $w = (int) ($p['width'] ?? 0);
        $h = (int) ($p['height'] ?? 0);
        $ratio = $h > 0 ? $w / $h : 1;

        return [
            'source' => 'pexels',
            'source_id' => (string) $p['id'],
            'url' => $p['src']['original'] ?? '',
            'thumb_url' => $p['src']['medium'] ?? ($p['src']['small'] ?? ''),
            'width' => min(65535, $w),
            'height' => min(65535, $h),
            'orientation' => $ratio > 1.15 ? 'landscape' : ($ratio < 0.87 ? 'portrait' : 'square'),
            'alt' => mb_strimwidth(trim((string) ($p['alt'] ?? '')), 0, 300, ''),
            'avg_color' => $p['avg_color'] ?? null,
            'photographer' => $p['photographer'] ?? null,
            'photographer_url' => $p['photographer_url'] ?? null,
        ];
    }

    /**
     * A Pexels CDN URL sized for a placement. Pexels serves any size via query params.
     */
    public static function sized(string $original, string $placement): string
    {
        $params = match ($placement) {
            'wide' => 'auto=compress&cs=tinysrgb&w=1600',
            'portrait' => 'auto=compress&cs=tinysrgb&w=400&h=400&fit=crop',
            'gallery' => 'auto=compress&cs=tinysrgb&w=900&h=900&fit=crop',
            'thumb' => 'auto=compress&cs=tinysrgb&w=400&h=300&fit=crop',
            default => 'auto=compress&cs=tinysrgb&w=900',
        };

        return $original . (str_contains($original, '?') ? '&' : '?') . $params;
    }
}
