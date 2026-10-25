<script setup>
import { Link } from '@inertiajs/vue3';
import BrandLogo from './BrandLogo.vue';
import ThemeToggle from './ThemeToggle.vue';
import { useLandingLinks } from './useLandingLinks';

const emit = defineEmits(['navigate']);
const { user, canLogin, canRegister } = useLandingLinks();
</script>

<template>
    <footer class="border-t border-lp-ink/[0.07]">
        <div class="mx-auto grid max-w-6xl grid-cols-2 gap-x-6 gap-y-10 px-4 py-14 sm:grid-cols-3 sm:px-6 lg:grid-cols-[1.6fr_1fr_1fr_1fr]">
            <div class="col-span-2 sm:col-span-3 lg:col-span-1">
                <BrandLogo small />
                <p class="mt-4 max-w-xs text-sm leading-relaxed text-lp-muted">Build stunning websites with AI — in seconds, not weeks.</p>
            </div>

            <div>
                <h3 class="footer-title">Product</h3>
                <ul class="mt-4 space-y-3">
                    <li><button type="button" class="footer-link" @click="emit('navigate', 'showcase')">Showcase</button></li>
                    <li><button type="button" class="footer-link" @click="emit('navigate', 'features')">Features</button></li>
                    <li><button type="button" class="footer-link" @click="emit('navigate', 'pricing')">Pricing</button></li>
                    <li><a href="/demo" target="_blank" rel="noopener" class="footer-link">Live demo</a></li>
                </ul>
            </div>

            <div>
                <h3 class="footer-title">Learn</h3>
                <ul class="mt-4 space-y-3">
                    <li><button type="button" class="footer-link" @click="emit('navigate', 'how-it-works')">How it works</button></li>
                    <li><button type="button" class="footer-link" @click="emit('navigate', 'faq')">FAQ</button></li>
                </ul>
            </div>

            <div v-if="canLogin">
                <h3 class="footer-title">Account</h3>
                <ul class="mt-4 space-y-3">
                    <li v-if="user"><Link :href="route('dashboard')" class="footer-link">Dashboard</Link></li>
                    <template v-else>
                        <li><Link :href="route('login')" class="footer-link">Log in</Link></li>
                        <li v-if="canRegister"><Link :href="route('register')" class="footer-link">Create account</Link></li>
                    </template>
                </ul>
            </div>
        </div>

        <div class="border-t border-lp-ink/[0.07]">
            <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-5 sm:px-6">
                <p class="text-xs text-lp-subtle">&copy; {{ new Date().getFullYear() }} VibeWeb. All rights reserved.</p>
                <ThemeToggle />
            </div>
        </div>
    </footer>
</template>

<style scoped>
.footer-title {
    font-size: 0.75rem;
    font-weight: 600;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    color: rgb(var(--lp-subtle));
}
.footer-link {
    font-size: 0.875rem;
    color: rgb(var(--lp-muted));
    transition: color 0.2s;
}
.footer-link:hover { color: rgb(var(--lp-fg)); }
</style>
