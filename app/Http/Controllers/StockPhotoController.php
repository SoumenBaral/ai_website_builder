<?php

namespace App\Http\Controllers;

use App\Services\Ai\ImageLibrary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * "Free photos" in the editor's image picker: the built-in library (hand-checked photos),
 * plus a live Unsplash search when UNSPLASH_ACCESS_KEY is set.
 */
class StockPhotoController extends Controller
{
    /** Picker categories → the tags that belong to them. */
    public const CATEGORIES = [
        'business' => ['label' => 'Business', 'tags' => ['office', 'business', 'team', 'meeting', 'startup', 'workspace', 'laptop', 'coworking', 'finance', 'corporate', 'collaboration']],
        'food' => ['label' => 'Food & drink', 'tags' => ['food', 'restaurant', 'coffee', 'cafe', 'dessert', 'bakery', 'drink', 'dish', 'pizza', 'cocktails', 'bar']],
        'fashion' => ['label' => 'Fashion', 'tags' => ['fashion', 'clothing', 'shoes', 'sneakers', 'jewelry', 'accessories', 'watch', 'bag', 'menswear', 'womenswear', 'shirt', 'jacket']],
        'beauty' => ['label' => 'Beauty & spa', 'tags' => ['beauty', 'cosmetics', 'makeup', 'skincare', 'salon', 'spa', 'barber', 'hair salon', 'wellness']],
        'health' => ['label' => 'Health', 'tags' => ['medical', 'doctor', 'healthcare', 'hospital', 'dentist', 'clinic', 'pharmacy']],
        'fitness' => ['label' => 'Fitness & sport', 'tags' => ['fitness', 'gym', 'yoga', 'sport', 'running', 'cricket', 'football', 'basketball']],
        'nature' => ['label' => 'Nature', 'tags' => ['nature', 'landscape', 'mountains', 'forest', 'beach', 'ocean', 'lake', 'desert', 'flowers', 'night sky']],
        'travel' => ['label' => 'Travel', 'tags' => ['travel', 'hotel', 'resort', 'airplane', 'vacation', 'adventure', 'city']],
        'home' => ['label' => 'Home & property', 'tags' => ['real estate', 'interior', 'home', 'house', 'living room', 'bedroom', 'kitchen', 'architecture', 'construction']],
        'tech' => ['label' => 'Tech', 'tags' => ['technology', 'code', 'developer', 'programming', 'gaming', 'smartphone', 'cybersecurity', 'analytics']],
        'education' => ['label' => 'Education', 'tags' => ['education', 'school', 'students', 'classroom', 'books', 'library', 'learning', 'kids']],
        'events' => ['label' => 'Events & music', 'tags' => ['event', 'wedding', 'concert', 'music', 'party', 'festival', 'guitar', 'drums']],
        'animals' => ['label' => 'Pets', 'tags' => ['dog', 'cat', 'pet', 'pets', 'animal']],
        'shop' => ['label' => 'Shopping', 'tags' => ['shopping', 'ecommerce', 'store', 'retail', 'grocery store', 'delivery', 'market', 'product']],
        'backgrounds' => ['label' => 'Backgrounds', 'tags' => ['abstract', 'gradient', 'background', 'texture', 'pattern', 'space', 'stars']],
        'people' => ['label' => 'People', 'tags' => []], // portraits
    ];

    public function index(Request $request)
    {
        $data = $request->validate([
            'q' => 'nullable|string|max:80',
            'category' => 'nullable|string|max:30',
        ]);
        $query = trim(mb_strtolower($data['q'] ?? ''));
        $category = $data['category'] ?? null;

        $photos = $this->library($query, $category);
        $unsplash = $query !== '' ? $this->unsplash($query) : [];

        return response()->json([
            'categories' => collect(self::CATEGORIES)->map(fn ($c, $key) => ['key' => $key, 'label' => $c['label']])->values(),
            // Live results first (more variety), then the curated library; no duplicates.
            'photos' => collect($unsplash)->concat($photos)->unique('id')->take(60)->values(),
            'live' => (bool) config('ai.unsplash_access_key'),
        ]);
    }

    private function library(string $query, ?string $category): array
    {
        $tokens = array_values(array_filter(preg_split('/[\s,]+/u', $query), fn ($t) => mb_strlen($t) > 1));
        // Search with synonyms too ("tailor" also finds "suit")
        foreach ($tokens as $t) {
            if (isset(ImageLibrary::SYNONYMS[$t])) {
                $tokens[] = ImageLibrary::SYNONYMS[$t];
            }
        }

        $all = ImageLibrary::PHOTOS;
        if ($category === 'people') {
            $all = collect(ImageLibrary::PORTRAITS)->map(fn ($who) => ["portrait of {$who}", $who, 'people', 'person', 'portrait'])->all();
        }

        $scored = [];
        foreach ($all as $id => $tags) {
            $text = ' ' . implode(' | ', $tags) . ' ';
            if ($category && $category !== 'people' && isset(self::CATEGORIES[$category])) {
                if (!array_intersect($tags, self::CATEGORIES[$category]['tags'])) {
                    continue;
                }
            }
            $score = 0;
            foreach ($tokens as $t) {
                if (in_array($t, $tags, true)) {
                    $score += 3;
                } elseif (str_contains($text, $t) || str_contains($text, rtrim($t, 's'))) {
                    $score += 1;
                }
            }
            if ($tokens && !$score) {
                continue;
            }
            $scored[] = ['id' => $id, 'score' => $score, 'tags' => array_slice($tags, 0, 3)];
        }
        usort($scored, fn ($a, $b) => $b['score'] <=> $a['score']);

        return array_map(fn ($p) => [
            'id' => $p['id'],
            'url' => "https://images.unsplash.com/photo-{$p['id']}?w=1600&q=80&auto=format&fit=crop",
            'thumb' => "https://images.unsplash.com/photo-{$p['id']}?w=400&h=300&q=60&auto=format&fit=crop",
            'label' => $p['tags'][0] ?? '',
        ], $scored);
    }

    /** Live Unsplash search (only when an access key is configured). Cached for a day. */
    private function unsplash(string $query): array
    {
        $key = config('ai.unsplash_access_key');
        if (!$key) {
            return [];
        }

        return Cache::remember('stock:' . md5($query), 86400, function () use ($key, $query) {
            try {
                $res = Http::timeout(8)->withHeaders(['Authorization' => "Client-ID {$key}", 'Accept-Version' => 'v1'])
                    ->get('https://api.unsplash.com/search/photos', ['query' => $query, 'per_page' => 30, 'content_filter' => 'high']);
                if (!$res->ok()) {
                    return [];
                }

                return collect($res->json('results', []))->map(fn ($p) => [
                    'id' => 'u-' . $p['id'],
                    'url' => $p['urls']['raw'] . '&w=1600&q=80&auto=format&fit=crop',
                    'thumb' => $p['urls']['small'],
                    'label' => $p['alt_description'] ?? '',
                    'credit' => $p['user']['name'] ?? null,
                ])->all();
            } catch (\Throwable $e) {
                Log::info('Unsplash search failed', ['error' => $e->getMessage()]);

                return [];
            }
        });
    }
}
