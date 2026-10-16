<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Icon from '@/Components/Landing/Icon.vue';
import StatCard from '@/Components/Dashboard/StatCard.vue';

const props = defineProps({
    project: { type: Object, default: null }, // null = every website ("Orders" in the sidebar)
    shops: { type: Array, default: () => [] },
    orders: Array,
    shopOn: Boolean,
    revenue: String,
});

const allSites = computed(() => !props.project);
const isLive = computed(() => (props.project ? props.project.status === 'published' : props.shops.some((s) => s.status === 'published')));
const site = ref('all'); // website filter on the all-orders page

const STATUS = {
    new: { label: 'New', cls: 'bg-violet-500/15 text-violet-600 dark:text-violet-300' },
    confirmed: { label: 'Confirmed', cls: 'bg-sky-500/15 text-sky-600 dark:text-sky-300' },
    completed: { label: 'Completed', cls: 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-300' },
    cancelled: { label: 'Cancelled', cls: 'bg-lp-ink/10 text-lp-muted' },
};
// What the owner usually does next with an order in each state.
const ACTIONS = {
    new: [{ to: 'confirmed', label: 'Confirm order', primary: true }, { to: 'cancelled', label: 'Cancel' }],
    confirmed: [{ to: 'completed', label: 'Mark completed', primary: true }, { to: 'cancelled', label: 'Cancel' }],
    completed: [{ to: 'confirmed', label: 'Reopen' }],
    cancelled: [{ to: 'new', label: 'Restore' }],
};

const filter = ref('all');
const query = ref('');
const openId = ref(props.orders.find((o) => o.status === 'new')?.id ?? null);
const saving = ref(null);

const counts = computed(() => {
    const c = { all: props.orders.length, new: 0, confirmed: 0, completed: 0, cancelled: 0 };
    props.orders.forEach((o) => c[o.status]++);
    return c;
});

const visible = computed(() => {
    const q = query.value.trim().toLowerCase();
    return props.orders.filter((o) => {
        if (filter.value !== 'all' && o.status !== filter.value) return false;
        if (site.value !== 'all' && o.projectId !== site.value) return false;
        if (!q) return true;
        return [String(o.number), o.name, o.email, o.phone, ...o.items.map((i) => i.title)].filter(Boolean).some((v) => v.toLowerCase().includes(q));
    });
});

function setStatus(order, status) {
    saving.value = order.id;
    router.patch(route('orders.update', [order.projectId, order.id]), { status }, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => { order.status = status; },
        onFinish: () => { saving.value = null; },
    });
}

function ago(iso) {
    const s = Math.round((Date.now() - new Date(iso)) / 1000);
    if (s < 60) return 'just now';
    if (s < 3600) return `${Math.floor(s / 60)} min ago`;
    if (s < 86400) return `${Math.floor(s / 3600)} h ago`;
    if (s < 604800) return `${Math.floor(s / 86400)} d ago`;
    return new Date(iso).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}
const fullDate = (iso) => new Date(iso).toLocaleString('en-US', { dateStyle: 'medium', timeStyle: 'short' });
const itemCount = (o) => o.items.reduce((n, i) => n + i.quantity, 0);
const digits = (phone) => (phone || '').replace(/\D+/g, '');

function waLink(o) {
    const first = o.name.split(' ')[0];
    const text = `Hi ${first}, thanks for your order #${o.number} at ${o.projectName}! `;
    return `https://wa.me/${digits(o.phone)}?text=${encodeURIComponent(text)}`;
}
</script>

<template>
    <Head :title="project ? `Orders · ${project.name}` : 'Orders'" />

    <AuthenticatedLayout>
        <div class="mx-auto max-w-5xl px-4 pb-16 pt-8 sm:px-6 lg:px-10 lg:pt-10">
            <!-- Header -->
            <div class="intro">
                <Link :href="route('dashboard')" class="inline-flex items-center gap-1.5 text-sm font-medium text-lp-muted transition hover:text-lp-fg">
                    <Icon name="arrowLeft" :stroke="2" class="size-4" />Websites
                </Link>
                <div class="mt-3 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div class="min-w-0">
                        <p class="text-sm text-lp-subtle">{{ project ? 'Orders' : 'All your websites' }}</p>
                        <h1 class="mt-1 truncate text-[1.9rem] font-bold tracking-[-0.03em] text-lp-fg sm:text-[2.2rem]">{{ project ? project.name : 'Orders' }}</h1>
                    </div>
                    <div v-if="project" class="flex shrink-0 gap-2">
                        <a v-if="isLive" :href="`/s/${project.slug}`" target="_blank" rel="noopener" class="btn btn-ghost h-10 px-4 text-sm"><Icon name="externalLink" :stroke="1.8" class="size-4" />View shop</a>
                        <a :href="route('editor.show', project.id)" class="btn btn-primary h-10 px-4 text-sm"><Icon name="pencil" :stroke="1.8" class="size-4" />Open editor</a>
                    </div>
                </div>
            </div>

            <!-- Website switcher (all-orders page) -->
            <div v-if="allSites && shops.length > 1" class="intro mt-6 flex flex-wrap gap-2" style="--d: 30ms">
                <button type="button" :class="['site-chip', site === 'all' && 'site-chip--on']" @click="site = 'all'">All websites</button>
                <button v-for="s in shops" :key="s.id" type="button" :class="['site-chip', site === s.id && 'site-chip--on']" @click="site = s.id">{{ s.name }}</button>
            </div>

            <!-- Setup hints -->
            <div v-if="!shopOn" class="intro mt-6 flex items-start gap-3 rounded-2xl border border-violet-500/25 bg-violet-500/10 px-4 py-3 text-sm text-lp-text" style="--d: 40ms">
                <Icon name="sparkles" :stroke="1.8" class="mt-px size-5 shrink-0 text-violet-500" />
                <span>Your shop is off. In the editor, select your products section → <strong>Details pages</strong> → turn on <strong>Add to cart</strong>.</span>
            </div>
            <div v-else-if="!isLive" class="intro mt-6 flex items-start gap-3 rounded-2xl border border-amber-500/25 bg-amber-500/10 px-4 py-3 text-sm text-lp-text" style="--d: 40ms">
                <Icon name="exclamation" :stroke="1.8" class="mt-px size-5 shrink-0 text-amber-500" />
                <span>This website is a draft. Publish it from the editor so customers can send orders.</span>
            </div>

            <!-- Stats -->
            <div class="intro mt-8 grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4" style="--d: 60ms">
                <StatCard label="New orders" :value="counts.new" icon="bolt" :hint="counts.new ? 'Waiting for you' : 'All caught up'" />
                <StatCard label="All orders" :value="counts.all" icon="tag" />
                <StatCard label="Completed" :value="counts.completed" icon="checkCircle" />
                <StatCard label="Revenue" :value="revenue" icon="chart" hint="Confirmed + completed" />
            </div>

            <!-- Filters -->
            <div class="intro mt-8 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between" style="--d: 100ms">
                <div class="seg" role="tablist">
                    <button v-for="f in ['all', 'new', 'confirmed', 'completed', 'cancelled']" :key="f" type="button" role="tab" :aria-selected="filter === f" :class="['seg-btn', filter === f && 'seg-btn--on']" @click="filter = f">
                        {{ f === 'all' ? 'All' : STATUS[f].label }}<span class="seg-count">{{ counts[f] }}</span>
                    </button>
                </div>
                <label class="search">
                    <Icon name="search" :stroke="1.8" class="size-4 text-lp-subtle" />
                    <input v-model="query" type="search" placeholder="Search name, #number, product…" />
                </label>
            </div>

            <!-- Orders -->
            <div class="intro mt-4 space-y-3" style="--d: 140ms">
                <article v-for="o in visible" :key="o.id" :class="['card order', openId === o.id && 'order--open', o.status === 'cancelled' && 'opacity-70']">
                    <button type="button" class="flex w-full items-center gap-3 p-4 text-left sm:gap-4 sm:p-5" :aria-expanded="openId === o.id" @click="openId = openId === o.id ? null : o.id">
                        <span class="icon-tile size-11 shrink-0 rounded-xl text-sm font-bold">{{ o.name.charAt(0).toUpperCase() }}</span>
                        <span class="min-w-0 flex-1">
                            <span class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                <span class="font-semibold text-lp-fg">#{{ o.number }}</span>
                                <span v-if="allSites" class="rounded-md bg-lp-ink/[0.06] px-1.5 py-px text-[11px] font-medium text-lp-muted">{{ o.projectName }}</span>
                                <span class="truncate text-lp-text">{{ o.name }}</span>
                                <span :class="['pill', STATUS[o.status].cls]">{{ STATUS[o.status].label }}</span>
                            </span>
                            <span class="mt-0.5 block truncate text-xs text-lp-subtle" :title="fullDate(o.createdAt)">
                                {{ ago(o.createdAt) }} · {{ itemCount(o) }} item{{ itemCount(o) === 1 ? '' : 's' }} · {{ o.items.map((i) => i.title).join(', ') }}
                            </span>
                        </span>
                        <span class="shrink-0 text-right">
                            <span class="block font-bold text-lp-fg">{{ o.total || 'On request' }}</span>
                            <span v-if="o.total && o.hasUnpriced" class="block text-[11px] text-lp-subtle">+ on request</span>
                        </span>
                        <Icon name="chevronDown" :stroke="2" :class="['size-4 shrink-0 text-lp-subtle transition-transform', openId === o.id && 'rotate-180']" />
                    </button>

                    <div v-if="openId === o.id" class="grid gap-5 border-t border-lp-ink/[0.07] p-4 sm:grid-cols-[1.4fr_1fr] sm:p-5">
                        <!-- Items -->
                        <div>
                            <h3 class="label">Items</h3>
                            <ul class="mt-2 divide-y divide-lp-ink/[0.07] rounded-xl border border-lp-ink/[0.07]">
                                <li v-for="item in o.items" :key="item.id" class="flex items-center justify-between gap-3 px-3.5 py-2.5 text-sm">
                                    <span class="min-w-0"><span class="font-semibold text-lp-fg">{{ item.quantity }} ×</span> <span class="text-lp-text">{{ item.title }}</span>
                                        <span class="block text-xs text-lp-subtle">{{ item.price || 'Price on request' }} each</span>
                                    </span>
                                    <span class="shrink-0 font-semibold text-lp-fg">{{ item.lineTotalText || '—' }}</span>
                                </li>
                                <li class="flex items-center justify-between px-3.5 py-2.5 text-sm">
                                    <span class="font-semibold text-lp-fg">Total</span>
                                    <span class="font-bold text-lp-fg">{{ o.total || 'On request' }}<span v-if="o.total && o.hasUnpriced" class="font-normal text-lp-subtle"> + on request</span></span>
                                </li>
                            </ul>
                            <div v-if="o.note" class="mt-3 rounded-xl bg-lp-ink/[0.04] px-3.5 py-2.5 text-sm text-lp-text">
                                <span class="label">Note</span>
                                <p class="mt-1 whitespace-pre-line">{{ o.note }}</p>
                            </div>
                        </div>

                        <!-- Customer + actions -->
                        <div>
                            <h3 class="label">Customer</h3>
                            <div class="mt-2 space-y-1.5 text-sm text-lp-text">
                                <p class="font-semibold text-lp-fg">{{ o.name }}</p>
                                <p v-if="o.phone">{{ o.phone }}</p>
                                <p v-if="o.email" class="break-all">{{ o.email }}</p>
                                <p v-if="o.address" class="whitespace-pre-line text-lp-muted">{{ o.address }}</p>
                                <p class="text-xs text-lp-subtle">{{ fullDate(o.createdAt) }}</p>
                            </div>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <a v-if="o.phone" :href="`tel:${o.phone}`" class="btn btn-ghost h-9 px-3 text-xs">Call</a>
                                <a v-if="o.phone && digits(o.phone).length >= 8" :href="waLink(o)" target="_blank" rel="noopener" class="btn btn-ghost h-9 px-3 text-xs">WhatsApp</a>
                                <a v-if="o.email" :href="`mailto:${o.email}?subject=${encodeURIComponent(`Your order #${o.number} — ${o.projectName}`)}`" class="btn btn-ghost h-9 px-3 text-xs">Email</a>
                            </div>
                            <div class="mt-4 flex flex-wrap gap-2 border-t border-lp-ink/[0.07] pt-4">
                                <button v-for="a in ACTIONS[o.status]" :key="a.to" type="button" :disabled="saving === o.id" :class="['btn h-9 px-3.5 text-xs', a.primary ? 'btn-primary' : 'btn-ghost']" @click="setStatus(o, a.to)">{{ a.label }}</button>
                            </div>
                        </div>
                    </div>
                </article>

                <!-- Empty states -->
                <div v-if="!orders.length" class="card flex flex-col items-center px-6 py-16 text-center">
                    <span class="icon-tile size-12 rounded-2xl"><Icon name="tag" :stroke="1.6" class="size-5" /></span>
                    <p class="mt-4 font-semibold text-lp-fg">No orders yet</p>
                    <p class="mt-1 max-w-sm text-sm text-lp-muted">{{ shopOn && isLive ? 'When a customer checks out, their order shows up here and you get an email.' : 'Turn on Add to cart and publish your site to start taking orders.' }}</p>
                </div>
                <p v-else-if="!visible.length" class="py-10 text-center text-sm text-lp-muted">No orders match.</p>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.site-chip { border-radius: 9999px; border: 1px solid rgb(var(--lp-ink) / 0.1); padding: 0.3rem 0.85rem; font-size: 13px; font-weight: 500; color: rgb(var(--lp-muted)); transition: all 0.15s; }
.site-chip:hover { color: rgb(var(--lp-fg)); }
.site-chip--on { border-color: rgb(139 92 246 / 0.5); background: rgb(139 92 246 / 0.12); color: rgb(var(--lp-accent)); font-weight: 600; }
.order { overflow: hidden; transition: box-shadow 0.2s, border-color 0.2s; }
.order--open { border-color: rgb(139 92 246 / 0.35); }
.pill { border-radius: 9999px; padding: 0.05rem 0.5rem; font-size: 11px; font-weight: 600; }
.label { font-size: 11px; font-weight: 600; letter-spacing: 0.06em; text-transform: uppercase; color: rgb(var(--lp-subtle)); }
.seg { display: inline-flex; flex-wrap: wrap; gap: 2px; border-radius: 0.8rem; background: rgb(var(--lp-ink) / 0.05); padding: 3px; }
.seg-btn { display: inline-flex; align-items: center; gap: 0.4rem; border-radius: 0.6rem; padding: 0.4rem 0.75rem; font-size: 13px; font-weight: 500; color: rgb(var(--lp-muted)); transition: background-color 0.15s, color 0.15s; }
.seg-btn:hover { color: rgb(var(--lp-text)); }
.seg-btn--on { background: rgb(var(--lp-surface)); color: rgb(var(--lp-fg)); box-shadow: 0 1px 3px rgb(0 0 0 / 0.12); }
.seg-count { border-radius: 9999px; background: rgb(var(--lp-ink) / 0.07); padding: 0 0.4rem; font-size: 11px; }
.search { display: flex; align-items: center; gap: 0.5rem; height: 2.5rem; min-width: 0; border-radius: 0.75rem; border: 1px solid rgb(var(--lp-ink) / 0.1); background: rgb(var(--lp-surface)); padding: 0 0.8rem; }
.search input { min-width: 0; flex: 1; border: 0; background: transparent; padding: 0; font-size: 14px; color: rgb(var(--lp-text)); box-shadow: none; }
.search input:focus { outline: none; box-shadow: none; }
@media (min-width: 640px) { .search { width: 18rem; } }
</style>
