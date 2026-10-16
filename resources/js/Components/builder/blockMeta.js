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

/**
 * Every field a list item can have, per block and list (mirrors app/Services/Ai/BlockCatalog.php).
 * The Inspector shows all of them — even ones an item doesn't have yet — so you can
 * always add an image, price, avatar, etc.
 */
export const ITEM_FIELDS = {
    navbar: { links: ['label', 'href'] },
    features: { items: ['icon', 'image', 'title', 'description', 'details', 'highlights', 'photos', 'ctaText', 'ctaHref'] },
    services: { items: ['icon', 'image', 'title', 'description', 'price', 'details', 'highlights', 'photos', 'ctaText', 'ctaHref'] },
    gallery: { images: ['src', 'caption'] },
    testimonials: { items: ['quote', 'author', 'role', 'avatar'] },
    pricing: { plans: ['name', 'price', 'period', 'ctaText', 'highlighted'] },
    stats: { items: ['value', 'label'] },
    faq: { items: ['question', 'answer'] },
    team: { members: ['image', 'name', 'role', 'bio', 'details', 'highlights', 'ctaText', 'ctaHref'] },
    footer: { links: ['label', 'href'], socials: ['platform', 'url'] },
};

/** Sections whose cards can open their own details page (mirrors BlockCatalog::ITEM_PAGE_LISTS). */
export const ITEM_PAGE_LISTS = { services: 'items', features: 'items', team: 'members' };
/** Card fields edited in the card's "Details page" group, not with the card itself. */
export const DETAIL_FIELDS = ['details', 'highlights', 'photos', 'ctaText', 'ctaHref'];

/** Lists whose cards can show an icon or a photo. */
export const CARD_VISUAL_LISTS = { features: 'items', services: 'items' };

export function itemFields(blockType, listKey, items = []) {
    const known = ITEM_FIELDS[blockType]?.[listKey] || [];
    const extra = [];
    for (const item of items) {
        for (const key of Object.keys(item || {})) {
            if (!known.includes(key) && !extra.includes(key)) extra.push(key);
        }
    }
    return [...known, ...extra];
}
