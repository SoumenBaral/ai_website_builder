<script setup>
import { ref, computed } from 'vue';
import { useEditor } from '@/stores/editor.js';
import { BLOCK_TYPES } from '@/schema/defaults.js';
import axios from 'axios';

const props = defineProps({
    projectId: [Number, String],
});

const emit = defineEmits(['generated', 'error']);
const editor = useEditor();

const prompt = ref('');
const loading = ref(false);
const statusText = ref('');

const hasSchema = computed(() => editor.schema?.sections?.length > 0);
const selectedBlock = computed(() => editor.selectedBlock);
const selectedLabel = computed(() => {
    if (!selectedBlock.value) return '';
    const def = BLOCK_TYPES[selectedBlock.value.type];
    return def?.label || selectedBlock.value.type;
});

// Determine mode: if site exists and a block is selected, default to editing that section
const mode = computed(() => {
    if (!hasSchema.value) return 'generate';
    if (selectedBlock.value) return 'section';
    return 'full';
});

const placeholderText = computed(() => {
    if (mode.value === 'generate') {
        return "Describe the website you want, e.g. 'A photography portfolio for a wedding photographer with gallery and contact sections'";
    }
    if (mode.value === 'section') {
        return `Edit the ${selectedLabel.value} section, e.g. 'Add phone number and address' or 'Make it more professional'`;
    }
    return "Describe changes to the full site, e.g. 'Make all headings shorter and punchier'";
});

const generateStatusMessages = [
    'Analyzing your request...',
    'Choosing the perfect layout...',
    'Writing compelling copy...',
    'Selecting a color palette...',
    'Arranging sections...',
    'Adding finishing touches...',
];

const editStatusMessages = [
    'Understanding your changes...',
    'Rewriting content...',
    'Polishing the result...',
];

let statusInterval = null;

function startStatusCycle(messages) {
    let i = 0;
    statusText.value = messages[0];
    statusInterval = setInterval(() => {
        i = (i + 1) % messages.length;
        statusText.value = messages[i];
    }, 2000);
}

function stopStatusCycle() {
    clearInterval(statusInterval);
    statusText.value = '';
}

async function submit() {
    if (!prompt.value.trim() || loading.value) return;

    loading.value = true;

    try {
        if (mode.value === 'generate') {
            // Generate new site
            startStatusCycle(generateStatusMessages);
            const { data } = await axios.post(route('ai.generate'), {
                prompt: prompt.value,
                project_id: props.projectId,
            });
            emit('generated', data.schema);
        } else if (mode.value === 'section' && selectedBlock.value) {
            // Edit selected section only
            startStatusCycle(editStatusMessages);
            const { data } = await axios.post(route('ai.editSection'), {
                section: selectedBlock.value,
                theme: editor.schema.theme,
                instruction: prompt.value,
            });
            editor.updateSection(selectedBlock.value.id, data.section);
        } else {
            // Edit full site
            startStatusCycle(editStatusMessages);
            const { data } = await axios.post(route('ai.edit'), {
                schema: editor.schema,
                instruction: prompt.value,
            });
            editor.setSchema(data.schema);
        }
        prompt.value = '';
    } catch (err) {
        const message = err.response?.data?.error || 'AI request failed. Please try again.';
        emit('error', message);
    } finally {
        loading.value = false;
        stopStatusCycle();
    }
}
</script>

<template>
    <div class="bg-white border-b border-gray-200 px-4 py-3">
        <form @submit.prevent="submit" class="flex gap-3 items-start max-w-4xl mx-auto">
            <div class="flex-1 relative">
                <!-- Mode indicator badge -->
                <div class="flex items-center gap-2 mb-1.5" v-if="hasSchema">
                    <span
                        v-if="mode === 'section'"
                        class="inline-flex items-center gap-1.5 px-2.5 py-1 text-[11px] font-semibold rounded-full bg-purple-50 text-purple-700 border border-purple-200"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-3"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z" /></svg>
                        Editing: {{ selectedLabel }}
                    </span>
                    <span
                        v-else
                        class="inline-flex items-center gap-1.5 px-2.5 py-1 text-[11px] font-semibold rounded-full bg-blue-50 text-blue-700 border border-blue-200"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-3"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5a17.92 17.92 0 0 1-8.716-2.247m0 0A9.015 9.015 0 0 1 3 12c0-1.605.42-3.113 1.157-4.418" /></svg>
                        Editing: Full Site
                    </span>
                    <span class="text-[10px] text-gray-400" v-if="mode === 'section'">
                        Click a different block or deselect to edit full site
                    </span>
                </div>

                <textarea
                    v-model="prompt"
                    :disabled="loading"
                    rows="2"
                    class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:border-transparent resize-none text-sm transition-colors"
                    :class="mode === 'section'
                        ? 'border-purple-200 focus:ring-purple-400 bg-purple-50/30'
                        : mode === 'full'
                            ? 'border-blue-200 focus:ring-blue-400 bg-blue-50/30'
                            : 'border-gray-300 focus:ring-blue-500'"
                    :placeholder="placeholderText"
                    @keydown.meta.enter="submit"
                    @keydown.ctrl.enter="submit"
                ></textarea>
                <div v-if="loading" class="absolute bottom-2 left-4 text-xs animate-pulse"
                     :class="mode === 'section' ? 'text-purple-600' : 'text-blue-600'">
                    {{ statusText }}
                </div>
            </div>
            <button
                type="submit"
                :disabled="loading || !prompt.trim()"
                class="px-6 py-3 text-sm font-medium text-white rounded-xl transition-colors disabled:opacity-50 disabled:cursor-not-allowed whitespace-nowrap shrink-0"
                :class="mode === 'section'
                    ? 'bg-purple-600 hover:bg-purple-700'
                    : 'bg-blue-600 hover:bg-blue-700'"
                style="margin-top: auto;"
                :style="hasSchema ? 'margin-top: 28px' : ''"
            >
                <span v-if="loading" class="flex items-center gap-2">
                    <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    Working...
                </span>
                <span v-else-if="mode === 'generate'">Generate with AI</span>
                <span v-else-if="mode === 'section'" class="flex items-center gap-1.5">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-4"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z" /></svg>
                    Edit Section
                </span>
                <span v-else class="flex items-center gap-1.5">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-4"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z" /></svg>
                    Edit Full Site
                </span>
            </button>
        </form>
    </div>
</template>
