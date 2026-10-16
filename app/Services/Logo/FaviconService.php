<?php

namespace App\Services\Logo;

use App\Models\Project;
use App\Services\Ai\Color;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Creates the complete favicon set for a project from its logo:
 *
 *   favicon.svg            sharp tab icon for modern browsers
 *   favicon.ico            16/32/48 px, for legacy browsers & bookmarks
 *   favicon-32.png         PNG tab icon
 *   apple-touch-icon.png   180 px, opaque (iOS turns transparency black)
 *   icon-192/512.png       Android / PWA, glyph inside the maskable safe zone
 *   manifest.json       name, icons and colours for "Add to home screen"
 *
 * AI logos are redrawn as vector marks; uploaded images are cropped or padded
 * to a square. Stored on the project (not the page schema) so AI edits can't
 * remove it.
 */
class FaviconService
{
    public function __construct(private FaviconDesigner $designer) {}

    /** Build the favicon from an image that belongs to this project (normally the navbar logo). */
    public function fromAssetUrl(Project $project, string $url, bool $followsLogo = true): array
    {
        $asset = $project->assets()->where('url', $url)->first();
        if (!$asset) {
            throw ValidationException::withMessages(['url' => 'Choose an image from this project.']);
        }

        $theme = $this->theme($project);
        $brand = $this->brand($project);

        if (!empty($asset->meta['logo']) && is_array($asset->meta['logo'])) {
            $files = $this->fromSpec($asset->meta['logo'], $brand);
            $kind = 'vector';
        } else {
            $path = Storage::disk('public')->path(ltrim(str_replace('/storage/', '', $url), '/'));
            if (!is_file($path)) {
                throw ValidationException::withMessages(['url' => 'That image file is missing.']);
            }
            $files = str_ends_with(strtolower($path), '.svg')
                ? $this->fromSvgFile($path, $theme)
                : $this->fromRaster($path, $theme);
            $kind = 'image';
        }

        return $this->store($project, $files, [
            'source' => $followsLogo ? 'logo' : 'custom',
            'followsLogo' => $followsLogo,
            'sourceUrl' => $url,
            'kind' => $kind,
        ]);
    }

    /** Build directly from an AI logo spec (used right after the Logo Studio saves a logo). */
    public function fromLogoSpec(Project $project, array $spec, string $sourceUrl): array
    {
        return $this->store($project, $this->fromSpec($spec, $this->brand($project)), [
            'source' => 'logo',
            'followsLogo' => true,
            'sourceUrl' => $sourceUrl,
            'kind' => 'vector',
        ]);
    }

    public function remove(Project $project): void
    {
        if (!empty($project->favicon['dir'])) {
            Storage::disk('public')->deleteDirectory($project->favicon['dir']);
        }
        $project->update(['favicon' => null]);
    }

    /* ==================================================================
     | HTML
     * ================================================================== */

    /**
     * <head> tags for a page. With no favicon set, an initials icon in the site's
     * colours is inlined so visitors never see the builder's own icon.
     *
     * @param string|null $base Prefix for file URLs (null = stored URLs; "" = files next to index.html, for ZIP export)
     */
    public function headTags(?array $favicon, array $schema, ?string $base = null): string
    {
        $themeColor = e(Color::normalize($schema['theme']['backgroundColor'] ?? null) ?? '#ffffff');

        if (empty($favicon['files'])) {
            $svg = $this->designer->svg($this->designer->scene($this->defaultSpec($schema), $this->brandFromSchema($schema), 'tab'));

            return '<link rel="icon" type="image/svg+xml" href="data:image/svg+xml,' . rawurlencode($svg) . '">'
                . "\n    <meta name=\"theme-color\" content=\"{$themeColor}\">";
        }

        $f = $favicon['files'];
        $url = fn (string $name) => e($base === null ? ($f[$name] ?? '') : $base . $name);
        $tags = [];
        if (isset($f['favicon.ico'])) {
            $tags[] = '<link rel="icon" href="' . $url('favicon.ico') . '" sizes="48x48">';
        }
        if (isset($f['favicon.svg'])) {
            $tags[] = '<link rel="icon" href="' . $url('favicon.svg') . '" type="image/svg+xml">';
        }
        if (isset($f['favicon-32.png'])) {
            $tags[] = '<link rel="icon" href="' . $url('favicon-32.png') . '" type="image/png" sizes="32x32">';
        }
        if (isset($f['apple-touch-icon.png'])) {
            $tags[] = '<link rel="apple-touch-icon" href="' . $url('apple-touch-icon.png') . '">';
        }
        if (isset($f['manifest.json'])) {
            $tags[] = '<link rel="manifest" href="' . $url('manifest.json') . '">';
        }
        $tags[] = "<meta name=\"theme-color\" content=\"{$themeColor}\">";

        return implode("\n    ", $tags);
    }

    /** Manifest JSON; $base null = absolute stored URLs, "" = relative (ZIP export). */
    public function manifest(Project $project, array $files, ?string $base = null): string
    {
        $schema = $project->homePage?->schema ?? [];
        $src = fn (string $name) => $base === null ? ($files[$name] ?? '') : $base . $name;
        $brand = $this->brand($project);

        return json_encode(array_filter([
            'name' => $schema['meta']['title'] ?? $brand,
            'short_name' => mb_substr($brand, 0, 12),
            'start_url' => $base === null ? "/s/{$project->slug}" : './',
            'display' => 'standalone',
            'background_color' => Color::normalize($schema['theme']['backgroundColor'] ?? null) ?? '#ffffff',
            'theme_color' => Color::normalize($schema['theme']['backgroundColor'] ?? null) ?? '#ffffff',
            'icons' => array_values(array_filter([
                isset($files['icon-192.png']) ? ['src' => $src('icon-192.png'), 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any maskable'] : null,
                isset($files['icon-512.png']) ? ['src' => $src('icon-512.png'), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any maskable'] : null,
            ])),
        ]), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /* ==================================================================
     | Sources
     * ================================================================== */

    /** @return array<string, string> filename => bytes */
    private function fromSpec(array $spec, string $brand): array
    {
        $tab = $this->designer->scene($spec, $brand, 'tab');
        $app = $this->designer->scene($spec, $brand, 'app');

        $files = ['favicon.svg' => $this->designer->svg($tab)];
        if (Rasterizer::available()) {
            $small = [16 => $this->designer->png($tab, 16), 32 => $this->designer->png($tab, 32), 48 => $this->designer->png($tab, 48)];
            $files['favicon.ico'] = $this->ico($small);
            $files['favicon-32.png'] = $small[32];
            $files['apple-touch-icon.png'] = $this->designer->png($app, 180);
            $files['icon-192.png'] = $this->designer->png($app, 192);
            $files['icon-512.png'] = $this->designer->png($app, 512);
        }

        return $files;
    }

    /** Photos and PNG logos: crop near-square images, pad wide ones (keeps the whole logo visible). */
    private function fromRaster(string $path, array $theme): array
    {
        $src = @imagecreatefromstring((string) file_get_contents($path));
        if (!$src) {
            throw ValidationException::withMessages(['url' => 'That image could not be read. Try a PNG, JPG or WebP.']);
        }
        $w = imagesx($src);
        $h = imagesy($src);
        $ratio = $w / max(1, $h);
        $mode = ($ratio > 0.8 && $ratio < 1.25) ? 'cover' : 'contain';
        $hasAlpha = $this->hasTransparency($src);
        $bg = Color::normalize($theme['backgroundColor'] ?? null) ?? '#ffffff';

        $square = function (int $size, ?string $background, float $padding) use ($src, $w, $h, $mode) {
            $out = imagecreatetruecolor($size, $size);
            imagealphablending($out, false);
            imagesavealpha($out, true);
            $fill = $background
                ? imagecolorallocate($out, ...array_map('hexdec', str_split(ltrim($background, '#'), 2)))
                : imagecolorallocatealpha($out, 0, 0, 0, 127);
            imagefill($out, 0, 0, $fill);
            imagealphablending($out, true);

            $inner = (int) round($size * (1 - 2 * $padding));
            if ($mode === 'cover') {
                $side = min($w, $h);
                imagecopyresampled($out, $src, (int) (($size - $inner) / 2), (int) (($size - $inner) / 2), (int) (($w - $side) / 2), (int) (($h - $side) / 2), $inner, $inner, $side, $side);
            } else {
                $scale = $inner / max($w, $h);
                $dw = (int) round($w * $scale);
                $dh = (int) round($h * $scale);
                imagecopyresampled($out, $src, (int) (($size - $dw) / 2), (int) (($size - $dh) / 2), 0, 0, $dw, $dh, $w, $h);
            }
            ob_start();
            imagepng($out, null, 9);
            imagedestroy($out);

            return (string) ob_get_clean();
        };

        // Tab icons keep transparency; app icons are opaque and padded into the safe zone.
        $appBackground = $hasAlpha || $mode === 'contain' ? $bg : null;
        $appPadding = $mode === 'cover' && !$hasAlpha ? 0.0 : 0.12;
        $small = [16 => $square(16, null, 0), 32 => $square(32, null, 0), 48 => $square(48, null, 0)];

        $files = [
            'favicon.ico' => $this->ico($small),
            'favicon-32.png' => $small[32],
            'apple-touch-icon.png' => $square(180, $appBackground ?? $bg, $appPadding),
            'icon-192.png' => $square(192, $appBackground ?? $bg, $appPadding),
            'icon-512.png' => $square(512, $appBackground ?? $bg, $appPadding),
        ];
        imagedestroy($src);

        return $files;
    }

    /** Uploaded SVG logos: used as-is for tabs; PNGs only if ImageMagick can draw it. */
    private function fromSvgFile(string $path, array $theme): array
    {
        $svg = (string) file_get_contents($path);
        $files = ['favicon.svg' => $svg];
        if (extension_loaded('imagick')) {
            try {
                $im = new \Imagick();
                $im->setBackgroundColor(new \ImagickPixel('transparent'));
                $im->setResolution(384, 384);
                $im->readImageBlob($svg);
                $im->setImageFormat('png32');
                $tmp = tempnam(sys_get_temp_dir(), 'fav');
                file_put_contents($tmp, $im->getImageBlob());
                $files = array_merge($this->fromRaster($tmp, $theme), $files);
                @unlink($tmp);
            } catch (\Throwable $e) {
                Log::info('SVG favicon kept vector-only', ['error' => $e->getMessage()]);
            }
        }

        return $files;
    }

    /* ==================================================================
     | Storage & helpers
     * ================================================================== */

    private function store(Project $project, array $files, array $meta): array
    {
        $old = $project->favicon['dir'] ?? null;
        $dir = "favicons/{$project->id}/" . substr(md5(implode('', array_map('md5', $files)) . microtime()), 0, 10);

        $urls = [];
        foreach ($files as $name => $bytes) {
            Storage::disk('public')->put("{$dir}/{$name}", $bytes);
            $urls[$name] = "/storage/{$dir}/{$name}";
        }
        $manifest = $this->manifest($project, $urls);
        Storage::disk('public')->put("{$dir}/manifest.json", $manifest);
        $urls['manifest.json'] = "/storage/{$dir}/manifest.json";

        $favicon = $meta + ['dir' => $dir, 'files' => $urls, 'updatedAt' => now()->toIso8601String()];
        $project->update(['favicon' => $favicon]);

        if ($old && $old !== $dir) {
            Storage::disk('public')->deleteDirectory($old);
        }

        return $favicon;
    }

    /** Legacy .ico containing PNG images (supported by every browser since IE Vista era). */
    private function ico(array $pngs): string
    {
        ksort($pngs);
        $header = pack('vvv', 0, 1, count($pngs));
        $directory = '';
        $data = '';
        $offset = 6 + 16 * count($pngs);
        foreach ($pngs as $size => $png) {
            $directory .= pack('CCCCvvVV', $size >= 256 ? 0 : $size, $size >= 256 ? 0 : $size, 0, 0, 1, 32, strlen($png), $offset);
            $data .= $png;
            $offset += strlen($png);
        }

        return $header . $directory . $data;
    }

    private function hasTransparency(\GdImage $img): bool
    {
        $w = imagesx($img);
        $h = imagesy($img);
        foreach ([[0, 0], [$w - 1, 0], [0, $h - 1], [$w - 1, $h - 1], [(int) ($w / 2), 0]] as [$x, $y]) {
            if (((imagecolorat($img, $x, $y) >> 24) & 0x7F) > 0) {
                return true;
            }
        }

        return false;
    }

    private function theme(Project $project): array
    {
        return $project->homePage?->schema['theme'] ?? [];
    }

    private function brand(Project $project): string
    {
        return $this->brandFromSchema($project->homePage?->schema ?? []) ?: $project->name;
    }

    private function brandFromSchema(array $schema): string
    {
        foreach ($schema['sections'] ?? [] as $section) {
            if (($section['type'] ?? '') === 'navbar' && !empty($section['props']['brand'])) {
                return (string) $section['props']['brand'];
            }
        }

        return (string) ($schema['meta']['title'] ?? 'Site');
    }

    /** Initials badge in the site's colours, used until a logo/favicon is set. */
    private function defaultSpec(array $schema): array
    {
        return [
            'layout' => 'monogram',
            'monogram' => '',
            'shape' => 'rounded',
            'markColor' => Color::normalize($schema['theme']['primaryColor'] ?? null) ?? '#2563eb',
            'font' => $schema['theme']['fontHeading'] ?? 'Poppins',
            'weight' => '700',
            'icon' => 'sparkles',
        ];
    }
}
