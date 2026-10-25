<script setup>
import Icon from './Icon.vue';
import SectionHeading from './SectionHeading.vue';
import { features, blockNames } from './content';
</script>

<template>
    <section id="features" class="section relative">
        <div class="mx-auto max-w-6xl px-4 sm:px-6">
            <SectionHeading eyebrow="Features">
                Everything you need, <em>nothing you don't.</em>
                <template #lead>From AI generation to visual editing to one-click publishing — every step is covered.</template>
            </SectionHeading>

            <!-- Bento grid. md: [AI AI][Ed Pub][Blk Thm][Hist Hist]  lg: [AI AI Ed][Pub Blk Thm][Hist Hist Hist] -->
            <div data-spotlight class="mt-14 grid gap-4 sm:mt-16 md:grid-cols-2 lg:grid-cols-3">
                <div v-for="(f, i) in features" :key="f.key" data-reveal :class="f.span" :style="{ '--d': `${(i % 3) * 80}ms` }">
                    <article class="spot card group flex h-full flex-col p-6 sm:p-7">
                        <div :class="['flex h-full gap-6', f.key === 'history' ? 'flex-col lg:flex-row lg:items-center' : 'flex-col']">
                            <div :class="f.key === 'history' && 'lg:w-80 lg:shrink-0'">
                                <span class="icon-tile"><Icon :name="f.icon" :stroke="1.5" class="size-5" /></span>
                                <h3 class="mt-5 text-lg font-semibold text-lp-fg">{{ f.title }}</h3>
                                <p class="mt-2 text-[15px] leading-relaxed text-lp-muted">{{ f.desc }}</p>
                            </div>

                            <div :class="['mt-auto', f.key === 'history' && 'lg:mt-0 lg:flex-1']">
                                <!-- AI generation: prompt in, sections out -->
                                <div v-if="f.key === 'ai'" class="flex flex-col gap-3 sm:flex-row sm:items-center">
                                    <div class="panel flex-1 px-4 py-3 text-sm text-lp-text">
                                        <span class="text-lp-subtle">“</span>A calm, minimal site for my yoga studio with class schedule and pricing<span class="text-lp-subtle">”</span>
                                    </div>
                                    <Icon name="arrow" class="size-5 shrink-0 rotate-90 self-center text-lp-accent sm:rotate-0" />
                                    <div class="flex flex-wrap gap-1.5 sm:w-48">
                                        <span v-for="b in ['Navbar', 'Hero', 'Schedule', 'Pricing', 'Testimonials', 'Footer']" :key="b" class="chip">{{ b }}</span>
                                    </div>
                                </div>

                                <!-- Visual editor: a selected, editable block -->
                                <div v-else-if="f.key === 'editor'" class="panel relative p-3">
                                    <div class="rounded-md border border-lp-ink/[0.07] p-2.5">
                                        <div class="h-1.5 w-1/2 rounded-full bg-lp-ink/15"></div>
                                        <div class="mt-1.5 h-1.5 w-1/3 rounded-full bg-lp-ink/10"></div>
                                    </div>
                                    <div class="mt-2 rounded-md border border-violet-400/60 bg-violet-500/10 p-2.5 shadow-[0_0_0_4px_rgba(139,92,246,0.08)]">
                                        <div class="text-[13px] font-medium text-lp-fg">Welcome to Lumen<span class="caret" style="height: 0.95em"></span></div>
                                        <div class="mt-1.5 h-1.5 w-2/3 rounded-full bg-lp-ink/15"></div>
                                    </div>
                                    <Icon name="cursor" solid class="absolute bottom-2 right-6 size-5 text-lp-fg drop-shadow" />
                                </div>

                                <!-- Publish: live URL -->
                                <div v-else-if="f.key === 'publish'" class="panel p-3">
                                    <div class="flex items-center gap-2 rounded-lg bg-lp-ink/[0.04] px-3 py-2 text-[13px] text-lp-text">
                                        <span class="relative flex size-2"><span class="live-ping absolute inset-0 rounded-full bg-emerald-400"></span><span class="relative size-2 rounded-full bg-emerald-400"></span></span>
                                        <span class="truncate">lumen.vibeweb.app</span>
                                        <span class="ml-auto rounded bg-emerald-500/10 px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-emerald-500">Live</span>
                                    </div>
                                </div>

                                <!-- Blocks -->
                                <div v-else-if="f.key === 'blocks'" class="flex flex-wrap gap-1.5">
                                    <span v-for="b in blockNames" :key="b" class="chip">{{ b }}</span>
                                    <span class="chip text-lp-accent">+ more</span>
                                </div>

                                <!-- Theme -->
                                <div v-else-if="f.key === 'theme'" class="panel flex items-center gap-4 p-3">
                                    <div class="flex -space-x-1.5">
                                        <span v-for="c in ['bg-indigo-500', 'bg-violet-500', 'bg-fuchsia-500', 'bg-amber-400']" :key="c" :class="['size-7 rounded-full border-2 border-lp-surface', c]"></span>
                                    </div>
                                    <span class="ml-auto font-bold text-lp-fg">Aa</span>
                                    <span class="serif text-xl text-lp-fg">Aa</span>
                                </div>

                                <!-- Version history timeline -->
                                <div v-else-if="f.key === 'history'" class="panel p-4 sm:p-5">
                                    <div class="relative flex items-center justify-between">
                                        <div class="absolute inset-x-2 top-1/2 h-px -translate-y-1/2 bg-gradient-to-r from-lp-ink/5 via-lp-ink/15 to-violet-400/70"></div>
                                        <span v-for="v in 5" :key="v" :class="['relative block size-3 rounded-full border-2', v === 5 ? 'border-violet-300 bg-violet-500 shadow-[0_0_0_4px_rgba(139,92,246,0.2)]' : 'border-lp-subtle/60 bg-lp-surface']"></span>
                                    </div>
                                    <div class="mt-3 flex justify-between text-[11px] text-lp-subtle">
                                        <span>v1</span><span class="hidden sm:inline">v2</span><span>v3</span><span class="hidden sm:inline">v4</span><span class="font-medium text-lp-accent">Current</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </article>
                </div>
            </div>
        </div>
    </section>
</template>
