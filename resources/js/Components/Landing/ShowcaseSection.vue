<script setup>
import { ref, computed, onMounted } from 'vue';
import Icon from './Icon.vue';
import SectionHeading from './SectionHeading.vue';
import MiniSite from './MiniSite.vue';
import { showcase } from './content';
import { prefersReducedMotion } from './useLandingEffects';

const active = ref(0);
// Auto-advance is driven by the progress bar's CSS animation (`animationend`),
// so it naturally pauses off-screen / on hover and needs no timers.
const autoplay = ref(false);
const site = computed(() => showcase[active.value]);

onMounted(() => { autoplay.value = !prefersReducedMotion(); });

function select(i) {
    active.value = i;
    autoplay.value = false; // the visitor took control
}
function next() {
    if (autoplay.value) active.value = (active.value + 1) % showcase.length;
}
</script>

<template>
    <section id="showcase" data-play class="showcase section relative">
        <div class="mx-auto max-w-6xl px-4 sm:px-6">
            <SectionHeading eyebrow="Showcase">
                One sentence. <em>A whole website.</em>
                <template #lead>Pick a prompt and see the kind of site VibeWeb generates — layout, copy, colors and all.</template>
            </SectionHeading>

            <div data-reveal style="--d: 100ms" class="mt-12 grid gap-6 sm:mt-16 lg:grid-cols-[minmax(0,20rem)_1fr] lg:gap-10">
                <!-- Prompt tabs -->
                <div role="tablist" aria-label="Example websites" class="tabs -mx-4 flex gap-2 overflow-x-auto px-4 pb-1 sm:mx-0 sm:px-0 lg:flex-col lg:overflow-visible">
                    <button
                        v-for="(s, i) in showcase"
                        :id="`tab-${s.key}`"
                        :key="s.key"
                        type="button"
                        role="tab"
                        :aria-selected="i === active"
                        :aria-controls="`panel-${s.key}`"
                        :class="['tab', i === active && 'tab--active']"
                        @click="select(i)"
                    >
                        <span class="flex items-center justify-between gap-3">
                            <span class="text-sm font-semibold text-lp-fg">{{ s.tab }}</span>
                            <Icon name="arrow" :stroke="2" class="tab-arrow hidden size-4 lg:block" />
                        </span>
                        <span class="tab-prompt hidden text-[13px] leading-relaxed text-lp-muted lg:block">“{{ s.prompt }}”</span>
                        <span v-if="i === active && autoplay" :key="`p-${active}`" class="tab-progress" @animationend="next"></span>
                    </button>
                </div>

                <!-- Live preview -->
                <div class="min-w-0">
                    <p class="mb-3 flex items-start gap-2 text-sm text-lp-muted lg:hidden">
                        <Icon name="sparkles" class="mt-0.5 size-4 shrink-0 text-lp-accent" />“{{ site.prompt }}”
                    </p>

                    <div :id="`panel-${site.key}`" role="tabpanel" :aria-labelledby="`tab-${site.key}`" class="browser">
                        <div class="flex items-center gap-3 border-b border-lp-ink/[0.07] px-3 py-2.5">
                            <div class="flex gap-1.5">
                                <span class="size-2.5 rounded-full bg-lp-ink/15"></span>
                                <span class="size-2.5 rounded-full bg-lp-ink/15"></span>
                                <span class="size-2.5 rounded-full bg-lp-ink/15"></span>
                            </div>
                            <Transition name="fade" mode="out-in">
                                <div :key="site.url" class="mx-auto flex min-w-0 items-center gap-1.5 truncate rounded-md bg-lp-ink/[0.05] px-3 py-1 text-[11px] text-lp-subtle">
                                    <span class="size-1.5 shrink-0 rounded-full bg-emerald-500"></span>{{ site.url }}
                                </div>
                            </Transition>
                            <span class="w-10"></span>
                        </div>

                        <div class="relative h-[350px] overflow-hidden sm:h-[440px]">
                            <Transition name="swap" mode="out-in">
                                <MiniSite :key="site.key" :site="site" class="absolute inset-0" />
                            </Transition>
                        </div>
                    </div>

                    <!-- What the AI produced -->
                    <div class="mt-4 flex flex-wrap items-center gap-2">
                        <span class="mr-1 text-xs font-medium uppercase tracking-[0.14em] text-lp-subtle">Generated</span>
                        <TransitionGroup name="fade">
                            <span v-for="name in site.sections" :key="`${site.key}-${name}`" class="chip">
                                <Icon name="check" :stroke="2.6" class="size-3 text-emerald-500" />{{ name }}
                            </span>
                        </TransitionGroup>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<style scoped>
.tabs { scrollbar-width: none; }
.tabs::-webkit-scrollbar { display: none; }

.tab {
    position: relative;
    display: flex;
    flex-shrink: 0;
    flex-direction: column;
    gap: 0.4rem;
    overflow: hidden;
    border-radius: 0.9rem;
    border: 1px solid rgb(var(--lp-ink) / 0.08);
    background: rgb(var(--lp-surface) / 0.5);
    padding: 0.7rem 1.1rem;
    text-align: left;
    transition: border-color 0.25s, background-color 0.25s, box-shadow 0.25s;
}
.tab:hover { border-color: rgb(var(--lp-ink) / 0.16); }
.tab--active {
    border-color: rgb(139 92 246 / 0.45);
    background: rgb(var(--lp-surface));
    box-shadow: 0 12px 32px -16px rgb(139 92 246 / 0.45);
}
@media (min-width: 1024px) {
    .tab { padding: 1.1rem 1.25rem; }
    .tab-prompt { display: none; }
    .tab--active .tab-prompt { display: block; }
}
.tab-arrow { color: rgb(var(--lp-subtle)); transition: transform 0.3s var(--ease-out), color 0.2s; }
.tab--active .tab-arrow { color: rgb(var(--lp-accent)); transform: translateX(2px); }

/* Auto-advance progress (scaleX = GPU only); pauses off-screen and on hover */
.tab-progress {
    position: absolute;
    inset: auto 0 0 0;
    height: 2px;
    background: linear-gradient(90deg, #818cf8, #e879f9);
    transform-origin: left;
    animation: progress 7s linear forwards;
    animation-play-state: paused;
}
.showcase.is-playing .tab-progress { animation-play-state: running; }
.showcase:hover .tab-progress { animation-play-state: paused; }
@keyframes progress { from { transform: scaleX(0); } to { transform: scaleX(1); } }

.browser {
    overflow: hidden;
    border-radius: 1rem;
    border: 1px solid rgb(var(--lp-ink) / 0.1);
    background: rgb(var(--lp-surface));
    box-shadow: 0 40px 80px -40px rgb(15 15 35 / 0.45), 0 0 0 6px rgb(var(--lp-ink) / 0.02);
}

.swap-enter-active { transition: opacity 0.45s var(--ease-out), transform 0.45s var(--ease-out); }
.swap-leave-active { transition: opacity 0.2s ease, transform 0.2s ease; }
.swap-enter-from { opacity: 0; transform: translate3d(0, 12px, 0) scale(0.99); }
.swap-leave-to { opacity: 0; transform: scale(0.99); }

.fade-enter-active { transition: opacity 0.35s ease; }
.fade-leave-active { transition: opacity 0.15s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
.chip.fade-leave-active { display: none; }
</style>
