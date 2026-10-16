<script setup>
import '../../css/landing.css';
import { ref, watch, onBeforeUnmount } from 'vue';
import { router } from '@inertiajs/vue3';
import BrandLogo from '@/Components/Landing/BrandLogo.vue';
import ThemeToggle from '@/Components/Landing/ThemeToggle.vue';
import Icon from '@/Components/Landing/Icon.vue';
import AppSidebar from '@/Components/App/AppSidebar.vue';
import { useColorMode, useDocumentTheme } from '@/Components/Landing/useColorMode';

/*
 * Signed-in app shell: fixed sidebar on desktop, slide-over drawer on mobile.
 * Shares the landing design system and the visitor's light/dark preference.
 */
const { theme } = useColorMode();
useDocumentTheme();

const drawer = ref(false);
const onKey = (e) => { if (e.key === 'Escape') drawer.value = false; };
watch(drawer, (open) => {
    document.body.style.overflow = open ? 'hidden' : '';
    open ? window.addEventListener('keydown', onKey) : window.removeEventListener('keydown', onKey);
});
const stopNavListener = router.on('navigate', () => { drawer.value = false; });
onBeforeUnmount(() => {
    stopNavListener();
    document.body.style.overflow = '';
    window.removeEventListener('keydown', onKey);
});
</script>

<template>
    <div class="landing app min-h-screen font-sans antialiased" :data-theme="theme">
        <!-- Desktop sidebar -->
        <aside class="sidebar fixed inset-y-0 left-0 z-40 hidden w-64 lg:block">
            <AppSidebar />
        </aside>

        <!-- Mobile top bar -->
        <div class="topbar sticky top-0 z-30 flex h-14 items-center justify-between gap-3 px-4 lg:hidden">
            <BrandLogo :href="route('dashboard')" small />
            <div class="flex items-center gap-1.5">
                <ThemeToggle />
                <button type="button" class="flex size-9 items-center justify-center rounded-lg text-lp-muted transition hover:bg-lp-ink/5 hover:text-lp-fg" aria-label="Open menu" :aria-expanded="drawer" @click="drawer = true">
                    <Icon name="menu" :stroke="1.8" class="size-6" />
                </button>
            </div>
        </div>

        <!-- Mobile drawer -->
        <Transition name="drawer">
            <div v-if="drawer" class="fixed inset-0 z-50 lg:hidden" role="dialog" aria-modal="true" aria-label="Menu">
                <div class="drawer-backdrop absolute inset-0" @click="drawer = false"></div>
                <div class="drawer-panel sidebar absolute inset-y-0 left-0 w-[17rem] max-w-[85vw]">
                    <button type="button" class="absolute right-3 top-3.5 z-10 flex size-9 items-center justify-center rounded-lg text-lp-subtle hover:bg-lp-ink/5 hover:text-lp-fg" aria-label="Close menu" @click="drawer = false">
                        <Icon name="xMark" :stroke="2" class="size-5" />
                    </button>
                    <AppSidebar @navigate="drawer = false" />
                </div>
            </div>
        </Transition>

        <div class="lg:pl-64">
            <header v-if="$slots.header" class="px-4 pt-8 sm:px-6 lg:px-10 lg:pt-10">
                <div class="mx-auto max-w-6xl"><slot name="header" /></div>
            </header>
            <main class="relative">
                <slot />
            </main>
        </div>
    </div>
</template>

<style scoped>
.sidebar {
    border-right: 1px solid rgb(var(--lp-ink) / 0.07);
    background: rgb(var(--lp-bg));
}
.topbar {
    border-bottom: 1px solid rgb(var(--lp-ink) / 0.07);
    background: var(--lp-nav);
    -webkit-backdrop-filter: blur(14px) saturate(160%);
    backdrop-filter: blur(14px) saturate(160%);
}
.drawer-backdrop { background: rgb(5 5 10 / 0.5); }
.drawer-panel { box-shadow: 20px 0 60px -10px rgb(0 0 0 / 0.4); }

.drawer-enter-active, .drawer-leave-active { transition: opacity 0.25s ease; }
.drawer-enter-active .drawer-panel, .drawer-leave-active .drawer-panel { transition: transform 0.35s cubic-bezier(0.22, 1, 0.36, 1); }
.drawer-enter-from, .drawer-leave-to { opacity: 0; }
.drawer-enter-from .drawer-panel, .drawer-leave-to .drawer-panel { transform: translateX(-100%); }
</style>
