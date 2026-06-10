<script setup>
import { useEditor } from '@/stores/editor.js';
import { BLOCK_TYPES, createBlock } from '@/schema/defaults.js';
import { ref } from 'vue';
import draggable from 'vuedraggable';

const editor = useEditor();
const showAddMenu = ref(false);
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
    const block = createBlock(type);
    editor.addBlock(block);
    showAddMenu.value = false;
}

function getBlockLabel(block) {
    const def = BLOCK_TYPES[block.type];
    return def ? def.label : block.type;
}

function getBlockIcon(block) {
    const def = BLOCK_TYPES[block.type];
    return def ? def.icon : '📦';
}

function getBlockPreview(block) {
    return block.props.heading || block.props.brand || block.props.text || '';
}
</script>

<template>
    <div class="bg-white border-r border-gray-200 flex flex-col h-full">
        <div class="p-3 border-b border-gray-100">
            <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Blocks</h3>
        </div>

        <div class="flex-1 overflow-y-auto p-2">
            <draggable
                :list="editor.schema?.sections"
                item-key="id"
                handle=".drag-handle"
                ghost-class="drag-ghost"
                drag-class="drag-active"
                :animation="250"
                @start="onDragStart"
                @end="onDragEnd"
                class="space-y-1"
            >
                <template #item="{ element: block }">
                    <div
                        @click="editor.select(block.id)"
                        class="group flex items-center gap-2 px-2 py-2 rounded-lg cursor-pointer text-sm transition-all duration-200"
                        :class="editor.selectedId === block.id
                            ? 'bg-blue-50 text-blue-700 shadow-sm ring-1 ring-blue-200'
                            : 'hover:bg-gray-50 text-gray-700'"
                    >
                        <!-- Drag handle -->
                        <div class="drag-handle flex-shrink-0 flex flex-col items-center justify-center w-5 h-8 cursor-grab active:cursor-grabbing rounded opacity-0 group-hover:opacity-100 transition-opacity hover:bg-gray-200/60"
                             :class="{ 'opacity-100': editor.selectedId === block.id }">
                            <svg class="w-3.5 h-3.5 text-gray-400" viewBox="0 0 16 16" fill="currentColor">
                                <circle cx="5" cy="3" r="1.2" />
                                <circle cx="11" cy="3" r="1.2" />
                                <circle cx="5" cy="8" r="1.2" />
                                <circle cx="11" cy="8" r="1.2" />
                                <circle cx="5" cy="13" r="1.2" />
                                <circle cx="11" cy="13" r="1.2" />
                            </svg>
                        </div>

                        <!-- Icon -->
                        <span class="text-base flex-shrink-0">{{ getBlockIcon(block) }}</span>

                        <!-- Label -->
                        <div class="flex-1 min-w-0">
                            <div class="font-medium text-xs">{{ getBlockLabel(block) }}</div>
                            <div v-if="getBlockPreview(block)" class="text-[10px] text-gray-400 truncate leading-tight">{{ getBlockPreview(block) }}</div>
                        </div>

                        <!-- Delete -->
                        <button
                            @click.stop="editor.removeBlock(block.id)"
                            class="flex-shrink-0 opacity-0 group-hover:opacity-100 p-1 rounded text-gray-400 hover:text-red-500 hover:bg-red-50 transition-all"
                            title="Remove block"
                        >
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </template>
            </draggable>

            <div v-if="!editor.schema?.sections?.length" class="text-xs text-gray-400 text-center py-4">
                No blocks yet
            </div>
        </div>

        <!-- Add block -->
        <div class="p-3 border-t border-gray-100 relative">
            <button
                @click="showAddMenu = !showAddMenu"
                class="w-full px-3 py-2 text-sm font-medium text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors"
            >
                + Add Block
            </button>

            <!-- Dropdown -->
            <div
                v-if="showAddMenu"
                class="absolute bottom-full left-3 right-3 mb-1 bg-white border border-gray-200 rounded-lg shadow-lg z-30 max-h-64 overflow-y-auto"
            >
                <button
                    v-for="(def, type) in BLOCK_TYPES"
                    :key="type"
                    @click="addBlock(type)"
                    class="w-full flex items-center gap-2 px-3 py-2 text-sm hover:bg-gray-50 transition-colors text-left"
                >
                    <span>{{ def.icon }}</span>
                    <span>{{ def.label }}</span>
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.drag-ghost {
    opacity: 0.4;
    background: #eff6ff;
    border-radius: 0.5rem;
    border: 2px dashed #93c5fd;
}
.drag-active {
    background: white;
    border-radius: 0.5rem;
    box-shadow: 0 8px 25px -5px rgba(59, 130, 246, 0.2), 0 4px 10px -4px rgba(59, 130, 246, 0.1);
    transform: rotate(1.5deg);
    z-index: 100;
}
</style>
