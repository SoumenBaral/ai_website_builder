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

function openGallery() {
    showGallery.value = true;
    fetchAssets();
}

onMounted(fetchAssets);
</script>

<template>
    <div class="space-y-2">
        <!-- Current image preview -->
        <div v-if="modelValue" class="relative group rounded-lg overflow-hidden border border-gray-200 bg-gray-50">
            <img :src="modelValue" alt="Current" class="w-full h-28 object-cover" @error="$event.target.src = ''" />
            <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                <button @click="openGallery" class="px-2.5 py-1 text-xs font-medium text-white bg-white/20 backdrop-blur rounded-md hover:bg-white/30">
                    Change
                </button>
                <button @click="deletePreviousAsset().then(() => emit('update:modelValue', ''))" class="px-2.5 py-1 text-xs font-medium text-white bg-red-500/60 backdrop-blur rounded-md hover:bg-red-500/80">
                    Remove
                </button>
            </div>
        </div>

        <!-- Upload / Gallery trigger -->
        <div v-if="!modelValue" class="flex gap-1.5">
            <button @click="openGallery" class="flex-1 flex items-center justify-center gap-1.5 px-3 py-2 text-xs font-medium text-indigo-600 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0 0 22.5 18.75V5.25A2.25 2.25 0 0 0 20.25 3H3.75A2.25 2.25 0 0 0 1.5 5.25v13.5A2.25 2.25 0 0 0 3.75 21Z" />
                </svg>
                Choose Image
            </button>
        </div>

        <!-- Gallery modal -->
        <Teleport to="body">
            <Transition enter-active-class="transition duration-200" enter-from-class="opacity-0" leave-active-class="transition duration-150" leave-to-class="opacity-0">
                <div v-if="showGallery" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 backdrop-blur-sm" @click.self="showGallery = false">
                    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg mx-4 max-h-[80vh] flex flex-col overflow-hidden">
                        <!-- Header -->
                        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                            <h3 class="text-sm font-semibold text-gray-800">Project Images</h3>
                            <button @click="showGallery = false" class="text-gray-400 hover:text-gray-600">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                            </button>
                        </div>

                        <!-- Upload area -->
                        <div class="px-5 py-3 border-b border-gray-100">
                            <div class="flex gap-2">
                                <label class="flex-1 flex items-center justify-center gap-2 px-4 py-3 border-2 border-dashed border-gray-200 rounded-xl cursor-pointer hover:border-indigo-300 hover:bg-indigo-50/50 transition">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5 text-gray-400"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" /></svg>
                                    <span class="text-xs font-medium text-gray-500">
                                        {{ uploading ? `Uploading ${uploadProgress}%...` : 'Upload Image' }}
                                    </span>
                                    <input ref="fileInput" type="file" accept="image/*" class="hidden" @change="handleUpload" :disabled="uploading" />
                                </label>
                                <button @click="urlMode = !urlMode" :class="urlMode ? 'bg-indigo-50 text-indigo-600 border-indigo-200' : 'bg-white text-gray-500 border-gray-200'" class="px-3 py-2 text-xs font-medium border rounded-xl hover:bg-gray-50 transition" title="Paste URL">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244" /></svg>
                                </button>
                            </div>

                            <!-- Upload progress -->
                            <div v-if="uploading" class="mt-2 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                                <div class="h-full bg-indigo-500 rounded-full transition-all duration-300" :style="{ width: uploadProgress + '%' }"></div>
                            </div>

                            <!-- URL input -->
                            <div v-if="urlMode" class="mt-2 flex gap-2">
                                <input v-model="urlInput" type="text" placeholder="https://example.com/image.jpg" class="flex-1 px-3 py-2 text-xs border border-gray-200 rounded-lg focus:ring-1 focus:ring-indigo-500" @keydown.enter="applyUrl" />
                                <button @click="applyUrl" class="px-3 py-2 text-xs font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">Use</button>
                            </div>
                        </div>

                        <!-- Gallery grid -->
                        <div class="flex-1 overflow-y-auto p-5">
                            <div v-if="assets.length === 0 && !uploading" class="text-center py-10 text-gray-400">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" class="size-10 mx-auto mb-3 text-gray-300"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0 0 22.5 18.75V5.25A2.25 2.25 0 0 0 20.25 3H3.75A2.25 2.25 0 0 0 1.5 5.25v13.5A2.25 2.25 0 0 0 3.75 21Z" /></svg>
                                <p class="text-sm font-medium">No images yet</p>
                                <p class="text-xs mt-1">Upload your first image above</p>
                            </div>
                            <div v-else class="grid grid-cols-3 gap-2">
                                <button
                                    v-for="asset in assets"
                                    :key="asset.id"
                                    @click="selectAsset(asset.url)"
                                    class="group relative aspect-square rounded-xl overflow-hidden border-2 transition-all hover:shadow-lg"
                                    :class="modelValue === asset.url ? 'border-indigo-500 ring-2 ring-indigo-200' : 'border-gray-100 hover:border-indigo-200'"
                                >
                                    <img :src="asset.url" alt="" class="w-full h-full object-cover" />
                                    <div v-if="modelValue === asset.url" class="absolute inset-0 bg-indigo-500/20 flex items-center justify-center">
                                        <div class="size-6 rounded-full bg-indigo-500 flex items-center justify-center">
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
