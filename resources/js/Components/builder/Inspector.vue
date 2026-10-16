<script setup>
import { computed, ref, inject } from 'vue';
import { useEditor } from '@/stores/editor.js';
import { BLOCK_TYPES } from '@/schema/defaults.js';
import ImagePicker from '@/Components/builder/ImagePicker.vue';
import CustomElementEditor from '@/Components/builder/CustomElementEditor.vue';
import Icon from '@/Components/Landing/Icon.vue';
import { blockIcons, humanize, singular, ITEM_FIELDS, CARD_VISUAL_LISTS, ITEM_PAGE_LISTS, DETAIL_FIELDS, itemFields } from './blockMeta';
import { useToast } from './useToast';
import { useLogoStudio } from './useLogoStudio';
import axios from 'axios';
import { streamAi } from './aiStream';
import { ANIMATION_INTENT, aiAnimate } from './animPreview';
import MotionPanel from './MotionPanel.vue';

const projectId = inject('projectId');
const saveNow = inject('saveNow', null);
const newOrders = inject('newOrders', 0);

const editor = useEditor();
const { notify } = useToast();
const { openStudio } = useLogoStudio();
const openLogoStudio = (hint = '') => openStudio(hint);
const LOGO_INTENT = /\blogos?\b|লোগো/i;
const aiInstruction = ref('');
const aiLoading = ref(false);
const aiMode = ref('section'); // 'section' | 'full'

const block = computed(() => editor.selectedBlock);
const blockDef = computed(() => block.value ? BLOCK_TYPES[block.value.type] : null);
const index = computed(() => (block.value ? editor.schema.sections.findIndex((s) => s.id === block.value.id) : -1));
const total = computed(() => editor.schema?.sections?.length || 0);
const STYLE_KEYS = ['sectionBg', 'sectionText', 'imageRadius', 'imageShadow', 'imageBorder', 'animation', 'animationSpeed', 'animationStagger', 'hoverEffect', 'buttonEffect', 'textEffect', 'textSpeed', 'typingPhrases', 'customAnimation', 'bgEffect', 'itemPages', 'cart'];

// Multi-line fields are chosen by name (not length) so the control never swaps mid-typing.
const LONG_TEXT = ['body', 'description', 'subheading', 'text', 'content', 'answer', 'quote', 'bio', 'message', 'about', 'paragraph'];
const isLongText = (key) => LONG_TEXT.includes(String(key).toLowerCase());
/* ---------- Lists ---------- */
const isObjectList = (key, value) => (value.length ? typeof value[0] === 'object' : !!ITEM_FIELDS[block.value?.type]?.[key]);

/** Which visual the cards use: inferred from the items themselves. */
function cardVisual(items) {
    if (items.some((it) => it.image)) return 'photo';
    if (items.some((it) => it.icon)) return 'icon';
    return 'none';
}

function visibleItemFields(key, items) {
    // Details-page fields have their own group (shown when the section's details pages are on).
    let fields = itemFields(block.value.type, key, items).filter((f) => !DETAIL_FIELDS.includes(f));
    if (CARD_VISUAL_LISTS[block.value.type] === key) {
        const mode = cardVisual(items);
        fields = fields.filter((f) => (f === 'icon' ? mode === 'icon' : f === 'image' ? mode === 'photo' : true));
    }
    return fields;
}

/* ---------- Card details pages ---------- */
const detailsOn = computed(() => !!block.value?.props.itemPages);
const isPageList = (key) => ITEM_PAGE_LISTS[block.value?.type] === key;
const missingDetails = (items) => items.filter((it) => !String(it.details || '').trim()).length;

const cartOn = computed(() => !!block.value?.props.cart);
function setCart(on) {
    update('cart', on);
    if (on && !detailsOn.value) update('itemPages', true);
    if (on) notify('Shop is on: product pages now have "Add to cart", and orders arrive in Orders.', 'success', { duration: 6500 });
}

function setItemPages(on) {
    update('itemPages', on);
    if (on) notify('Each card now opens its own details page. Fill in the details below — or let the AI write them.', 'success', { duration: 6000 });
}

function writeDetailsWithAi(key) {
    aiMode.value = 'section';
    const noun = block.value.type === 'team' ? 'team member' : 'card';
    askAi(`Turn on details pages and write the details page for every ${noun}: 2–3 short, specific paragraphs and 3–5 highlights each, plus a fitting button label. Keep all existing text, prices and photos exactly as they are.`);
}

function addPhoto(key, i, url) {
    if (!url) return;
    const photos = [...(block.value.props[key][i].photos || [])];
    if (!photos.includes(url)) updateNested(key, i, 'photos', [...photos, url].slice(0, 6));
}
function removePhoto(key, i, n) {
    updateNested(key, i, 'photos', (block.value.props[key][i].photos || []).filter((_, k) => k !== n));
}

/** Open the card's real details page (saving first so it shows the latest edits). */
async function previewDetails(i) {
    const url = route('export.previewCard', { project: projectId, sectionId: block.value.id, index: i });
    const tab = window.open('', '_blank'); // open now so pop-up blockers allow it
    if (saveNow) await saveNow();
    if (tab) tab.location.href = url;
    else window.open(url, '_blank');
}

function addItem(key, items) {
    const template = Object.fromEntries(itemFields(block.value.type, key, items).map((f) => [f, f === 'highlighted' ? false : '']));
    // Keep the new card consistent with the others
    if (CARD_VISUAL_LISTS[block.value.type] === key && cardVisual(items) === 'icon') template.icon = '✨';
    addArrayItem(key, template);
}

const fillingPhotos = ref(false);

/** Switch every card to icon / photo / nothing (removes the other so cards never show both). */
async function setCardVisual(key, items, mode) {
    const next = items.map((it) => {
        const copy = { ...it };
        if (mode !== 'photo') delete copy.image;
        if (mode !== 'icon') delete copy.icon;
        if (mode === 'icon' && !copy.icon) copy.icon = '✨';
        return copy;
    });
    update(key, next);
    if (mode === 'photo') await fillPhotos(key, next, true);
}

/** Pick matching, non-repeating photos from each card's title (no AI tokens used). */
async function fillPhotos(key, items, onlyMissing = true) {
    // Fill only empty cards, or (when all have photos) find fresh alternatives for every card.
    const targets = items.map((it, i) => ({ it, i })).filter(({ it }) => !onlyMissing || !it.image);
    if (!targets.length) return;
    fillingPhotos.value = true;
    try {
        const pageImages = JSON.stringify(editor.schema).match(/https:\/\/images\.unsplash\.com\/[^"]+/g) || [];
        const meta = editor.schema.meta || {};
        const { data } = await axios.post(route('ai.photos'), {
            queries: targets.map(({ it }) => [it.title || it.name || '', it.description || ''].join(' ').slice(0, 120)),
            hint: [meta.title, meta.description].filter(Boolean).join(' ').slice(0, 300),
            exclude: pageImages.slice(0, 100), // never repeat a photo already on the page
        });
        const next = items.map((it) => ({ ...it }));
        let filled = 0;
        targets.forEach(({ i }, n) => {
            if (data.urls[n]) {
                next[i].image = data.urls[n];
                filled++;
            }
        });
        update(key, next);
        notify(filled ? `Added ${filled} matching photo${filled === 1 ? '' : 's'} — click any card to change it.` : 'No good matches found — upload your own photos instead.', filled ? 'success' : 'info');
    } catch (e) {
        notify(e.response?.data?.message || 'Could not find photos right now.', 'error');
    } finally {
        fillingPhotos.value = false;
    }
}

// Fields every block of a type should offer, even if older saved sections don't have them yet.
const OFFERED_FIELDS = { navbar: { logo: '', hideBrandText: false } };
const LOGO_SIZES = [{ v: 'sm', l: 'Small' }, { v: 'md', l: 'Medium' }, { v: 'lg', l: 'Large' }, { v: 'xl', l: 'Extra large' }];
const displayProps = computed(() => (block.value ? { ...(OFFERED_FIELDS[block.value.type] || {}), ...block.value.props } : {}));
const hasImageStyle = computed(() => block.value && (block.value.props.image || block.value.props.imageRadius || block.value.props.imageShadow || block.value.props.imageBorder));

function update(key, value) {
    if (block.value) {
        editor.updateProps(block.value.id, { [key]: value });
    }
}

function updateNested(key, index, field, value) {
    if (!block.value) return;
    const arr = [...(block.value.props[key] || [])];
    arr[index] = { ...arr[index], [field]: value };
    editor.updateProps(block.value.id, { [key]: arr });
}

function addArrayItem(key, template) {
    if (!block.value) return;
    const arr = [...(block.value.props[key] || []), template];
    editor.updateProps(block.value.id, { [key]: arr });
}

function removeArrayItem(key, index) {
    if (!block.value) return;
    const arr = [...(block.value.props[key] || [])];
    arr.splice(index, 1);
    editor.updateProps(block.value.id, { [key]: arr });
}

// Custom block element management
const showAddElement = ref(false);
const elementTemplates = {
    heading: { type: 'heading', text: 'New Heading', size: '2xl', align: 'left' },
    text: { type: 'text', text: 'Add your text here...', size: 'base', align: 'left' },
    image: { type: 'image', src: '', alt: '', radius: '', shadow: '', border: '', width: 'full' },
    icon: { type: 'icon', emoji: '⭐', size: '4xl' },
    button: { type: 'button', text: 'Click Me', href: '#', variant: 'primary' },
    divider: { type: 'divider', style: 'solid' },
    spacer: { type: 'spacer', height: 'md' },
};
const elementTypeList = [
    { type: 'heading', icon: '𝐇', label: 'Heading' },
    { type: 'text', icon: '𝐓', label: 'Text' },
    { type: 'image', icon: '🖼', label: 'Image' },
    { type: 'icon', icon: '⭐', label: 'Icon' },
    { type: 'button', icon: '🔘', label: 'Button' },
    { type: 'divider', icon: '―', label: 'Divider' },
    { type: 'spacer', icon: '↕', label: 'Spacer' },
];

function addElement(type) {
    if (!block.value) return;
    const els = [...(block.value.props.elements || []), { ...elementTemplates[type] }];
    editor.updateProps(block.value.id, { elements: els });
    showAddElement.value = false;
}

function updateElement(index, newEl) {
    if (!block.value) return;
    const els = [...(block.value.props.elements || [])];
    els[index] = newEl;
    editor.updateProps(block.value.id, { elements: els });
}

function removeElement(index) {
    if (!block.value) return;
    const els = [...(block.value.props.elements || [])];
    els.splice(index, 1);
    editor.updateProps(block.value.id, { elements: els });
}

function isImageProp(key) {
    const k = key.toLowerCase();
    return k === 'image' || k === 'src' || k === 'avatar' || k === 'photo' || k === 'logo' || k === 'backgroundimage';
}

async function askAi(instruction) {
    const text = instruction || aiInstruction.value.trim();
    if (!text || aiLoading.value) return;
    if (LOGO_INTENT.test(text)) {
        aiInstruction.value = '';
        openLogoStudio(text);
        return;
    }
    aiLoading.value = true;
    let summary = '';
    try {
        // Animation-only requests: a fast edit that can't touch any content.
        if (ANIMATION_INTENT.test(text)) {
            const result = await aiAnimate(editor, text, aiMode.value === 'section' ? block.value?.id : null);
            if (result.handled) {
                aiInstruction.value = '';
                notify(result.summary, 'success', { duration: 6000 });
                return;
            }
        }
        const wantsMotion = ANIMATION_INTENT.test(text);
        if (aiMode.value === 'section' && block.value) {
            // Edit only the selected section
            const { data } = await axios.post(route('ai.editSection'), {
                section: block.value,
                theme: editor.schema.theme,
                instruction: text,
            });
            editor.updateSection(block.value.id, data.section);
            summary = data.summary;
        } else {
            // Edit the full site (streamed so long edits don't time out)
            const data = await streamAi(route('ai.edit'), { schema: editor.schema, instruction: text });
            editor.setSchema(data.schema);
            summary = data.summary;
        }
        // Mixed request ("make it red and add falling stars"): now add the motion part too.
        if (wantsMotion) {
            const motion = await aiAnimate(editor, text, aiMode.value === 'section' ? block.value?.id : null, { extract: true }).catch(() => null);
            if (motion?.summary) summary = `${summary} ${motion.summary}`.trim();
        }
        aiInstruction.value = '';
        notify(summary || (aiMode.value === 'section' ? 'Section updated with AI' : 'Site updated with AI'), 'success', { duration: 6000 });
    } catch (e) {
        notify(e.response?.data?.error || e.message || 'AI edit failed. Please try again.', 'error');
    } finally {
        aiLoading.value = false;
    }
}

// Quick action chips per block type
const quickActions = computed(() => {
    if (!block.value) return [];
    const type = block.value.type;
    const common = [
        { label: 'Make more professional', icon: '✨' },
        { label: 'Improve the copy', icon: '✍️' },
    ];
    const typeActions = {
        navbar: [
            { label: 'Add more nav links', icon: '🔗' },
            { label: 'Make brand name bolder', icon: '💪' },
        ],
        hero: [
            { label: 'Make heading more impactful', icon: '🎯' },
            { label: 'Add a subheading', icon: '📝' },
            { label: 'Change CTA text to something catchy', icon: '🚀' },
        ],
        features: [
            { label: 'Add 3 more features', icon: '➕' },
            { label: 'Rewrite with better descriptions', icon: '📖' },
            { label: 'Change icons to more relevant ones', icon: '🎨' },
        ],
        gallery: [
            { label: 'Add more images', icon: '🖼️' },
            { label: 'Write better captions', icon: '📝' },
        ],
        about: [
            { label: 'Make story more compelling', icon: '📖' },
            { label: 'Add mission and vision', icon: '🎯' },
        ],
        testimonials: [
            { label: 'Add 2 more testimonials', icon: '➕' },
            { label: 'Make reviews more detailed', icon: '⭐' },
        ],
        pricing: [
            { label: 'Adjust pricing tiers', icon: '💰' },
            { label: 'Add more features to each plan', icon: '📋' },
            { label: 'Highlight the best plan', icon: '🏆' },
        ],
        services: [
            { label: 'Add more services', icon: '➕' },
            { label: 'Write detailed descriptions', icon: '📖' },
        ],
        stats: [
            { label: 'Add more stats', icon: '📊' },
            { label: 'Update numbers to be more impressive', icon: '🔢' },
        ],
        faq: [
            { label: 'Add 3 more questions', icon: '❓' },
            { label: 'Make answers more detailed', icon: '📝' },
        ],
        team: [
            { label: 'Add more team members', icon: '👥' },
            { label: 'Add bios for each member', icon: '📄' },
        ],
        cta: [
            { label: 'Make more urgency-driven', icon: '⚡' },
            { label: 'Change CTA to something compelling', icon: '🎯' },
        ],
        contact: [
            { label: 'Add address and phone', icon: '📍' },
            { label: 'Add social media links', icon: '🌐' },
        ],
        footer: [
            { label: 'Add more footer links', icon: '🔗' },
            { label: 'Add social media icons', icon: '📱' },
        ],
    };
    return [...(typeActions[type] || []), ...common];
});
</script>

<template>
    <div>
        <!-- Nothing selected -->
        <div v-if="!block" class="flex flex-col items-center px-6 py-16 text-center">
            <span class="icon-tile size-12 rounded-2xl"><Icon name="cursorClick" :stroke="1.6" class="size-5" /></span>
            <p class="mt-4 text-sm font-semibold text-lp-fg">Select a section</p>
            <p class="mt-1 text-[13px] leading-relaxed text-lp-muted">Click any part of your website, or a layer on the left, to edit its content and style.</p>
            <div class="mt-8 w-full space-y-2 text-left text-xs text-lp-muted">
                <div class="ed-heading mb-3 text-center">Shortcuts</div>
                <div class="flex justify-between"><span>Undo</span><span><kbd class="kbd">⌘</kbd> <kbd class="kbd">Z</kbd></span></div>
                <div class="flex justify-between"><span>Redo</span><span><kbd class="kbd">⌘</kbd> <kbd class="kbd">⇧</kbd> <kbd class="kbd">Z</kbd></span></div>
                <div class="flex justify-between"><span>Ask AI</span><span><kbd class="kbd">⌘</kbd> <kbd class="kbd">K</kbd></span></div>
                <div class="flex justify-between"><span>Delete section</span><kbd class="kbd">Del</kbd></div>
                <div class="flex justify-between"><span>Deselect</span><kbd class="kbd">Esc</kbd></div>
            </div>
        </div>

        <div v-else>
            <!-- Header -->
            <div class="sticky top-0 z-10 flex items-center gap-3 border-b border-lp-ink/[0.07] bg-lp-bg/95 px-4 py-3 backdrop-blur">
                <span class="icon-solid size-9 rounded-xl"><Icon :name="blockIcons[block.type] || 'grid'" :stroke="1.7" class="size-[18px]" /></span>
                <div class="min-w-0 flex-1">
                    <h3 class="truncate text-sm font-semibold text-lp-fg">{{ blockDef?.label || block.type }}</h3>
                    <p class="text-[11px] text-lp-subtle">Section {{ index + 1 }} of {{ total }}</p>
                </div>
                <div class="flex items-center">
                    <button type="button" class="ed-icon-btn size-7" title="Move up" :disabled="index <= 0" @click="editor.moveBlock(block.id, -1)"><Icon name="chevronUp" :stroke="2" class="size-4" /></button>
                    <button type="button" class="ed-icon-btn size-7" title="Move down" :disabled="index >= total - 1" @click="editor.moveBlock(block.id, 1)"><Icon name="chevronDown" :stroke="2" class="size-4" /></button>
                    <button type="button" class="ed-icon-btn size-7" title="Duplicate" @click="editor.duplicateBlock(block.id)"><Icon name="duplicate" :stroke="1.7" class="size-4" /></button>
                    <button type="button" class="ed-icon-btn ed-icon-btn--danger size-7" title="Delete section" @click="editor.removeBlock(block.id)"><Icon name="trash" :stroke="1.7" class="size-4" /></button>
                </div>
            </div>

            <div class="space-y-6 p-4">
                <!-- AI quick actions -->
                <section class="ai-card rounded-2xl p-3.5">
                    <div class="flex items-center justify-between gap-2">
                        <span class="flex items-center gap-1.5 text-[13px] font-semibold text-lp-fg"><Icon name="sparkles" :stroke="1.8" class="size-4 text-lp-accent" />AI assist</span>
                        <div class="flex rounded-lg bg-lp-ink/[0.06] p-0.5 text-[11px] font-medium">
                            <button type="button" :class="['rounded-md px-2 py-1 transition', aiMode === 'section' ? 'bg-lp-surface text-lp-fg shadow-sm' : 'text-lp-muted hover:text-lp-fg']" @click="aiMode = 'section'">This section</button>
                            <button type="button" :class="['rounded-md px-2 py-1 transition', aiMode === 'full' ? 'bg-lp-surface text-lp-fg shadow-sm' : 'text-lp-muted hover:text-lp-fg']" @click="aiMode = 'full'">Full site</button>
                        </div>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-1.5">
                        <button
                            v-for="action in quickActions"
                            :key="action.label"
                            type="button"
                            :disabled="aiLoading"
                            class="ed-option !rounded-full !px-2.5 !py-1 !text-[11px] disabled:opacity-40"
                            @click="askAi(action.label)"
                        >{{ action.label }}</button>
                    </div>
                    <div class="mt-3 flex gap-2">
                        <input
                            v-model="aiInstruction"
                            type="text"
                            :placeholder="aiMode === 'section' ? `Tell AI how to change this ${(blockDef?.label || block.type).toLowerCase()}…` : 'Tell AI how to change the site…'"
                            class="ed-input ed-input-sm flex-1"
                            :disabled="aiLoading"
                            @keydown.enter="askAi()"
                        />
                        <button type="button" class="btn btn-primary h-8 shrink-0 !rounded-lg px-3 text-xs" :disabled="aiLoading || !aiInstruction.trim()" @click="askAi()">
                            <svg v-if="aiLoading" class="size-3.5 animate-spin" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-opacity=".25" stroke-width="3" /><path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round" /></svg>
                            {{ aiLoading ? 'Working' : 'Apply' }}
                        </button>
                    </div>
                </section>

                <!-- Custom block layout & elements -->
                <section v-if="block.type === 'custom'" class="space-y-4">
                    <h4 class="ed-heading">Layout</h4>
                    <div class="grid grid-cols-3 gap-2">
                        <div>
                            <label class="ed-label">Columns</label>
                            <select :value="block.props.columns || 1" class="ed-select ed-input-sm" @change="update('columns', Number($event.target.value))">
                                <option :value="1">1</option><option :value="2">2</option><option :value="3">3</option><option :value="4">4</option>
                            </select>
                        </div>
                        <div>
                            <label class="ed-label">Gap</label>
                            <select :value="block.props.gap || 'md'" class="ed-select ed-input-sm" @change="update('gap', $event.target.value)">
                                <option value="sm">Small</option><option value="md">Medium</option><option value="lg">Large</option><option value="xl">XL</option>
                            </select>
                        </div>
                        <div>
                            <label class="ed-label">Padding</label>
                            <select :value="block.props.padding || 'lg'" class="ed-select ed-input-sm" @change="update('padding', $event.target.value)">
                                <option value="sm">Small</option><option value="md">Medium</option><option value="lg">Large</option><option value="xl">XL</option>
                            </select>
                        </div>
                    </div>

                    <h4 class="ed-heading pt-2">Elements</h4>
                    <div class="space-y-2">
                        <CustomElementEditor
                            v-for="(el, i) in (block.props.elements || [])"
                            :key="i"
                            :element="el"
                            :index="i"
                            @update="updateElement"
                            @remove="removeElement"
                        />
                    </div>
                    <div class="relative">
                        <button type="button" class="btn btn-ghost h-9 w-full text-xs" @click="showAddElement = !showAddElement"><Icon name="plus" :stroke="2" class="size-3.5" />Add element</button>
                        <div v-if="showAddElement" class="menu-pop absolute bottom-full left-0 right-0 z-30 mb-2 grid grid-cols-2 gap-1 p-1.5">
                            <button v-for="et in elementTypeList" :key="et.type" type="button" class="flex items-center gap-2 rounded-lg px-2.5 py-2 text-left text-xs text-lp-text transition hover:bg-lp-ink/[0.06]" @click="addElement(et.type)">
                                <span class="w-4 text-center text-lp-muted">{{ et.icon }}</span>{{ et.label }}
                            </button>
                        </div>
                    </div>
                </section>

                <!-- Content fields -->
                <section v-if="block.type !== 'custom'" class="space-y-4">
                    <h4 class="ed-heading">Content</h4>
                    <template v-for="(value, key) in displayProps" :key="key">
                        <template v-if="STYLE_KEYS.includes(key)" />

                        <!-- Text / image / colour -->
                        <div v-else-if="typeof value === 'string'">
                            <label class="ed-label">{{ humanize(key) }}</label>
                            <ImagePicker v-if="isImageProp(key)" :model-value="value" :project-id="projectId" @update:model-value="update(key, $event)" />
                            <template v-if="block.type === 'navbar' && key === 'logo'">
                                <button type="button" class="logo-ai-btn mt-2" @click="openLogoStudio()">
                                    <Icon name="sparkles" :stroke="1.8" class="size-4" />Design a logo with AI
                                </button>
                                <p class="mt-1.5 text-[11px] leading-relaxed text-lp-subtle">Or upload your own — PNG or SVG with a transparent background works best.</p>
                            </template>
                            <textarea v-else-if="isLongText(key)" :value="value" rows="3" class="ed-textarea" @input="update(key, $event.target.value)"></textarea>
                            <div v-else-if="key.toLowerCase().includes('color')" class="flex items-center gap-2">
                                <input type="color" :value="value" class="ed-color" @input="update(key, $event.target.value)" />
                                <input type="text" :value="value" class="ed-input font-mono" @input="update(key, $event.target.value)" />
                            </div>
                            <input v-else type="text" :value="value" class="ed-input" @input="update(key, $event.target.value)" />
                        </div>

                        <!-- Logo size (navbar) -->
                        <div v-else-if="key === 'logoSize'" />

                        <!-- On/off options -->
                        <label v-else-if="typeof value === 'boolean'" class="flex cursor-pointer items-center justify-between gap-3">
                            <span class="ed-label !mb-0">{{ key === 'hideBrandText' ? 'Show only the logo (hide brand text)' : humanize(key) }}</span>
                            <button type="button" role="switch" :aria-checked="value" :class="['toggle', value && 'toggle--on']" @click="update(key, !value)"><span></span></button>
                        </label>
                        <div v-if="key === 'hideBrandText' && block.props.logo" class="!mt-3">
                            <label class="ed-label">Logo size</label>
                            <div class="grid grid-cols-4 gap-1">
                                <button v-for="z in LOGO_SIZES" :key="z.v" type="button" :class="['ed-option !px-1 !py-1.5 text-[11.5px]', (block.props.logoSize || (block.props.hideBrandText ? 'md' : 'sm')) === z.v && 'ed-option--active']" @click="update('logoSize', z.v)">{{ z.l }}</button>
                            </div>
                        </div>

                        <!-- Numbers -->
                        <div v-else-if="typeof value === 'number'">
                            <label class="ed-label">{{ humanize(key) }}</label>
                            <input type="number" :value="value" min="1" max="6" class="ed-input" @input="update(key, Number($event.target.value))" />
                        </div>

                        <!-- Lists of objects (links, features, plans…) -->
                        <div v-else-if="Array.isArray(value) && (isObjectList(key, value))">
                            <div class="mb-2 flex items-center justify-between">
                                <label class="ed-label !mb-0">{{ humanize(key) }} <span class="text-lp-subtle">· {{ value.length }}</span></label>
                            </div>

                            <!-- Details pages: each card opens its own page -->
                            <div v-if="isPageList(key)" :class="['details-box mb-3', detailsOn && 'details-box--on']">
                                <label class="flex cursor-pointer items-start justify-between gap-3">
                                    <span class="min-w-0">
                                        <span class="flex items-center gap-1.5 text-[12px] font-semibold text-lp-text"><Icon name="externalLink" :stroke="1.9" class="size-3.5 text-lp-accent" />Details pages</span>
                                        <span class="mt-0.5 block text-[11px] leading-snug text-lp-subtle">Clicking a {{ block.type === 'team' ? 'person' : 'card' }} opens its own page with photos, full description{{ block.type === 'services' ? ', price' : '' }} and a button.</span>
                                    </span>
                                    <button type="button" role="switch" :aria-checked="detailsOn" :class="['toggle mt-0.5', detailsOn && 'toggle--on']" @click="setItemPages(!detailsOn)"><span></span></button>
                                </label>
                                <template v-if="detailsOn">
                                    <button type="button" class="logo-ai-btn mt-2.5" :disabled="aiLoading" @click="writeDetailsWithAi(key)">
                                        <svg v-if="aiLoading" viewBox="0 0 24 24" fill="none" class="size-4 animate-spin"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-opacity=".25" stroke-width="3" /><path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round" /></svg>
                                        <Icon v-else name="sparkles" :stroke="1.8" class="size-4" />
                                        {{ missingDetails(value) ? `Write details for ${missingDetails(value) === value.length ? 'every' : missingDetails(value)} ${missingDetails(value) === 1 ? singular(key) : 'card'}${missingDetails(value) > 1 && missingDetails(value) < value.length ? 's' : ''} with AI` : 'Rewrite all details with AI' }}
                                    </button>
                                    <p class="mt-2 text-[11px] text-lp-subtle">Open a card below → <span class="font-medium text-lp-muted">Details page</span> to edit it or preview it.</p>

                                    <!-- Online shop -->
                                    <div v-if="block.type === 'services'" class="mt-3 border-t border-lp-ink/[0.07] pt-3">
                                        <label class="flex cursor-pointer items-start justify-between gap-3">
                                            <span class="min-w-0">
                                                <span class="flex items-center gap-1.5 text-[12px] font-semibold text-lp-text">
                                                    <svg class="size-3.5 text-lp-accent" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 4h2l2.4 11.2a2 2 0 0 0 2 1.6h7.7a2 2 0 0 0 2-1.5L21 8H6.2" /><circle cx="10" cy="20" r="1.2" /><circle cx="17" cy="20" r="1.2" /></svg>
                                                    Add to cart
                                                </span>
                                                <span class="mt-0.5 block text-[11px] leading-snug text-lp-subtle">Visitors pick a quantity, add items to a cart and send you an order (no online payment).</span>
                                            </span>
                                            <button type="button" role="switch" :aria-checked="cartOn" :class="['toggle mt-0.5', cartOn && 'toggle--on']" @click="setCart(!cartOn)"><span></span></button>
                                        </label>
                                        <div v-if="cartOn" class="mt-2 space-y-1.5">
                                            <p v-if="value.some((it) => !it.price)" class="text-[11px] text-amber-600">{{ value.filter((it) => !it.price).length }} item(s) have no price — they'll show as “Price on request”.</p>
                                            <a :href="route('orders.index', projectId)" class="flex items-center justify-between rounded-lg border border-lp-ink/[0.08] px-2.5 py-1.5 text-[11.5px] font-semibold text-lp-text transition hover:border-violet-400/60">
                                                View orders
                                                <span v-if="newOrders" class="rounded-full bg-violet-500 px-1.5 text-[10px] leading-4 text-white">{{ newOrders }} new</span>
                                                <Icon v-else name="arrowUpRight" :stroke="2" class="size-3 text-lp-subtle" />
                                            </a>
                                            <p class="text-[11px] text-lp-subtle">Orders only work on the published site. Customers can also order on WhatsApp if your Contact section has a phone number.</p>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <!-- Card visual: icon, photo or nothing -->
                            <div v-if="CARD_VISUAL_LISTS[block.type] === key" class="visual-box mb-3">
                                <div class="text-[11px] font-medium text-lp-muted">Card visual</div>
                                <div class="mt-1.5 grid grid-cols-3 gap-1">
                                    <button v-for="m in [{ k: 'icon', l: 'Icon', i: 'sparkles' }, { k: 'photo', l: 'Photo', i: 'photo' }, { k: 'none', l: 'None', i: 'xMark' }]" :key="m.k" type="button"
                                        :class="['ed-option !py-1.5', cardVisual(value) === m.k && 'ed-option--active']" :disabled="fillingPhotos" @click="setCardVisual(key, value, m.k)">
                                        <Icon :name="m.i" :stroke="1.8" class="size-3.5" />{{ m.l }}
                                    </button>
                                </div>
                                <button v-if="cardVisual(value) === 'photo'" type="button" class="logo-ai-btn mt-2" :disabled="fillingPhotos" @click="fillPhotos(key, value, value.some((it) => !it.image))">
                                    <svg v-if="fillingPhotos" viewBox="0 0 24 24" fill="none" class="size-4 animate-spin"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-opacity=".25" stroke-width="3" /><path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round" /></svg>
                                    <Icon v-else name="sparkles" :stroke="1.8" class="size-4" />
                                    {{ value.some((it) => !it.image) ? 'Fill missing photos to match each card' : 'Find new matching photos' }}
                                </button>
                            </div>

                            <div class="space-y-2">
                                <details v-for="(item, i) in value" :key="i" class="ed-item group/item" :open="value.length <= 3">
                                    <summary class="flex cursor-pointer list-none items-center gap-2 px-3 py-2 text-xs font-medium text-lp-text">
                                        <Icon name="chevronDown" :stroke="2" class="size-3.5 -rotate-90 text-lp-subtle transition-transform group-open/item:rotate-0" />
                                        <img v-if="item.image || item.avatar || item.src" :src="item.image || item.avatar || item.src" alt="" class="size-6 shrink-0 rounded object-cover" />
                                        <span v-else-if="item.icon" class="w-6 shrink-0 text-center text-sm">{{ item.icon }}</span>
                                        <span class="min-w-0 flex-1 truncate">{{ item.title || item.label || item.name || item.question || item.value || item.author || item.caption || `${singular(key)} ${i + 1}` }}</span>
                                        <span v-if="isPageList(key) && detailsOn && !String(item.details || '').trim()" class="shrink-0 rounded-full bg-amber-500/15 px-1.5 py-px text-[10px] font-semibold text-amber-600" title="This card's details page has no description yet">No details</span>
                                        <button type="button" class="ed-icon-btn ed-icon-btn--danger size-6" :title="`Remove ${singular(key)}`" @click.prevent="removeArrayItem(key, i)"><Icon name="xMark" :stroke="2" class="size-3.5" /></button>
                                    </summary>
                                    <div class="space-y-2.5 border-t border-lp-ink/[0.06] px-3 pb-3 pt-2.5">
                                        <div v-for="field in visibleItemFields(key, value)" :key="field">
                                            <!-- On/off -->
                                            <label v-if="typeof item[field] === 'boolean' || field === 'highlighted'" class="flex cursor-pointer items-center justify-between gap-3">
                                                <span class="ed-label !mb-0 !text-[11px]">{{ field === 'highlighted' ? 'Recommended plan' : humanize(field) }}</span>
                                                <button type="button" role="switch" :aria-checked="!!item[field]" :class="['toggle', item[field] && 'toggle--on']" @click="updateNested(key, i, field, !item[field])"><span></span></button>
                                            </label>
                                            <template v-else>
                                                <label class="ed-label !text-[11px]">{{ field === 'icon' ? 'Icon (emoji)' : humanize(field) }}</label>
                                                <ImagePicker v-if="isImageProp(field)" :model-value="item[field] || ''" :project-id="projectId" @update:model-value="updateNested(key, i, field, $event)" />
                                                <textarea v-else-if="Array.isArray(item[field])" :value="item[field].join('\n')" rows="4" placeholder="One per line" class="ed-textarea ed-input-sm" @change="updateNested(key, i, field, $event.target.value.split('\n').map((l) => l.trim()).filter(Boolean))"></textarea>
                                                <textarea v-else-if="isLongText(field) || field === 'quote'" :value="item[field] || ''" rows="3" class="ed-textarea ed-input-sm" @input="updateNested(key, i, field, $event.target.value)"></textarea>
                                                <input v-else type="text" :value="item[field] ?? ''" :placeholder="field === 'icon' ? 'e.g. 👕' : humanize(field)" :class="['ed-input ed-input-sm', field === 'icon' && '!w-20 text-center !text-base']" @input="updateNested(key, i, field, $event.target.value)" />
                                            </template>
                                        </div>

                                        <!-- This card's details page -->
                                        <div v-if="isPageList(key) && detailsOn" class="page-box">
                                            <div class="flex items-center justify-between gap-2">
                                                <span class="text-[11px] font-semibold uppercase tracking-wider text-lp-accent">Details page</span>
                                                <button type="button" class="inline-flex items-center gap-1 text-[11px] font-semibold text-lp-accent hover:opacity-80" @click="previewDetails(i)">
                                                    Preview page<Icon name="arrowUpRight" :stroke="2" class="size-3" />
                                                </button>
                                            </div>
                                            <div>
                                                <label class="ed-label !text-[11px]">Full description</label>
                                                <textarea :value="item.details || ''" rows="5" placeholder="A few short paragraphs: what it is, what's included, who it's for. Leave a blank line between paragraphs." class="ed-textarea ed-input-sm" @input="updateNested(key, i, 'details', $event.target.value)"></textarea>
                                            </div>
                                            <div>
                                                <label class="ed-label !text-[11px]">Highlights <span class="font-normal text-lp-subtle">· one per line, shown as a checklist</span></label>
                                                <textarea :value="(item.highlights || []).join('\n')" rows="3" placeholder="100% organic cotton&#10;Free returns within 30 days" class="ed-textarea ed-input-sm" @change="updateNested(key, i, 'highlights', $event.target.value.split('\n').map((l) => l.trim()).filter(Boolean).slice(0, 6))"></textarea>
                                            </div>
                                            <div v-if="block.type !== 'team'">
                                                <label class="ed-label !text-[11px]">More photos <span class="font-normal text-lp-subtle">· shown as a gallery</span></label>
                                                <div class="flex flex-wrap gap-1.5">
                                                    <div v-for="(photo, n) in item.photos || []" :key="photo" class="group/ph relative size-12 overflow-hidden rounded-md border border-lp-ink/10">
                                                        <img :src="photo" alt="" class="size-full object-cover" />
                                                        <button type="button" class="absolute inset-0 flex items-center justify-center bg-black/55 text-white opacity-0 transition-opacity group-hover/ph:opacity-100" title="Remove photo" @click="removePhoto(key, i, n)"><Icon name="xMark" :stroke="2.2" class="size-4" /></button>
                                                    </div>
                                                </div>
                                                <div v-if="(item.photos || []).length < 6" class="mt-1.5">
                                                    <ImagePicker :model-value="''" :project-id="projectId" @update:model-value="addPhoto(key, i, $event)" />
                                                </div>
                                            </div>
                                            <div class="grid grid-cols-2 gap-2">
                                                <div>
                                                    <label class="ed-label !text-[11px]">Button text</label>
                                                    <input type="text" :value="item.ctaText || ''" :placeholder="item.price ? 'Order now' : 'Get in touch'" class="ed-input ed-input-sm" @input="updateNested(key, i, 'ctaText', $event.target.value)" />
                                                </div>
                                                <div>
                                                    <label class="ed-label !text-[11px]">Button link</label>
                                                    <input type="text" :value="item.ctaHref || ''" placeholder="#contact" class="ed-input ed-input-sm" @input="updateNested(key, i, 'ctaHref', $event.target.value)" />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </details>
                                <p v-if="!value.length" class="rounded-lg border border-dashed border-lp-ink/15 px-3 py-4 text-center text-xs text-lp-subtle">No {{ humanize(key).toLowerCase() }} yet.</p>
                            </div>
                            <button type="button" class="mt-2 inline-flex items-center gap-1 text-xs font-semibold text-lp-accent hover:opacity-80" @click="addItem(key, value)">
                                <Icon name="plus" :stroke="2.2" class="size-3.5" />Add {{ singular(key) }}
                            </button>
                        </div>

                        <!-- Lists of strings (e.g. contact form fields) -->
                        <div v-else-if="Array.isArray(value)">
                            <label class="ed-label">{{ humanize(key) }}</label>
                            <textarea :value="value.join('\n')" rows="4" placeholder="One per line" class="ed-textarea ed-input-sm" @change="update(key, $event.target.value.split('\n').map((l) => l.trim()).filter(Boolean))"></textarea>
                            <p class="mt-1 text-[11px] text-lp-subtle">One per line.</p>
                        </div>
                    </template>
                </section>

                <!-- Style -->
                <details class="group/style border-t border-lp-ink/[0.07] pt-5" open>
                    <summary class="flex cursor-pointer list-none items-center justify-between">
                        <h4 class="ed-heading">Style</h4>
                        <Icon name="chevronDown" :stroke="2" class="size-4 text-lp-subtle transition-transform group-open/style:rotate-180" />
                    </summary>
                    <div class="mt-4 space-y-4">
                        <div>
                            <label class="ed-label">Background</label>
                            <div class="flex items-center gap-2">
                                <input type="color" :value="block.props.sectionBg || '#ffffff'" class="ed-color" @input="update('sectionBg', $event.target.value)" />
                                <input type="text" :value="block.props.sectionBg || ''" placeholder="Theme default" class="ed-input font-mono" @input="update('sectionBg', $event.target.value)" />
                                <button v-if="block.props.sectionBg" type="button" class="ed-icon-btn" title="Reset to theme" @click="update('sectionBg', '')"><Icon name="undo" :stroke="1.8" class="size-4" /></button>
                            </div>
                        </div>
                        <div>
                            <label class="ed-label">Text color</label>
                            <div class="flex items-center gap-2">
                                <input type="color" :value="block.props.sectionText || '#1a1a1a'" class="ed-color" @input="update('sectionText', $event.target.value)" />
                                <input type="text" :value="block.props.sectionText || ''" placeholder="Theme default" class="ed-input font-mono" @input="update('sectionText', $event.target.value)" />
                                <button v-if="block.props.sectionText" type="button" class="ed-icon-btn" title="Reset to theme" @click="update('sectionText', '')"><Icon name="undo" :stroke="1.8" class="size-4" /></button>
                            </div>
                        </div>

                        <div v-if="hasImageStyle" class="grid grid-cols-3 gap-2">
                            <div>
                                <label class="ed-label">Image radius</label>
                                <select :value="block.props.imageRadius || ''" class="ed-select ed-input-sm" @change="update('imageRadius', $event.target.value)">
                                    <option value="">Default</option><option value="none">None</option><option value="sm">Small</option><option value="md">Medium</option><option value="lg">Large</option><option value="xl">XL</option><option value="full">Full</option>
                                </select>
                            </div>
                            <div>
                                <label class="ed-label">Shadow</label>
                                <select :value="block.props.imageShadow || ''" class="ed-select ed-input-sm" @change="update('imageShadow', $event.target.value)">
                                    <option value="">None</option><option value="sm">Small</option><option value="md">Medium</option><option value="lg">Large</option><option value="xl">XL</option>
                                </select>
                            </div>
                            <div>
                                <label class="ed-label">Border</label>
                                <select :value="block.props.imageBorder || ''" class="ed-select ed-input-sm" @change="update('imageBorder', $event.target.value)">
                                    <option value="">None</option><option value="thin">Thin</option><option value="thick">Thick</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </details>

                <!-- Motion: entrance, heading, buttons, hover, background -->
                <details class="group/motion border-t border-lp-ink/[0.07] pt-5" open>
                    <summary class="flex cursor-pointer list-none items-center justify-between">
                        <h4 class="ed-heading">Motion</h4>
                        <Icon name="chevronDown" :stroke="2" class="size-4 text-lp-subtle transition-transform group-open/motion:rotate-180" />
                    </summary>
                    <div class="mt-4">
                        <MotionPanel :block="block" @ask="askAi" />
                    </div>
                </details>
            </div>
        </div>
    </div>
</template>

<style scoped>
.details-box {
    border-radius: 0.75rem;
    border: 1px solid rgb(var(--lp-ink) / 0.09);
    background: rgb(var(--lp-ink) / 0.02);
    padding: 0.7rem 0.75rem;
    transition: border-color 0.2s, background-color 0.2s;
}
.details-box--on { border-color: rgb(139 92 246 / 0.35); background: linear-gradient(180deg, rgb(139 92 246 / 0.08), rgb(139 92 246 / 0.02)); }
.page-box {
    display: grid;
    gap: 0.6rem;
    margin-top: 0.35rem;
    border-radius: 0.6rem;
    border: 1px dashed rgb(139 92 246 / 0.35);
    background: rgb(139 92 246 / 0.04);
    padding: 0.65rem;
}
.ai-card {
    border: 1px solid rgb(139 92 246 / 0.25);
    background: linear-gradient(180deg, rgb(139 92 246 / 0.09), rgb(139 92 246 / 0.02));
}
.logo-ai-btn {
    display: inline-flex;
    width: 100%;
    align-items: center;
    justify-content: center;
    gap: 0.45rem;
    border-radius: 0.6rem;
    border: 1px solid rgb(139 92 246 / 0.35);
    background: linear-gradient(180deg, rgb(139 92 246 / 0.14), rgb(139 92 246 / 0.06));
    padding: 0.5rem 0.75rem;
    font-size: 12px;
    font-weight: 600;
    color: rgb(var(--lp-accent));
    transition: border-color 0.15s, background-color 0.15s;
}
.logo-ai-btn:hover { border-color: rgb(139 92 246 / 0.6); }
.toggle {
    position: relative;
    width: 2.25rem;
    height: 1.25rem;
    flex-shrink: 0;
    border-radius: 9999px;
    background: rgb(var(--lp-ink) / 0.15);
    transition: background-color 0.2s;
}
.toggle span {
    position: absolute;
    left: 2px;
    top: 2px;
    width: 1rem;
    height: 1rem;
    border-radius: 9999px;
    background: #fff;
    box-shadow: 0 1px 3px rgb(0 0 0 / 0.3);
    transition: transform 0.2s cubic-bezier(0.22, 1, 0.36, 1);
}
.toggle--on { background: #8b5cf6; }
.toggle--on span { transform: translateX(1rem); }
.menu-pop {
    border-radius: 0.9rem;
    border: 1px solid rgb(var(--lp-ink) / 0.1);
    background: rgb(var(--lp-surface));
    box-shadow: 0 20px 50px -12px rgb(0 0 0 / 0.35);
}
.kbd {
    border-radius: 0.3rem;
    border: 1px solid rgb(var(--lp-ink) / 0.15);
    padding: 0 0.3rem;
    font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
    font-size: 10px;
}
summary::-webkit-details-marker { display: none; }
.btn:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }
</style>
