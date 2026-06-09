<script setup>
import { computed, ref } from 'vue';
import { useEditor } from '@/stores/editor.js';
import { BLOCK_TYPES } from '@/schema/defaults.js';
import axios from 'axios';

const editor = useEditor();
const aiInstruction = ref('');
const aiLoading = ref(false);

const block = computed(() => editor.selectedBlock);
const blockDef = computed(() => block.value ? BLOCK_TYPES[block.value.type] : null);

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

async function askAi() {
    if (!aiInstruction.value.trim() || aiLoading.value) return;
    aiLoading.value = true;
    try {
        const { data } = await axios.post(route('ai.edit'), {
            schema: editor.schema,
            instruction: aiInstruction.value,
        });
        editor.setSchema(data.schema);
        aiInstruction.value = '';
    } catch (e) {
        alert(e.response?.data?.error || 'AI edit failed');
    } finally {
        aiLoading.value = false;
    }
}
</script>

<template>
    <div class="p-4">
        <div v-if="!block" class="text-center text-gray-400 py-8">
            <div class="text-3xl mb-2">👆</div>
            <p class="text-sm">Select a block to edit its properties</p>
        </div>

        <div v-else>
            <div class="flex items-center gap-2 mb-4 pb-3 border-b border-gray-100">
                <span class="text-lg">{{ blockDef?.icon }}</span>
                <h3 class="font-semibold text-gray-800">{{ blockDef?.label || block.type }}</h3>
                <button
                    @click="editor.removeBlock(block.id)"
                    class="ml-auto text-xs text-red-500 hover:text-red-700"
                >Delete</button>
            </div>

            <div class="space-y-4">
                <!-- String props -->
                <template v-for="(value, key) in block.props" :key="key">
                    <!-- Skip arrays and objects (handled separately) -->
                    <div v-if="typeof value === 'string'" class="space-y-1">
                        <label class="text-xs font-medium text-gray-500 capitalize">{{ key }}</label>
                        <textarea
                            v-if="key === 'body' || key === 'description'"
                            :value="value"
                            @input="update(key, $event.target.value)"
                            rows="3"
                            class="w-full px-3 py-1.5 text-sm border border-gray-200 rounded-lg focus:ring-1 focus:ring-blue-500"
                        ></textarea>
                        <input
                            v-else-if="key.toLowerCase().includes('color')"
                            type="color"
                            :value="value"
                            @input="update(key, $event.target.value)"
                            class="w-full h-8 rounded cursor-pointer"
                        />
                        <input
                            v-else
                            type="text"
                            :value="value"
                            @input="update(key, $event.target.value)"
                            class="w-full px-3 py-1.5 text-sm border border-gray-200 rounded-lg focus:ring-1 focus:ring-blue-500"
                        />
                    </div>

                    <!-- Number props -->
                    <div v-else-if="typeof value === 'number'" class="space-y-1">
                        <label class="text-xs font-medium text-gray-500 capitalize">{{ key }}</label>
                        <input
                            type="number"
                            :value="value"
                            @input="update(key, Number($event.target.value))"
                            class="w-full px-3 py-1.5 text-sm border border-gray-200 rounded-lg focus:ring-1 focus:ring-blue-500"
                            min="1"
                            max="6"
                        />
                    </div>

                    <!-- Array of objects (links, items, images) -->
                    <div v-else-if="Array.isArray(value) && value.length && typeof value[0] === 'object'" class="space-y-2">
                        <label class="text-xs font-medium text-gray-500 capitalize">{{ key }}</label>
                        <div
                            v-for="(item, i) in value"
                            :key="i"
                            class="p-2 bg-gray-50 rounded-lg space-y-1 relative group"
                        >
                            <button
                                @click="removeArrayItem(key, i)"
                                class="absolute top-1 right-1 text-xs text-red-400 hover:text-red-600 opacity-0 group-hover:opacity-100"
                            >✕</button>
                            <div v-for="(val, field) in item" :key="field">
                                <input
                                    type="text"
                                    :value="val"
                                    @input="updateNested(key, i, field, $event.target.value)"
                                    :placeholder="field"
                                    class="w-full px-2 py-1 text-xs border border-gray-200 rounded focus:ring-1 focus:ring-blue-500"
                                />
                            </div>
                        </div>
                        <button
                            @click="addArrayItem(key, Object.fromEntries(Object.keys(value[0] || {}).map(k => [k, ''])))"
                            class="text-xs text-blue-600 hover:text-blue-800"
                        >+ Add {{ key.slice(0, -1) }}</button>
                    </div>

                    <!-- Array of strings (fields) -->
                    <div v-else-if="Array.isArray(value) && (!value.length || typeof value[0] === 'string')" class="space-y-1">
                        <label class="text-xs font-medium text-gray-500 capitalize">{{ key }}</label>
                        <div class="text-xs text-gray-400">{{ value.join(', ') }}</div>
                    </div>
                </template>
            </div>

            <!-- AI Assist -->
            <div class="mt-6 pt-4 border-t border-gray-100">
                <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2 block">AI Assist</label>
                <div class="flex gap-2">
                    <input
                        v-model="aiInstruction"
                        type="text"
                        placeholder="e.g. Make the heading more professional"
                        class="flex-1 px-3 py-1.5 text-sm border border-gray-200 rounded-lg focus:ring-1 focus:ring-blue-500"
                        @keydown.enter="askAi"
                        :disabled="aiLoading"
                    />
                    <button
                        @click="askAi"
                        :disabled="aiLoading || !aiInstruction.trim()"
                        class="px-3 py-1.5 text-xs font-medium text-white bg-purple-600 rounded-lg hover:bg-purple-700 disabled:opacity-50"
                    >
                        {{ aiLoading ? '...' : 'Ask AI' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
