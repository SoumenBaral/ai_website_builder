<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { Link } from '@inertiajs/vue3';
import BrandLogo from './BrandLogo.vue';
import ThemeToggle from './ThemeToggle.vue';
import Icon from './Icon.vue';
import { navItems } from './content';
import { useLandingLinks } from './useLandingLinks';

const emit = defineEmits(['navigate']);
const { user, canLogin, canRegister } = useLandingLinks();

const open = ref(false);
const scrolled = ref(false);

function go(id) {
    open.value = false;
    emit('navigate', id);
}

// rAF-throttled; Vue only re-renders when the boolean actually flips.
let frame = 0;
const onScroll = () => {
    if (frame) return;
    frame = requestAnimationFrame(() => { frame = 0; scrolled.value = window.scrollY > 12; });
};
const onKey = (e) => { if (e.key === 'Escape') open.value = false; };
const desktop = window.matchMedia('(min-width: 768px)');
const onBreakpoint = (e) => { if (e.matches) open.value = false; };

onMounted(() => {
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
    window.addEventListener('keydown', onKey);
    desktop.addEventListener('change', onBreakpoint);
});
onBeforeUnmount(() => {
    cancelAnimationFrame(frame);
    window.removeEventListener('scroll', onScroll);
    window.removeEventListener('keydown', onKey);
    desktop.removeEventListener('change', onBreakpoint);
});
</script>

<template>
    <header :class="['nav fixed inset-x-0 top-0 z-50', { 'nav--solid': scrolled || open }]">
        <div class="mx-auto flex h-16 max-w-6xl items-center justify-between gap-4 px-4 sm:px-6">
            <BrandLogo />

            <nav class="hidden items-center gap-0.5 lg:flex" aria-label="Primary">
                <button v-for="item in navItems" :key="item.id" type="button" class="nav-link" @click="go(item.id)">{{ item.label }}</button>
                <a href="/demo" target="_blank" rel="noopener" class="nav-link">Demo</a>
            </nav>

            <div class="flex items-center gap-2">
                <ThemeToggle />
                <template v-if="canLogin">
                    <Link v-if="user" :href="route('dashboard')" class="btn btn-primary hidden h-9 px-4 text-sm md:inline-flex">Dashboard</Link>
                    <template v-else>
                        <Link :href="route('login')" class="nav-link hidden md:inline-flex">Log in</Link>
                        <Link v-if="canRegister" :href="route('register')" class="btn btn-primary hidden h-9 px-4 text-sm md:inline-flex">Get started</Link>
                    </template>
                </template>
                <button
                    type="button"
                    class="-mr-2 flex size-10 items-center justify-center rounded-lg text-lp-muted transition hover:bg-lp-ink/5 hover:text-lp-fg lg:hidden"
                    :aria-expanded="open"
                    aria-controls="mobile-menu"
                    aria-label="Toggle menu"
                    @click="open = !open"
                >
                    <Icon :name="open ? 'xMark' : 'menu'" class="size-6" />
                </button>
            </div>
        </div>

        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0 -translate-y-2"
            leave-active-class="transition duration-150 ease-in"
            leave-to-class="opacity-0 -translate-y-2"
        >
            <div v-if="open" id="mobile-menu" class="mobile-menu lg:hidden">
                <div class="mx-auto flex max-w-6xl flex-col px-4 pb-6 pt-2 sm:px-6">
                    <button v-for="item in navItems" :key="item.id" type="button" class="mobile-link" @click="go(item.id)">{{ item.label }}</button>
                    <a href="/demo" target="_blank" rel="noopener" class="mobile-link">Live demo</a>
                    <div v-if="canLogin" class="mt-5 grid gap-2.5 sm:grid-cols-2 md:hidden">
                        <Link v-if="user" :href="route('dashboard')" class="btn btn-primary h-11 text-sm">Dashboard</Link>
                        <template v-else>
                            <Link v-if="canRegister" :href="route('register')" class="btn btn-primary h-11 text-sm">Get started — it's free</Link>
                            <Link :href="route('login')" class="btn btn-ghost h-11 text-sm">Log in</Link>
                        </template>
                    </div>
                </div>
            </div>
        </Transition>
    </header>
</template>

<style scoped>
.nav { border-bottom: 1px solid transparent; transition: background-color 0.3s, border-color 0.3s; }
/* The only backdrop-filter on the page */
.nav--solid {
    background: var(--lp-nav);
    border-bottom-color: rgb(var(--lp-ink) / 0.08);
    -webkit-backdrop-filter: blur(14px) saturate(160%);
    backdrop-filter: blur(14px) saturate(160%);
}
.nav-link {
    border-radius: 0.5rem;
    padding: 0.4rem 0.75rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: rgb(var(--lp-muted));
    transition: color 0.2s, background-color 0.2s;
}
.nav-link:hover { color: rgb(var(--lp-fg)); background: rgb(var(--lp-ink) / 0.05); }
.mobile-menu {
    background: rgb(var(--lp-bg));
    border-bottom: 1px solid rgb(var(--lp-ink) / 0.08);
    box-shadow: 0 24px 48px -12px rgb(0 0 0 / 0.35);
}
.mobile-link {
    border-bottom: 1px solid rgb(var(--lp-ink) / 0.06);
    padding: 0.9rem 0.25rem;
    text-align: left;
    font-size: 1rem;
    font-weight: 500;
    color: rgb(var(--lp-text));
}
</style>
