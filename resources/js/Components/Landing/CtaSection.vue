<script setup>
import { Link } from '@inertiajs/vue3';
import Icon from './Icon.vue';
import { useLandingLinks } from './useLandingLinks';

const { ctaHref, ctaLabel } = useLandingLinks();
</script>

<!-- Stays dark in both themes (`force-dark`) — a strong closing moment on a light page too. -->
<template>
    <section class="px-4 pb-20 sm:px-6 sm:pb-28">
        <div data-reveal class="cta force-dark relative mx-auto max-w-6xl overflow-hidden rounded-3xl px-6 py-16 text-center sm:px-12 sm:py-24">
            <div class="cta-bg" aria-hidden="true"></div>
            <div class="relative">
                <h2 class="section-title mx-auto max-w-3xl">Your next website is <span class="serif text-gradient">one sentence away.</span></h2>
                <p class="mx-auto mt-5 max-w-xl text-base text-zinc-300 sm:text-lg">Join thousands of creators building beautiful websites with AI. Start free — no credit card required.</p>
                <div class="mt-9 flex flex-col items-stretch justify-center gap-3 sm:flex-row sm:items-center">
                    <Link :href="ctaHref" class="btn btn-primary group h-12 px-7 text-[15px]">
                        {{ ctaLabel }}
                        <Icon name="arrow" :stroke="2.2" class="size-4 transition-transform duration-300 group-hover:translate-x-0.5" />
                    </Link>
                    <a href="/demo" target="_blank" rel="noopener" class="btn btn-ghost h-12 px-7 text-[15px]">View live demo</a>
                </div>
            </div>
        </div>
    </section>
</template>

<style scoped>
.cta {
    border: 1px solid rgb(255 255 255 / 0.08);
    background: #0b0b12;
    box-shadow: 0 40px 100px -40px rgb(139 92 246 / 0.5);
}
.cta-bg {
    position: absolute;
    inset: 0;
    background:
        radial-gradient(ellipse 60% 70% at 50% 0%, rgb(139 92 246 / 0.38), transparent 70%),
        radial-gradient(ellipse 40% 50% at 85% 100%, rgb(217 70 239 / 0.16), transparent 70%),
        linear-gradient(to right, rgb(255 255 255 / 0.04) 1px, transparent 1px) 0 0 / 48px 48px,
        linear-gradient(to bottom, rgb(255 255 255 / 0.04) 1px, transparent 1px) 0 0 / 48px 48px;
    -webkit-mask-image: radial-gradient(ellipse 90% 90% at 50% 30%, #000 40%, transparent 100%);
    mask-image: radial-gradient(ellipse 90% 90% at 50% 30%, #000 40%, transparent 100%);
}
</style>
