<script setup>
import Icon from './Icon.vue';
import SectionHeading from './SectionHeading.vue';
import { steps } from './content';
</script>

<template>
    <section id="how-it-works" class="section relative">
        <div class="mx-auto max-w-6xl px-4 sm:px-6">
            <SectionHeading eyebrow="How it works">
                From idea to <em>live website</em> in three steps.
            </SectionHeading>

            <div class="relative mt-14 sm:mt-20">
                <!-- Connector drawn across the icons once revealed (lg only) -->
                <div data-reveal class="steps-line hidden lg:block" aria-hidden="true"><span></span></div>
                <ol class="grid gap-5 lg:grid-cols-3 lg:gap-8">
                    <li v-for="(s, i) in steps" :key="s.num" data-reveal :style="{ '--d': `${i * 120}ms` }">
                        <div class="card h-full p-6 sm:p-7 lg:text-center">
                            <div class="flex items-center gap-4 lg:flex-col lg:gap-0">
                                <span class="icon-solid size-14 rounded-2xl"><Icon :name="s.icon" :stroke="1.5" class="size-6" /></span>
                                <span class="serif text-3xl text-lp-subtle lg:mt-6">{{ s.num }}</span>
                            </div>
                            <h3 class="mt-5 text-lg font-semibold text-lp-fg lg:mt-2">{{ s.title }}</h3>
                            <p class="mt-2 text-[15px] leading-relaxed text-lp-muted lg:mx-auto lg:max-w-xs">{{ s.desc }}</p>
                        </div>
                    </li>
                </ol>
            </div>
        </div>
    </section>
</template>

<style scoped>
.steps-line {
    position: absolute;
    top: 3.5rem;
    left: 16.66%;
    right: 16.66%;
    height: 1px;
    background: rgb(var(--lp-ink) / 0.1);
}
.steps-line span {
    display: block;
    height: 100%;
    background: linear-gradient(90deg, #818cf8, #c084fc, #f0abfc);
    transform: scaleX(0);
    transform-origin: left;
    transition: transform 1.4s var(--ease-out) 0.3s;
}
.steps-line.is-visible span { transform: scaleX(1); }
.landing:not(.reveal-ready) .steps-line span { transform: scaleX(1); }
</style>
