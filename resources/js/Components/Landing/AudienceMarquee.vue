<script setup>
import Icon from './Icon.vue';
import { audiences } from './content';
</script>

<template>
    <section class="border-y border-lp-ink/[0.07] py-8 sm:py-10" aria-label="Who it's for">
        <p class="px-4 text-center text-xs font-medium uppercase tracking-[0.2em] text-lp-subtle">Made for every kind of website</p>
        <div data-play class="marquee mt-6">
            <ul class="marquee-track">
                <template v-for="copy in 2" :key="copy">
                    <li v-for="a in audiences" :key="`${copy}-${a}`" class="marquee-item" :aria-hidden="copy === 2 ? 'true' : undefined">
                        <Icon name="sparkles" class="size-4 text-lp-accent/70" />
                        {{ a }}
                    </li>
                </template>
            </ul>
        </div>
    </section>
</template>

<style scoped>
.marquee {
    overflow: hidden;
    -webkit-mask-image: linear-gradient(to right, transparent, #000 12%, #000 88%, transparent);
    mask-image: linear-gradient(to right, transparent, #000 12%, #000 88%, transparent);
}
.marquee-track {
    display: flex;
    width: max-content;
    gap: 0.75rem;
    animation: marquee 45s linear infinite;
    animation-play-state: paused;
}
.marquee.is-playing .marquee-track { animation-play-state: running; }
.marquee:hover .marquee-track { animation-play-state: paused; }
.marquee-item {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    border-radius: 9999px;
    border: 1px solid rgb(var(--lp-ink) / 0.08);
    background: rgb(var(--lp-surface) / 0.6);
    padding: 0.55rem 1rem;
    font-size: 0.875rem;
    color: rgb(var(--lp-text));
    white-space: nowrap;
}
/* -50% minus half the gap keeps the loop seamless */
@keyframes marquee { to { transform: translate3d(calc(-50% - 0.375rem), 0, 0); } }
@media (prefers-reduced-motion: reduce) {
    .marquee { overflow-x: auto; }
    .marquee-track { animation: none !important; }
}
</style>
