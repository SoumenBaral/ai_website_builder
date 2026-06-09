<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    project: Object,
    stats: Object,
});

const maxDailyViews = computed(() => {
    if (!props.stats.dailyViews?.length) return 1;
    return Math.max(...props.stats.dailyViews.map(d => d.views), 1);
});
</script>

<template>
    <Head :title="`Analytics - ${project.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <a :href="route('editor.show', project.id)" class="text-gray-500 hover:text-gray-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    </a>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800">
                        Analytics — {{ project.name }}
                    </h2>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">
                <!-- Stats cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                        <div class="text-sm text-gray-500 mb-1">Total Views (30d)</div>
                        <div class="text-3xl font-bold text-gray-800">{{ stats.totalViews }}</div>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                        <div class="text-sm text-gray-500 mb-1">Unique Visitors (30d)</div>
                        <div class="text-3xl font-bold text-gray-800">{{ stats.uniqueSessions }}</div>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                        <div class="text-sm text-gray-500 mb-1">Avg. Views / Day</div>
                        <div class="text-3xl font-bold text-gray-800">
                            {{ stats.dailyViews?.length ? Math.round(stats.totalViews / stats.dailyViews.length) : 0 }}
                        </div>
                    </div>
                </div>

                <!-- Daily views chart -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h3 class="font-semibold text-gray-800 mb-4">Daily Views</h3>
                    <div v-if="stats.dailyViews?.length" class="flex items-end gap-1 h-40">
                        <div
                            v-for="day in stats.dailyViews"
                            :key="day.date"
                            class="flex-1 bg-blue-500 rounded-t hover:bg-blue-600 transition-colors relative group"
                            :style="{ height: (day.views / maxDailyViews * 100) + '%', minHeight: '4px' }"
                        >
                            <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-1 px-2 py-1 bg-gray-800 text-white text-xs rounded opacity-0 group-hover:opacity-100 whitespace-nowrap pointer-events-none">
                                {{ day.date }}: {{ day.views }} views
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-center text-gray-400 py-8">
                        No data yet. Publish your site to start tracking views.
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Top referrers -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                        <h3 class="font-semibold text-gray-800 mb-4">Top Referrers</h3>
                        <div v-if="stats.topReferrers?.length" class="space-y-3">
                            <div v-for="ref in stats.topReferrers" :key="ref.referrer" class="flex items-center justify-between">
                                <span class="text-sm text-gray-600 truncate max-w-xs">{{ ref.referrer }}</span>
                                <span class="text-sm font-medium text-gray-800">{{ ref.count }}</span>
                            </div>
                        </div>
                        <div v-else class="text-center text-gray-400 py-4 text-sm">No referrer data yet</div>
                    </div>

                    <!-- Device breakdown -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                        <h3 class="font-semibold text-gray-800 mb-4">Devices</h3>
                        <div v-if="stats.deviceBreakdown?.length" class="space-y-3">
                            <div v-for="dev in stats.deviceBreakdown" :key="dev.device" class="flex items-center gap-3">
                                <span class="text-lg">{{ dev.device === 'mobile' ? '📱' : '💻' }}</span>
                                <div class="flex-1">
                                    <div class="flex justify-between text-sm mb-1">
                                        <span class="capitalize text-gray-600">{{ dev.device || 'Unknown' }}</span>
                                        <span class="font-medium text-gray-800">{{ dev.count }}</span>
                                    </div>
                                    <div class="w-full bg-gray-100 rounded-full h-2">
                                        <div
                                            class="bg-blue-500 h-2 rounded-full"
                                            :style="{ width: (dev.count / stats.totalViews * 100) + '%' }"
                                        ></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center text-gray-400 py-4 text-sm">No device data yet</div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
