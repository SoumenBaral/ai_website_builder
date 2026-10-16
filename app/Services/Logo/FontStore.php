<?php

namespace App\Services\Logo;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Downloads Google Fonts as TrueType once and caches them on disk, so logo
 * text can be converted to vector paths in the site's real typeface.
 */
class FontStore
{
    /** @var array<string, TrueTypeFont|null> */
    private array $loaded = [];

    public function __construct(private ?string $directory = null)
    {
        $this->directory ??= config('ai.font_path', storage_path('app/fonts'));
    }

    /** The requested weight, or the closest available one. Null if the font can't be loaded. */
    public function font(string $family, int $weight = 700): ?TrueTypeFont
    {
        $chain = array_values(array_unique([$weight, 700, 600, 800, 500, 400]));
        foreach ($chain as $w) {
            $font = $this->load($family, $w);
            if ($font) {
                return $font;
            }
        }

        return null;
    }

    private function load(string $family, int $weight): ?TrueTypeFont
    {
        $key = Str::slug($family) . "-{$weight}";
        if (array_key_exists($key, $this->loaded)) {
            return $this->loaded[$key];
        }

        $path = "{$this->directory}/{$key}.ttf";
        $missing = "{$path}.missing";

        if (!is_file($path) && !is_file($missing)) {
            $this->download($family, $weight, $path, $missing);
        }

        try {
            return $this->loaded[$key] = is_file($path) ? TrueTypeFont::fromFile($path) : null;
        } catch (\Throwable $e) {
            Log::warning('Unreadable font file', ['font' => $key, 'error' => $e->getMessage()]);

            return $this->loaded[$key] = null;
        }
    }

    private function download(string $family, int $weight, string $path, string $missing): void
    {
        try {
            // Without a browser user agent, Google Fonts serves plain TrueType (.ttf).
            $css = Http::timeout(8)->get('https://fonts.googleapis.com/css2?family=' . str_replace(' ', '+', $family) . ":wght@{$weight}");
            if ($css->status() === 400) {
                $this->remember($missing); // weight doesn't exist for this family

                return;
            }
            if (!$css->ok() || !preg_match('/url\((https:[^)]+\.ttf)\)/', $css->body(), $m)) {
                return;
            }
            $file = Http::timeout(15)->get($m[1]);
            if ($file->ok() && strlen($file->body()) > 1000) {
                if (!is_dir($this->directory)) {
                    @mkdir($this->directory, 0755, true);
                }
                file_put_contents($path, $file->body());
            }
        } catch (\Throwable $e) {
            Log::warning('Font download failed', ['family' => $family, 'weight' => $weight, 'error' => $e->getMessage()]);
        }
    }

    private function remember(string $missing): void
    {
        if (!is_dir($this->directory)) {
            @mkdir($this->directory, 0755, true);
        }
        @touch($missing);
    }
}
