<script setup>
import { ref } from 'vue';
import axios from 'axios';

const props = defineProps({
    projectId: [Number, String],
});

const emit = defineEmits(['generated', 'error']);

const prompt = ref('');
const loading = ref(false);
const statusText = ref('');

const statusMessages = [
    'Analyzing your request...',
    'Choosing the perfect layout...',
    'Writing compelling copy...',
    'Selecting a color palette...',
    'Arranging sections...',
    'Adding finishing touches...',
];

let statusInterval = null;

function startStatusCycle() {
    let i = 0;
    statusText.value = statusMessages[0];
    statusInterval = setInterval(() => {
        i = (i + 1) % statusMessages.length;
        statusText.value = statusMessages[i];
    }, 2500);
}

function stopStatusCycle() {
    clearInterval(statusInterval);
    statusText.value = '';
}

async function generate() {
    if (!prompt.value.trim() || loading.value) return;

    loading.value = true;
    startStatusCycle();

    try {
        const { data } = await axios.post(route('ai.generate'), {
            prompt: prompt.value,
            project_id: props.projectId,
        });

        emit('generated', data.schema);
        prompt.value = '';
    } catch (err) {
        const message = err.response?.data?.error || 'Generation failed. Please try again.';
        emit('error', message);
    } finally {
        loading.value = false;
        stopStatusCycle();
    }
}
</script>

<template>
    <div class="bg-white border-b border-gray-200 p-4">
        <form @submit.prevent="generate" class="flex gap-3 items-start max-w-4xl mx-auto">
            <div class="flex-1 relative">
                <textarea
                    v-model="prompt"
                    :disabled="loading"
                    rows="2"
                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none text-sm"
                    placeholder="Describe the website you want, e.g. 'A photography portfolio for a wedding photographer in Dhaka with gallery, about, and contact sections'"
                    @keydown.meta.enter="generate"
                    @keydown.ctrl.enter="generate"
                ></textarea>
                <div v-if="loading" class="absolute bottom-2 left-4 text-xs text-blue-600 animate-pulse">
                    {{ statusText }}
                </div>
            </div>
            <button
                type="submit"
                :disabled="loading || !prompt.trim()"
                class="px-6 py-3 text-sm font-medium text-white bg-blue-600 rounded-xl hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed whitespace-nowrap"
            >
                <span v-if="loading" class="flex items-center gap-2">
                    <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    Generating...
                </span>
                <span v-else>Generate with AI</span>
            </button>
        </form>
    </div>
</template>
