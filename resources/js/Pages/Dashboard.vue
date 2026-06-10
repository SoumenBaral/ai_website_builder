<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    projects: Array,
});

const showModal = ref(false);
const searchQuery = ref('');
const form = useForm({
    name: '',
    description: '',
});

const filteredProjects = computed(() => {
    if (!searchQuery.value.trim()) return props.projects || [];
    const q = searchQuery.value.toLowerCase();
    return (props.projects || []).filter(p =>
        p.name.toLowerCase().includes(q) || (p.description || '').toLowerCase().includes(q)
    );
});

const publishedCount = computed(() => (props.projects || []).filter(p => p.status === 'published').length);
const draftCount = computed(() => (props.projects || []).filter(p => p.status !== 'published').length);

function createProject() {
    form.post(route('projects.store'), {
        onSuccess: () => {
            showModal.value = false;
            form.reset();
        },
    });
}

function deleteProject(project) {
    if (confirm(`Delete "${project.name}"? This cannot be undone.`)) {
        router.delete(route('projects.destroy', project.id));
    }
}

function timeAgo(date) {
    if (!date) return '';
    const d = new Date(date);
    const now = new Date();
    const diff = Math.floor((now - d) / 1000);
    if (diff < 60) return 'just now';
    if (diff < 3600) return `${Math.floor(diff / 60)}m ago`;
    if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`;
    if (diff < 604800) return `${Math.floor(diff / 86400)}d ago`;
    return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
}

const gradients = [
    'from-indigo-500 to-violet-500',
    'from-violet-500 to-purple-500',
    'from-blue-500 to-indigo-500',
    'from-emerald-500 to-teal-500',
    'from-rose-500 to-pink-500',
    'from-amber-500 to-orange-500',
    'from-cyan-500 to-blue-500',
    'from-fuchsia-500 to-pink-500',
];

function getGradient(index) {
    return gradients[index % gradients.length];
}
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">My Projects</h2>
                    <p class="text-sm text-gray-500 mt-0.5">Manage and edit your AI-generated websites</p>
                </div>
                <button
                    @click="showModal = true"
                    class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold text-white bg-gradient-to-r from-indigo-600 to-violet-600 rounded-xl shadow-lg shadow-indigo-500/20 hover:shadow-xl hover:shadow-indigo-500/30 hover:-translate-y-0.5 transition-all duration-200"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-4"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    New Project
                </button>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">

                <!-- Stats bar -->
                <div v-if="projects?.length" class="grid grid-cols-3 gap-4 mb-8">
                    <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
                        <div class="text-sm font-medium text-gray-500">Total Projects</div>
                        <div class="text-3xl font-bold text-gray-900 mt-1">{{ projects.length }}</div>
                    </div>
                    <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
                        <div class="text-sm font-medium text-gray-500">Published</div>
                        <div class="text-3xl font-bold text-emerald-600 mt-1">{{ publishedCount }}</div>
                    </div>
                    <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
                        <div class="text-sm font-medium text-gray-500">Drafts</div>
                        <div class="text-3xl font-bold text-gray-400 mt-1">{{ draftCount }}</div>
                    </div>
                </div>

                <!-- Search bar -->
                <div v-if="projects?.length > 2" class="mb-6">
                    <div class="relative max-w-md">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="absolute left-3.5 top-1/2 -translate-y-1/2 size-4 text-gray-400"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Search projects..."
                            class="w-full pl-10 pr-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-400 transition-shadow bg-white"
                        />
                    </div>
                </div>

                <!-- Empty state -->
                <div v-if="!projects?.length" class="text-center py-24 bg-white rounded-3xl border border-gray-100 shadow-sm">
                    <div class="relative mx-auto w-24 h-24 mb-6">
                        <div class="absolute inset-0 rounded-3xl bg-gradient-to-br from-indigo-100 to-violet-100 animate-pulse"></div>
                        <div class="relative flex items-center justify-center h-full">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-10 text-indigo-500"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456Z" /></svg>
                        </div>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Create your first website</h3>
                    <p class="text-gray-500 mb-8 max-w-sm mx-auto">Describe what you want and AI will build a complete, professional website in seconds.</p>
                    <button
                        @click="showModal = true"
                        class="inline-flex items-center gap-2 px-7 py-3 text-white bg-gradient-to-r from-indigo-600 to-violet-600 rounded-xl font-semibold shadow-lg shadow-indigo-500/20 hover:shadow-xl hover:shadow-indigo-500/30 hover:-translate-y-0.5 transition-all duration-200"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                        Create Your First Project
                    </button>
                </div>

                <!-- No search results -->
                <div v-else-if="filteredProjects.length === 0" class="text-center py-16 text-gray-400">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" class="size-12 mx-auto mb-3 text-gray-300"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
                    <p class="font-medium">No projects match "{{ searchQuery }}"</p>
                </div>

                <!-- Project grid -->
                <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div
                        v-for="(project, index) in filteredProjects"
                        :key="project.id"
                        class="group bg-white rounded-2xl border border-gray-100 overflow-hidden shadow-sm hover:shadow-xl hover:shadow-indigo-500/[0.04] hover:-translate-y-1 transition-all duration-300"
                    >
                        <!-- Card header with gradient -->
                        <div class="relative h-36 overflow-hidden" :class="`bg-gradient-to-br ${getGradient(index)}`">
                            <!-- Pattern overlay -->
                            <div class="absolute inset-0 opacity-10" style="background-image: url('data:image/svg+xml,%3Csvg width=&quot;60&quot; height=&quot;60&quot; viewBox=&quot;0 0 60 60&quot; xmlns=&quot;http://www.w3.org/2000/svg&quot;%3E%3Cg fill=&quot;none&quot; fill-rule=&quot;evenodd&quot;%3E%3Cg fill=&quot;%23ffffff&quot;%3E%3Cpath d=&quot;M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z&quot;/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>

                            <!-- Project initial -->
                            <div class="absolute inset-0 flex items-center justify-center">
                                <span class="text-5xl font-bold text-white/30">{{ project.name.charAt(0).toUpperCase() }}</span>
                            </div>

                            <!-- Status badge -->
                            <div class="absolute top-3 right-3">
                                <span
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 text-[11px] font-semibold rounded-full backdrop-blur-sm"
                                    :class="project.status === 'published'
                                        ? 'bg-white/20 text-white'
                                        : 'bg-black/10 text-white/80'"
                                >
                                    <span class="size-1.5 rounded-full" :class="project.status === 'published' ? 'bg-green-300' : 'bg-white/50'"></span>
                                    {{ project.status === 'published' ? 'Live' : 'Draft' }}
                                </span>
                            </div>

                            <!-- Quick open on hover -->
                            <a
                                :href="route('editor.show', project.id)"
                                class="absolute inset-0 flex items-center justify-center bg-black/0 group-hover:bg-black/20 transition-all duration-300"
                            >
                                <div class="flex items-center gap-2 px-5 py-2.5 bg-white rounded-xl text-sm font-semibold text-gray-900 shadow-xl opacity-0 translate-y-2 group-hover:opacity-100 group-hover:translate-y-0 transition-all duration-300">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-4"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z" /></svg>
                                    Open Editor
                                </div>
                            </a>
                        </div>

                        <!-- Card body -->
                        <div class="p-5">
                            <h3 class="font-bold text-gray-900 truncate text-base">{{ project.name }}</h3>
                            <p v-if="project.description" class="text-sm text-gray-400 mt-1 line-clamp-2 leading-relaxed">{{ project.description }}</p>
                            <p v-else class="text-sm text-gray-300 mt-1 italic">No description</p>

                            <!-- Footer -->
                            <div class="flex items-center justify-between mt-4 pt-4 border-t border-gray-50">
                                <div class="flex items-center gap-4">
                                    <a
                                        v-if="project.status === 'published'"
                                        :href="`/s/${project.slug}`"
                                        target="_blank"
                                        class="inline-flex items-center gap-1 text-xs font-medium text-indigo-600 hover:text-indigo-700 transition-colors"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-3.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" /></svg>
                                        View Live
                                    </a>
                                    <span class="text-[11px] text-gray-300">{{ timeAgo(project.updated_at) }}</span>
                                </div>
                                <button
                                    @click="deleteProject(project)"
                                    class="p-1.5 rounded-lg text-gray-300 hover:text-red-500 hover:bg-red-50 transition-all opacity-0 group-hover:opacity-100"
                                    title="Delete project"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- New project card -->
                    <button
                        @click="showModal = true"
                        class="group flex flex-col items-center justify-center bg-white rounded-2xl border-2 border-dashed border-gray-200 hover:border-indigo-300 hover:bg-indigo-50/30 transition-all duration-300 min-h-[280px]"
                    >
                        <div class="size-14 rounded-2xl bg-gray-100 group-hover:bg-indigo-100 flex items-center justify-center transition-colors mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 text-gray-400 group-hover:text-indigo-500 transition-colors"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                        </div>
                        <span class="text-sm font-semibold text-gray-400 group-hover:text-indigo-600 transition-colors">New Project</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Create Project Modal -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center">
                    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="showModal = false"></div>
                    <div class="relative bg-white rounded-2xl shadow-2xl p-8 w-full max-w-md mx-4">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="size-10 rounded-xl bg-gradient-to-br from-indigo-500 to-violet-500 flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="white" class="size-5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-gray-900">New Project</h3>
                                <p class="text-xs text-gray-500">Create a new AI-powered website</p>
                            </div>
                        </div>
                        <form @submit.prevent="createProject">
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Project Name</label>
                                <input
                                    v-model="form.name"
                                    type="text"
                                    class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-400 transition-shadow text-sm"
                                    placeholder="My Awesome Website"
                                    required
                                    autofocus
                                />
                                <p v-if="form.errors.name" class="text-red-500 text-xs mt-1.5">{{ form.errors.name }}</p>
                            </div>
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Description <span class="text-gray-400 font-normal">(optional)</span></label>
                                <textarea
                                    v-model="form.description"
                                    rows="2"
                                    class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-400 transition-shadow text-sm resize-none"
                                    placeholder="A brief description of your website..."
                                ></textarea>
                            </div>
                            <div class="flex gap-3">
                                <button
                                    type="button"
                                    @click="showModal = false"
                                    class="flex-1 px-4 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 rounded-xl hover:bg-gray-200 transition-colors"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="flex-1 px-4 py-2.5 text-sm font-semibold text-white bg-gradient-to-r from-indigo-600 to-violet-600 rounded-xl hover:shadow-lg hover:shadow-indigo-500/20 transition-all disabled:opacity-50"
                                >
                                    {{ form.processing ? 'Creating...' : 'Create Project' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </AuthenticatedLayout>
</template>
