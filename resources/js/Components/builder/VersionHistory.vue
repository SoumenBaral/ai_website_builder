<script setup>
import { ref, onMounted } from 'vue';
import { useEditor } from '@/stores/editor.js';
import axios from 'axios';

const props = defineProps({
    projectId: [Number, String],
});

const emit = defineEmits(['close']);
const editor = useEditor();
const versions = ref([]);
const loading = ref(false);
const restoring = ref(null);

onMounted(loadVersions);

async function loadVersions() {
    loading.value = true;
    try {
        const { data } = await axios.get(route('versions.index', props.projectId));
        versions.value = data.versions;
    } catch (e) {
        console.error('Failed to load versions', e);
    } finally {
        loading.value = false;
    }
}

async function restore(version) {
    if (!confirm('Restore this version? Current state will be saved as a backup.')) return;
    restoring.value = version.id;
    try {
        const { data } = await axios.post(route('versions.restore', version.id));
        editor.setSchema(data.schema);
        emit('close');
    } catch (e) {
        alert('Failed to restore version');
    } finally {
        restoring.value = null;
    }
}

function formatDate(dateStr) {
    const d = new Date(dateStr);
    return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
}
</script>

<template>
    <div class="fixed inset-0 z-50 flex">
        <div class="absolute inset-0 bg-black/30" @click="emit('close')"></div>
        <div class="relative ml-auto w-96 bg-white h-full shadow-xl flex flex-col">
            <div class="flex items-center justify-between p-4 border-b border-gray-100">
                <h3 class="font-semibold text-gray-800">Version History</h3>
                <button @click="emit('close')" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto p-4">
                <div v-if="loading" class="text-center py-8 text-gray-400">
                    Loading versions...
                </div>

                <div v-else-if="!versions.length" class="text-center py-8 text-gray-400">
                    <div class="text-3xl mb-2">📋</div>
                    <p class="text-sm">No versions saved yet.<br>Versions are created automatically before AI edits.</p>
                </div>

                <div v-else class="space-y-2">
                    <div
                        v-for="version in versions"
                        :key="version.id"
                        class="p-3 border border-gray-100 rounded-lg hover:border-gray-200 transition-colors"
                    >
                        <div class="flex items-start justify-between">
                            <div>
                                <div class="text-sm font-medium text-gray-700">{{ version.label }}</div>
                                <div class="text-xs text-gray-400 mt-0.5">{{ formatDate(version.created_at) }}</div>
                            </div>
                            <button
                                @click="restore(version)"
                                :disabled="restoring === version.id"
                                class="px-2 py-1 text-xs font-medium text-blue-600 hover:bg-blue-50 rounded transition-colors disabled:opacity-50"
                            >
                                {{ restoring === version.id ? 'Restoring...' : 'Restore' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
