/**
 * Colour maths shared by the editor (mirrors App\Services\Ai\Color so the
 * canvas and the published site make identical decisions).
 */
export function lum(hex) {
    const m = /^#?([0-9a-f]{6})$/i.exec(String(hex || '').trim());
    if (!m) return null;
    const [r, g, b] = [0, 2, 4].map((i) => parseInt(m[1].slice(i, i + 2), 16) / 255).map((c) => (c <= 0.03928 ? c / 12.92 : ((c + 0.055) / 1.055) ** 2.4));
    return 0.2126 * r + 0.7152 * g + 0.0722 * b;
}

export function contrast(a, b) {
    const la = lum(a);
    const lb = lum(b);
    if (la === null || lb === null) return 21;
    return (Math.max(la, lb) + 0.05) / (Math.min(la, lb) + 0.05);
}

export const isDark = (hex) => (lum(hex) ?? 1) < 0.18;

/** Near-black or near-white, whichever reads best on the background. */
export const readableOn = (bg) => (contrast('#0f172a', bg) >= contrast('#f8fafc', bg) ? '#0f172a' : '#f8fafc');

export function mixHex(a, b, amount) {
    const pa = /^#?([0-9a-f]{6})$/i.exec(a || '');
    const pb = /^#?([0-9a-f]{6})$/i.exec(b || '');
    if (!pa || !pb) return a;
    const ch = (h, i) => parseInt(h.slice(i, i + 2), 16);
    return '#' + [0, 2, 4].map((i) => Math.round(ch(pa[1], i) + (ch(pb[1], i) - ch(pa[1], i)) * amount).toString(16).padStart(2, '0')).join('');
}

/**
 * The derived variables every block relies on, for a given background:
 *   --surface  card / panel fill that stands off the background
 *   --line     hairline borders that are visible on it
 */
export function surfaceVars(bg) {
    if (lum(bg) === null) return {};
    const dark = isDark(bg);
    return {
        '--surface': mixHex(bg, dark ? '#ffffff' : '#000000', dark ? 0.1 : 0.04),
        '--line': mixHex(bg, readableOn(bg), 0.16),
    };
}
