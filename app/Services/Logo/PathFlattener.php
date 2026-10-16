<?php

namespace App\Services\Logo;

/**
 * Converts SVG path data into polylines (lists of [x, y] points).
 * Supports M L H V C S Q T A Z in absolute and relative form — everything
 * our glyph paths, shapes and line icons use.
 */
final class PathFlattener
{
    /**
     * @return array<int, array{points: array<int, array{0: float, 1: float}>, closed: bool}>
     */
    public static function flatten(string $d, float $tolerance = 0.25): array
    {
        preg_match_all('/[MmLlHhVvCcSsQqTtAaZz]|-?(?:\d+\.?\d*|\.\d+)(?:[eE][-+]?\d+)?/', $d, $m);
        $tokens = $m[0];

        $subpaths = [];
        $current = [];
        $closed = false;
        $x = $y = 0.0;
        $startX = $startY = 0.0;
        $lastCtrl = null;   // last cubic control point (for S)
        $lastQuad = null;   // last quadratic control point (for T)
        $cmd = null;
        $i = 0;
        $n = count($tokens);

        $num = function () use (&$i, &$tokens): float {
            return (float) $tokens[$i++];
        };
        $flag = function () use (&$i, &$tokens): int {
            // A flag is a single "0"/"1" and may be glued to what follows ("011.5" = 0, 1, 1.5).
            $t = $tokens[$i];
            if (strlen($t) > 1 && ($t[0] === '0' || $t[0] === '1')) {
                $tokens[$i] = substr($t, 1);

                return (int) $t[0];
            }
            $i++;

            return (int) $t;
        };
        $finish = function () use (&$current, &$subpaths, &$closed) {
            if (count($current) > 1) {
                $subpaths[] = ['points' => $current, 'closed' => $closed];
            }
            $current = [];
            $closed = false;
        };

        while ($i < $n) {
            if (preg_match('/^[A-Za-z]$/', $tokens[$i])) {
                $cmd = $tokens[$i++];
            } elseif ($cmd === null) {
                $i++;
                continue;
            }
            $rel = ctype_lower($cmd);

            switch (strtoupper($cmd)) {
                case 'M':
                    $finish();
                    $x = $num() + ($rel ? $x : 0);
                    $y = $num() + ($rel ? $y : 0);
                    $startX = $x;
                    $startY = $y;
                    $current = [[$x, $y]];
                    $cmd = $rel ? 'l' : 'L'; // implicit lineto after moveto
                    $lastCtrl = $lastQuad = null;
                    break;
                case 'L':
                    $x = $num() + ($rel ? $x : 0);
                    $y = $num() + ($rel ? $y : 0);
                    $current[] = [$x, $y];
                    $lastCtrl = $lastQuad = null;
                    break;
                case 'H':
                    $x = $num() + ($rel ? $x : 0);
                    $current[] = [$x, $y];
                    $lastCtrl = $lastQuad = null;
                    break;
                case 'V':
                    $y = $num() + ($rel ? $y : 0);
                    $current[] = [$x, $y];
                    $lastCtrl = $lastQuad = null;
                    break;
                case 'C':
                case 'S':
                    if (strtoupper($cmd) === 'C') {
                        $x1 = $num() + ($rel ? $x : 0);
                        $y1 = $num() + ($rel ? $y : 0);
                    } else {
                        [$x1, $y1] = $lastCtrl ? [2 * $x - $lastCtrl[0], 2 * $y - $lastCtrl[1]] : [$x, $y];
                    }
                    $x2 = $num() + ($rel ? $x : 0);
                    $y2 = $num() + ($rel ? $y : 0);
                    $ex = $num() + ($rel ? $x : 0);
                    $ey = $num() + ($rel ? $y : 0);
                    $steps = self::steps([$x, $y], [$x1, $y1], [$x2, $y2], [$ex, $ey], $tolerance);
                    for ($s = 1; $s <= $steps; $s++) {
                        $t = $s / $steps;
                        $mt = 1 - $t;
                        $current[] = [
                            $mt ** 3 * $x + 3 * $mt ** 2 * $t * $x1 + 3 * $mt * $t ** 2 * $x2 + $t ** 3 * $ex,
                            $mt ** 3 * $y + 3 * $mt ** 2 * $t * $y1 + 3 * $mt * $t ** 2 * $y2 + $t ** 3 * $ey,
                        ];
                    }
                    $lastCtrl = [$x2, $y2];
                    $lastQuad = null;
                    $x = $ex;
                    $y = $ey;
                    break;
                case 'Q':
                case 'T':
                    if (strtoupper($cmd) === 'Q') {
                        $qx = $num() + ($rel ? $x : 0);
                        $qy = $num() + ($rel ? $y : 0);
                    } else {
                        [$qx, $qy] = $lastQuad ? [2 * $x - $lastQuad[0], 2 * $y - $lastQuad[1]] : [$x, $y];
                    }
                    $ex = $num() + ($rel ? $x : 0);
                    $ey = $num() + ($rel ? $y : 0);
                    $steps = self::steps([$x, $y], [$qx, $qy], [$qx, $qy], [$ex, $ey], $tolerance);
                    for ($s = 1; $s <= $steps; $s++) {
                        $t = $s / $steps;
                        $mt = 1 - $t;
                        $current[] = [$mt * $mt * $x + 2 * $mt * $t * $qx + $t * $t * $ex, $mt * $mt * $y + 2 * $mt * $t * $qy + $t * $t * $ey];
                    }
                    $lastQuad = [$qx, $qy];
                    $lastCtrl = null;
                    $x = $ex;
                    $y = $ey;
                    break;
                case 'A':
                    $rx = abs($num());
                    $ry = abs($num());
                    $rot = $num();
                    $large = $flag();
                    $sweep = $flag();
                    $ex = $num() + ($rel ? $x : 0);
                    $ey = $num() + ($rel ? $y : 0);
                    foreach (self::arc($x, $y, $rx, $ry, $rot, $large, $sweep, $ex, $ey, $tolerance) as $p) {
                        $current[] = $p;
                    }
                    $x = $ex;
                    $y = $ey;
                    $lastCtrl = $lastQuad = null;
                    break;
                case 'Z':
                    $closed = true;
                    $current[] = [$startX, $startY];
                    $x = $startX;
                    $y = $startY;
                    $finish();
                    $current = [[$x, $y]];
                    $lastCtrl = $lastQuad = null;
                    break;
                default:
                    $i++;
            }
        }
        $finish();

        return $subpaths;
    }

    /** Number of segments for a curve, from its control polygon length. */
    private static function steps(array $p0, array $p1, array $p2, array $p3, float $tolerance): int
    {
        $len = hypot($p1[0] - $p0[0], $p1[1] - $p0[1]) + hypot($p2[0] - $p1[0], $p2[1] - $p1[1]) + hypot($p3[0] - $p2[0], $p3[1] - $p2[1]);

        return (int) max(2, min(48, ceil(sqrt($len / $tolerance) * 1.5)));
    }

    /** SVG elliptical arc (endpoint parameterisation) → points, per SVG spec F.6.5. */
    private static function arc(float $x1, float $y1, float $rx, float $ry, float $rotDeg, int $large, int $sweep, float $x2, float $y2, float $tolerance): array
    {
        if ($rx == 0 || $ry == 0 || ($x1 == $x2 && $y1 == $y2)) {
            return [[$x2, $y2]];
        }
        $phi = deg2rad($rotDeg);
        $cos = cos($phi);
        $sin = sin($phi);
        $dx = ($x1 - $x2) / 2;
        $dy = ($y1 - $y2) / 2;
        $x1p = $cos * $dx + $sin * $dy;
        $y1p = -$sin * $dx + $cos * $dy;

        $lambda = ($x1p ** 2) / ($rx ** 2) + ($y1p ** 2) / ($ry ** 2);
        if ($lambda > 1) {
            $rx *= sqrt($lambda);
            $ry *= sqrt($lambda);
        }
        $num = $rx ** 2 * $ry ** 2 - $rx ** 2 * $y1p ** 2 - $ry ** 2 * $x1p ** 2;
        $den = $rx ** 2 * $y1p ** 2 + $ry ** 2 * $x1p ** 2;
        $coef = ($large === $sweep ? -1 : 1) * sqrt(max(0, $num / $den));
        $cxp = $coef * ($rx * $y1p / $ry);
        $cyp = $coef * (-$ry * $x1p / $rx);
        $cx = $cos * $cxp - $sin * $cyp + ($x1 + $x2) / 2;
        $cy = $sin * $cxp + $cos * $cyp + ($y1 + $y2) / 2;

        $angle = fn ($ux, $uy, $vx, $vy) => atan2($ux * $vy - $uy * $vx, $ux * $vx + $uy * $vy);
        $theta1 = $angle(1, 0, ($x1p - $cxp) / $rx, ($y1p - $cyp) / $ry);
        $delta = $angle(($x1p - $cxp) / $rx, ($y1p - $cyp) / $ry, (-$x1p - $cxp) / $rx, (-$y1p - $cyp) / $ry);
        if (!$sweep && $delta > 0) {
            $delta -= 2 * M_PI;
        } elseif ($sweep && $delta < 0) {
            $delta += 2 * M_PI;
        }

        $steps = (int) max(4, min(64, ceil(abs($delta) * max($rx, $ry) / max(0.5, $tolerance * 4))));
        $points = [];
        for ($s = 1; $s <= $steps; $s++) {
            $t = $theta1 + $delta * $s / $steps;
            $px = $rx * cos($t);
            $py = $ry * sin($t);
            $points[] = [$cos * $px - $sin * $py + $cx, $sin * $px + $cos * $py + $cy];
        }

        return $points;
    }
}
