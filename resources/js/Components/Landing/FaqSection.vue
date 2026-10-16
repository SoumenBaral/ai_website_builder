<script setup>
import Icon from './Icon.vue';
import SectionHeading from './SectionHeading.vue';
import { faqs } from './content';
</script>

<template>
    <section id="faq" class="section relative">
        <div class="mx-auto grid max-w-6xl gap-10 px-4 sm:px-6 lg:grid-cols-[1fr_1.4fr] lg:gap-16">
            <SectionHeading eyebrow="FAQ" align="left">
                Questions, <em>answered.</em>
                <template #lead>
                    Can't find what you're looking for? The
                    <a href="/demo" target="_blank" rel="noopener" class="font-medium text-lp-fg underline decoration-lp-ink/20 underline-offset-4 transition hover:decoration-lp-accent">live demo</a>
                    is the fastest way to see how everything works.
                </template>
            </SectionHeading>

            <!-- Native <details>: accessible, keyboard-friendly, zero JS -->
            <div data-reveal style="--d: 100ms" class="divide-y divide-lp-ink/[0.08] border-y border-lp-ink/[0.08]">
                <details v-for="(f, i) in faqs" :key="f.q" class="faq group" :open="i === 0">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-6 py-5 text-left text-[15px] font-medium text-lp-fg sm:text-base">
                        {{ f.q }}
                        <span class="flex size-7 shrink-0 items-center justify-center rounded-full border border-lp-ink/10 text-lp-muted transition duration-300 group-open:rotate-45 group-open:border-violet-400/50 group-open:text-lp-accent">
                            <Icon name="plus" :stroke="2" class="size-3.5" />
                        </span>
                    </summary>
                    <p class="answer pb-5 pr-12 text-[15px] leading-relaxed text-lp-muted">{{ f.a }}</p>
                </details>
            </div>
        </div>
    </section>
</template>

<style scoped>
.faq summary::-webkit-details-marker { display: none; }
.faq[open] .answer { animation: lp-rise 0.45s var(--ease-out) both; }
</style>
