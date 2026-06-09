<script setup>
import { useEditor } from '@/stores/editor.js';
import { BLOCK_TYPES, createBlock } from '@/schema/defaults.js';
import { ref } from 'vue';

const editor = useEditor();
const showAddMenu = ref(false);

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

        <div class="flex-1 overflow-y-auto p-2 space-y-1">
            <div
                v-for="(block, index) in editor.schema?.sections"
                :key="block.id"
                @click="editor.select(block.id)"
                class="flex items-center gap-2 px-3 py-2 rounded-lg cursor-pointer text-sm transition-colors"
                :class="editor.selectedId === block.id ? 'bg-blue-50 text-blue-700' : 'hover:bg-gray-50 text-gray-700'"
            >
                <span class="text-base">{{ getBlockIcon(block) }}</span>
                <div class="flex-1 min-w-0">
                    <div class="font-medium text-xs">{{ getBlockLabel(block) }}</div>
                    <div class="text-xs text-gray-400 truncate">{{ getBlockPreview(block) }}</div>
                </div>
                <button
                    @click.stop="editor.removeBlock(block.id)"
                    class="opacity-0 group-hover:opacity-100 p-1 text-gray-400 hover:text-red-500 transition-all"
                    title="Remove block"
                >
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

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
