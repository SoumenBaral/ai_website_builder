<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { useEditor } from '@/stores/editor.js';
import Icon from '@/Components/Landing/Icon.vue';
import { useToast } from './useToast';
import axios from 'axios';

const props = defineProps({
    projectId: [Number, String],
});

const emit = defineEmits(['close']);
const editor = useEditor();
const { notify } = useToast();
const versions = ref([]);
const loading = ref(false);
const restoring = ref(null);
const confirming = ref(null); // id of the version awaiting "Yes, restore"

const onKey = (e) => { if (e.key === 'Escape') emit('close'); };
onMounted(() => {
    loadVersions();
    window.addEventListener('keydown', onKey);
});
onBeforeUnmount(() => window.removeEventListener('keydown', onKey));

async function loadVersions() {
    loading.value = true;
    try {
        const { data } = await axios.get(route('versions.index', props.projectId));
        versions.value = data.versions;
    } catch (e) {
        notify('Could not load version history.', 'error');
    } finally {
        loading.value = false;
    }
}

async function restore(version) {
    restoring.value = version.id;
    try {
        const { data } = await axios.post(route('versions.restore', version.id));
        editor.setSchema(data.schema);
        notify(`Restored “${version.label}”`, 'success');
        emit('close');
    } catch (e) {
        notify('Failed to restore this version.', 'error');
    } finally {
        restoring.value = null;
        confirming.value = null;
    }
}

function formatDate(dateStr) {
    const d = new Date(dateStr);
    return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' });
}
</script>

<template>
    <div class="fixed inset-0 z-50 flex" role="dialog" aria-modal="true" aria-label="Version history">
        <div class="history-backdrop absolute inset-0" @click="emit('close')"></div>
        <aside class="history-panel relative ml-auto flex h-full w-full max-w-sm flex-col">
            <div class="flex items-center gap-3 border-b border-lp-ink/[0.07] px-5 py-4">
                <span class="icon-tile size-9 rounded-xl"><Icon name="clock" :stroke="1.7" class="size-[18px]" /></span>
                <div class="min-w-0 flex-1">
                    <h3 class="text-sm font-semibold text-lp-fg">Version history</h3>
                    <p class="text-[11px] text-lp-subtle">Saved automatically before AI edits</p>
                </div>
                <button type="button" class="ed-icon-btn" aria-label="Close" @click="emit('close')"><Icon name="xMark" :stroke="2" class="size-5" /></button>
            </div>

            <div class="ed-scroll flex-1 overflow-y-auto px-5 py-5">
                <div v-if="loading" class="space-y-3">
                    <div v-for="n in 4" :key="n" class="h-16 animate-pulse rounded-xl bg-lp-ink/[0.05]"></div>
                </div>

                <div v-else-if="!versions.length" class="flex flex-col items-center py-16 text-center">
                    <span class="icon-tile size-12 rounded-2xl"><Icon name="clock" :stroke="1.6" class="size-5" /></span>
                    <p class="mt-4 text-sm font-semibold text-lp-fg">No versions yet</p>
                    <p class="mt-1 max-w-[16rem] text-[13px] text-lp-muted">A snapshot is saved automatically every time AI changes your site.</p>
                </div>

                <ol v-else class="timeline">
                    <li v-for="(version, i) in versions" :key="version.id" class="relative pb-4 pl-7 last:pb-0">
                        <span :class="['dot', i === 0 && 'dot--latest']"></span>
                        <div class="ed-item p-3">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="truncate text-[13px] font-medium text-lp-fg">{{ version.label }}</div>
                                    <div class="mt-0.5 text-[11px] text-lp-subtle">{{ formatDate(version.created_at) }}<span v-if="i === 0"> · latest</span></div>
                                </div>
                                <button v-if="confirming !== version.id" type="button" class="ed-option shrink-0 !py-1" @click="confirming = version.id">Restore</button>
                            </div>
                            <div v-if="confirming === version.id" class="mt-3 flex items-center justify-between gap-2 rounded-lg bg-lp-ink/[0.04] px-2.5 py-2">
                                <span class="text-[11px] text-lp-muted">Current state is backed up first.</span>
                                <div class="flex shrink-0 gap-1.5">
                                    <button type="button" class="ed-option !py-1" @click="confirming = null">Cancel</button>
                                    <button type="button" class="btn btn-primary h-7 !rounded-lg px-2.5 text-[11px]" :disabled="restoring === version.id" @click="restore(version)">
                                        {{ restoring === version.id ? 'Restoring…' : 'Yes, restore' }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </li>
                </ol>
            </div>
        </aside>
    </div>
</template>

<style scoped>
.history-backdrop { background: rgb(5 5 10 / 0.45); animation: fade 0.2s ease; }
.history-panel {
    border-left: 1px solid rgb(var(--lp-ink) / 0.08);
    background: rgb(var(--lp-bg));
    box-shadow: -20px 0 60px -10px rgb(0 0 0 / 0.4);
    animation: slide 0.35s cubic-bezier(0.22, 1, 0.36, 1);
}
.timeline { position: relative; }
.timeline::before {
    content: '';
    position: absolute;
    left: 0.4rem;
    top: 0.5rem;
    bottom: 0.5rem;
    width: 1px;
    background: rgb(var(--lp-ink) / 0.1);
}
.dot {
    position: absolute;
    left: 0;
    top: 1rem;
    width: 0.85rem;
    height: 0.85rem;
    border-radius: 9999px;
    border: 2px solid rgb(var(--lp-ink) / 0.25);
    background: rgb(var(--lp-bg));
}
.dot--latest { border-color: #a855f7; background: #a855f7; box-shadow: 0 0 0 4px rgb(168 85 247 / 0.2); }
@keyframes slide { from { transform: translateX(100%); } }
@keyframes fade { from { opacity: 0; } }
</style>
