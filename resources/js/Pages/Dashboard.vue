<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Icon from '@/Components/Landing/Icon.vue';
import AppMenu from '@/Components/App/AppMenu.vue';
import AppModal from '@/Components/App/AppModal.vue';
import { useCreateProject } from '@/Components/App/useCreateProject';
import QuickStart from '@/Components/Dashboard/QuickStart.vue';
import StatCard from '@/Components/Dashboard/StatCard.vue';
import ProjectCard from '@/Components/Dashboard/ProjectCard.vue';
import CreateProjectModal from '@/Components/Dashboard/CreateProjectModal.vue';
import { compactNumber } from '@/Components/Dashboard/format';

const props = defineProps({
    projects: { type: Array, default: () => [] },
});

const page = usePage();
const user = computed(() => page.props.auth.user);
const usage = computed(() => page.props.usage);
const { open: createOpen, openCreate } = useCreateProject();

/* ---------- Greeting & summary ---------- */
const firstName = computed(() => (user.value?.name || '').split(' ')[0]);
const greeting = computed(() => {
    const h = new Date().getHours();
    return h < 12 ? 'Good morning' : h < 18 ? 'Good afternoon' : 'Good evening';
});
const today = new Date().toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric' });

const live = computed(() => props.projects.filter((p) => p.status === 'published'));
const drafts = computed(() => props.projects.filter((p) => p.status !== 'published'));
const totalViews = computed(() => props.projects.reduce((sum, p) => sum + (p.views || 0), 0));
const totalPages = computed(() => props.projects.reduce((sum, p) => sum + (p.pages_count || 0), 0));
const usagePct = computed(() => (usage.value ? (usage.value.used / usage.value.limit) * 100 : 0));
const resetDate = (() => {
    const d = new Date();
    return new Date(d.getFullYear(), d.getMonth() + 1, 1).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
})();

/* ---------- Filter / search / sort ---------- */
const filter = ref('all');
const search = ref('');
const sort = ref('updated');
const searchInput = ref(null);

const filters = computed(() => [
    { key: 'all', label: 'All', count: props.projects.length },
    { key: 'live', label: 'Live', count: live.value.length },
    { key: 'draft', label: 'Drafts', count: drafts.value.length },
]);
const sorts = [
    { key: 'updated', label: 'Last edited' },
    { key: 'name', label: 'Name (A–Z)' },
    { key: 'views', label: 'Most viewed' },
];
const sortLabel = computed(() => sorts.find((s) => s.key === sort.value).label);

const visible = computed(() => {
    let list = filter.value === 'live' ? live.value : filter.value === 'draft' ? drafts.value : props.projects;
    const q = search.value.trim().toLowerCase();
    if (q) list = list.filter((p) => p.name.toLowerCase().includes(q) || (p.description || '').toLowerCase().includes(q));
    const sorted = [...list];
    if (sort.value === 'name') sorted.sort((a, b) => a.name.localeCompare(b.name));
    else if (sort.value === 'views') sorted.sort((a, b) => (b.views || 0) - (a.views || 0));
    else sorted.sort((a, b) => new Date(b.updated_at) - new Date(a.updated_at));
    return sorted;
});

function resetFilters() {
    filter.value = 'all';
    search.value = '';
}

/* ---------- Delete ---------- */
const toDelete = ref(null);
const deleteOpen = ref(false);
const deleting = ref(false);
function askDelete(project) {
    toDelete.value = project;
    deleteOpen.value = true;
}
function confirmDelete() {
    deleting.value = true;
    router.delete(route('projects.destroy', toDelete.value.id), {
        preserveScroll: true,
        onFinish: () => { deleting.value = false; deleteOpen.value = false; },
    });
}

/* ---------- Keyboard shortcuts: "/" search, "n" new website ---------- */
function onKey(e) {
    if (e.metaKey || e.ctrlKey || e.altKey) return;
    const el = document.activeElement;
    const typing = el && (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA' || el.isContentEditable);
    if (typing || createOpen.value || deleteOpen.value) return;
    if (e.key === '/' && searchInput.value) { e.preventDefault(); searchInput.value.focus(); }
    else if (e.key.toLowerCase() === 'n') { e.preventDefault(); openCreate(); }
}

onMounted(() => {
    window.addEventListener('keydown', onKey);
    // Arriving from the sidebar "New website" button on another page
    const url = new URL(window.location.href);
    if (url.searchParams.has('new')) {
        createOpen.value = true;
        url.searchParams.delete('new');
        window.history.replaceState(window.history.state, '', url);
    }
});
onBeforeUnmount(() => {
    window.removeEventListener('keydown', onKey);
    createOpen.value = false;
});
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <div class="mx-auto max-w-6xl px-4 pb-16 pt-8 sm:px-6 lg:px-10 lg:pt-10">
            <!-- Header -->
            <div class="intro flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-sm text-lp-subtle">{{ today }}</p>
                    <h1 class="mt-1 text-[1.9rem] font-bold leading-tight tracking-[-0.03em] text-lp-fg sm:text-[2.3rem]">
                        {{ greeting }}, <span class="serif text-gradient pr-1 text-[1.08em]">{{ firstName }}.</span>
                    </h1>
                    <p v-if="projects.length" class="mt-1.5 text-[15px] text-lp-muted">
                        You have {{ projects.length }} {{ projects.length === 1 ? 'website' : 'websites' }}<template v-if="live.length">, {{ live.length }} live</template>.
                    </p>
                </div>
                <button v-if="projects.length" type="button" class="btn btn-primary h-11 self-start px-5 text-sm sm:self-auto" @click="openCreate">
                    <Icon name="plus" :stroke="2.2" class="size-4" /> New website
                    <kbd class="kbd ml-1 hidden sm:inline-flex">N</kbd>
                </button>
            </div>

            <!-- Empty state: the prompt *is* the onboarding -->
            <div v-if="!projects.length" class="intro mt-8" style="--d: 80ms">
                <QuickStart hero />
                <div class="mt-6 grid gap-4 sm:grid-cols-3">
                    <div v-for="(s, i) in [
                        { icon: 'chat', title: 'Describe it', text: 'One sentence about your business or idea.' },
                        { icon: 'sparkles', title: 'AI builds it', text: 'Layout, copy, colors and images in seconds.' },
                        { icon: 'rocket', title: 'Publish it', text: 'Polish in the editor, then go live in one click.' },
                    ]" :key="s.title" class="card flex gap-3.5 p-5">
                        <span class="icon-tile size-10 rounded-xl"><Icon :name="s.icon" :stroke="1.6" class="size-[18px]" /></span>
                        <div>
                            <div class="text-sm font-semibold text-lp-fg"><span class="mr-1.5 text-lp-subtle">0{{ i + 1 }}</span>{{ s.title }}</div>
                            <p class="mt-0.5 text-[13px] leading-relaxed text-lp-muted">{{ s.text }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <template v-else>
                <!-- Stats -->
                <div class="intro mt-8 grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4" style="--d: 60ms">
                    <StatCard label="Websites" :value="projects.length" icon="globe" :hint="`${totalPages} ${totalPages === 1 ? 'page' : 'pages'} in total`" />
                    <StatCard label="Live" :value="live.length" icon="rocket" :hint="drafts.length ? `${drafts.length} in draft` : 'Everything is published'" />
                    <StatCard label="Views · 30 days" :value="compactNumber(totalViews)" icon="chart" :hint="live.length ? 'Across your live sites' : 'Publish to start tracking'" />
                    <StatCard v-if="usage" label="AI generations" :value="`${usage.used}/${usage.limit}`" icon="sparkles" :progress="usagePct" :hint="`Resets ${resetDate}`" />
                </div>

                <!-- Quick start -->
                <div class="intro mt-6" style="--d: 120ms">
                    <QuickStart />
                </div>

                <!-- Projects -->
                <section class="intro mt-12" style="--d: 180ms" aria-labelledby="projects-heading">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <h2 id="projects-heading" class="text-xl font-semibold tracking-tight text-lp-fg">Your websites</h2>

                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                            <!-- Segmented filter -->
                            <div class="segmented" role="tablist" aria-label="Filter websites">
                                <button
                                    v-for="f in filters"
                                    :key="f.key"
                                    type="button"
                                    role="tab"
                                    :aria-selected="filter === f.key"
                                    :class="['segment', filter === f.key && 'segment--active']"
                                    @click="filter = f.key"
                                >
                                    {{ f.label }} <span class="segment-count">{{ f.count }}</span>
                                </button>
                            </div>

                            <div class="flex gap-2">
                                <!-- Search -->
                                <div class="search relative min-w-0 flex-1 sm:w-56 sm:flex-none">
                                    <Icon name="search" :stroke="1.8" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-lp-subtle" />
                                    <input ref="searchInput" v-model="search" type="search" placeholder="Search websites" aria-label="Search websites" class="search-input" />
                                    <kbd v-if="!search" class="kbd pointer-events-none absolute right-2.5 top-1/2 hidden -translate-y-1/2 sm:inline-flex">/</kbd>
                                </div>

                                <!-- Sort -->
                                <AppMenu width="w-44">
                                    <template #trigger="{ toggle, open }">
                                        <button type="button" class="tool-btn" :aria-expanded="open" aria-haspopup="menu" @click="toggle">
                                            <Icon name="chevronUpDown" :stroke="1.8" class="size-4" /><span class="hidden sm:inline">{{ sortLabel }}</span>
                                        </button>
                                    </template>
                                    <template #default="{ close }">
                                        <button v-for="s in sorts" :key="s.key" type="button" class="menu-item justify-between" role="menuitemradio" :aria-checked="sort === s.key" @click="sort = s.key; close()">
                                            {{ s.label }}<Icon v-if="sort === s.key" name="check" :stroke="2.2" class="size-4 text-lp-accent" />
                                        </button>
                                    </template>
                                </AppMenu>
                            </div>
                        </div>
                    </div>

                    <!-- No matches -->
                    <div v-if="!visible.length" class="card mt-6 flex flex-col items-center px-6 py-14 text-center">
                        <span class="icon-tile size-12 rounded-2xl"><Icon name="search" :stroke="1.6" class="size-5" /></span>
                        <p class="mt-4 font-semibold text-lp-fg">No websites match{{ search ? ` “${search}”` : '' }}</p>
                        <p class="mt-1 text-sm text-lp-muted">Try a different search or filter.</p>
                        <button type="button" class="btn btn-ghost mt-5 h-10 px-4 text-sm" @click="resetFilters">Clear filters</button>
                    </div>

                    <!-- Grid -->
                    <TransitionGroup v-else tag="div" name="grid" class="mt-6 grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                        <ProjectCard v-for="project in visible" :key="project.id" :project="project" @delete="askDelete" />
                        <button v-if="filter === 'all' && !search" key="new" type="button" class="new-card group" @click="openCreate">
                            <span class="flex size-12 items-center justify-center rounded-2xl border border-dashed border-lp-ink/20 text-lp-subtle transition group-hover:border-violet-400/60 group-hover:text-lp-accent">
                                <Icon name="plus" :stroke="1.8" class="size-5" />
                            </span>
                            <span class="mt-3 text-sm font-semibold text-lp-fg">New website</span>
                            <span class="mt-0.5 text-xs text-lp-subtle">Start blank or from an idea</span>
                        </button>
                    </TransitionGroup>
                </section>
            </template>
        </div>

        <CreateProjectModal v-model="createOpen" />

        <AppModal v-model="deleteOpen" tone="danger" icon="trash" title="Delete this website?" :description="toDelete ? `“${toDelete.name}” and all of its pages, versions and analytics will be permanently removed. This can't be undone.` : ''">
            <div class="flex flex-col-reverse gap-2.5 sm:flex-row sm:justify-end">
                <button type="button" class="btn btn-ghost h-11 px-5 text-sm" @click="deleteOpen = false">Cancel</button>
                <button type="button" class="btn h-11 bg-rose-600 px-5 text-sm text-white hover:bg-rose-500 disabled:opacity-60" :disabled="deleting" @click="confirmDelete">
                    {{ deleting ? 'Deleting…' : 'Delete website' }}
                </button>
            </div>
        </AppModal>
    </AuthenticatedLayout>
</template>

<style scoped>
.kbd {
    align-items: center;
    justify-content: center;
    min-width: 1.25rem;
    height: 1.25rem;
    border-radius: 0.35rem;
    border: 1px solid rgb(var(--lp-ink) / 0.15);
    padding: 0 0.3rem;
    font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
    font-size: 11px;
    font-weight: 500;
    line-height: 1;
    opacity: 0.7;
}
.btn-primary .kbd { border-color: currentColor; opacity: 0.45; }

.segmented {
    display: inline-flex;
    gap: 0.25rem;
    border-radius: 0.75rem;
    border: 1px solid rgb(var(--lp-ink) / 0.08);
    background: rgb(var(--lp-ink) / 0.03);
    padding: 0.25rem;
}
.segment {
    display: inline-flex;
    flex: 1;
    align-items: center;
    justify-content: center;
    gap: 0.4rem;
    border-radius: 0.55rem;
    padding: 0.4rem 0.8rem;
    font-size: 0.8125rem;
    font-weight: 500;
    color: rgb(var(--lp-muted));
    transition: background-color 0.2s, color 0.2s, box-shadow 0.2s;
}
.segment:hover { color: rgb(var(--lp-fg)); }
.segment--active {
    background: rgb(var(--lp-surface));
    color: rgb(var(--lp-fg));
    box-shadow: 0 1px 3px rgb(0 0 0 / 0.12), 0 0 0 1px rgb(var(--lp-ink) / 0.06);
}
.segment-count {
    border-radius: 9999px;
    background: rgb(var(--lp-ink) / 0.07);
    padding: 0 0.4rem;
    font-size: 11px;
    font-variant-numeric: tabular-nums;
}

.search-input {
    width: 100%;
    height: 2.5rem;
    border-radius: 0.75rem;
    border: 1px solid rgb(var(--lp-ink) / 0.1) !important;
    background: rgb(var(--lp-surface)) !important;
    padding: 0 2.25rem 0 2.25rem;
    font-size: 0.875rem;
    color: rgb(var(--lp-fg));
    box-shadow: none !important;
    transition: border-color 0.2s, box-shadow 0.2s;
}
.search-input::placeholder { color: rgb(var(--lp-subtle)); }
.search-input:focus { outline: none; border-color: rgb(139 92 246 / 0.7) !important; box-shadow: 0 0 0 4px rgb(139 92 246 / 0.14) !important; }
.search-input::-webkit-search-cancel-button { display: none; }

.tool-btn {
    display: inline-flex;
    height: 2.5rem;
    align-items: center;
    gap: 0.4rem;
    border-radius: 0.75rem;
    border: 1px solid rgb(var(--lp-ink) / 0.1);
    background: rgb(var(--lp-surface));
    padding: 0 0.8rem;
    font-size: 0.8125rem;
    font-weight: 500;
    color: rgb(var(--lp-text));
    white-space: nowrap;
    transition: border-color 0.2s;
}
.tool-btn:hover { border-color: rgb(var(--lp-ink) / 0.2); }

.new-card {
    display: flex;
    min-height: 16rem;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    border-radius: 1.25rem;
    border: 1px dashed rgb(var(--lp-ink) / 0.15);
    transition: border-color 0.25s, background-color 0.25s;
}
.new-card:hover { border-color: rgb(139 92 246 / 0.5); background: rgb(139 92 246 / 0.04); }

/* Filter/search changes animate with transform + opacity only */
.grid-enter-active { transition: opacity 0.35s ease, transform 0.35s cubic-bezier(0.22, 1, 0.36, 1); }
.grid-leave-active { transition: opacity 0.15s ease; position: absolute; visibility: hidden; }
.grid-enter-from { opacity: 0; transform: translateY(8px) scale(0.98); }
.grid-leave-to { opacity: 0; }
.grid-move { transition: transform 0.4s cubic-bezier(0.22, 1, 0.36, 1); }
</style>
