<script setup>
import { computed } from 'vue';
import { useEditor } from '@/stores/editor.js';
import Icon from '@/Components/Landing/Icon.vue';

const editor = useEditor();
const theme = computed(() => editor.schema?.theme);

function update(key, value) {
    editor.updateTheme({ [key]: value });
}

const fonts = [
    'Poppins', 'Inter', 'Roboto', 'Open Sans', 'Lato', 'Montserrat',
    'Playfair Display', 'Merriweather', 'Raleway', 'Nunito',
    'Source Sans 3', 'PT Sans', 'Ubuntu', 'Oswald', 'DM Sans',
];

const radiusOptions = [
    { value: 'none', label: 'None', px: '0' },
    { value: 'sm', label: 'Small', px: '3px' },
    { value: 'md', label: 'Medium', px: '6px' },
    { value: 'lg', label: 'Large', px: '9px' },
    { value: 'xl', label: 'XL', px: '12px' },
    { value: 'full', label: 'Full', px: '999px' },
];

const colorFields = [
    { key: 'primaryColor', label: 'Primary', hint: 'Buttons & accents' },
    { key: 'secondaryColor', label: 'Secondary', hint: 'Footer & highlights' },
    { key: 'backgroundColor', label: 'Background', hint: 'Page background' },
    { key: 'textColor', label: 'Text', hint: 'Body copy' },
];

// One-click palettes (primary, secondary, background, text, mode)
const palettes = [
    { name: 'Ocean', colors: ['#2563eb', '#0f172a', '#ffffff', '#0f172a'], mode: 'light' },
    { name: 'Violet', colors: ['#7c3aed', '#1e1b4b', '#faf9ff', '#1e1b4b'], mode: 'light' },
    { name: 'Forest', colors: ['#047857', '#022c22', '#f6f8f4', '#14261d'], mode: 'light' },
    { name: 'Terracotta', colors: ['#c2410c', '#431407', '#fbf6ef', '#2b1a12'], mode: 'light' },
    { name: 'Midnight', colors: ['#818cf8', '#0b0d1a', '#0b0d1a', '#e5e7eb'], mode: 'dark' },
    { name: 'Mono', colors: ['#111111', '#111111', '#ffffff', '#111111'], mode: 'light' },
];
function applyPalette(p) {
    editor.updateTheme({ primaryColor: p.colors[0], secondaryColor: p.colors[1], backgroundColor: p.colors[2], textColor: p.colors[3], mode: p.mode });
}
const isActivePalette = (p) => theme.value
    && [theme.value.primaryColor, theme.value.secondaryColor, theme.value.backgroundColor, theme.value.textColor].map((c) => (c || '').toLowerCase()).join() === p.colors.join();

const titleLength = computed(() => (editor.schema?.meta?.title || '').length);
const descLength = computed(() => (editor.schema?.meta?.description || '').length);
</script>

<template>
    <div v-if="theme" class="space-y-7 p-4">
        <!-- Palettes -->
        <section>
            <h4 class="ed-heading">Palettes</h4>
            <div class="mt-3 grid grid-cols-3 gap-2">
                <button
                    v-for="p in palettes"
                    :key="p.name"
                    type="button"
                    :class="['palette', isActivePalette(p) && 'palette--active']"
                    :title="`Apply ${p.name}`"
                    @click="applyPalette(p)"
                >
                    <span class="flex h-7 overflow-hidden rounded-md ring-1 ring-black/5">
                        <span class="flex-1" :style="{ background: p.colors[2] }"></span>
                        <span class="flex-1" :style="{ background: p.colors[0] }"></span>
                        <span class="flex-1" :style="{ background: p.colors[1] }"></span>
                    </span>
                    <span class="mt-1.5 block text-[11px] font-medium">{{ p.name }}</span>
                </button>
            </div>
        </section>

        <!-- Colors -->
        <section>
            <h4 class="ed-heading">Colors</h4>
            <div class="mt-3 space-y-2.5">
                <div v-for="c in colorFields" :key="c.key" class="flex items-center gap-3">
                    <input type="color" :value="theme[c.key]" class="ed-color" :aria-label="`${c.label} color`" @input="update(c.key, $event.target.value)" />
                    <div class="min-w-0 flex-1">
                        <div class="text-[13px] font-medium text-lp-fg">{{ c.label }}</div>
                        <div class="text-[11px] text-lp-subtle">{{ c.hint }}</div>
                    </div>
                    <input type="text" :value="theme[c.key]" maxlength="9" class="ed-input ed-input-sm w-24 font-mono uppercase" :aria-label="`${c.label} hex`" @change="update(c.key, $event.target.value)" />
                </div>
            </div>
        </section>

        <!-- Typography -->
        <section>
            <h4 class="ed-heading">Typography</h4>
            <div class="ed-item mt-3 px-4 py-3">
                <div class="truncate text-xl font-bold text-lp-fg" :style="{ fontFamily: `'${theme.fontHeading}', sans-serif` }">Beautiful headings</div>
                <div class="mt-0.5 truncate text-[13px] text-lp-muted" :style="{ fontFamily: `'${theme.fontBody}', sans-serif` }">And easy-to-read body text.</div>
            </div>
            <div class="mt-3 grid grid-cols-2 gap-2">
                <div>
                    <label class="ed-label">Headings</label>
                    <select :value="theme.fontHeading" class="ed-select ed-input-sm" @change="update('fontHeading', $event.target.value)">
                        <option v-for="font in fonts" :key="font" :value="font">{{ font }}</option>
                    </select>
                </div>
                <div>
                    <label class="ed-label">Body</label>
                    <select :value="theme.fontBody" class="ed-select ed-input-sm" @change="update('fontBody', $event.target.value)">
                        <option v-for="font in fonts" :key="font" :value="font">{{ font }}</option>
                    </select>
                </div>
            </div>
        </section>

        <!-- Radius -->
        <section>
            <h4 class="ed-heading">Corner radius</h4>
            <div class="mt-3 grid grid-cols-3 gap-2">
                <button v-for="opt in radiusOptions" :key="opt.value" type="button" :class="['ed-option flex-col !gap-1.5 !py-2.5', theme.radius === opt.value && 'ed-option--active']" @click="update('radius', opt.value)">
                    <span class="radius-demo" :style="{ borderTopLeftRadius: opt.px }"></span>
                    {{ opt.label }}
                </button>
            </div>
        </section>

        <!-- Mode -->
        <section>
            <h4 class="ed-heading">Site mode</h4>
            <div class="mt-3 grid grid-cols-2 gap-2">
                <button type="button" :class="['ed-option !py-2.5', theme.mode === 'light' && 'ed-option--active']" @click="update('mode', 'light')"><Icon name="sun" :stroke="1.8" class="size-4" />Light</button>
                <button type="button" :class="['ed-option !py-2.5', theme.mode === 'dark' && 'ed-option--active']" @click="update('mode', 'dark')"><Icon name="moon" :stroke="1.8" class="size-4" />Dark</button>
            </div>
        </section>

        <!-- SEO -->
        <section class="border-t border-lp-ink/[0.07] pt-6">
            <h4 class="ed-heading">Search & sharing</h4>
            <div class="mt-3 space-y-3">
                <div>
                    <label class="ed-label flex justify-between">Page title <span :class="titleLength > 60 ? 'text-amber-500' : 'text-lp-subtle'">{{ titleLength }}/60</span></label>
                    <input type="text" :value="editor.schema?.meta?.title" class="ed-input" @input="editor.updateMeta({ title: $event.target.value })" />
                </div>
                <div>
                    <label class="ed-label flex justify-between">Description <span :class="descLength > 160 ? 'text-amber-500' : 'text-lp-subtle'">{{ descLength }}/160</span></label>
                    <textarea :value="editor.schema?.meta?.description" rows="3" class="ed-textarea" @input="editor.updateMeta({ description: $event.target.value })"></textarea>
                </div>
                <!-- Search result preview -->
                <div class="ed-item px-3.5 py-3">
                    <div class="text-[11px] text-lp-subtle">Search preview</div>
                    <div class="mt-1 truncate text-[15px] font-medium text-[#8ab4f8] [[data-theme=light]_&]:text-[#1a0dab]">{{ editor.schema?.meta?.title || 'Untitled website' }}</div>
                    <div class="mt-0.5 line-clamp-2 text-xs leading-relaxed text-lp-muted">{{ editor.schema?.meta?.description || 'Add a description to tell people what your site is about.' }}</div>
                </div>
            </div>
        </section>
    </div>
</template>

<style scoped>
.palette {
    border-radius: 0.7rem;
    border: 1px solid rgb(var(--lp-ink) / 0.1);
    padding: 0.4rem 0.4rem 0.45rem;
    text-align: center;
    color: rgb(var(--lp-muted));
    transition: border-color 0.15s, background-color 0.15s, color 0.15s;
}
.palette:hover { border-color: rgb(var(--lp-ink) / 0.22); color: rgb(var(--lp-fg)); }
.palette--active { border-color: rgb(139 92 246 / 0.65); background: rgb(139 92 246 / 0.08); color: rgb(var(--lp-accent)); }
.radius-demo {
    width: 1.25rem;
    height: 1.25rem;
    border-top: 2px solid currentColor;
    border-left: 2px solid currentColor;
}
</style>
