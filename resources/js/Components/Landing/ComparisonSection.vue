<script setup>
import Icon from './Icon.vue';
import SectionHeading from './SectionHeading.vue';
import { comparison } from './content';
</script>

<template>
    <section class="section relative">
        <div class="mx-auto max-w-5xl px-4 sm:px-6">
            <SectionHeading eyebrow="Why VibeWeb">
                Stop waiting weeks. <em>Start in seconds.</em>
            </SectionHeading>

            <div class="mt-12 grid gap-4 sm:mt-16 md:grid-cols-2">
                <div data-reveal class="card p-6 sm:p-8">
                    <h3 class="text-sm font-semibold uppercase tracking-[0.14em] text-lp-subtle">{{ comparison.old.title }}</h3>
                    <ul class="mt-6 space-y-4">
                        <li v-for="p in comparison.old.points" :key="p" class="flex items-start gap-3 text-[15px] text-lp-muted">
                            <span class="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full bg-lp-ink/[0.06] text-lp-subtle">
                                <Icon name="xMark" :stroke="2.4" class="size-3" />
                            </span>
                            {{ p }}
                        </li>
                    </ul>
                </div>

                <div data-reveal style="--d: 100ms" class="card card-featured p-6 sm:p-8">
                    <h3 class="flex items-center gap-2 text-sm font-semibold uppercase tracking-[0.14em] text-lp-accent">
                        <Icon name="sparkles" class="size-4" />{{ comparison.new.title }}
                    </h3>
                    <ul class="mt-6 space-y-4">
                        <li v-for="p in comparison.new.points" :key="p" class="flex items-start gap-3 text-[15px] font-medium text-lp-fg">
                            <span class="icon-solid mt-0.5 size-5 rounded-full">
                                <Icon name="check" :stroke="3" class="size-3" />
                            </span>
                            {{ p }}
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>
</template>
