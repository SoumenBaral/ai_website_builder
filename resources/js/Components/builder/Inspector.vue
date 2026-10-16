<script setup>
import { computed, ref, inject } from 'vue';
import { useEditor } from '@/stores/editor.js';
import { BLOCK_TYPES } from '@/schema/defaults.js';
import ImagePicker from '@/Components/builder/ImagePicker.vue';
import CustomElementEditor from '@/Components/builder/CustomElementEditor.vue';
import Icon from '@/Components/Landing/Icon.vue';
import { blockIcons, humanize, singular, ITEM_FIELDS, CARD_VISUAL_LISTS, itemFields } from './blockMeta';
import { useToast } from './useToast';
import { useLogoStudio } from './useLogoStudio';
import axios from 'axios';
import { streamAi } from './aiStream';
import { ANIMATIONS, HOVER_EFFECTS, BUTTON_EFFECTS, TEXT_EFFECTS, replayAnimation, canonicalAnimation, ANIMATION_INTENT, aiAnimate, bgPresets, presetKeyOf } from './animPreview';

const projectId = inject('projectId');

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
const STYLE_KEYS = ['sectionBg', 'sectionText', 'imageRadius', 'imageShadow', 'imageBorder', 'animation', 'animationSpeed', 'animationStagger', 'hoverEffect', 'buttonEffect', 'textEffect', 'textSpeed', 'typingPhrases', 'customAnimation', 'bgEffect'];
const currentTextEffect = computed(() => block.value?.props.textEffect || 'none');
const phrasesText = computed(() => (block.value?.props.typingPhrases || []).join('\n'));
function setPhrases(value) {
    update('typingPhrases', value.split('\n').map((l) => l.trim()).filter(Boolean).slice(0, 5));
}
// Only offer button effects where the section actually has buttons (or might, via the AI).
const hasButtons = computed(() => {
    const p = block.value?.props || {};
    return ['navbar', 'hero', 'cta', 'pricing', 'contact', 'custom'].includes(block.value?.type) || !!(p.ctaText || p.buttonText || p.button || p.cta);
});

/* ---------- Background effect ---------- */
const effectPresets = computed(() => bgPresets(editor.schema?.theme));
const effect = computed(() => (block.value?.props.bgEffect?.kind ? block.value.props.bgEffect : null));
const effectPreset = computed(() => presetKeyOf(effect.value, editor.schema?.theme));
function setEffect(spec) {
    update('bgEffect', spec ? JSON.parse(JSON.stringify(spec)) : null);
}
function tweakEffect(patch) {
    if (effect.value) update('bgEffect', { ...effect.value, ...patch });
}
const currentAnimation = computed(() => canonicalAnimation(block.value?.props.animation) || 'none');
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
    let fields = itemFields(block.value.type, key, items);
    if (CARD_VISUAL_LISTS[block.value.type] === key) {
        const mode = cardVisual(items);
        fields = fields.filter((f) => (f === 'icon' ? mode === 'icon' : f === 'image' ? mode === 'photo' : true));
    }
    return fields;
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

                        <!-- On/off options -->
                        <label v-else-if="typeof value === 'boolean'" class="flex cursor-pointer items-center justify-between gap-3">
                            <span class="ed-label !mb-0">{{ key === 'hideBrandText' ? 'Show only the logo (hide brand text)' : humanize(key) }}</span>
                            <button type="button" role="switch" :aria-checked="value" :class="['toggle', value && 'toggle--on']" @click="update(key, !value)"><span></span></button>
                        </label>

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

                        <!-- Animation -->
                        <div>
                            <div class="mb-1.5 flex items-center justify-between">
                                <label class="ed-label !mb-0">Animation</label>
                                <button v-if="currentAnimation !== 'none'" type="button" class="inline-flex items-center gap-1 text-[11px] font-semibold text-lp-accent hover:opacity-80" @click="replayAnimation(block.id)">
                                    <Icon name="redo" :stroke="2" class="size-3" />Preview
                                </button>
                            </div>
                            <div class="grid grid-cols-3 gap-1">
                                <button
                                    v-for="a in ANIMATIONS"
                                    :key="a.value"
                                    type="button"
                                    :class="['anim-chip', currentAnimation === a.value && 'anim-chip--active']"
                                    @click="update('animation', a.value)"
                                >
                                    <span :class="['anim-glyph', `anim-glyph--${a.value}`]"></span>{{ a.label }}
                                </button>
                                <button v-if="currentAnimation === 'custom'" type="button" class="anim-chip anim-chip--active col-span-3 !justify-center" :title="'Designed by AI'" @click="replayAnimation(block.id)">
                                    ✨ {{ block.props.customAnimation?.name || 'Custom' }} <span class="font-normal opacity-70">· made by AI</span>
                                </button>
                            </div>
                            <template v-if="currentAnimation !== 'none'">
                                <div class="mt-3 flex items-center gap-2">
                                    <span class="w-12 shrink-0 text-[11px] font-medium text-lp-muted">Speed</span>
                                    <div class="grid flex-1 grid-cols-3 gap-1">
                                        <button v-for="sp in ['fast', 'normal', 'slow']" :key="sp" type="button" :class="['anim-chip !justify-center', (block.props.animationSpeed || 'normal') === sp && 'anim-chip--active']" @click="update('animationSpeed', sp)">{{ humanize(sp) }}</button>
                                    </div>
                                </div>
                                <label class="mt-3 flex cursor-pointer items-center justify-between gap-3">
                                    <span class="text-[11px] font-medium text-lp-muted">Show content one by one</span>
                                    <button type="button" role="switch" :aria-checked="!!block.props.animationStagger" :class="['toggle', block.props.animationStagger && 'toggle--on']" @click="update('animationStagger', !block.props.animationStagger)"><span></span></button>
                                </label>
                            </template>
                            <div class="mt-3 flex items-center gap-2">
                                <span class="w-12 shrink-0 text-[11px] font-medium text-lp-muted">Hover</span>
                                <div class="grid flex-1 grid-cols-4 gap-1">
                                    <button v-for="h in HOVER_EFFECTS" :key="h.value" type="button" :title="h.value === 'none' ? 'No hover effect' : `${h.label} on cards and photos`" :class="['anim-chip !justify-center !px-1', (block.props.hoverEffect || 'none') === h.value && 'anim-chip--active']" @click="update('hoverEffect', h.value)">{{ h.value === 'zoom' ? 'Zoom' : h.label }}</button>
                                </div>
                            </div>
                            <p class="mt-2 text-[11px] text-lp-subtle">The content animates in as visitors scroll here; the background stays still. Or ask the AI, e.g. “slide this in from the left, one by one”.</p>
                        </div>

                        <!-- Heading effect -->
                        <div>
                            <div class="mb-1.5 flex items-center justify-between">
                                <label class="ed-label !mb-0">Heading effect</label>
                                <button v-if="currentTextEffect !== 'none'" type="button" class="inline-flex items-center gap-1 text-[11px] font-semibold text-lp-accent hover:opacity-80" @click="replayAnimation(block.id)">
                                    <Icon name="redo" :stroke="2" class="size-3" />Preview
                                </button>
                            </div>
                            <div class="grid grid-cols-2 gap-1">
                                <button v-for="t in TEXT_EFFECTS" :key="t.value" type="button" :title="t.hint" :class="['anim-chip', t.value === 'none' && 'col-span-2 !justify-center', currentTextEffect === t.value && 'anim-chip--active']" @click="update('textEffect', t.value)">
                                    <span v-if="t.value !== 'none'" class="tx-demo" :data-demo="t.value">Aa</span>{{ t.label }}
                                </button>
                            </div>
                            <template v-if="currentTextEffect !== 'none'">
                                <div v-if="currentTextEffect !== 'gradient'" class="mt-3 flex items-center gap-2">
                                    <span class="w-12 shrink-0 text-[11px] font-medium text-lp-muted">Speed</span>
                                    <div class="grid flex-1 grid-cols-3 gap-1">
                                        <button v-for="sp in ['fast', 'normal', 'slow']" :key="sp" type="button" :class="['anim-chip !justify-center', (block.props.textSpeed || 'normal') === sp && 'anim-chip--active']" @click="update('textSpeed', sp)">{{ humanize(sp) }}</button>
                                    </div>
                                </div>
                                <div v-if="currentTextEffect === 'typing-loop'" class="mt-3">
                                    <label class="ed-label !text-[11px]">Phrases typed after your heading</label>
                                    <textarea :value="phrasesText" rows="3" placeholder="One per line, e.g.&#10;Brands that stand out&#10;Stores that grow" class="ed-textarea ed-input-sm" @change="setPhrases($event.target.value)"></textarea>
                                    <p v-if="!block.props.typingPhrases?.length" class="mt-1 text-[11px] text-amber-600">Add a few phrases (or ask the AI to write them) — otherwise it just retypes the heading.</p>
                                </div>
                            </template>
                            <p class="mt-1.5 text-[11px] text-lp-subtle">{{ TEXT_EFFECTS.find((t) => t.value === currentTextEffect)?.hint }}{{ currentTextEffect !== 'none' ? ' — on this section\'s main heading.' : '.' }}</p>
                        </div>

                        <!-- Button effect -->
                        <div v-if="hasButtons || (block.props.buttonEffect && block.props.buttonEffect !== 'none')">
                            <label class="ed-label">Button effect</label>
                            <div class="grid grid-cols-4 gap-1">
                                <button v-for="b in BUTTON_EFFECTS" :key="b.value" type="button" :title="b.hint" :class="['anim-chip !justify-center', (block.props.buttonEffect || 'none') === b.value && 'anim-chip--active']" @click="update('buttonEffect', b.value)">
                                    <span v-if="b.value !== 'none'" :class="['btn-demo', `btn-demo--${b.value}`]"></span>{{ b.label }}
                                </button>
                            </div>
                            <p class="mt-1.5 text-[11px] text-lp-subtle">{{ BUTTON_EFFECTS.find((b) => b.value === (block.props.buttonEffect || 'none'))?.hint }} — on every button in this section.</p>
                        </div>

                        <!-- Background effect -->
                        <div>
                            <label class="ed-label">Background effect</label>
                            <div class="grid grid-cols-2 gap-1">
                                <button type="button" :class="['anim-chip', !effect && 'anim-chip--active']" @click="setEffect(null)"><span class="w-4 text-center">⊘</span>None</button>
                                <button v-for="p in effectPresets" :key="p.key" type="button" :class="['anim-chip', effectPreset === p.key && 'anim-chip--active']" @click="setEffect(p.spec)">
                                    <span class="w-4 text-center">{{ p.icon }}</span><span class="truncate">{{ p.label }}</span>
                                </button>
                                <div v-if="effect && effectPreset === null" class="anim-chip anim-chip--active col-span-2 !justify-center">✨ {{ effect.name }} <span class="font-normal opacity-70">· made by AI</span></div>
                            </div>
                            <div v-if="effect" class="mt-3 space-y-2.5 rounded-xl border border-lp-ink/[0.07] p-3">
                                <div v-if="effect.kind === 'particles'" class="flex items-center gap-2">
                                    <span class="w-14 shrink-0 text-[11px] font-medium text-lp-muted">Amount</span>
                                    <input type="range" min="3" max="120" :value="effect.count" class="flex-1 accent-violet-500" @change="tweakEffect({ count: Number($event.target.value) })" />
                                    <span class="w-7 text-right text-[11px] tabular-nums text-lp-subtle">{{ effect.count }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="w-14 shrink-0 text-[11px] font-medium text-lp-muted">Strength</span>
                                    <input type="range" min="5" max="100" :value="effect.opacity" class="flex-1 accent-violet-500" @change="tweakEffect({ opacity: Number($event.target.value) })" />
                                    <span class="w-7 text-right text-[11px] tabular-nums text-lp-subtle">{{ effect.opacity }}%</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="w-14 shrink-0 text-[11px] font-medium text-lp-muted">Speed</span>
                                    <div class="grid flex-1 grid-cols-3 gap-1">
                                        <button v-for="sp in ['slow', 'normal', 'fast']" :key="sp" type="button" :class="['anim-chip !justify-center', (effect.speed || 'normal') === sp && 'anim-chip--active']" @click="tweakEffect({ speed: sp })">{{ humanize(sp) }}</button>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="w-14 shrink-0 text-[11px] font-medium text-lp-muted">Colours</span>
                                    <div class="flex flex-1 items-center gap-1.5">
                                        <input v-for="(c, ci) in effect.colors" :key="ci" type="color" :value="c" class="ed-color !size-7" @change="tweakEffect({ colors: effect.colors.map((x, xi) => (xi === ci ? $event.target.value : x)) })" />
                                    </div>
                                    <label v-if="effect.kind === 'particles'" class="flex items-center gap-1.5 text-[11px] text-lp-muted">Glow
                                        <button type="button" role="switch" :aria-checked="!!effect.glow" :class="['toggle', effect.glow && 'toggle--on']" @click="tweakEffect({ glow: !effect.glow })"><span></span></button>
                                    </label>
                                </div>
                            </div>
                            <p class="mt-2 text-[11px] text-lp-subtle">Want something else? Ask the AI — e.g. “falling 🍂 leaves over the hero” or “soft purple glow behind the pricing”.</p>
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
            </div>
        </div>
    </div>
</template>

<style scoped>
.anim-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    min-width: 0;
    border-radius: 0.5rem;
    border: 1px solid rgb(var(--lp-ink) / 0.09);
    background: rgb(var(--lp-ink) / 0.02);
    padding: 0.4rem 0.45rem;
    font-size: 11px;
    font-weight: 500;
    white-space: nowrap;
    color: rgb(var(--lp-muted));
    transition: border-color 0.15s, background-color 0.15s, color 0.15s;
}
.anim-chip:hover { border-color: rgb(139 92 246 / 0.4); color: rgb(var(--lp-text)); }
.anim-chip--active { border-color: #8b5cf6; background: rgb(139 92 246 / 0.1); color: rgb(var(--lp-accent)); font-weight: 600; }
/* Tiny looping demo of each animation */
.anim-glyph {
    width: 0.6rem;
    height: 0.6rem;
    flex-shrink: 0;
    border-radius: 0.15rem;
    background: currentColor;
    opacity: 0.75;
}
.anim-chip:hover .anim-glyph, .anim-chip--active .anim-glyph { animation: 1.4s cubic-bezier(0.16, 1, 0.3, 1) infinite var(--glyph); }
.anim-glyph--none { background: none; border: 1.5px solid currentColor; }
.anim-glyph--fade { --glyph: g-fade; }
.anim-glyph--fade-up { --glyph: g-up; }
.anim-glyph--fade-down { --glyph: g-down; }
.anim-glyph--from-right { --glyph: g-left; }
.anim-glyph--from-left { --glyph: g-right; }
.anim-glyph--zoom { --glyph: g-zoom; }
.anim-glyph--blur { --glyph: g-blur; }
.anim-glyph--flip { --glyph: g-flip; }
@keyframes g-fade { 0% { opacity: 0; } 60%, 100% { opacity: 0.75; } }
@keyframes g-up { 0% { opacity: 0; transform: translateY(5px); } 60%, 100% { opacity: 0.75; transform: none; } }
@keyframes g-down { 0% { opacity: 0; transform: translateY(-5px); } 60%, 100% { opacity: 0.75; transform: none; } }
@keyframes g-left { 0% { opacity: 0; transform: translateX(6px); } 60%, 100% { opacity: 0.75; transform: none; } }
@keyframes g-right { 0% { opacity: 0; transform: translateX(-6px); } 60%, 100% { opacity: 0.75; transform: none; } }
@keyframes g-zoom { 0% { opacity: 0; transform: scale(0.4); } 60%, 100% { opacity: 0.75; transform: none; } }
@keyframes g-blur { 0% { opacity: 0; filter: blur(3px); } 60%, 100% { opacity: 0.75; filter: none; } }
@keyframes g-flip { 0% { opacity: 0; transform: perspective(40px) rotateX(60deg); } 60%, 100% { opacity: 0.75; transform: none; } }
@media (prefers-reduced-motion: reduce) { .anim-glyph { animation: none !important; } }
/* Mini heading demos */
.tx-demo { display: inline-block; width: 1.1rem; flex-shrink: 0; font-size: 10px; font-weight: 700; line-height: 1; text-align: center; }
.anim-chip:hover .tx-demo[data-demo='typing'], .anim-chip--active .tx-demo[data-demo='typing'],
.anim-chip:hover .tx-demo[data-demo='typing-loop'], .anim-chip--active .tx-demo[data-demo='typing-loop'] { overflow: hidden; white-space: nowrap; animation: demo-type 1.6s steps(2) infinite; }
.anim-chip:hover .tx-demo[data-demo='words'], .anim-chip--active .tx-demo[data-demo='words'] { animation: g-up 1.4s ease infinite; }
.anim-chip:hover .tx-demo[data-demo='letters'], .anim-chip--active .tx-demo[data-demo='letters'] { animation: g-down 1.4s ease infinite; }
.tx-demo[data-demo='gradient'] { background: linear-gradient(90deg, currentColor, #a78bfa, currentColor); background-size: 200% 100%; -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent; }
.anim-chip:hover .tx-demo[data-demo='gradient'], .anim-chip--active .tx-demo[data-demo='gradient'] { animation: demo-grad 1.5s linear infinite; }
.tx-demo[data-demo='highlight'] { background: linear-gradient(rgb(139 92 246 / 0.35), rgb(139 92 246 / 0.35)) no-repeat 0 90% / 100% 40%; }
@keyframes demo-type { from { width: 0; } to { width: 1.1rem; } }
@keyframes demo-grad { to { background-position: 200% 0; } }
@media (prefers-reduced-motion: reduce) { .tx-demo { animation: none !important; width: 1.1rem !important; } }
/* Mini button demos in the effect chips */
.btn-demo { position: relative; display: inline-block; width: 0.75rem; height: 0.45rem; flex-shrink: 0; overflow: hidden; border-radius: 0.15rem; background: currentColor; opacity: 0.8; }
.anim-chip:hover .btn-demo--pulse, .anim-chip--active .btn-demo--pulse { overflow: visible; animation: demo-pulse 1.4s ease-out infinite; }
.btn-demo--shine::after { content: ''; position: absolute; inset: 0 auto 0 -60%; width: 40%; background: rgb(255 255 255 / 0.85); transform: skewX(-20deg); }
.anim-chip:hover .btn-demo--shine::after, .anim-chip--active .btn-demo--shine::after { animation: demo-shine 1.4s ease-in-out infinite; }
.anim-chip:hover .btn-demo--glow, .anim-chip--active .btn-demo--glow { overflow: visible; animation: demo-glow 1.2s ease-in-out infinite alternate; }
.anim-chip:hover .btn-demo--bounce, .anim-chip--active .btn-demo--bounce { animation: demo-bounce 1.2s ease infinite; }
.anim-chip:hover .btn-demo--wiggle, .anim-chip--active .btn-demo--wiggle { animation: demo-wiggle 1.2s ease-in-out infinite; }
.anim-chip:hover .btn-demo--pop, .anim-chip--active .btn-demo--pop { animation: demo-pop 1.2s ease infinite; }
@keyframes demo-pulse { from { box-shadow: 0 0 0 0 currentColor; } to { box-shadow: 0 0 0 5px transparent; } }
@keyframes demo-shine { 0% { left: -60%; } 60%, 100% { left: 130%; } }
@keyframes demo-glow { from { box-shadow: 0 0 1px currentColor; } to { box-shadow: 0 0 7px currentColor; } }
@keyframes demo-bounce { 0%, 40%, 100% { transform: translateY(0); } 20% { transform: translateY(-3px); } }
@keyframes demo-wiggle { 0%, 50%, 100% { transform: rotate(0); } 15% { transform: rotate(-12deg); } 35% { transform: rotate(12deg); } }
@keyframes demo-pop { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.3); } }
@media (prefers-reduced-motion: reduce) { .btn-demo, .btn-demo::after { animation: none !important; } }
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
