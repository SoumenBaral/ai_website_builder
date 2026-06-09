<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    projects: Array,
});

const showModal = ref(false);
const form = useForm({
    name: '',
    description: '',
});

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
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    My Projects
                </h2>
                <button
                    @click="showModal = true"
                    class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors"
                >
                    + New Project
                </button>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Empty state -->
                <div v-if="!projects?.length" class="text-center py-20">
                    <div class="text-6xl mb-4">🚀</div>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">No projects yet</h3>
                    <p class="text-gray-500 mb-6">Create your first AI-powered website in seconds.</p>
                    <button
                        @click="showModal = true"
                        class="px-6 py-3 text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors font-medium"
                    >
                        Create Your First Project
                    </button>
                </div>

                <!-- Project grid -->
                <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div
                        v-for="project in projects"
                        :key="project.id"
                        class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition-shadow"
                    >
                        <div class="h-40 bg-gradient-to-br from-blue-50 to-indigo-100 flex items-center justify-center">
                            <span class="text-5xl">🌐</span>
                        </div>
                        <div class="p-5">
                            <div class="flex items-start justify-between mb-2">
                                <h3 class="font-semibold text-gray-800 truncate">{{ project.name }}</h3>
                                <span
                                    class="px-2 py-0.5 text-xs rounded-full"
                                    :class="project.status === 'published' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'"
                                >
                                    {{ project.status }}
                                </span>
                            </div>
                            <p v-if="project.description" class="text-sm text-gray-500 mb-4 line-clamp-2">
                                {{ project.description }}
                            </p>
                            <div class="flex items-center gap-2">
                                <a
                                    :href="route('editor.show', project.id)"
                                    class="flex-1 text-center px-3 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors"
                                >
                                    Open Editor
                                </a>
                                <button
                                    @click="deleteProject(project)"
                                    class="px-3 py-2 text-sm text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                >
                                    Delete
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create Project Modal -->
        <Teleport to="body">
            <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center">
                <div class="absolute inset-0 bg-black/50" @click="showModal = false"></div>
                <div class="relative bg-white rounded-xl shadow-xl p-6 w-full max-w-md mx-4">
                    <h3 class="text-lg font-semibold mb-4">Create New Project</h3>
                    <form @submit.prevent="createProject">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Project Name</label>
                            <input
                                v-model="form.name"
                                type="text"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="My Awesome Website"
                                required
                            />
                            <p v-if="form.errors.name" class="text-red-500 text-sm mt-1">{{ form.errors.name }}</p>
                        </div>
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Description (optional)</label>
                            <textarea
                                v-model="form.description"
                                rows="2"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="A brief description of your website..."
                            ></textarea>
                        </div>
                        <div class="flex gap-3">
                            <button
                                type="button"
                                @click="showModal = false"
                                class="flex-1 px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="flex-1 px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50"
                            >
                                {{ form.processing ? 'Creating...' : 'Create Project' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>
