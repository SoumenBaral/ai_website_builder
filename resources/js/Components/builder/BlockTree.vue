<script setup>
import { ref } from 'vue';
import draggable from 'vuedraggable';
import { useEditor } from '@/stores/editor.js';
import { BLOCK_TYPES, createBlock } from '@/schema/defaults.js';
import Icon from '@/Components/Landing/Icon.vue';
import AppMenu from '@/Components/App/AppMenu.vue';
import { blockIcons, blockBlurbs } from './blockMeta';

const emit = defineEmits(['select']);
const editor = useEditor();
const dragging = ref(false);

function onDragStart() {
    dragging.value = true;
    // Save state BEFORE the drag mutates the array
    editor.pushHistory();
}

function onDragEnd() {
    dragging.value = false;
    editor.dirty = true;
}

function addBlock(type) {
    // Insert after the selected section if there is one, otherwise at the end
    const idx = editor.schema.sections.findIndex((s) => s.id === editor.selectedId);
    editor.addBlock(createBlock(type), idx === -1 ? undefined : idx + 1);
    emit('select', editor.selectedId);
}

function select(id) {
    editor.select(id);
    emit('select', id);
}

const label = (block) => BLOCK_TYPES[block.type]?.label || block.type;
const preview = (block) => {
    const p = block.props || {};
    const text = p.heading || p.brand || p.text || p.title || '';
    return typeof text === 'string' ? text : '';
};
</script>

<template>
    <div class="flex h-full flex-col">
        <div class="flex h-12 shrink-0 items-center justify-between px-4">
            <div class="flex items-center gap-2">
                <span class="ed-heading">Layers</span>
                <span v-if="editor.schema?.sections?.length" class="rounded-full bg-lp-ink/[0.07] px-1.5 text-[10px] font-semibold tabular-nums text-lp-muted">{{ editor.schema.sections.length }}</span>
            </div>
        </div>

        <div class="ed-scroll min-h-0 flex-1 overflow-y-auto px-2 pb-2">
            <draggable
                :list="editor.schema?.sections"
                item-key="id"
                handle=".drag-handle"
                ghost-class="layer-ghost"
                drag-class="layer-drag"
                :animation="200"
                class="space-y-0.5"
                @start="onDragStart"
                @end="onDragEnd"
            >
                <template #item="{ element: block, index }">
                    <div
                        :class="['layer group', editor.selectedId === block.id && 'layer--active']"
                        role="button"
                        tabindex="0"
                        :aria-pressed="editor.selectedId === block.id"
                        @click="select(block.id)"
                        @keydown.enter.prevent="select(block.id)"
                    >
                        <span class="drag-handle" title="Drag to reorder" aria-hidden="true">
                            <svg viewBox="0 0 16 16" fill="currentColor" class="size-3.5"><circle cx="5.5" cy="3" r="1.2" /><circle cx="10.5" cy="3" r="1.2" /><circle cx="5.5" cy="8" r="1.2" /><circle cx="10.5" cy="8" r="1.2" /><circle cx="5.5" cy="13" r="1.2" /><circle cx="10.5" cy="13" r="1.2" /></svg>
                        </span>
                        <span class="layer-icon"><Icon :name="blockIcons[block.type] || 'grid'" :stroke="1.7" class="size-4" /></span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-[13px] font-medium">{{ label(block) }}</span>
                            <span v-if="preview(block)" class="block truncate text-[11px] text-lp-subtle">{{ preview(block) }}</span>
                        </span>
                        <span class="text-[10px] tabular-nums text-lp-subtle opacity-60 group-hover:hidden">{{ index + 1 }}</span>
                        <button type="button" class="ed-icon-btn ed-icon-btn--danger hidden size-7 group-hover:inline-flex" title="Remove section" :aria-label="`Remove ${label(block)}`" @click.stop="editor.removeBlock(block.id)">
                            <Icon name="trash" :stroke="1.7" class="size-3.5" />
                        </button>
                    </div>
                </template>
            </draggable>

            <div v-if="!editor.schema?.sections?.length" class="mx-2 mt-2 rounded-xl border border-dashed border-lp-ink/15 px-3 py-6 text-center text-xs text-lp-subtle">
                No sections yet.<br />Generate with AI or add one below.
            </div>
        </div>

        <!-- Add section -->
        <div class="shrink-0 border-t border-lp-ink/[0.07] p-3">
            <AppMenu placement="top" align="left" width="w-[19rem]" class="w-full">
                <template #trigger="{ toggle, open }">
                    <button type="button" class="btn btn-ghost h-9 w-full text-[13px]" :aria-expanded="open" aria-haspopup="menu" @click="toggle">
                        <Icon name="plus" :stroke="2" class="size-4" />Add section
                    </button>
                </template>
                <template #default="{ close }">
                    <div class="ed-scroll grid max-h-[60vh] grid-cols-1 gap-0.5 overflow-y-auto">
                        <button v-for="(def, type) in BLOCK_TYPES" :key="type" type="button" class="menu-item !items-start" role="menuitem" @click="addBlock(type); close()">
                            <span class="layer-icon mt-0.5"><Icon :name="blockIcons[type] || 'grid'" :stroke="1.7" class="size-4" /></span>
                            <span class="min-w-0">
                                <span class="block text-[13px] font-medium text-lp-fg">{{ def.label }}</span>
                                <span class="block truncate text-[11px] text-lp-subtle">{{ blockBlurbs[type] }}</span>
                            </span>
                        </button>
                    </div>
                </template>
            </AppMenu>
        </div>
    </div>
</template>

<style scoped>
.layer {
    display: flex;
    cursor: pointer;
    align-items: center;
    gap: 0.5rem;
    border-radius: 0.65rem;
    border: 1px solid transparent;
    padding: 0.45rem 0.5rem 0.45rem 0.25rem;
    color: rgb(var(--lp-text));
    transition: background-color 0.15s, border-color 0.15s;
}
.layer:hover { background: rgb(var(--lp-ink) / 0.045); }
.layer:focus-visible { outline: 2px solid rgb(139 92 246 / 0.6); outline-offset: -2px; }
.layer--active,
.layer--active:hover {
    border-color: rgb(139 92 246 / 0.35);
    background: rgb(139 92 246 / 0.1);
    color: rgb(var(--lp-fg));
}
.layer-icon {
    display: flex;
    width: 1.75rem;
    height: 1.75rem;
    flex-shrink: 0;
    align-items: center;
    justify-content: center;
    border-radius: 0.5rem;
    background: rgb(var(--lp-ink) / 0.05);
    color: rgb(var(--lp-muted));
}
.layer--active .layer-icon { background: rgb(139 92 246 / 0.18); color: rgb(var(--lp-accent)); }
.drag-handle {
    display: flex;
    width: 1rem;
    height: 1.75rem;
    flex-shrink: 0;
    cursor: grab;
    align-items: center;
    justify-content: center;
    color: rgb(var(--lp-subtle));
    opacity: 0;
    transition: opacity 0.15s;
}
.drag-handle:active { cursor: grabbing; }
.layer:hover .drag-handle, .layer--active .drag-handle { opacity: 0.8; }
@media (hover: none) { .drag-handle { opacity: 0.6; } }

.layer-ghost { opacity: 0.4; border: 1px dashed rgb(139 92 246 / 0.6) !important; background: rgb(139 92 246 / 0.08) !important; }
.layer-drag { background: rgb(var(--lp-surface)) !important; box-shadow: 0 12px 30px -8px rgb(0 0 0 / 0.35); }
</style>
