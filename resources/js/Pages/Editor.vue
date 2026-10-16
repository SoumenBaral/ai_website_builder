<script setup>
import '../../css/landing.css';
import '../../css/editor.css';
import { ref, computed, provide, nextTick, watch, onMounted, onUnmounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { watchDebounced } from '@vueuse/core';
import axios from 'axios';
import { useEditor } from '@/stores/editor.js';
import { themeToVars } from '@/composables/useTheme.js';
import BlockRenderer from '@/Components/builder/BlockRenderer.vue';
import PromptBar from '@/Components/builder/PromptBar.vue';
import BlockTree from '@/Components/builder/BlockTree.vue';
import Inspector from '@/Components/builder/Inspector.vue';
import ThemePanel from '@/Components/builder/ThemePanel.vue';
import VersionHistory from '@/Components/builder/VersionHistory.vue';
import EditorToasts from '@/Components/builder/EditorToasts.vue';
import { useToast } from '@/Components/builder/useToast';
import Icon from '@/Components/Landing/Icon.vue';
import ThemeToggle from '@/Components/Landing/ThemeToggle.vue';
import AppMenu from '@/Components/App/AppMenu.vue';
import { useColorMode, useDocumentTheme } from '@/Components/Landing/useColorMode';

const props = defineProps({
    project: Object,
    page: Object,
});

const editor = useEditor();
const { notify } = useToast();
const { theme: uiTheme } = useColorMode();
useDocumentTheme();
provide('projectId', props.project.id);

/* ---------- Layout state ---------- */
const devices = [
    { key: 'desktop', width: '100%', icon: 'desktop', label: 'Desktop' },
    { key: 'tablet', width: '768px', icon: 'tablet', label: 'Tablet' },
    { key: 'mobile', width: '390px', icon: 'phone', label: 'Mobile' },
];
const device = ref('desktop');
const previewWidth = computed(() => devices.find((d) => d.key === device.value).width);
const activeRightPanel = ref('inspector'); // 'inspector' | 'theme'
const showVersionHistory = ref(false);
const layersOpen = ref(false); // drawer below lg
const panelOpen = ref(false); // drawer below xl
const canvasScroll = ref(null);
const promptBar = ref(null);

const isLive = computed(() => props.project.status === 'published');
const liveUrl = computed(() => `${window.location.origin}/s/${props.project.slug}`);
const hasSections = computed(() => editor.schema?.sections?.length > 0);

/* ---------- Load + autosave ---------- */
onMounted(() => {
    editor.load(props.page.schema);
});

watchDebounced(
    () => editor.schema,
    async () => {
        if (!editor.dirty) return;
        editor.saving = true;
        try {
            await axios.post(route('pages.autosave', { project: props.project.id, page: props.page.id }), {
                schema: editor.schema,
            });
            editor.markClean();
        } catch (e) {
            notify('Autosave failed — check your connection.', 'error');
        } finally {
            editor.saving = false;
        }
    },
    { debounce: 1200, deep: true },
);

const saveState = computed(() => (editor.saving ? 'saving' : editor.dirty ? 'unsaved' : 'saved'));

/* ---------- Publishing ---------- */
const publishing = ref(false);
function publishSite() {
    publishing.value = true;
    router.post(route('projects.publish', props.project.id), {}, {
        preserveScroll: true,
        onSuccess: () => notify('Your site is live!', 'success', { duration: 6000, action: { label: 'View', href: `/s/${props.project.slug}` } }),
        onError: () => notify('Publishing failed. Please try again.', 'error'),
        onFinish: () => { publishing.value = false; },
    });
}
function unpublishSite() {
    router.post(route('projects.unpublish', props.project.id), {}, {
        preserveScroll: true,
        onSuccess: () => notify('Site unpublished — it is no longer public.', 'info'),
    });
}
async function copyLink() {
    try {
        await navigator.clipboard.writeText(liveUrl.value);
        notify('Link copied to clipboard', 'success');
    } catch {
        notify(liveUrl.value, 'info', { duration: 8000 });
    }
}
function exportZip() {
    window.location.href = route('export.download', props.project.id);
}

/* ---------- Selection helpers ---------- */
function revealBlock(id) {
    layersOpen.value = false;
    nextTick(() => {
        canvasScroll.value?.querySelector(`[data-block-id="${id}"]`)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
}
// Selecting something switches the right panel back to the block inspector
watch(() => editor.selectedId, (id) => { if (id) activeRightPanel.value = 'inspector'; });

/* ---------- AI results ---------- */
function onGenerated(schema) {
    editor.setSchema(schema);
    notify('Website generated! Click any section to refine it.', 'success', { duration: 6000 });
}
function onError(message) {
    notify(message, 'error', { duration: 6000 });
}
function onUpdateProps(blockId, patch) {
    editor.updateProps(blockId, patch);
}

/* ---------- Keyboard shortcuts ---------- */
function isTyping(target) {
    return ['INPUT', 'TEXTAREA', 'SELECT'].includes(target.tagName) || target.isContentEditable;
}
function handleKeydown(e) {
    const mod = e.metaKey || e.ctrlKey;
    if (mod && e.key.toLowerCase() === 'z' && !e.shiftKey && !isTyping(e.target)) {
        e.preventDefault();
        editor.undo();
    } else if (mod && e.key.toLowerCase() === 'z' && e.shiftKey && !isTyping(e.target)) {
        e.preventDefault();
        editor.redo();
    } else if (mod && e.key.toLowerCase() === 'k') {
        e.preventDefault();
        promptBar.value?.focus();
    } else if ((e.key === 'Delete' || e.key === 'Backspace') && editor.selectedId && !isTyping(e.target)) {
        e.preventDefault();
        editor.removeBlock(editor.selectedId);
    } else if (e.key === 'Escape' && !isTyping(e.target) && !showVersionHistory.value) {
        if (layersOpen.value || panelOpen.value) { layersOpen.value = false; panelOpen.value = false; }
        else editor.select(null);
    }
}
onMounted(() => window.addEventListener('keydown', handleKeydown));
onUnmounted(() => window.removeEventListener('keydown', handleKeydown));

/* ---------- Site theme on the canvas ---------- */
const themeVars = computed(() => {
    if (!editor.schema?.theme) return {};
    return themeToVars(editor.schema.theme);
});
const fontLink = computed(() => {
    if (!editor.schema?.theme) return '';
    const h = editor.schema.theme.fontHeading;
    const b = editor.schema.theme.fontBody;
    return `https://fonts.googleapis.com/css2?family=${h}:wght@400;600;700&family=${b}:wght@400;500;600&display=swap`;
});
</script>

<template>
    <Head :title="`Editor · ${project.name}`" />
    <link v-if="fontLink" :href="fontLink" rel="stylesheet" />

    <div id="editor-root" class="landing editor-ui flex h-[100dvh] flex-col overflow-hidden font-sans antialiased" :data-theme="uiTheme">
        <!-- ================= Top bar ================= -->
        <header class="topbar relative z-30 flex h-14 shrink-0 items-center gap-2 px-2 sm:px-3">
            <!-- Left: back, layers, name, save state -->
            <div class="flex min-w-0 flex-1 items-center gap-1.5">
                <a :href="route('dashboard')" class="ed-icon-btn size-9" title="Back to websites" aria-label="Back to websites">
                    <Icon name="arrowLeft" :stroke="2" class="size-[18px]" />
                </a>
                <button type="button" class="ed-icon-btn size-9 lg:hidden" title="Layers" aria-label="Show layers" @click="layersOpen = true">
                    <Icon name="layers" :stroke="1.7" class="size-[18px]" />
                </button>
                <span class="mx-1 hidden h-5 w-px bg-lp-ink/10 sm:block"></span>
                <div class="min-w-0">
                    <div class="truncate text-sm font-semibold text-lp-fg">{{ project.name }}</div>
                    <div class="flex items-center gap-1.5 truncate whitespace-nowrap text-[11px] text-lp-subtle">
                        <template v-if="saveState === 'saving'">
                            <svg viewBox="0 0 24 24" fill="none" class="size-3 animate-spin"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-opacity=".25" stroke-width="3" /><path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round" /></svg>Saving…
                        </template>
                        <template v-else-if="saveState === 'unsaved'"><span class="size-1.5 rounded-full bg-amber-500"></span>Unsaved changes</template>
                        <template v-else><Icon name="checkCircle" :stroke="2" class="size-3 text-emerald-500" />All changes saved</template>
                    </div>
                </div>
            </div>

            <!-- Centre: device preview -->
            <div class="segmented hidden md:flex" role="radiogroup" aria-label="Preview size">
                <button
                    v-for="d in devices"
                    :key="d.key"
                    type="button"
                    role="radio"
                    :aria-checked="device === d.key"
                    :title="d.label"
                    :class="['segment', device === d.key && 'segment--active']"
                    @click="device = d.key"
                >
                    <Icon :name="d.icon" :stroke="1.7" class="size-4" />
                </button>
            </div>

            <!-- Right: history, more, theme, publish -->
            <div class="flex flex-1 items-center justify-end gap-1">
                <button type="button" class="ed-icon-btn size-9" :disabled="!editor.canUndo" title="Undo (⌘Z)" aria-label="Undo" @click="editor.undo()"><Icon name="undo" :stroke="1.8" class="size-[18px]" /></button>
                <button type="button" class="ed-icon-btn size-9" :disabled="!editor.canRedo" title="Redo (⌘⇧Z)" aria-label="Redo" @click="editor.redo()"><Icon name="redo" :stroke="1.8" class="size-[18px]" /></button>
                <span class="mx-1 hidden h-5 w-px bg-lp-ink/10 sm:block"></span>
                <button type="button" class="ed-icon-btn hidden size-9 sm:inline-flex" title="Version history" aria-label="Version history" @click="showVersionHistory = true"><Icon name="clock" :stroke="1.7" class="size-[18px]" /></button>
                <button type="button" :class="['ed-icon-btn size-9 xl:hidden', editor.selectedId && 'text-lp-accent']" title="Properties" aria-label="Show properties panel" @click="panelOpen = true"><Icon name="adjustments" :stroke="1.7" class="size-[18px]" /></button>

                <AppMenu width="w-56">
                    <template #trigger="{ toggle, open }">
                        <button type="button" class="ed-icon-btn size-9" :aria-expanded="open" aria-haspopup="menu" aria-label="More actions" @click="toggle"><Icon name="ellipsis" :stroke="2" class="size-5" /></button>
                    </template>
                    <template #default="{ close }">
                        <div class="flex items-center justify-between px-2.5 py-1.5 md:hidden" role="none">
                            <span class="text-xs font-medium text-lp-subtle">Preview</span>
                            <div class="flex gap-1">
                                <button v-for="d in devices" :key="d.key" type="button" :class="['ed-icon-btn size-7', device === d.key && '!text-lp-accent']" :aria-label="d.label" @click="device = d.key; close()"><Icon :name="d.icon" :stroke="1.7" class="size-4" /></button>
                            </div>
                        </div>
                        <button type="button" class="menu-item sm:hidden" role="menuitem" @click="showVersionHistory = true; close()"><Icon name="clock" :stroke="1.7" class="size-4" />Version history</button>
                        <a :href="route('analytics.dashboard', project.id)" class="menu-item" role="menuitem"><Icon name="chart" :stroke="1.7" class="size-4" />Analytics</a>
                        <button type="button" class="menu-item" role="menuitem" @click="exportZip(); close()"><Icon name="download" :stroke="1.7" class="size-4" />Download ZIP</button>
                        <template v-if="isLive">
                            <div class="menu-sep"></div>
                            <a :href="`/s/${project.slug}`" target="_blank" rel="noopener" class="menu-item" role="menuitem" @click="close"><Icon name="externalLink" :stroke="1.7" class="size-4" />View live site</a>
                            <button type="button" class="menu-item" role="menuitem" @click="copyLink(); close()"><Icon name="link" :stroke="1.7" class="size-4" />Copy link</button>
                            <button type="button" class="menu-item menu-item--danger" role="menuitem" @click="unpublishSite(); close()"><Icon name="xMark" :stroke="1.7" class="size-4" />Unpublish</button>
                        </template>
                    </template>
                </AppMenu>

                <ThemeToggle class="hidden sm:inline-flex" />

                <a v-if="isLive" :href="`/s/${project.slug}`" target="_blank" rel="noopener" class="live-pill ml-1" title="Open the live site">
                    <span class="relative flex size-2"><span class="live-ping absolute inset-0 rounded-full bg-emerald-400"></span><span class="relative size-2 rounded-full bg-emerald-400"></span></span>
                    <span class="hidden sm:inline">Live</span>
                </a>
                <button v-else type="button" class="btn btn-primary ml-1 h-9 px-3.5 text-[13px]" :disabled="publishing || !hasSections" :title="hasSections ? 'Publish your site' : 'Add some content first'" @click="publishSite">
                    <Icon name="rocket" :stroke="1.8" class="size-4" /><span class="hidden sm:inline">{{ publishing ? 'Publishing…' : 'Publish' }}</span>
                </button>
            </div>
        </header>

        <!-- ================= Workspace ================= -->
        <div class="relative flex min-h-0 flex-1">
            <!-- Layers (desktop) -->
            <aside v-if="editor.schema" class="side-panel hidden w-64 shrink-0 border-r lg:block">
                <BlockTree @select="revealBlock" />
            </aside>

            <!-- Canvas -->
            <main class="canvas-area relative min-w-0 flex-1" @click="editor.select(null)">
                <div ref="canvasScroll" class="ed-scroll h-full overflow-y-auto px-3 pb-44 pt-5 sm:px-8 sm:pt-8">
                    <div
                        v-if="hasSections"
                        :class="['frame mx-auto transition-[max-width] duration-500', `frame--${device}`]"
                        :style="{ maxWidth: previewWidth }"
                        @click.stop
                    >
                        <!-- Browser chrome -->
                        <div class="frame-bar flex items-center gap-2 px-3.5 py-2.5">
                            <div class="flex gap-1.5"><span class="size-2.5 rounded-full bg-lp-ink/15"></span><span class="size-2.5 rounded-full bg-lp-ink/15"></span><span class="size-2.5 rounded-full bg-lp-ink/15"></span></div>
                            <div class="mx-auto flex min-w-0 max-w-sm flex-1 items-center justify-center gap-1.5 truncate rounded-md bg-lp-ink/[0.05] px-3 py-1 text-[11px] text-lp-subtle">
                                <Icon :name="isLive ? 'globe' : 'lock'" :stroke="1.8" class="size-3 shrink-0" />{{ isLive ? `/s/${project.slug}` : 'Draft preview' }}
                            </div>
                            <span class="w-10"></span>
                        </div>
                        <!-- The user's website — styled only by its own theme -->
                        <div
                            class="site-canvas preview-canvas"
                            :class="{ 'preview-mobile': device === 'mobile', 'preview-tablet': device === 'tablet' }"
                            :style="themeVars"
                        >
                            <BlockRenderer
                                v-for="block in editor.schema.sections"
                                :key="block.id"
                                :block="block"
                                :editable="true"
                                :selected="editor.selectedId === block.id"
                                @select="editor.select"
                                @update:props="onUpdateProps"
                            />
                        </div>
                    </div>
                </div>

                <PromptBar
                    v-if="editor.schema"
                    ref="promptBar"
                    :project-id="project.id"
                    :initial-prompt="page?.schema?.sections?.length ? '' : (project.description || '')"
                    @generated="onGenerated"
                    @error="onError"
                    @click.stop
                />
            </main>

            <!-- Properties (desktop) -->
            <aside v-if="editor.schema" class="side-panel hidden w-80 shrink-0 flex-col border-l xl:flex">
                <div class="panel-tabs shrink-0 p-2.5">
                    <div class="segmented flex w-full">
                        <button type="button" :class="['segment flex-1', activeRightPanel === 'inspector' && 'segment--active']" @click="activeRightPanel = 'inspector'"><Icon name="cursorClick" :stroke="1.7" class="size-4" />Section</button>
                        <button type="button" :class="['segment flex-1', activeRightPanel === 'theme' && 'segment--active']" @click="activeRightPanel = 'theme'"><Icon name="swatch" :stroke="1.7" class="size-4" />Site style</button>
                    </div>
                </div>
                <div class="ed-scroll min-h-0 flex-1 overflow-y-auto">
                    <Inspector v-if="activeRightPanel === 'inspector'" />
                    <ThemePanel v-else />
                </div>
            </aside>

            <!-- Drawers (smaller screens) -->
            <Transition name="drawer-left">
                <div v-if="layersOpen" class="absolute inset-0 z-40 lg:hidden">
                    <div class="drawer-backdrop absolute inset-0" @click="layersOpen = false"></div>
                    <aside class="side-panel drawer absolute inset-y-0 left-0 w-72 max-w-[85vw] border-r">
                        <BlockTree @select="revealBlock" />
                    </aside>
                </div>
            </Transition>
            <Transition name="drawer-right">
                <div v-if="panelOpen" class="absolute inset-0 z-40 xl:hidden">
                    <div class="drawer-backdrop absolute inset-0" @click="panelOpen = false"></div>
                    <aside class="side-panel drawer absolute inset-y-0 right-0 flex w-80 max-w-[90vw] flex-col border-l">
                        <div class="panel-tabs flex shrink-0 items-center gap-2 p-2.5">
                            <div class="segmented flex flex-1">
                                <button type="button" :class="['segment flex-1', activeRightPanel === 'inspector' && 'segment--active']" @click="activeRightPanel = 'inspector'">Section</button>
                                <button type="button" :class="['segment flex-1', activeRightPanel === 'theme' && 'segment--active']" @click="activeRightPanel = 'theme'">Site style</button>
                            </div>
                            <button type="button" class="ed-icon-btn" aria-label="Close panel" @click="panelOpen = false"><Icon name="xMark" :stroke="2" class="size-5" /></button>
                        </div>
                        <div class="ed-scroll min-h-0 flex-1 overflow-y-auto">
                            <Inspector v-if="activeRightPanel === 'inspector'" />
                            <ThemePanel v-else />
                        </div>
                    </aside>
                </div>
            </Transition>
        </div>

        <VersionHistory v-if="showVersionHistory" :project-id="project.id" @close="showVersionHistory = false" />
        <EditorToasts />
    </div>
</template>

<style scoped>
.topbar {
    border-bottom: 1px solid rgb(var(--lp-ink) / 0.08);
    background: rgb(var(--lp-bg));
}
.side-panel {
    border-color: rgb(var(--lp-ink) / 0.08);
    background: rgb(var(--lp-bg));
}
.panel-tabs { border-bottom: 1px solid rgb(var(--lp-ink) / 0.07); }

/* Dotted workspace behind the site */
.canvas-area {
    background-color: rgb(var(--lp-ink) / 0.025);
    background-image: radial-gradient(rgb(var(--lp-ink) / 0.09) 1px, transparent 1px);
    background-size: 20px 20px;
}

.frame {
    overflow: hidden;
    border-radius: 0.9rem;
    border: 1px solid rgb(var(--lp-ink) / 0.1);
    background: rgb(var(--lp-surface));
    box-shadow: 0 30px 80px -30px rgb(0 0 0 / 0.45), 0 0 0 1px rgb(0 0 0 / 0.02);
}
.frame--mobile { border-radius: 1.75rem; border-width: 6px; border-color: rgb(var(--lp-ink) / 0.14); }
.frame--tablet { border-radius: 1.4rem; border-width: 5px; border-color: rgb(var(--lp-ink) / 0.12); }
.frame-bar { border-bottom: 1px solid rgb(var(--lp-ink) / 0.07); }

/* Reset everything the editor UI would otherwise pass down to the user's site */
.site-canvas {
    background: var(--bg, #fff);
    color: var(--text, #111);
    color-scheme: light;
    font-family: var(--font-body);
    letter-spacing: normal;
}
.site-canvas :deep([data-block-id]) { scroll-margin-top: 1rem; }

.segmented {
    gap: 0.2rem;
    border-radius: 0.7rem;
    background: rgb(var(--lp-ink) / 0.05);
    padding: 0.2rem;
}
.segment {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.4rem;
    border-radius: 0.5rem;
    padding: 0.35rem 0.7rem;
    font-size: 12px;
    font-weight: 500;
    color: rgb(var(--lp-muted));
    transition: background-color 0.15s, color 0.15s, box-shadow 0.15s;
}
.segment:hover { color: rgb(var(--lp-fg)); }
.segment--active { background: rgb(var(--lp-surface)); color: rgb(var(--lp-fg)); box-shadow: 0 1px 3px rgb(0 0 0 / 0.15), 0 0 0 1px rgb(var(--lp-ink) / 0.06); }

.live-pill {
    display: inline-flex;
    height: 2.25rem;
    align-items: center;
    gap: 0.5rem;
    border-radius: 0.7rem;
    border: 1px solid rgb(16 185 129 / 0.3);
    background: rgb(16 185 129 / 0.1);
    padding: 0 0.8rem;
    font-size: 13px;
    font-weight: 600;
    color: rgb(16 185 129);
    transition: background-color 0.15s;
}
.live-pill:hover { background: rgb(16 185 129 / 0.16); }
.btn:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }

.drawer-backdrop { background: rgb(5 5 10 / 0.45); }
.drawer { box-shadow: 0 0 60px -10px rgb(0 0 0 / 0.5); }
.drawer-left-enter-active, .drawer-left-leave-active,
.drawer-right-enter-active, .drawer-right-leave-active { transition: opacity 0.25s ease; }
.drawer-left-enter-active .drawer, .drawer-left-leave-active .drawer,
.drawer-right-enter-active .drawer, .drawer-right-leave-active .drawer { transition: transform 0.35s cubic-bezier(0.22, 1, 0.36, 1); }
.drawer-left-enter-from, .drawer-left-leave-to, .drawer-right-enter-from, .drawer-right-leave-to { opacity: 0; }
.drawer-left-enter-from .drawer, .drawer-left-leave-to .drawer { transform: translateX(-100%); }
.drawer-right-enter-from .drawer, .drawer-right-leave-to .drawer { transform: translateX(100%); }
</style>

<style>
/*
  Force responsive layouts inside the editor preview canvas.
  Tailwind's md:/lg: breakpoints read the viewport, not the container,
  so when we shrink the canvas to 390px or 768px we override the
  multi-column grids back to single-column.
*/
.preview-mobile .grid.md\:grid-cols-2,
.preview-mobile .grid.md\:grid-cols-3,
.preview-mobile .grid.md\:grid-cols-4,
.preview-mobile .grid.lg\:grid-cols-3,
.preview-mobile .grid.lg\:grid-cols-4,
.preview-mobile .grid.sm\:grid-cols-2 {
    grid-template-columns: repeat(1, minmax(0, 1fr)) !important;
}
.preview-mobile h1.text-4xl.md\:text-6xl,
.preview-mobile h1.text-4xl.md\:text-5xl {
    font-size: 1.875rem !important;
    line-height: 2.25rem !important;
}
.preview-mobile .md\:text-xl {
    font-size: 1rem !important;
}
.preview-mobile .flex-row {
    flex-direction: column !important;
}
.preview-mobile nav .hidden.md\:flex {
    display: none !important;
}
/* Split layouts (e.g. hero image + text) collapse to a single column like on a real phone */
.preview-mobile .hidden.md\:block,
.preview-mobile .hidden.md\:flex,
.preview-mobile .hidden.lg\:block {
    display: none !important;
}
.preview-mobile .md\:w-1\/2,
.preview-mobile .md\:w-2\/3,
.preview-mobile .md\:w-1\/3 {
    width: 100% !important;
}
.preview-mobile .md\:text-5xl,
.preview-mobile .md\:text-6xl {
    font-size: 2.25rem !important;
    line-height: 2.5rem !important;
}
.preview-mobile .lg\:px-12 {
    padding-left: 1.5rem !important;
    padding-right: 1.5rem !important;
}

/* Tablet: 2 cols max instead of 3+ */
.preview-tablet .grid.md\:grid-cols-3,
.preview-tablet .grid.lg\:grid-cols-3 {
    grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
}
.preview-tablet .grid.md\:grid-cols-4,
.preview-tablet .grid.lg\:grid-cols-4 {
    grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
}
</style>
