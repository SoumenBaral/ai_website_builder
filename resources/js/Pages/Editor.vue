<script setup>
import { ref, computed, provide, onMounted, onUnmounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { useEditor } from '@/stores/editor.js';
import { themeToVars } from '@/composables/useTheme.js';
import BlockRenderer from '@/Components/builder/BlockRenderer.vue';
import PromptBar from '@/Components/builder/PromptBar.vue';
import BlockTree from '@/Components/builder/BlockTree.vue';
import Inspector from '@/Components/builder/Inspector.vue';
import ThemePanel from '@/Components/builder/ThemePanel.vue';
import VersionHistory from '@/Components/builder/VersionHistory.vue';
import { watchDebounced } from '@vueuse/core';
import axios from 'axios';

const props = defineProps({
    project: Object,
    page: Object,
});

function exportZip() {
    window.location.href = route('export.download', props.project.id);
}

function publishSite() {
    router.post(route('projects.publish', props.project.id), {}, {
        onSuccess: () => showNotification('Site published!', 'success'),
    });
}

function unpublishSite() {
    router.post(route('projects.unpublish', props.project.id), {}, {
        onSuccess: () => showNotification('Site unpublished.', 'success'),
    });
}

const editor = useEditor();
provide('projectId', props.project.id);
const previewWidth = ref('100%');
const activeRightPanel = ref('inspector'); // 'inspector' | 'theme'
const showVersionHistory = ref(false);
const notification = ref(null);

onMounted(() => {
    editor.setSchema(props.page.schema);
    editor.dirty = false;
});

// Autosave
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
            showNotification('Autosave failed', 'error');
        } finally {
            editor.saving = false;
        }
    },
    { debounce: 1200, deep: true },
);

// Keyboard shortcuts
function handleKeydown(e) {
    if ((e.metaKey || e.ctrlKey) && e.key === 'z' && !e.shiftKey) {
        e.preventDefault();
        editor.undo();
    }
    if ((e.metaKey || e.ctrlKey) && e.key === 'z' && e.shiftKey) {
        e.preventDefault();
        editor.redo();
    }
    if (e.key === 'Delete' || e.key === 'Backspace') {
        if (editor.selectedId && !['INPUT', 'TEXTAREA'].includes(e.target.tagName) && !e.target.contentEditable?.toString().includes('true')) {
            e.preventDefault();
            editor.removeBlock(editor.selectedId);
        }
    }
}

onMounted(() => window.addEventListener('keydown', handleKeydown));
onUnmounted(() => window.removeEventListener('keydown', handleKeydown));

function onGenerated(schema) {
    editor.setSchema(schema);
    showNotification('Website generated successfully!', 'success');
}

function onError(message) {
    showNotification(message, 'error');
}

function showNotification(message, type = 'info') {
    notification.value = { message, type };
    setTimeout(() => notification.value = null, 4000);
}

function onUpdateProps(blockId, patch) {
    editor.updateProps(blockId, patch);
}

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
    <Head :title="`Editor - ${project.name}`" />

    <link v-if="fontLink" :href="fontLink" rel="stylesheet" />

    <div class="h-screen flex flex-col bg-gray-50">
        <!-- Top bar -->
        <header class="bg-white border-b border-gray-200 px-4 py-2 flex items-center justify-between shrink-0 z-20">
            <div class="flex items-center gap-4">
                <a :href="route('dashboard')" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                </a>
                <h1 class="font-semibold text-gray-800 truncate max-w-xs">{{ project.name }}</h1>
                <span v-if="editor.saving" class="text-xs text-gray-400 animate-pulse">Saving...</span>
                <span v-else-if="editor.dirty" class="text-xs text-gray-400">Unsaved changes</span>
                <span v-else class="text-xs text-green-500">Saved</span>
            </div>

            <div class="flex items-center gap-2">
                <!-- Undo/Redo -->
                <button @click="editor.undo()" :disabled="!editor.canUndo" class="p-2 text-gray-500 hover:text-gray-700 disabled:opacity-30" title="Undo (Ctrl+Z)">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                </button>
                <button @click="editor.redo()" :disabled="!editor.canRedo" class="p-2 text-gray-500 hover:text-gray-700 disabled:opacity-30" title="Redo (Ctrl+Shift+Z)">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 10H11a8 8 0 00-8 8v2m18-10l-6 6m6-6l-6-6"/></svg>
                </button>

                <div class="w-px h-6 bg-gray-200 mx-1"></div>

                <!-- Responsive toggles -->
                <button @click="previewWidth = '100%'" :class="previewWidth === '100%' ? 'text-blue-600 bg-blue-50' : 'text-gray-500'" class="p-2 rounded" title="Desktop">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="2" y="3" width="20" height="14" rx="2" stroke-width="2"/><path stroke-width="2" d="M8 21h8m-4-4v4"/></svg>
                </button>
                <button @click="previewWidth = '768px'" :class="previewWidth === '768px' ? 'text-blue-600 bg-blue-50' : 'text-gray-500'" class="p-2 rounded" title="Tablet">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="4" y="2" width="16" height="20" rx="2" stroke-width="2"/></svg>
                </button>
                <button @click="previewWidth = '390px'" :class="previewWidth === '390px' ? 'text-blue-600 bg-blue-50' : 'text-gray-500'" class="p-2 rounded" title="Mobile">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="6" y="2" width="12" height="20" rx="2" stroke-width="2"/><circle cx="12" cy="18" r="1"/></svg>
                </button>

                <div class="w-px h-6 bg-gray-200 mx-1"></div>

                <!-- Right panel toggles -->
                <button @click="activeRightPanel = 'inspector'" :class="activeRightPanel === 'inspector' ? 'text-blue-600 bg-blue-50' : 'text-gray-500'" class="px-3 py-1.5 text-xs font-medium rounded">
                    Inspector
                </button>
                <button @click="activeRightPanel = 'theme'" :class="activeRightPanel === 'theme' ? 'text-blue-600 bg-blue-50' : 'text-gray-500'" class="px-3 py-1.5 text-xs font-medium rounded">
                    Theme
                </button>

                <div class="w-px h-6 bg-gray-200 mx-1"></div>

                <button
                    @click="showVersionHistory = true"
                    class="px-3 py-1.5 text-xs font-medium text-gray-600 hover:text-gray-800 hover:bg-gray-100 rounded transition-colors"
                >
                    History
                </button>
                <a
                    :href="route('analytics.dashboard', project.id)"
                    class="px-3 py-1.5 text-xs font-medium text-gray-600 hover:text-gray-800 hover:bg-gray-100 rounded transition-colors"
                >
                    Analytics
                </a>

                <div class="w-px h-6 bg-gray-200 mx-1"></div>

                <button
                    @click="exportZip"
                    class="px-3 py-1.5 text-xs font-medium text-gray-600 hover:text-gray-800 hover:bg-gray-100 rounded transition-colors"
                >
                    Export ZIP
                </button>
                <button
                    v-if="project.status !== 'published'"
                    @click="publishSite"
                    class="px-3 py-1.5 text-xs font-medium text-white bg-green-600 rounded hover:bg-green-700 transition-colors"
                >
                    Publish
                </button>
                <template v-else>
                    <a
                        :href="`/s/${project.slug}`"
                        target="_blank"
                        class="px-3 py-1.5 text-xs font-medium text-green-600 hover:text-green-800"
                    >
                        View Live
                    </a>
                    <button
                        @click="unpublishSite"
                        class="px-3 py-1.5 text-xs font-medium text-red-600 hover:text-red-800"
                    >
                        Unpublish
                    </button>
                </template>
            </div>
        </header>

        <!-- Prompt bar -->
        <PromptBar :project-id="project.id" @generated="onGenerated" @error="onError" />

        <!-- Main content -->
        <div class="flex flex-1 overflow-hidden">
            <!-- Left sidebar: Block tree -->
            <BlockTree v-if="editor.schema" class="w-60 shrink-0" />

            <!-- Canvas -->
            <div class="flex-1 overflow-y-auto p-6 bg-gray-100">
                <div
                    v-if="editor.schema?.sections?.length"
                    class="mx-auto shadow-xl transition-all duration-300 bg-white preview-canvas"
                    :class="{
                        'preview-mobile': previewWidth === '390px',
                        'preview-tablet': previewWidth === '768px',
                    }"
                    :style="{ maxWidth: previewWidth, ...themeVars }"
                    style="font-family: var(--font-body);"
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

                <!-- Empty state -->
                <div v-else class="flex flex-col items-center justify-center h-full text-gray-400">
                    <div class="text-6xl mb-4">✨</div>
                    <p class="text-lg font-medium mb-2">Your canvas is empty</p>
                    <p class="text-sm">Use the prompt bar above to generate a website with AI,<br>or add blocks manually from the left sidebar.</p>
                </div>
            </div>

            <!-- Right sidebar -->
            <div v-if="editor.schema" class="w-80 shrink-0 bg-white border-l border-gray-200 overflow-y-auto">
                <Inspector v-if="activeRightPanel === 'inspector'" />
                <ThemePanel v-else-if="activeRightPanel === 'theme'" />
            </div>
        </div>

        <!-- Version History panel -->
        <VersionHistory
            v-if="showVersionHistory"
            :project-id="project.id"
            @close="showVersionHistory = false"
        />

        <!-- Notification toast -->
        <Transition enter-active-class="transition duration-300" enter-from-class="opacity-0 translate-y-4" leave-active-class="transition duration-200" leave-to-class="opacity-0 translate-y-4">
            <div
                v-if="notification"
                class="fixed bottom-6 right-6 px-4 py-3 rounded-lg shadow-lg text-sm font-medium z-50"
                :class="{
                    'bg-green-50 text-green-800 border border-green-200': notification.type === 'success',
                    'bg-red-50 text-red-800 border border-red-200': notification.type === 'error',
                    'bg-blue-50 text-blue-800 border border-blue-200': notification.type === 'info',
                }"
            >
                {{ notification.message }}
            </div>
        </Transition>
    </div>
</template>

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

.preview-mobile .md\:grid-cols-2,
.preview-mobile .md\:text-5xl,
.preview-mobile .md\:text-4xl {
    /* Reset large text sizes for mobile */
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
