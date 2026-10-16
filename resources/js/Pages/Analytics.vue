<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Icon from '@/Components/Landing/Icon.vue';
import StatCard from '@/Components/Dashboard/StatCard.vue';
import { compactNumber } from '@/Components/Dashboard/format';

const props = defineProps({
    project: Object,
    stats: Object,
});

const isLive = computed(() => props.project.status === 'published');

// The API only returns days that had views; fill the full 30-day window so the chart is honest.
const series = computed(() => {
    const byDate = Object.fromEntries((props.stats.dailyViews || []).map((d) => [String(d.date).slice(0, 10), Number(d.views)]));
    const days = [];
    for (let i = 29; i >= 0; i--) {
        const d = new Date();
        d.setHours(0, 0, 0, 0);
        d.setDate(d.getDate() - i);
        const key = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
        days.push({ key, label: d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }), views: byDate[key] || 0 });
    }
    return days;
});
const maxViews = computed(() => Math.max(1, ...series.value.map((d) => d.views)));
const activeDays = computed(() => series.value.filter((d) => d.views > 0).length);
const avgPerDay = computed(() => (props.stats.totalViews / 30).toFixed(1).replace(/\.0$/, ''));
const bestDay = computed(() => series.value.reduce((best, d) => (d.views > best.views ? d : best), { views: 0 }));

const maxReferrer = computed(() => Math.max(1, ...(props.stats.topReferrers || []).map((r) => r.count)));
function hostOf(url) {
    try { return new URL(url).hostname.replace(/^www\./, ''); } catch { return url; }
}
const deviceIcon = (d) => (d === 'mobile' ? 'M10.5 1.5H8.25A2.25 2.25 0 0 0 6 3.75v16.5a2.25 2.25 0 0 0 2.25 2.25h7.5A2.25 2.25 0 0 0 18 20.25V3.75a2.25 2.25 0 0 0-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3' : 'M9 17.25v1.007a3 3 0 0 1-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0 1 15 18.257V17.25m6-12V15a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 15V5.25m18 0A2.25 2.25 0 0 0 18.75 3H5.25A2.25 2.25 0 0 0 3 5.25m18 0V12a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 12V5.25');
const deviceTotal = computed(() => (props.stats.deviceBreakdown || []).reduce((s, d) => s + d.count, 0) || 1);
</script>

<template>
    <Head :title="`Analytics · ${project.name}`" />

    <AuthenticatedLayout>
        <div class="mx-auto max-w-6xl px-4 pb-16 pt-8 sm:px-6 lg:px-10 lg:pt-10">
            <!-- Header -->
            <div class="intro">
                <Link :href="route('dashboard')" class="inline-flex items-center gap-1.5 text-sm font-medium text-lp-muted transition hover:text-lp-fg">
                    <Icon name="arrowLeft" :stroke="2" class="size-4" />Websites
                </Link>
                <div class="mt-3 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div class="min-w-0">
                        <p class="text-sm text-lp-subtle">Analytics · last 30 days</p>
                        <h1 class="mt-1 truncate text-[1.9rem] font-bold tracking-[-0.03em] text-lp-fg sm:text-[2.2rem]">{{ project.name }}</h1>
                    </div>
                    <div class="flex shrink-0 gap-2">
                        <a v-if="isLive" :href="`/s/${project.slug}`" target="_blank" rel="noopener" class="btn btn-ghost h-10 px-4 text-sm"><Icon name="externalLink" :stroke="1.8" class="size-4" />View live</a>
                        <a :href="route('editor.show', project.id)" class="btn btn-primary h-10 px-4 text-sm"><Icon name="pencil" :stroke="1.8" class="size-4" />Open editor</a>
                    </div>
                </div>
            </div>

            <div v-if="!isLive" class="intro mt-6 flex items-start gap-3 rounded-2xl border border-amber-500/25 bg-amber-500/10 px-4 py-3 text-sm text-lp-text" style="--d: 40ms">
                <Icon name="exclamation" :stroke="1.8" class="mt-px size-5 shrink-0 text-amber-500" />
                <span>This website is a draft. Publish it from the editor to start collecting visitor analytics.</span>
            </div>

            <!-- Stats -->
            <div class="intro mt-8 grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4" style="--d: 60ms">
                <StatCard label="Page views" :value="compactNumber(stats.totalViews)" icon="eye" />
                <StatCard label="Unique visitors" :value="compactNumber(stats.uniqueSessions)" icon="user" />
                <StatCard label="Avg. per day" :value="avgPerDay" icon="chart" />
                <StatCard label="Best day" :value="bestDay.views ? compactNumber(bestDay.views) : '—'" icon="bolt" :hint="bestDay.views ? bestDay.label : 'No views yet'" />
            </div>

            <!-- Chart -->
            <section class="intro card mt-6 p-5 sm:p-7" style="--d: 120ms">
                <div class="flex items-center justify-between">
                    <h2 class="text-base font-semibold text-lp-fg">Daily views</h2>
                    <span class="text-xs text-lp-subtle">{{ activeDays }} of 30 days with visits</span>
                </div>

                <div v-if="stats.totalViews" class="mt-6">
                    <div class="chart flex h-48 items-end gap-[3px] sm:gap-1">
                        <div v-for="d in series" :key="d.key" class="bar-col group relative flex h-full flex-1 items-end">
                            <div class="bar w-full rounded-t-[4px]" :class="d.views ? 'bar--on' : 'bar--off'" :style="{ height: d.views ? `${Math.max(4, (d.views / maxViews) * 100)}%` : '3px' }"></div>
                            <div class="tip pointer-events-none absolute bottom-full left-1/2 mb-2 -translate-x-1/2 whitespace-nowrap rounded-lg px-2.5 py-1.5 text-xs opacity-0 transition-opacity group-hover:opacity-100">
                                <span class="font-semibold">{{ d.views }}</span> {{ d.views === 1 ? 'view' : 'views' }} · {{ d.label }}
                            </div>
                        </div>
                    </div>
                    <div class="mt-3 flex justify-between text-[11px] text-lp-subtle">
                        <span>{{ series[0].label }}</span><span class="hidden sm:inline">{{ series[15].label }}</span><span>Today</span>
                    </div>
                </div>
                <div v-else class="mt-6 flex flex-col items-center rounded-xl border border-dashed border-lp-ink/15 py-14 text-center">
                    <span class="icon-tile size-12 rounded-2xl"><Icon name="chart" :stroke="1.6" class="size-5" /></span>
                    <p class="mt-4 font-semibold text-lp-fg">No views yet</p>
                    <p class="mt-1 max-w-xs text-sm text-lp-muted">{{ isLive ? 'Share your link — visits will show up here within seconds.' : 'Publish your site to start tracking views.' }}</p>
                </div>
            </section>

            <div class="mt-6 grid gap-6 md:grid-cols-2">
                <!-- Referrers -->
                <section class="intro card p-5 sm:p-7" style="--d: 180ms">
                    <h2 class="text-base font-semibold text-lp-fg">Top referrers</h2>
                    <ul v-if="stats.topReferrers?.length" class="mt-5 space-y-2.5">
                        <li v-for="r in stats.topReferrers" :key="r.referrer" class="relative overflow-hidden rounded-lg px-3 py-2">
                            <div class="absolute inset-y-0 left-0 rounded-lg bg-violet-500/10" :style="{ width: `${(r.count / maxReferrer) * 100}%` }"></div>
                            <div class="relative flex items-center justify-between gap-3 text-sm">
                                <span class="truncate text-lp-text" :title="r.referrer">{{ hostOf(r.referrer) }}</span>
                                <span class="font-semibold tabular-nums text-lp-fg">{{ r.count }}</span>
                            </div>
                        </li>
                    </ul>
                    <p v-else class="mt-5 rounded-xl border border-dashed border-lp-ink/15 py-8 text-center text-sm text-lp-muted">No referrer data yet</p>
                </section>

                <!-- Devices -->
                <section class="intro card p-5 sm:p-7" style="--d: 220ms">
                    <h2 class="text-base font-semibold text-lp-fg">Devices</h2>
                    <ul v-if="stats.deviceBreakdown?.length" class="mt-5 space-y-4">
                        <li v-for="d in stats.deviceBreakdown" :key="d.device" class="flex items-center gap-3">
                            <span class="icon-tile size-9 rounded-xl">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" class="size-[18px]"><path stroke-linecap="round" stroke-linejoin="round" :d="deviceIcon(d.device)" /></svg>
                            </span>
                            <div class="min-w-0 flex-1">
                                <div class="mb-1.5 flex justify-between text-sm">
                                    <span class="capitalize text-lp-text">{{ d.device || 'Unknown' }}</span>
                                    <span class="text-lp-muted"><span class="font-semibold text-lp-fg">{{ Math.round((d.count / deviceTotal) * 100) }}%</span> · {{ d.count }}</span>
                                </div>
                                <div class="h-1.5 overflow-hidden rounded-full bg-lp-ink/10">
                                    <div class="h-full rounded-full bg-gradient-to-r from-indigo-500 to-fuchsia-500" :style="{ width: `${(d.count / deviceTotal) * 100}%` }"></div>
                                </div>
                            </div>
                        </li>
                    </ul>
                    <p v-else class="mt-5 rounded-xl border border-dashed border-lp-ink/15 py-8 text-center text-sm text-lp-muted">No device data yet</p>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.bar { transition: height 0.6s cubic-bezier(0.22, 1, 0.36, 1), opacity 0.2s; }
.bar--on { background: linear-gradient(to top, #6366f1, #a855f7); }
.bar--off { background: rgb(var(--lp-ink) / 0.08); }
.bar-col:hover .bar--on { opacity: 0.8; }
.tip {
    z-index: 10;
    border: 1px solid rgb(var(--lp-ink) / 0.1);
    background: rgb(var(--lp-surface));
    color: rgb(var(--lp-text));
    box-shadow: 0 10px 30px -10px rgb(0 0 0 / 0.4);
}
</style>
