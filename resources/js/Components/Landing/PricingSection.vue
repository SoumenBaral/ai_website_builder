<script setup>
import { Link } from '@inertiajs/vue3';
import Icon from './Icon.vue';
import SectionHeading from './SectionHeading.vue';
import { plans } from './content';
import { useLandingLinks } from './useLandingLinks';

const { user, ctaHref } = useLandingLinks();
</script>

<template>
    <section id="pricing" class="section relative">
        <div class="pricing-glow" aria-hidden="true"></div>
        <div class="relative mx-auto max-w-6xl px-4 sm:px-6">
            <SectionHeading eyebrow="Pricing">
                Simple pricing that <em>grows with you.</em>
                <template #lead>Start for free, upgrade when you need more. No hidden fees.</template>
            </SectionHeading>

            <div data-spotlight class="mx-auto mt-14 grid max-w-md gap-5 sm:mt-16 lg:max-w-none lg:grid-cols-3">
                <div v-for="(plan, i) in plans" :key="plan.name" data-reveal :style="{ '--d': `${i * 90}ms` }">
                    <div :class="['spot card relative flex h-full flex-col p-7 sm:p-8', plan.highlight && 'card-featured']">
                        <div class="flex items-center justify-between">
                            <h3 class="text-base font-semibold text-lp-fg">{{ plan.name }}</h3>
                            <span v-if="plan.highlight" class="rounded-full bg-gradient-to-r from-indigo-500 to-fuchsia-500 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-white">Most popular</span>
                        </div>
                        <p class="mt-2 text-sm text-lp-muted">{{ plan.blurb }}</p>
                        <div class="mt-6 flex items-baseline gap-1">
                            <span class="text-5xl font-bold tracking-tight text-lp-fg">{{ plan.price }}</span>
                            <span class="text-sm text-lp-subtle">/ month</span>
                        </div>
                        <Link :href="ctaHref" :class="['btn mt-7 h-11 w-full text-sm', plan.highlight ? 'btn-primary' : 'btn-ghost']">
                            {{ user ? 'Go to dashboard' : plan.cta }}
                        </Link>
                        <ul class="mt-7 space-y-3 border-t border-lp-ink/[0.08] pt-7">
                            <li v-for="feat in plan.features" :key="feat" class="flex items-center gap-3 text-sm text-lp-text">
                                <Icon name="check" :stroke="2.4" :class="['size-4 shrink-0', plan.highlight ? 'text-lp-accent' : 'text-lp-subtle']" />
                                {{ feat }}
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<style scoped>
.pricing-glow {
    position: absolute;
    left: 50%;
    top: 58%;
    width: min(70rem, 140vw);
    height: 36rem;
    transform: translate(-50%, -50%);
    background: radial-gradient(ellipse at center, var(--lp-blob-a), transparent 65%);
    pointer-events: none;
}
</style>
