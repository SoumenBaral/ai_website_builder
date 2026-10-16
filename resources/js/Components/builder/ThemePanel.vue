<script setup>
import { computed, inject } from 'vue';
import { useEditor } from '@/stores/editor.js';
import Icon from '@/Components/Landing/Icon.vue';
import ImagePicker from './ImagePicker.vue';
import { useFavicon } from './useFavicon';
import { useToast } from './useToast';

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

/* ---------- Site icon (favicon) ---------- */
const projectId = inject('projectId');
const { favicon, busy: faviconBusy, fromImage, reset } = useFavicon();
const { notify } = useToast();
const navbar = computed(() => editor.schema?.sections?.find((s) => s.type === 'navbar'));
const navbarLogo = computed(() => navbar.value?.props?.logo || '');
const tabIcon = computed(() => favicon.value?.files?.['favicon.svg'] || favicon.value?.files?.['favicon-32.png'] || '');
const appIcon = computed(() => favicon.value?.files?.['apple-touch-icon.png'] || tabIcon.value);
const initials = computed(() => (navbar.value?.props?.brand || editor.schema?.meta?.title || 'S').split(/\s+/).map((w) => w[0]).slice(0, 2).join('').toUpperCase());
const shortTitle = computed(() => editor.schema?.meta?.title || navbar.value?.props?.brand || 'My website');
const faviconStatus = computed(() => {
    if (!favicon.value) return navbarLogo.value ? 'Not created yet' : 'Using your initials — add a navbar logo to make it yours';
    return favicon.value.followsLogo ? 'Matches your navbar logo' : 'Custom icon';
});

async function useNavbarLogo() {
    try {
        await fromImage(projectId, navbarLogo.value, true);
        notify('Site icon now matches your navbar logo.', 'success');
    } catch (e) {
        notify(e.response?.data?.message || 'Could not create the site icon from this logo.', 'error');
    }
}
async function useCustom(url) {
    if (!url) return;
    try {
        await fromImage(projectId, url, false);
        notify('Custom site icon saved. Publish to update your live site.', 'success');
    } catch (e) {
        notify(e.response?.data?.message || 'Could not use that image as the site icon.', 'error');
    }
}
async function resetIcon() {
    try {
        await reset(projectId);
        notify('Site icon reset to your initials.', 'info');
    } catch {
        notify('Could not reset the site icon.', 'error');
    }
}

const titleLength = computed(() => (editor.schema?.meta?.title || '').length);
const descLength = computed(() => (editor.schema?.meta?.description || '').length);
</script>

<template>
    <div v-if="theme" class="space-y-7 p-4">
        <!-- Site icon -->
        <section>
            <div class="flex items-center justify-between">
                <h4 class="ed-heading">Site icon (favicon)</h4>
                <svg v-if="faviconBusy" viewBox="0 0 24 24" fill="none" class="size-4 animate-spin text-lp-accent"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-opacity=".25" stroke-width="3" /><path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round" /></svg>
            </div>

            <!-- Previews: browser tabs (light & dark) and a phone home screen -->
            <div class="mt-3 grid grid-cols-[minmax(0,1fr)_auto] gap-2">
                <div class="space-y-1.5">
                    <div v-for="mode in ['light', 'dark']" :key="mode" :class="['tab-preview', `tab-preview--${mode}`]">
                        <img v-if="tabIcon" :src="tabIcon" alt="" class="size-4 shrink-0" />
                        <span v-else class="initials-icon size-4 text-[7px]" :style="{ background: editor.schema.theme.primaryColor }">{{ initials }}</span>
                        <span class="truncate">{{ shortTitle }}</span>
                        <Icon name="xMark" :stroke="2" class="ml-auto size-3 shrink-0 opacity-50" />
                    </div>
                </div>
                <div class="home-preview">
                    <img v-if="appIcon" :src="appIcon" alt="" class="size-11 rounded-[11px] shadow-md" />
                    <span v-else class="initials-icon size-11 rounded-[11px] text-sm shadow-md" :style="{ background: editor.schema.theme.primaryColor }">{{ initials }}</span>
                    <span class="mt-1 max-w-[3.5rem] truncate text-[9px] text-white/90">{{ navbar?.props?.brand || shortTitle }}</span>
                </div>
            </div>

            <p class="mt-2.5 flex items-center gap-1.5 text-xs text-lp-muted">
                <Icon :name="favicon ? 'checkCircle' : 'sparkles'" :stroke="1.8" :class="['size-3.5', favicon ? 'text-emerald-500' : 'text-lp-subtle']" />{{ faviconStatus }}
            </p>

            <div class="mt-3 flex flex-wrap gap-2">
                <button v-if="navbarLogo && !(favicon?.followsLogo && favicon?.sourceUrl === navbarLogo)" type="button" class="ed-option" :disabled="faviconBusy" @click="useNavbarLogo">
                    <Icon name="photo" :stroke="1.7" class="size-3.5" />Use navbar logo
                </button>
                <button v-if="favicon" type="button" class="ed-option" :disabled="faviconBusy" @click="resetIcon">
                    <Icon name="undo" :stroke="1.7" class="size-3.5" />Reset
                </button>
            </div>
            <details class="mt-3">
                <summary class="cursor-pointer list-none text-xs font-medium text-lp-accent hover:opacity-80">Use a different image…</summary>
                <div class="mt-2.5">
                    <ImagePicker :model-value="''" :project-id="projectId" @update:model-value="useCustom" />
                    <p class="mt-1.5 text-[11px] text-lp-subtle">Square images work best. We create every size browsers and phones need.</p>
                </div>
            </details>
        </section>

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
.tab-preview {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    border-radius: 0.6rem 0.6rem 0.2rem 0.2rem;
    padding: 0.45rem 0.6rem;
    font-size: 11px;
}
.tab-preview--light { background: #f1f3f4; color: #3c4043; }
.tab-preview--dark { background: #35363a; color: #e8eaed; }
.home-preview {
    display: flex;
    width: 4.75rem;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    border-radius: 0.75rem;
    background: linear-gradient(160deg, #4f46e5, #0f172a 70%);
    padding: 0.5rem;
}
.initials-icon {
    display: inline-flex;
    flex-shrink: 0;
    align-items: center;
    justify-content: center;
    border-radius: 25%;
    font-weight: 700;
    color: #fff;
}
details summary::-webkit-details-marker { display: none; }
.radius-demo {
    width: 1.25rem;
    height: 1.25rem;
    border-top: 2px solid currentColor;
    border-left: 2px solid currentColor;
}
</style>
