<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const props = defineProps({
    modelValue: { type: String, default: '' },
    projectId: { type: [Number, String], required: true },
});

const emit = defineEmits(['update:modelValue']);

const assets = ref([]);
const uploading = ref(false);
const uploadProgress = ref(0);
const showGallery = ref(false);
const urlMode = ref(false);
const urlInput = ref('');
const fileInput = ref(null);

/* ---------- Free photos ---------- */
const tab = ref('yours'); // yours · free
const stock = ref([]);
const categories = ref([]);
const stockLoading = ref(false);
const stockQuery = ref('');
const stockCategory = ref('');
const stockLive = ref(false);
let stockTimer = 0;
let stockSeq = 0;

async function loadStock() {
    const seq = ++stockSeq;
    stockLoading.value = true;
    try {
        const { data } = await axios.get(route('stock.index'), { params: { q: stockQuery.value || undefined, category: stockCategory.value || undefined } });
        if (seq !== stockSeq) return; // a newer search finished first
        stock.value = data.photos;
        categories.value = data.categories;
        stockLive.value = data.live;
    } catch (e) {
        if (seq === stockSeq) stock.value = [];
    } finally {
        if (seq === stockSeq) stockLoading.value = false;
    }
}
function searchStock() {
    clearTimeout(stockTimer);
    stockTimer = setTimeout(loadStock, 300);
}
function pickCategory(key) {
    stockCategory.value = stockCategory.value === key ? '' : key;
    stockQuery.value = '';
    loadStock();
}
function showFree() {
    tab.value = 'free';
    if (!stock.value.length) loadStock();
}

async function fetchAssets() {
    try {
        const { data } = await axios.get(`/projects/${props.projectId}/assets`);
        assets.value = data;
    } catch (e) {
        // silent
    }
}

async function handleUpload(event) {
    const file = event.target.files?.[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('image', file);

    uploading.value = true;
    uploadProgress.value = 0;

    try {
        const { data } = await axios.post(`/projects/${props.projectId}/assets`, formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
            onUploadProgress: (e) => {
                uploadProgress.value = Math.round((e.loaded / e.total) * 100);
            },
        });

        await deletePreviousAsset();
        assets.value.unshift({ id: data.id, url: data.url, meta: data.meta });
        emit('update:modelValue', data.url);
        showGallery.value = false;
    } catch (e) {
        alert(e.response?.data?.message || 'Upload failed');
    } finally {
        uploading.value = false;
        uploadProgress.value = 0;
        if (fileInput.value) fileInput.value.value = '';
    }
}

async function deletePreviousAsset() {
    if (!props.modelValue) return;
    // Only delete if it's a project-uploaded asset (starts with /storage/assets/)
    if (!props.modelValue.startsWith('/storage/assets/')) return;
    const old = assets.value.find(a => a.url === props.modelValue);
    if (!old) return;
    try {
        await axios.delete(`/projects/${props.projectId}/assets/${old.id}`);
        assets.value = assets.value.filter(a => a.id !== old.id);
    } catch (e) {
        // silent — old file cleanup is best-effort
    }
}

async function selectAsset(url) {
    if (url !== props.modelValue) {
        await deletePreviousAsset();
    }
    emit('update:modelValue', url);
    showGallery.value = false;
}

async function applyUrl() {
    if (urlInput.value.trim()) {
        await deletePreviousAsset();
        emit('update:modelValue', urlInput.value.trim());
        urlInput.value = '';
        urlMode.value = false;
        showGallery.value = false;
    }
}

async function openGallery() {
    showGallery.value = true;
    await fetchAssets();
    // No uploads yet? Start on the free photo library.
    if (!assets.value.length) showFree();
}

onMounted(fetchAssets);
</script>

<template>
    <div class="space-y-2">
        <!-- Current image preview -->
        <div v-if="modelValue" class="group relative overflow-hidden rounded-xl border border-lp-ink/10 bg-lp-ink/[0.03]">
            <img :src="modelValue" alt="Current" class="w-full h-28 object-cover" @error="$event.target.src = ''" />
            <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                <button @click="openGallery" class="rounded-md bg-white/20 px-2.5 py-1 text-xs font-medium text-white backdrop-blur hover:bg-white/30">
                    Change
                </button>
                <button @click="deletePreviousAsset().then(() => emit('update:modelValue', ''))" class="px-2.5 py-1 text-xs font-medium text-white bg-red-500/60 backdrop-blur rounded-md hover:bg-red-500/80">
                    Remove
                </button>
            </div>
        </div>

        <!-- Upload / Gallery trigger -->
        <div v-if="!modelValue" class="flex gap-1.5">
            <button @click="openGallery" class="ed-option flex-1 !py-2">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0 0 22.5 18.75V5.25A2.25 2.25 0 0 0 20.25 3H3.75A2.25 2.25 0 0 0 1.5 5.25v13.5A2.25 2.25 0 0 0 3.75 21Z" />
                </svg>
                Choose Image
            </button>
        </div>

        <!-- Gallery modal -->
        <Teleport defer to="#editor-root">
            <Transition enter-active-class="transition duration-200" enter-from-class="opacity-0" leave-active-class="transition duration-150" leave-to-class="opacity-0">
                <div v-if="showGallery" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50" @click.self="showGallery = false">
                    <div class="picker-panel mx-4 flex max-h-[85vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl">
                        <!-- Header -->
                        <div class="flex items-center justify-between gap-3 border-b border-lp-ink/[0.07] px-5 py-3.5">
                            <div class="picker-tabs" role="tablist">
                                <button type="button" role="tab" :aria-selected="tab === 'yours'" :class="['picker-tab', tab === 'yours' && 'picker-tab--on']" @click="tab = 'yours'">Your images<span v-if="assets.length" class="picker-count">{{ assets.length }}</span></button>
                                <button type="button" role="tab" :aria-selected="tab === 'free'" :class="['picker-tab', tab === 'free' && 'picker-tab--on']" @click="showFree">Free photos</button>
                            </div>
                            <button @click="showGallery = false" class="ed-icon-btn">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                            </button>
                        </div>

                        <!-- Upload area -->
                        <div v-show="tab === 'yours'" class="border-b border-lp-ink/[0.07] px-5 py-3">
                            <div class="flex gap-2">
                                <label class="flex flex-1 cursor-pointer items-center justify-center gap-2 rounded-xl border-2 border-dashed border-lp-ink/15 px-4 py-3 transition hover:border-violet-400/60 hover:bg-violet-500/5">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5 text-lp-subtle"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" /></svg>
                                    <span class="text-xs font-medium text-lp-muted">
                                        {{ uploading ? `Uploading ${uploadProgress}%...` : 'Upload Image' }}
                                    </span>
                                    <input ref="fileInput" type="file" accept="image/*" class="hidden" @change="handleUpload" :disabled="uploading" />
                                </label>
                                <button @click="urlMode = !urlMode" :class="['ed-option !rounded-xl !px-3', urlMode && 'ed-option--active']" title="Paste URL">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244" /></svg>
                                </button>
                            </div>

                            <!-- Upload progress -->
                            <div v-if="uploading" class="mt-2 h-1.5 overflow-hidden rounded-full bg-lp-ink/10">
                                <div class="h-full rounded-full bg-gradient-to-r from-indigo-500 to-fuchsia-500 transition-all duration-300" :style="{ width: uploadProgress + '%' }"></div>
                            </div>

                            <!-- URL input -->
                            <div v-if="urlMode" class="mt-2 flex gap-2">
                                <input v-model="urlInput" type="text" placeholder="https://example.com/image.jpg" class="ed-input ed-input-sm flex-1" @keydown.enter="applyUrl" />
                                <button @click="applyUrl" class="btn btn-primary h-8 !rounded-lg px-3 text-xs">Use</button>
                            </div>
                        </div>

                        <!-- Free photos -->
                        <template v-if="tab === 'free'">
                            <div class="border-b border-lp-ink/[0.07] px-5 py-3">
                                <label class="picker-search">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="size-4 shrink-0 text-lp-subtle"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
                                    <input v-model="stockQuery" type="search" placeholder="Search photos — e.g. coffee, cricket, sneakers, wedding" @input="searchStock" @keydown.enter.prevent="loadStock" />
                                </label>
                                <div class="picker-cats mt-2.5">
                                    <button v-for="c in categories" :key="c.key" type="button" :class="['picker-cat', stockCategory === c.key && 'picker-cat--on']" @click="pickCategory(c.key)">{{ c.label }}</button>
                                </div>
                            </div>
                            <div class="flex-1 overflow-y-auto p-5">
                                <div v-if="stockLoading && !stock.length" class="grid grid-cols-3 gap-2 sm:grid-cols-4">
                                    <div v-for="n in 12" :key="n" class="aspect-[4/3] animate-pulse rounded-xl bg-lp-ink/[0.07]"></div>
                                </div>
                                <div v-else-if="!stock.length" class="py-10 text-center text-lp-subtle">
                                    <p class="text-sm font-medium">No photos for “{{ stockQuery }}”</p>
                                    <p class="mt-1 text-xs">Try a simpler word, or pick a category above.</p>
                                </div>
                                <div v-else :class="['grid grid-cols-3 gap-2 sm:grid-cols-4 transition-opacity', stockLoading && 'opacity-60']">
                                    <button
                                        v-for="photo in stock"
                                        :key="photo.id"
                                        type="button"
                                        :title="photo.label"
                                        class="group relative aspect-[4/3] overflow-hidden rounded-xl border-2 transition-all hover:shadow-lg"
                                        :class="modelValue === photo.url ? 'border-violet-500 ring-2 ring-violet-500/30' : 'border-transparent hover:border-violet-400/60'"
                                        @click="selectAsset(photo.url)"
                                    >
                                        <img :src="photo.thumb" :alt="photo.label" loading="lazy" class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105" />
                                        <span class="absolute inset-x-0 bottom-0 truncate bg-gradient-to-t from-black/70 to-transparent px-2 pb-1 pt-4 text-left text-[10px] font-medium capitalize text-white opacity-0 transition-opacity group-hover:opacity-100">
                                            {{ photo.label }}<template v-if="photo.credit"> · {{ photo.credit }}</template>
                                        </span>
                                    </button>
                                </div>
                                <p class="mt-4 text-center text-[11px] text-lp-subtle">Free to use photos from Unsplash{{ stockLive ? '' : ' · hand-picked library' }}.</p>
                            </div>
                        </template>

                        <!-- Gallery grid -->
                        <div v-if="tab === 'yours'" class="flex-1 overflow-y-auto p-5">
                            <div v-if="assets.length === 0 && !uploading" class="py-10 text-center text-lp-subtle">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" class="mx-auto mb-3 size-10 text-lp-subtle/60"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0 0 22.5 18.75V5.25A2.25 2.25 0 0 0 20.25 3H3.75A2.25 2.25 0 0 0 1.5 5.25v13.5A2.25 2.25 0 0 0 3.75 21Z" /></svg>
                                <p class="text-sm font-medium">No images yet</p>
                                <p class="text-xs mt-1">Upload your first image above, or <button type="button" class="font-semibold text-violet-500 hover:underline" @click="showFree">browse free photos</button>.</p>
                            </div>
                            <div v-else class="grid grid-cols-3 gap-2">
                                <button
                                    v-for="asset in assets"
                                    :key="asset.id"
                                    @click="selectAsset(asset.url)"
                                    class="group relative aspect-square rounded-xl overflow-hidden border-2 transition-all hover:shadow-lg"
                                    :class="modelValue === asset.url ? 'border-violet-500 ring-2 ring-violet-500/30' : 'border-lp-ink/10 hover:border-violet-400/50'"
                                >
                                    <img :src="asset.url" alt="" class="w-full h-full object-cover" />
                                    <div v-if="modelValue === asset.url" class="absolute inset-0 flex items-center justify-center bg-violet-500/20">
                                        <div class="flex size-6 items-center justify-center rounded-full bg-violet-500">
                                            <svg class="size-3.5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                        </div>
                                    </div>
                                    <div v-if="asset.meta?.original_name" class="absolute bottom-0 inset-x-0 bg-gradient-to-t from-black/60 to-transparent p-1.5 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <p class="text-[10px] text-white truncate">{{ asset.meta.original_name }}</p>
                                    </div>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>

<style scoped>
.picker-tabs { display: inline-flex; gap: 2px; border-radius: 0.7rem; background: rgb(var(--lp-ink) / 0.05); padding: 3px; }
.picker-tab { display: inline-flex; align-items: center; gap: 0.35rem; border-radius: 0.5rem; padding: 0.35rem 0.8rem; font-size: 13px; font-weight: 500; color: rgb(var(--lp-muted)); transition: background-color 0.15s, color 0.15s; }
.picker-tab:hover { color: rgb(var(--lp-text)); }
.picker-tab--on { background: rgb(var(--lp-surface)); color: rgb(var(--lp-fg)); font-weight: 600; box-shadow: 0 1px 3px rgb(0 0 0 / 0.12); }
.picker-count { border-radius: 9999px; background: rgb(var(--lp-ink) / 0.08); padding: 0 0.4rem; font-size: 11px; }
.picker-search { display: flex; align-items: center; gap: 0.5rem; height: 2.4rem; border-radius: 0.7rem; border: 1px solid rgb(var(--lp-ink) / 0.1); background: rgb(var(--lp-ink) / 0.03); padding: 0 0.75rem; }
.picker-search:focus-within { border-color: rgb(139 92 246 / 0.6); box-shadow: 0 0 0 3px rgb(139 92 246 / 0.15); }
.picker-search input { min-width: 0; flex: 1; border: 0; background: transparent; padding: 0; font-size: 13px; color: rgb(var(--lp-text)); box-shadow: none; }
.picker-search input:focus { outline: none; box-shadow: none; }
.picker-cats { display: flex; gap: 0.35rem; overflow-x: auto; padding-bottom: 2px; scrollbar-width: none; }
.picker-cats::-webkit-scrollbar { display: none; }
.picker-cat { flex-shrink: 0; border-radius: 9999px; border: 1px solid rgb(var(--lp-ink) / 0.1); padding: 0.25rem 0.7rem; font-size: 12px; font-weight: 500; color: rgb(var(--lp-muted)); transition: all 0.15s; }
.picker-cat:hover { color: rgb(var(--lp-fg)); border-color: rgb(139 92 246 / 0.4); }
.picker-cat--on { border-color: rgb(139 92 246 / 0.6); background: rgb(139 92 246 / 0.12); color: rgb(var(--lp-accent)); font-weight: 600; }
.picker-panel {
    border: 1px solid rgb(var(--lp-ink) / 0.1);
    background: rgb(var(--lp-surface));
    box-shadow: 0 40px 100px -20px rgb(0 0 0 / 0.5);
}
</style>
