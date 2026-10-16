// Editor-only presentation metadata for block types (icons, blurbs) and
// helpers for turning schema prop keys into readable labels.

export const blockIcons = {
    navbar: 'menu',
    hero: 'bolt',
    features: 'sparkles',
    gallery: 'photo',
    about: 'user',
    contact: 'envelope',
    services: 'cog',
    testimonials: 'chat',
    pricing: 'tag',
    stats: 'chart',
    faq: 'question',
    team: 'users',
    cta: 'rocket',
    custom: 'grid',
    footer: 'document',
};

export const blockBlurbs = {
    navbar: 'Logo, links and a button',
    hero: 'Big headline and call to action',
    features: 'Key benefits in a grid',
    gallery: 'Image grid',
    about: 'Your story and image',
    contact: 'Form and contact details',
    services: 'What you offer',
    testimonials: 'Customer quotes',
    pricing: 'Plans and prices',
    stats: 'Numbers that impress',
    faq: 'Questions and answers',
    team: 'People behind the work',
    cta: 'Closing call to action',
    custom: 'Build your own layout',
    footer: 'Links and copyright',
};

const ACRONYMS = { cta: 'CTA', url: 'URL', faq: 'FAQ', bg: 'background', href: 'link' };

/** "ctaText" -> "CTA text", "backgroundImage" -> "Background image", "ctaHref" -> "CTA link" */
export function humanize(key) {
    const words = String(key)
        .replace(/([a-z0-9])([A-Z])/g, '$1 $2')
        .replace(/[_-]+/g, ' ')
        .toLowerCase()
        .split(' ')
        .filter(Boolean)
        .map((w) => ACRONYMS[w] ?? w);
    const text = words.join(' ');
    return text.charAt(0).toUpperCase() + text.slice(1);
}

/** Singular for the "Add item" button: "links" -> "link", "testimonials" -> "testimonial", "faqs"/"items" handled too. */
export function singular(key) {
    const k = humanize(key).toLowerCase();
    if (k.endsWith('ies')) return `${k.slice(0, -3)}y`;
    if (k.endsWith('s')) return k.slice(0, -1);
    return k;
}
