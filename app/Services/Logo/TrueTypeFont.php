<?php

namespace App\Services\Logo;

use RuntimeException;

/**
 * Minimal TrueType (glyf-outline) reader: enough to lay out a line of text and
 * turn it into SVG path data, so logos keep their exact font everywhere —
 * an SVG used as an <img> cannot load web fonts.
 *
 * Supports cmap formats 4 and 12, simple and composite glyphs, hmtx advances.
 * (No kerning/shaping — fine for short Latin wordmarks.)
 */
final class TrueTypeFont
{
    private string $data;
    /** @var array<string, array{0:int,1:int}> tag => [offset, length] */
    private array $tables = [];

    public int $unitsPerEm;
    public int $ascender;
    public int $descender;
    public int $capHeight;
    private int $indexToLocFormat;
    private int $numGlyphs;
    private int $numberOfHMetrics;
    /** @var array<int, int> codepoint => glyph id */
    private array $cmap = [];
    /** @var array<int, array> glyph id => contours cache */
    private array $outlines = [];

    public function __construct(string $binary)
    {
        $this->data = $binary;
        if (strlen($binary) < 12) {
            throw new RuntimeException('Not a font file');
        }
        $numTables = $this->u16(4);
        for ($i = 0; $i < $numTables; $i++) {
            $rec = 12 + $i * 16;
            $this->tables[substr($binary, $rec, 4)] = [$this->u32($rec + 8), $this->u32($rec + 12)];
        }
        foreach (['head', 'hhea', 'hmtx', 'maxp', 'cmap', 'loca', 'glyf'] as $required) {
            if (!isset($this->tables[$required])) {
                throw new RuntimeException("Font is missing the {$required} table (CFF fonts are not supported)");
            }
        }

        $head = $this->tables['head'][0];
        $this->unitsPerEm = $this->u16($head + 18);
        $this->indexToLocFormat = $this->i16($head + 50);

        $hhea = $this->tables['hhea'][0];
        $this->ascender = $this->i16($hhea + 4);
        $this->descender = $this->i16($hhea + 6);
        $this->numberOfHMetrics = $this->u16($hhea + 34);
        $this->numGlyphs = $this->u16($this->tables['maxp'][0] + 4);

        $this->capHeight = (int) round($this->unitsPerEm * 0.7);
        if (isset($this->tables['OS/2'])) {
            $os2 = $this->tables['OS/2'][0];
            if ($this->u16($os2) >= 2 && $this->tables['OS/2'][1] >= 90) {
                $cap = $this->i16($os2 + 88);
                if ($cap > 0) {
                    $this->capHeight = $cap;
                }
            }
        }

        $this->readCmap();
    }

    public static function fromFile(string $path): self
    {
        $binary = @file_get_contents($path);
        if ($binary === false) {
            throw new RuntimeException("Cannot read font {$path}");
        }

        return new self($binary);
    }

    /* ---------------- Public API ---------------- */

    public function glyphIndex(int $codepoint): int
    {
        return $this->cmap[$codepoint] ?? 0;
    }

    /** True when every character in $text has a glyph in this font. */
    public function supports(string $text): bool
    {
        foreach (mb_str_split($text) as $char) {
            if ($char !== ' ' && $this->glyphIndex(mb_ord($char)) === 0) {
                return false;
            }
        }

        return true;
    }

    public function advance(int $gid): int
    {
        $hmtx = $this->tables['hmtx'][0];
        $index = min($gid, $this->numberOfHMetrics - 1);

        return $this->u16($hmtx + $index * 4);
    }

    /** Width of $text in font units (with extra tracking per gap, in units). */
    public function measure(string $text, float $trackingUnits = 0): float
    {
        $chars = mb_str_split($text);
        $width = 0.0;
        foreach ($chars as $i => $char) {
            $width += $this->advance($this->glyphIndex(mb_ord($char)));
            if ($i < count($chars) - 1) {
                $width += $trackingUnits;
            }
        }

        return $width;
    }

    /**
     * SVG path data for $text with its baseline at ($x, $baseline), scaled to $size px.
     *
     * @return array{d: string, width: float} width in px
     */
    public function textPath(string $text, float $x, float $baseline, float $size, float $tracking = 0): array
    {
        $scale = $size / $this->unitsPerEm;
        $trackingUnits = $tracking / $scale;
        $pen = 0.0;
        $d = '';
        $chars = mb_str_split($text);

        foreach ($chars as $i => $char) {
            $gid = $this->glyphIndex(mb_ord($char));
            foreach ($this->outline($gid) as $contour) {
                $d .= $this->contourToPath($contour, $x + $pen * $scale, $baseline, $scale);
            }
            $pen += $this->advance($gid);
            if ($i < count($chars) - 1) {
                $pen += $trackingUnits;
            }
        }

        return ['d' => $d, 'width' => $pen * $scale];
    }

    /* ---------------- Outlines ---------------- */

    /** @return array<int, array<int, array{0:float,1:float,2:bool}>> contours of [x, y, onCurve] */
    private function outline(int $gid, int $depth = 0): array
    {
        if (isset($this->outlines[$gid])) {
            return $this->outlines[$gid];
        }
        if ($gid >= $this->numGlyphs || $depth > 8) {
            return [];
        }

        [$start, $end] = $this->glyphRange($gid);
        if ($end <= $start) {
            return $this->outlines[$gid] = []; // e.g. space
        }

        $offset = $this->tables['glyf'][0] + $start;
        $numContours = $this->i16($offset);

        $contours = $numContours >= 0
            ? $this->simpleGlyph($offset, $numContours)
            : $this->compositeGlyph($offset, $depth);

        return $this->outlines[$gid] = $contours;
    }

    /** @return array{0:int,1:int} byte range of a glyph inside glyf */
    private function glyphRange(int $gid): array
    {
        $loca = $this->tables['loca'][0];
        if ($this->indexToLocFormat === 0) {
            return [$this->u16($loca + $gid * 2) * 2, $this->u16($loca + ($gid + 1) * 2) * 2];
        }

        return [$this->u32($loca + $gid * 4), $this->u32($loca + ($gid + 1) * 4)];
    }

    private function simpleGlyph(int $offset, int $numContours): array
    {
        $p = $offset + 10;
        $endPts = [];
        for ($i = 0; $i < $numContours; $i++) {
            $endPts[] = $this->u16($p);
            $p += 2;
        }
        $numPoints = $numContours ? end($endPts) + 1 : 0;
        $p += 2 + $this->u16($p); // skip instructions

        $flags = [];
        while (count($flags) < $numPoints) {
            $flag = ord($this->data[$p++]);
            $flags[] = $flag;
            if ($flag & 8) { // repeat
                $repeat = ord($this->data[$p++]);
                for ($r = 0; $r < $repeat; $r++) {
                    $flags[] = $flag;
                }
            }
        }

        $xs = [];
        $value = 0;
        foreach ($flags as $flag) {
            if ($flag & 2) {
                $delta = ord($this->data[$p++]);
                $value += ($flag & 16) ? $delta : -$delta;
            } elseif (!($flag & 16)) {
                $value += $this->i16($p);
                $p += 2;
            }
            $xs[] = $value;
        }
        $ys = [];
        $value = 0;
        foreach ($flags as $flag) {
            if ($flag & 4) {
                $delta = ord($this->data[$p++]);
                $value += ($flag & 32) ? $delta : -$delta;
            } elseif (!($flag & 32)) {
                $value += $this->i16($p);
                $p += 2;
            }
            $ys[] = $value;
        }

        $contours = [];
        $startPt = 0;
        foreach ($endPts as $endPt) {
            $contour = [];
            for ($i = $startPt; $i <= $endPt; $i++) {
                $contour[] = [(float) $xs[$i], (float) $ys[$i], (bool) ($flags[$i] & 1)];
            }
            $contours[] = $contour;
            $startPt = $endPt + 1;
        }

        return $contours;
    }

    private function compositeGlyph(int $offset, int $depth): array
    {
        $p = $offset + 10;
        $result = [];
        do {
            $flags = $this->u16($p);
            $component = $this->u16($p + 2);
            $p += 4;

            if ($flags & 1) { // ARG_1_AND_2_ARE_WORDS
                $a = $this->i16($p);
                $b = $this->i16($p + 2);
                $p += 4;
            } else {
                $a = $this->i8($p);
                $b = $this->i8($p + 1);
                $p += 2;
            }

            [$xx, $xy, $yx, $yy] = [1.0, 0.0, 0.0, 1.0];
            if ($flags & 8) { // WE_HAVE_A_SCALE
                $xx = $yy = $this->f2dot14($p);
                $p += 2;
            } elseif ($flags & 64) { // X_AND_Y_SCALE
                $xx = $this->f2dot14($p);
                $yy = $this->f2dot14($p + 2);
                $p += 4;
            } elseif ($flags & 128) { // TWO_BY_TWO
                $xx = $this->f2dot14($p);
                $xy = $this->f2dot14($p + 2);
                $yx = $this->f2dot14($p + 4);
                $yy = $this->f2dot14($p + 6);
                $p += 8;
            }

            // ARGS_ARE_XY_VALUES (point matching is rare in Latin fonts; treated as no offset)
            [$dx, $dy] = ($flags & 2) ? [$a, $b] : [0, 0];

            foreach ($this->outline($component, $depth + 1) as $contour) {
                $result[] = array_map(fn ($pt) => [
                    $pt[0] * $xx + $pt[1] * $yx + $dx,
                    $pt[0] * $xy + $pt[1] * $yy + $dy,
                    $pt[2],
                ], $contour);
            }
        } while ($flags & 32); // MORE_COMPONENTS

        return $result;
    }

    /** Quadratic TrueType contour → SVG path (y flipped to screen space). */
    private function contourToPath(array $contour, float $originX, float $baseline, float $scale): string
    {
        $n = count($contour);
        if ($n === 0) {
            return '';
        }
        $pt = fn (array $p) => [$originX + $p[0] * $scale, $baseline - $p[1] * $scale];
        $fmt = fn (float $v) => rtrim(rtrim(number_format($v, 2, '.', ''), '0'), '.');
        $mid = fn (array $a, array $b) => [($a[0] + $b[0]) / 2, ($a[1] + $b[1]) / 2, true];

        // Start on an on-curve point (or the midpoint of two off-curve points).
        $startIndex = null;
        foreach ($contour as $i => $p) {
            if ($p[2]) {
                $startIndex = $i;
                break;
            }
        }
        if ($startIndex === null) {
            $start = $mid($contour[0], $contour[1 % $n]);
            $points = $contour;
        } else {
            $start = $contour[$startIndex];
            $points = array_merge(array_slice($contour, $startIndex + 1), array_slice($contour, 0, $startIndex));
        }

        [$sx, $sy] = $pt($start);
        $d = 'M' . $fmt($sx) . ' ' . $fmt($sy);
        $control = null;
        $points[] = $start; // close back to the start

        foreach ($points as $p) {
            if ($p[2]) {
                [$x, $y] = $pt($p);
                if ($control) {
                    [$cx, $cy] = $pt($control);
                    $d .= 'Q' . $fmt($cx) . ' ' . $fmt($cy) . ' ' . $fmt($x) . ' ' . $fmt($y);
                    $control = null;
                } else {
                    $d .= 'L' . $fmt($x) . ' ' . $fmt($y);
                }
            } else {
                if ($control) {
                    $m = $mid($control, $p);
                    [$cx, $cy] = $pt($control);
                    [$mx, $my] = $pt($m);
                    $d .= 'Q' . $fmt($cx) . ' ' . $fmt($cy) . ' ' . $fmt($mx) . ' ' . $fmt($my);
                }
                $control = $p;
            }
        }

        return $d . 'Z';
    }

    /* ---------------- cmap ---------------- */

    private function readCmap(): void
    {
        $cmap = $this->tables['cmap'][0];
        $count = $this->u16($cmap + 2);
        $best = null;
        $bestFormat = 0;
        for ($i = 0; $i < $count; $i++) {
            $rec = $cmap + 4 + $i * 8;
            $platform = $this->u16($rec);
            $encoding = $this->u16($rec + 2);
            $sub = $cmap + $this->u32($rec + 4);
            $format = $this->u16($sub);
            $unicode = $platform === 0 || ($platform === 3 && in_array($encoding, [1, 10], true));
            if ($unicode && in_array($format, [4, 12], true) && $format > $bestFormat) {
                $best = $sub;
                $bestFormat = $format;
            }
        }
        if ($best === null) {
            throw new RuntimeException('Font has no Unicode cmap');
        }
        $bestFormat === 12 ? $this->cmap12($best) : $this->cmap4($best);
    }

    private function cmap4(int $sub): void
    {
        $segX2 = $this->u16($sub + 6);
        $ends = $sub + 14;
        $starts = $ends + $segX2 + 2;
        $deltas = $starts + $segX2;
        $rangeOffsets = $deltas + $segX2;
        for ($s = 0; $s < $segX2 / 2; $s++) {
            $end = $this->u16($ends + $s * 2);
            $start = $this->u16($starts + $s * 2);
            $delta = $this->i16($deltas + $s * 2);
            $rangeOffset = $this->u16($rangeOffsets + $s * 2);
            if ($start > 0x20FF) {
                continue; // wordmarks only need Latin, other scripts below U+2100 and punctuation
            }
            for ($c = $start; $c <= $end && $c !== 0xFFFF; $c++) {
                if ($rangeOffset === 0) {
                    $gid = ($c + $delta) & 0xFFFF;
                } else {
                    $addr = $rangeOffsets + $s * 2 + $rangeOffset + ($c - $start) * 2;
                    $gid = $this->u16($addr);
                    if ($gid !== 0) {
                        $gid = ($gid + $delta) & 0xFFFF;
                    }
                }
                if ($gid) {
                    $this->cmap[$c] = $gid;
                }
            }
        }
    }

    private function cmap12(int $sub): void
    {
        $groups = $this->u32($sub + 12);
        for ($g = 0; $g < $groups; $g++) {
            $rec = $sub + 16 + $g * 12;
            $start = $this->u32($rec);
            $end = min($this->u32($rec + 4), 0x20FF);
            $gid = $this->u32($rec + 8);
            for ($c = $start; $c <= $end; $c++) {
                $this->cmap[$c] = $gid + ($c - $start);
            }
        }
    }

    /* ---------------- Binary helpers ---------------- */

    private function u16(int $o): int
    {
        return (ord($this->data[$o]) << 8) | ord($this->data[$o + 1]);
    }

    private function i16(int $o): int
    {
        $v = $this->u16($o);

        return $v >= 0x8000 ? $v - 0x10000 : $v;
    }

    private function i8(int $o): int
    {
        $v = ord($this->data[$o]);

        return $v >= 0x80 ? $v - 0x100 : $v;
    }

    private function u32(int $o): int
    {
        return (ord($this->data[$o]) << 24) | (ord($this->data[$o + 1]) << 16) | (ord($this->data[$o + 2]) << 8) | ord($this->data[$o + 3]);
    }

    private function f2dot14(int $o): float
    {
        return $this->i16($o) / 16384;
    }
}
