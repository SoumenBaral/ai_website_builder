<?php

namespace App\Services\Shop;

/**
 * Understands the price text people type on cards: "$45", "From $120", "৳1,200", "1.200 €",
 * "Rs 499", "Tk 350/kg", "€9,50"… and formats totals the same way the shop writes prices.
 */
class Price
{
    /** Longest first, so "R$" wins over "$" and "Rs" over "R". */
    private const SYMBOLS = ['R$', 'US$', 'A$', 'C$', 'Rs.', 'Rs', 'Tk.', 'Tk', 'BDT', 'USD', 'EUR', 'GBP', 'INR', 'AED', 'SAR', '$', '€', '£', '₹', '৳', '¥', '₦', '₱', '₩', '₺', '₫', '₽', '﷼'];

    /**
     * @return array{amount: float, symbol: string, prefix: bool, space: bool}|null null = no number ("Price on request")
     */
    public static function parse(mixed $text): ?array
    {
        if (!is_string($text) || !preg_match('/\d(?:[\d.,\s]*\d)?/u', $text, $m, PREG_OFFSET_CAPTURE)) {
            return null;
        }
        [$raw, $offset] = $m[0];
        $amount = self::number(preg_replace('/\s+/u', '', $raw));
        if ($amount === null || $amount <= 0 || $amount > 100000000) {
            return null;
        }

        $before = rtrim(substr($text, 0, $offset));
        $after = ltrim(substr($text, $offset + strlen($raw)));
        $symbol = '';
        $prefix = true;
        $space = false;
        foreach (self::SYMBOLS as $sym) {
            if ($before !== '' && str_ends_with(mb_strtolower($before), mb_strtolower($sym))) {
                $symbol = mb_substr($before, -mb_strlen($sym));
                $space = substr($text, 0, $offset) !== $before; // "Rs 499"
                break;
            }
            if ($after !== '' && str_starts_with(mb_strtolower($after), mb_strtolower($sym))) {
                $symbol = mb_substr($after, 0, mb_strlen($sym));
                $prefix = false;
                $space = substr($text, $offset + strlen($raw)) !== $after; // "1.200 €"
                break;
            }
        }

        return ['amount' => $amount, 'symbol' => $symbol, 'prefix' => $prefix, 'space' => $space];
    }

    /** "1,200.50" / "1.200,50" / "1.200" (thousands) / "9,50" (decimal) → float */
    private static function number(string $n): ?float
    {
        $dot = strrpos($n, '.');
        $comma = strrpos($n, ',');
        if ($dot !== false && $comma !== false) {
            $dec = max($dot, $comma);
            $int = preg_replace('/[.,]/', '', substr($n, 0, $dec));
            $n = $int . '.' . substr($n, $dec + 1);
        } elseif ($comma !== false || $dot !== false) {
            $sep = $comma !== false ? ',' : '.';
            $last = $comma !== false ? $comma : $dot;
            $decimals = strlen($n) - $last - 1;
            // One separator followed by exactly 3 digits is a thousands mark ("1,200", "1.200").
            $n = substr_count($n, $sep) > 1 || $decimals === 3 ? str_replace($sep, '', $n) : str_replace($sep, '.', $n);
        }

        return is_numeric($n) ? (float) $n : null;
    }

    /** Format like the shop's own prices: same symbol, same side. */
    public static function format(float $amount, string $symbol = '', bool $prefix = true, bool $space = false): string
    {
        $number = number_format($amount, fmod($amount, 1.0) == 0.0 ? 0 : 2);
        if ($symbol === '') {
            return $number;
        }
        $gap = $space ? ' ' : '';

        return $prefix ? $symbol . $gap . $number : $number . $gap . $symbol;
    }
}
