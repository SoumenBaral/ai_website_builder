<script setup>
import '../../css/landing.css';
import { Link } from '@inertiajs/vue3';
import BrandLogo from '@/Components/Landing/BrandLogo.vue';
import ThemeToggle from '@/Components/Landing/ThemeToggle.vue';
import Icon from '@/Components/Landing/Icon.vue';
import AuthShowcasePanel from '@/Components/Auth/AuthShowcasePanel.vue';
import { useColorMode, useDocumentTheme } from '@/Components/Landing/useColorMode';

/*
 * Split-screen layout for every auth page. Shares the landing page's design
 * system and light/dark preference, so the brand stays continuous from
 * "Get started" through sign-up.
 */
defineProps({
    title: { type: String, required: true },
    accent: { type: String, default: '' }, // rendered in the serif gradient after the title
    subtitle: { type: String, default: '' },
    icon: { type: String, default: '' }, // optional badge icon above the title
});

const { theme } = useColorMode();
useDocumentTheme();
</script>

<template>
    <div class="landing min-h-screen font-sans antialiased" :data-theme="theme">
        <div class="lg:grid lg:grid-cols-[minmax(0,1fr)_minmax(0,1.1fr)]">
            <!-- Form column -->
            <div class="relative isolate flex min-h-screen flex-col overflow-hidden px-4 sm:px-8">
                <div class="auth-glow pointer-events-none absolute inset-0 -z-10" aria-hidden="true"></div>

                <header class="flex h-16 items-center justify-between gap-3 sm:h-20">
                    <BrandLogo />
                    <div class="flex items-center gap-2">
                        <Link href="/" class="hidden items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-medium text-lp-muted transition hover:bg-lp-ink/5 hover:text-lp-fg sm:inline-flex">
                            <Icon name="arrowLeft" :stroke="2" class="size-4" />Back to home
                        </Link>
                        <ThemeToggle />
                    </div>
                </header>

                <main class="flex flex-1 items-center justify-center py-10 sm:py-14">
                    <div class="w-full max-w-[25rem]">
                        <div class="intro" style="--d: 0ms">
                            <span v-if="icon" class="icon-solid mb-6 size-12 rounded-2xl"><Icon :name="icon" :stroke="1.6" class="size-6" /></span>
                            <h1 class="text-[2rem] font-bold leading-[1.1] tracking-[-0.035em] text-lp-fg sm:text-[2.35rem]">
                                {{ title }}<template v-if="accent">{{ " " }}<span class="serif text-gradient pr-1 text-[1.08em]">{{ accent }}</span></template>
                            </h1>
                            <p v-if="subtitle" class="mt-3 text-[15px] leading-relaxed text-lp-muted">{{ subtitle }}</p>
                        </div>

                        <div class="intro mt-8" style="--d: 120ms">
                            <slot />
                        </div>

                        <div v-if="$slots.footer" class="intro mt-8 text-center text-sm text-lp-muted" style="--d: 220ms">
                            <slot name="footer" />
                        </div>
                    </div>
                </main>

                <footer class="flex items-center justify-between gap-4 py-6 text-xs text-lp-subtle">
                    <span>&copy; {{ new Date().getFullYear() }} VibeWeb</span>
                    <Link href="/" class="transition hover:text-lp-fg sm:hidden">Back to home</Link>
                </footer>
            </div>

            <!-- Brand panel (desktop) -->
            <AuthShowcasePanel class="hidden lg:block" />
        </div>
    </div>
</template>

<style scoped>
.auth-glow {
    background:
        radial-gradient(ellipse 60% 40% at 50% -5%, var(--lp-beam), transparent 70%),
        radial-gradient(ellipse 40% 30% at 0% 100%, var(--lp-blob-a), transparent 70%);
}
</style>
