<?php

namespace App\Services\Logo;

use RuntimeException;

/**
 * Pure-GD vector rasterizer for favicon scenes — no ImageMagick/librsvg needed
 * (ImageMagick's built-in SVG renderer drops stroked paths).
 *
 * A scene is a list of primitives on a square design canvas:
 *   ['fill',   'd' => path, 'color' => '#rrggbb', 'transform' => [scale, tx, ty]]
 *   ['stroke', 'd' => path, 'color' => '#rrggbb', 'width' => w, 'transform' => [...]]
 * Fills use the nonzero winding rule (letter counters stay open); strokes get
 * round caps and joins. Drawn at 4× and downsampled for anti-aliasing.
 */
final class Rasterizer
{
    private const SUPERSAMPLE = 4;

    public static function available(): bool
    {
        return function_exists('imagecreatetruecolor') && function_exists('imagepng');
    }

    /**
     * @param array<int, array> $scene
     * @param float $canvas      design-space size (e.g. 48)
     * @param string|null $background opaque background colour, or null for transparent
     * @return string PNG bytes
     */
    public static function png(array $scene, float $canvas, int $size, ?string $background = null): string
    {
        if (!self::available()) {
            throw new RuntimeException('GD is not available');
        }

        $big = $size * self::SUPERSAMPLE;
        $scale = $big / $canvas;
        $img = imagecreatetruecolor($big, $big);
        imagealphablending($img, false);
        imagesavealpha($img, true);
        imagefill($img, 0, 0, $background ? self::color($img, $background) : imagecolorallocatealpha($img, 0, 0, 0, 127));
        imagealphablending($img, true);

        foreach ($scene as $p) {
            [$s, $tx, $ty] = $p['transform'] ?? [1, 0, 0];
            $map = fn (array $pt) => [($pt[0] * $s + $tx) * $scale, ($pt[1] * $s + $ty) * $scale];
            $subpaths = array_map(
                fn ($sp) => ['points' => array_map($map, $sp['points']), 'closed' => $sp['closed']],
                PathFlattener::flatten($p['d'], 0.2 / max($s, 0.01)),
            );
            $color = self::color($img, $p['color']);

            if ($p[0] === 'fill') {
                self::fill($img, $subpaths, $color, $big);
            } else {
                self::stroke($img, $subpaths, $color, $p['width'] * $s * $scale);
            }
        }

        $out = imagecreatetruecolor($size, $size);
        imagealphablending($out, false);
        imagesavealpha($out, true);
        imagefill($out, 0, 0, imagecolorallocatealpha($out, 0, 0, 0, 127));
        imagecopyresampled($out, $img, 0, 0, 0, 0, $size, $size, $big, $big);
        imagedestroy($img);

        ob_start();
        imagepng($out, null, 9);
        imagedestroy($out);

        return (string) ob_get_clean();
    }

    /** Scanline polygon fill, nonzero winding rule. */
    private static function fill(\GdImage $img, array $subpaths, int $color, int $height): void
    {
        $edges = [];
        foreach ($subpaths as $sp) {
            $pts = $sp['points'];
            $count = count($pts);
            for ($i = 0; $i < $count; $i++) {
                [$x0, $y0] = $pts[$i];
                [$x1, $y1] = $pts[($i + 1) % $count]; // fills are implicitly closed
                if ($y0 == $y1) {
                    continue;
                }
                $dir = $y1 > $y0 ? 1 : -1;
                if ($y0 > $y1) {
                    [$x0, $y0, $x1, $y1] = [$x1, $y1, $x0, $y0];
                }
                $edges[] = [$y0, $y1, $x0, ($x1 - $x0) / ($y1 - $y0), $dir];
            }
        }
        if (!$edges) {
            return;
        }

        $minY = max(0, (int) floor(min(array_column($edges, 0))));
        $maxY = min($height - 1, (int) ceil(max(array_column($edges, 1))));
        for ($y = $minY; $y <= $maxY; $y++) {
            $sy = $y + 0.5;
            $hits = [];
            foreach ($edges as [$ya, $yb, $xa, $slope, $dir]) {
                if ($sy >= $ya && $sy < $yb) {
                    $hits[] = [$xa + ($sy - $ya) * $slope, $dir];
                }
            }
            if (count($hits) < 2) {
                continue;
            }
            usort($hits, fn ($a, $b) => $a[0] <=> $b[0]);
            $winding = 0;
            foreach ($hits as $k => [$hx, $dir]) {
                $before = $winding;
                $winding += $dir;
                if ($before === 0 && $winding !== 0) {
                    $start = $hx;
                } elseif ($before !== 0 && $winding === 0) {
                    $x0 = (int) round($start);
                    $x1 = (int) round($hx) - 1;
                    if ($x1 >= $x0) {
                        imageline($img, $x0, $y, $x1, $y, $color);
                    }
                }
            }
        }
    }

    /** Thick polylines with round caps and joins. */
    private static function stroke(\GdImage $img, array $subpaths, int $color, float $width): void
    {
        $r = $width / 2;
        $d = max(1, (int) round($width));
        foreach ($subpaths as $sp) {
            $pts = $sp['points'];
            $count = count($pts);
            for ($i = 0; $i < $count - 1; $i++) {
                [$x0, $y0] = $pts[$i];
                [$x1, $y1] = $pts[$i + 1];
                $len = hypot($x1 - $x0, $y1 - $y0);
                if ($len > 0.01) {
                    $nx = -($y1 - $y0) / $len * $r;
                    $ny = ($x1 - $x0) / $len * $r;
                    imagefilledpolygon($img, [
                        (int) round($x0 + $nx), (int) round($y0 + $ny),
                        (int) round($x1 + $nx), (int) round($y1 + $ny),
                        (int) round($x1 - $nx), (int) round($y1 - $ny),
                        (int) round($x0 - $nx), (int) round($y0 - $ny),
                    ], $color);
                }
                imagefilledellipse($img, (int) round($x0), (int) round($y0), $d, $d, $color);
            }
            [$xe, $ye] = $pts[$count - 1];
            imagefilledellipse($img, (int) round($xe), (int) round($ye), $d, $d, $color);
        }
    }

    private static function color(\GdImage $img, string $hex): int
    {
        $hex = ltrim($hex, '#');

        return imagecolorallocate($img, hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2)));
    }
}
