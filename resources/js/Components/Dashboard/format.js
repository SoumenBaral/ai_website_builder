export function timeAgo(date) {
    if (!date) return '';
    const d = new Date(date);
    const diff = Math.floor((Date.now() - d.getTime()) / 1000);
    if (diff < 60) return 'Just now';
    if (diff < 3600) return `${Math.floor(diff / 60)}m ago`;
    if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`;
    if (diff < 604800) return `${Math.floor(diff / 86400)}d ago`;
    const sameYear = d.getFullYear() === new Date().getFullYear();
    return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', ...(sameYear ? {} : { year: 'numeric' }) });
}

export function compactNumber(n) {
    return new Intl.NumberFormat('en', { notation: 'compact', maximumFractionDigits: 1 }).format(n || 0);
}

const SMALL_WORDS = new Set(['a', 'an', 'the', 'and', 'or', 'for', 'of', 'to', 'in', 'on', 'with', 'my', 'our', 'at', 'by']);

/** "a landing page for my SaaS startup" -> "Landing Page for My SaaS Startup" (max ~6 words). */
export function nameFromPrompt(prompt) {
    const words = prompt
        .replace(/[^\p{L}\p{N}\s&'-]/gu, ' ')
        .trim()
        .split(/\s+/)
        .filter(Boolean);
    while (words.length && ['a', 'an', 'the', 'build', 'create', 'make', 'me'].includes(words[0].toLowerCase())) words.shift();
    const name = words
        .slice(0, 6)
        .map((w, i) => (i > 0 && SMALL_WORDS.has(w.toLowerCase()) ? w.toLowerCase() : w.charAt(0).toUpperCase() + w.slice(1)))
        .join(' ');
    return (name || 'Untitled website').slice(0, 60);
}
