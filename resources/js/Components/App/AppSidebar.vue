<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import BrandLogo from '@/Components/Landing/BrandLogo.vue';
import Icon from '@/Components/Landing/Icon.vue';
import ThemeToggle from '@/Components/Landing/ThemeToggle.vue';
import AppMenu from './AppMenu.vue';
import { useCreateProject } from './useCreateProject';

const emit = defineEmits(['navigate']);
const page = usePage();
const user = computed(() => page.props.auth.user);
const usage = computed(() => page.props.usage);
const { openCreate } = useCreateProject();

const nav = [
    { label: 'Websites', icon: 'home', route: 'dashboard', active: () => route().current('dashboard') || route().current('analytics.*') },
    { label: 'Orders', icon: 'tag', route: 'orders.all', active: () => route().current('orders.*'), badge: () => page.props.newOrders },
    { label: 'Settings', icon: 'cog', route: 'profile.edit', active: () => route().current('profile.*') },
];
const resources = [
    { label: 'Live demo', icon: 'sparkles', href: '/demo' },
    { label: 'Help & FAQ', icon: 'question', href: '/#faq' },
];

const initials = computed(() => (user.value?.name || '?').split(/\s+/).map((w) => w[0]).slice(0, 2).join('').toUpperCase());
const planLabel = computed(() => {
    const plan = usage.value?.plan || 'free';
    return plan.charAt(0).toUpperCase() + plan.slice(1);
});
const usedPct = computed(() => (usage.value ? Math.min(100, (usage.value.used / usage.value.limit) * 100) : 0));
const resetDate = computed(() => {
    const d = new Date();
    return new Date(d.getFullYear(), d.getMonth() + 1, 1).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
});

function create() {
    emit('navigate');
    openCreate();
}
</script>

<template>
    <div class="flex h-full flex-col">
        <div class="flex h-16 shrink-0 items-center justify-between px-5">
            <BrandLogo :href="route('dashboard')" />
        </div>

        <div class="px-4 pt-2">
            <button type="button" class="btn btn-primary h-10 w-full text-sm" @click="create">
                <Icon name="plus" :stroke="2.2" class="size-4" /> New website
            </button>
        </div>

        <nav class="mt-6 flex-1 space-y-6 overflow-y-auto px-3" aria-label="Main">
            <div class="space-y-0.5">
                <Link
                    v-for="item in nav"
                    :key="item.label"
                    :href="route(item.route)"
                    :class="['side-link', item.active() && 'side-link--active']"
                    :aria-current="item.active() ? 'page' : undefined"
                    @click="emit('navigate')"
                >
                    <Icon :name="item.icon" :stroke="1.7" class="size-[18px]" />{{ item.label }}
                    <span v-if="item.badge?.()" class="ml-auto rounded-full bg-violet-500 px-1.5 text-[11px] font-bold leading-[18px] text-white" :aria-label="`${item.badge()} new`">{{ item.badge() }}</span>
                </Link>
            </div>
            <div>
                <div class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-[0.14em] text-lp-subtle">Resources</div>
                <div class="space-y-0.5">
                    <a v-for="item in resources" :key="item.label" :href="item.href" target="_blank" rel="noopener" class="side-link group">
                        <Icon :name="item.icon" :stroke="1.7" class="size-[18px]" />{{ item.label }}
                        <Icon name="arrowUpRight" :stroke="2" class="ml-auto size-3.5 opacity-0 transition group-hover:opacity-60" />
                    </a>
                </div>
            </div>
        </nav>

        <!-- AI usage -->
        <div v-if="usage" class="mx-3 mb-3 rounded-2xl border border-lp-ink/[0.08] bg-lp-ink/[0.025] p-4">
            <div class="flex items-center justify-between">
                <span class="flex items-center gap-1.5 text-[13px] font-semibold text-lp-fg">
                    <Icon name="sparkles" :stroke="1.8" class="size-4 text-lp-accent" />AI generations
                </span>
                <span class="rounded-full bg-gradient-to-r from-indigo-500 to-fuchsia-500 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-white">{{ planLabel }}</span>
            </div>
            <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-lp-ink/10" role="progressbar" :aria-valuenow="usage.used" aria-valuemin="0" :aria-valuemax="usage.limit">
                <div :class="['h-full rounded-full transition-all duration-700', usedPct >= 90 ? 'bg-rose-500' : 'bg-gradient-to-r from-indigo-500 to-fuchsia-500']" :style="{ width: `${Math.max(usedPct, 2)}%` }"></div>
            </div>
            <p class="mt-2 text-xs text-lp-muted">
                <span class="font-semibold text-lp-fg">{{ usage.remaining }}</span> of {{ usage.limit }} left · resets {{ resetDate }}
            </p>
            <a v-if="usage.plan === 'free'" href="/#pricing" class="mt-3 inline-flex items-center gap-1 text-xs font-semibold text-lp-accent hover:opacity-80">
                Upgrade for more <Icon name="arrow" :stroke="2.2" class="size-3" />
            </a>
        </div>

        <!-- User -->
        <div class="border-t border-lp-ink/[0.07] p-3">
            <AppMenu placement="top" align="left" width="w-[calc(100%)] min-w-[14rem]" class="w-full">
                <template #trigger="{ toggle, open }">
                    <button type="button" class="user-btn" :aria-expanded="open" aria-haspopup="menu" @click="toggle">
                        <span class="avatar">{{ initials }}</span>
                        <span class="min-w-0 flex-1 text-left">
                            <span class="block truncate text-sm font-semibold text-lp-fg">{{ user.name }}</span>
                            <span class="block truncate text-xs text-lp-subtle">{{ user.email }}</span>
                        </span>
                        <Icon name="chevronUpDown" :stroke="1.8" class="size-4 shrink-0 text-lp-subtle" />
                    </button>
                </template>
                <template #default="{ close }">
                    <Link :href="route('profile.edit')" class="menu-item" role="menuitem" @click="close(); emit('navigate')">
                        <Icon name="user" :stroke="1.7" class="size-4" />Profile & settings
                    </Link>
                    <div class="menu-item justify-between" role="none">
                        <span class="flex items-center gap-2.5"><Icon name="moon" :stroke="1.7" class="size-4" />Appearance</span>
                        <ThemeToggle />
                    </div>
                    <div class="menu-sep"></div>
                    <Link :href="route('logout')" method="post" as="button" class="menu-item menu-item--danger" role="menuitem">
                        <Icon name="logout" :stroke="1.7" class="size-4" />Log out
                    </Link>
                </template>
            </AppMenu>
        </div>
    </div>
</template>

<style scoped>
.side-link {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    border-radius: 0.65rem;
    padding: 0.55rem 0.75rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: rgb(var(--lp-muted));
    transition: background-color 0.15s, color 0.15s;
}
.side-link:hover { background: rgb(var(--lp-ink) / 0.05); color: rgb(var(--lp-fg)); }
.side-link--active {
    background: rgb(var(--lp-ink) / 0.07);
    color: rgb(var(--lp-fg));
    box-shadow: inset 0 0 0 1px rgb(var(--lp-ink) / 0.06);
}
.side-link--active :deep(svg) { color: rgb(var(--lp-accent)); }

.user-btn {
    display: flex;
    width: 100%;
    align-items: center;
    gap: 0.75rem;
    border-radius: 0.75rem;
    padding: 0.5rem;
    transition: background-color 0.15s;
}
.user-btn:hover { background: rgb(var(--lp-ink) / 0.05); }
.avatar {
    display: flex;
    width: 2.25rem;
    height: 2.25rem;
    flex-shrink: 0;
    align-items: center;
    justify-content: center;
    border-radius: 9999px;
    background: linear-gradient(135deg, #6366f1, #a855f7 60%, #ec4899);
    font-size: 0.75rem;
    font-weight: 700;
    color: #fff;
    box-shadow: 0 0 0 2px rgb(var(--lp-bg)), 0 0 0 3px rgb(var(--lp-ink) / 0.1);
}
</style>
