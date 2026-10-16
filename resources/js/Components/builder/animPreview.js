import { ref } from 'vue';
import axios from 'axios';

export const ANIMATIONS = [
    { value: 'none', label: 'None' },
    { value: 'fade', label: 'Fade' },
    { value: 'fade-up', label: 'Fade up' },
    { value: 'fade-down', label: 'Fade down' },
    { value: 'from-left', label: 'From left' },
    { value: 'from-right', label: 'From right' },
    { value: 'zoom', label: 'Zoom' },
    { value: 'blur', label: 'Blur in' },
    { value: 'flip', label: 'Flip up' },
];

export const HOVER_EFFECTS = [
    { value: 'none', label: 'None' },
    { value: 'lift', label: 'Lift' },
    { value: 'zoom', label: 'Photo zoom' },
    { value: 'glow', label: 'Glow' },
];

export const BUTTON_EFFECTS = [
    { value: 'none', label: 'None', hint: 'No effect' },
    { value: 'pulse', label: 'Pulse', hint: 'A soft ring ripples out' },
    { value: 'shine', label: 'Shine', hint: 'A light sweep glides across' },
    { value: 'glow', label: 'Glow', hint: 'Breathing colour glow' },
    { value: 'bounce', label: 'Bounce', hint: 'A small hop every few seconds' },
    { value: 'wiggle', label: 'Wiggle', hint: 'A playful shake every few seconds' },
    { value: 'pop', label: 'Pop', hint: 'Grows on hover and press' },
];

export const TEXT_EFFECTS = [
    { value: 'none', label: 'None', hint: 'No heading effect' },
    { value: 'typing', label: 'Typing', hint: 'Typed out with a blinking caret' },
    { value: 'typing-loop', label: 'Type & rotate', hint: 'Types, deletes and cycles through phrases' },
    { value: 'words', label: 'Word by word', hint: 'Each word rises into place' },
    { value: 'letters', label: 'Letters', hint: 'Letters drop in one by one' },
    { value: 'gradient', label: 'Shimmer', hint: 'A colour shimmer flows through the text' },
    { value: 'highlight', label: 'Highlight', hint: 'A marker sweeps behind the words' },
];

// Mirrors BlockCatalog::ANIMATION_ALIASES — older saved values keep working.
const ALIASES = { 'slide-left': 'from-right', 'slide-right': 'from-left', 'fade-in': 'fade', 'zoom-in': 'zoom', 'blur-in': 'blur', 'flip-up': 'flip' };
const VALID = new Set([...ANIMATIONS.map((a) => a.value), 'custom']);

export function canonicalAnimation(value) {
    if (typeof value !== 'string') return null;
    const v = ALIASES[value] || value;
    return VALID.has(v) ? v : null;
}

const CARD_SECTIONS = ['features', 'services', 'pricing', 'team', 'testimonials', 'stats', 'faq', 'gallery'];

/** Site-wide presets for the Site style panel. */
export const ANIMATION_PRESETS = {
    none: () => ({ animation: 'none', animationStagger: false, hoverEffect: 'none', buttonEffect: 'none', textEffect: 'none', bgEffect: null }),
    subtle: (type) => ({
        animation: type === 'navbar' ? 'none' : 'fade',
        animationSpeed: 'normal',
        animationStagger: false,
        hoverEffect: CARD_SECTIONS.includes(type) ? 'lift' : 'none',
    }),
    smooth: (type) => ({
        animation: { navbar: 'fade-down', footer: 'fade', gallery: 'zoom' }[type] || 'fade-up',
        animationSpeed: 'normal',
        animationStagger: type !== 'navbar' && type !== 'footer',
        hoverEffect: type === 'gallery' ? 'zoom' : CARD_SECTIONS.includes(type) ? 'lift' : 'none',
    }),
    lively: (type, i) => ({
        animation: { navbar: 'fade-down', hero: 'zoom', footer: 'fade', gallery: 'blur', stats: 'flip', cta: 'zoom', pricing: 'flip' }[type] || (i % 2 ? 'from-right' : 'from-left'),
        animationSpeed: 'normal',
        animationStagger: type !== 'navbar' && type !== 'footer',
        hoverEffect: type === 'gallery' ? 'zoom' : CARD_SECTIONS.includes(type) ? 'glow' : 'none',
    }),
};

/** Bump to replay a section's animation in the editor ("Preview" button). */
export const replayRequest = ref({ id: null, n: 0 });
export function replayAnimation(id) {
    replayRequest.value = { id, n: replayRequest.value.n + 1 };
}

/* ---------- Background effects (rendered by resources/js/site/animRuntime.js) ---------- */

/** Ready-made effects; the AI can also design its own from the same building blocks. */
export function bgPresets(theme = {}) {
    const primary = theme.primaryColor || '#8b5cf6';
    const secondary = theme.secondaryColor || '#111827';
    return [
        { key: 'stars', short: 'Stars', icon: '✨', label: 'Twinkling stars', spec: { kind: 'particles', shape: 'star', colors: ['#ffffff', '#fde68a'], count: 45, sizeMin: 3, sizeMax: 9, motion: 'twinkle', speed: 'normal', opacity: 85, glow: true } },
        { key: 'sparkles', short: 'Sparkles', icon: '💫', label: 'Sparkles', spec: { kind: 'particles', shape: 'sparkle', colors: ['#fde68a', primary], count: 22, sizeMin: 6, sizeMax: 16, motion: 'twinkle', speed: 'slow', opacity: 90, glow: true } },
        { key: 'snow', short: 'Snow', icon: '❄️', label: 'Snowfall', spec: { kind: 'particles', shape: 'snowflake', colors: ['#ffffff', '#e0f2fe'], count: 55, sizeMin: 6, sizeMax: 14, motion: 'sway-fall', speed: 'normal', opacity: 85, glow: false } },
        { key: 'bubbles', short: 'Bubbles', icon: '🫧', label: 'Bubbles', spec: { kind: 'particles', shape: 'bubble', colors: ['#ffffff', '#bae6fd'], count: 22, sizeMin: 10, sizeMax: 34, motion: 'rise-sway', speed: 'normal', opacity: 70, glow: false } },
        { key: 'fireflies', short: 'Fireflies', icon: '🌟', label: 'Fireflies', spec: { kind: 'particles', shape: 'dot', colors: ['#fde047', '#facc15'], count: 30, sizeMin: 3, sizeMax: 6, motion: 'drift', speed: 'slow', opacity: 90, glow: true } },
        { key: 'petals', short: 'Petals', icon: '🌸', label: 'Falling petals', spec: { kind: 'particles', shape: 'petal', colors: ['#fbcfe8', '#f9a8d4', '#fda4af'], count: 26, sizeMin: 10, sizeMax: 18, motion: 'sway-fall', speed: 'slow', opacity: 85, glow: false } },
        { key: 'leaves', short: 'Leaves', icon: '🍂', label: 'Autumn leaves', spec: { kind: 'particles', shape: 'leaf', colors: ['#f59e0b', '#ea580c', '#b45309'], count: 22, sizeMin: 12, sizeMax: 22, motion: 'sway-fall', speed: 'slow', opacity: 90, glow: false } },
        { key: 'hearts', short: 'Hearts', icon: '💗', label: 'Floating hearts', spec: { kind: 'particles', shape: 'heart', colors: ['#f43f5e', '#fb7185', '#fda4af'], count: 18, sizeMin: 10, sizeMax: 22, motion: 'float-up', speed: 'slow', opacity: 75, glow: false } },
        { key: 'confetti', short: 'Confetti', icon: '🎉', label: 'Confetti', spec: { kind: 'particles', shape: 'confetti', colors: [primary, '#f59e0b', '#10b981', '#ec4899'], count: 45, sizeMin: 7, sizeMax: 12, motion: 'fall', speed: 'normal', opacity: 95, glow: false } },
        { key: 'rain', short: 'Rain', icon: '🌧️', label: 'Rain', spec: { kind: 'particles', shape: 'line', colors: ['#93c5fd'], count: 70, sizeMin: 10, sizeMax: 18, motion: 'rain', speed: 'normal', opacity: 55, glow: false } },
        { key: 'shooting', short: 'Meteors', icon: '🌠', label: 'Shooting stars', spec: { kind: 'particles', shape: 'line', colors: ['#ffffff'], count: 7, sizeMin: 2, sizeMax: 4, motion: 'shoot', speed: 'normal', opacity: 95, glow: true } },
        { key: 'aurora', short: 'Aurora', icon: '🌌', label: 'Aurora glow', spec: { kind: 'blobs', shape: 'dot', colors: [primary, '#22d3ee', '#a78bfa'], count: 3, sizeMin: 4, sizeMax: 10, motion: 'drift', speed: 'slow', opacity: 45, glow: false } },
        { key: 'gradient', short: 'Gradient', icon: '🎨', label: 'Moving gradient', spec: { kind: 'gradient', shape: 'dot', colors: [primary, secondary, '#a78bfa'], count: 3, sizeMin: 4, sizeMax: 10, motion: 'drift', speed: 'normal', opacity: 30, glow: false } },
    ].map((p) => ({ ...p, spec: { name: p.label, emoji: null, ...p.spec } }));
}

/** Which preset an effect came from (null = designed by the AI / customised). */
export function presetKeyOf(effect, theme) {
    if (!effect?.kind) return 'none';
    const match = bgPresets(theme).find((p) => p.spec.name === effect.name);
    return match ? match.key : null;
}

/* ---------- AI ---------- */

export const ANIMATION_INTENT = /animat|motion|transition|hover|scroll effect|reveal|stagger|one by one|fade|slide|zoom in|pop in|bounce|spin|twinkl|sparkl|glitter|stars?\b|snow|bubble|firefl|petal|confetti|falling|floating|particle|aurora|moving (background|gradient)|background effect|rain\b|shooting|typing|typewriter|type (it )?out|typed|rotating (words|text|headline)|changing (words|text)|text (effect|animation)|heading (effect|animation)|shimmer text|highlight(ed)? (the )?(heading|text|title)|word by word|letter by letter|pulse|shine|shimmer|gleam|wiggle|glow|button (effect|animation)|অ্যানিমেশন|এনিমেশন|এনিমেট|অ্যানিমেট|তারা|ঝিকমিক/i;

/**
 * Animation-only AI edit. Applies the result as ONE undo step and returns
 * { handled, summary }. handled=false means the request needs a normal edit.
 */
export async function aiAnimate(editor, instruction, selectedId = null, { extract = false } = {}) {
    const sections = editor.schema.sections.map((s) => ({ id: s.id, type: s.type, props: s.props }));
    const { data } = await axios.post(route('ai.animate'), { sections, instruction, selected: selectedId, extract, theme: editor.schema.theme });
    if (!data.handled) return { handled: false, summary: '' };

    if (data.changes.length) {
        const next = JSON.parse(JSON.stringify(editor.schema));
        for (const change of data.changes) {
            const section = next.sections.find((s) => s.id === change.id);
            if (section) section.props = { ...section.props, ...change.props };
        }
        editor.setSchema(next);
        // Show the result right away: replay every changed section that's on screen.
        setTimeout(() => data.changes.forEach((c) => replayAnimation(c.id)), 60);
    }
    // In extract mode (after a normal edit) stay quiet when there was nothing to animate.
    return { handled: true, summary: data.changes.length || !extract ? data.summary : '' };
}
