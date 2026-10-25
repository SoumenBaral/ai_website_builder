<script setup>
import Icon from './Icon.vue';
import { buildSteps } from './content';
</script>

<!--
  Always rendered dark (`force-dark`), in both themes — an editor UI reads as
  "product" on either background. Blocks assemble on a 10s loop that only runs
  while the parent hero has `.is-playing`.
-->
<template>
    <div class="mockup-frame force-dark" aria-hidden="true">
        <!-- Window chrome -->
        <div class="flex items-center gap-3 border-b border-white/[0.06] px-3 py-2.5 sm:px-4">
            <div class="flex gap-1.5">
                <span class="size-2.5 rounded-full bg-white/15"></span>
                <span class="size-2.5 rounded-full bg-white/15"></span>
                <span class="size-2.5 rounded-full bg-white/15"></span>
            </div>
            <div class="mx-auto flex min-w-0 max-w-xs flex-1 items-center justify-center gap-1.5 truncate rounded-md bg-white/[0.04] px-3 py-1 text-[11px] text-zinc-500">
                <span class="size-1.5 shrink-0 rounded-full bg-emerald-400"></span>
                vibeweb.app/editor
            </div>
            <div class="building-chip hidden sm:flex">
                <Icon name="sparkles" :stroke="1.8" class="size-3" />
                <span class="shimmer">AI is building</span>
            </div>
        </div>

        <div class="flex h-[280px] sm:h-[380px] lg:h-[440px]">
            <!-- AI panel -->
            <div class="hidden w-56 shrink-0 flex-col border-r border-white/[0.06] p-4 text-left sm:flex">
                <div class="text-[10px] font-semibold uppercase tracking-[0.18em] text-zinc-600">Prompt</div>
                <div class="mt-2 rounded-lg rounded-tl-sm border border-white/[0.07] bg-white/[0.04] p-2.5 text-[12px] leading-relaxed text-zinc-300">
                    A portfolio for a wedding photographer with a gallery and booking form
                </div>
                <div class="mt-5 text-[10px] font-semibold uppercase tracking-[0.18em] text-zinc-600">Progress</div>
                <ol class="mt-2.5 space-y-2.5">
                    <li v-for="(step, i) in buildSteps" :key="step" class="flex items-center gap-2.5 text-[12px] text-zinc-500">
                        <span class="relative flex size-4 shrink-0 items-center justify-center rounded-full border border-white/10">
                            <span class="build check absolute inset-0 flex items-center justify-center rounded-full bg-violet-500 text-white" :style="{ '--b': i }">
                                <Icon name="check" :stroke="3" class="size-2.5" />
                            </span>
                        </span>
                        <span class="relative">
                            {{ step }}
                            <span class="build absolute inset-0 text-zinc-200" :style="{ '--b': i }">{{ step }}</span>
                        </span>
                    </li>
                </ol>
            </div>

            <!-- Canvas: a real-looking site assembling itself -->
            <div class="min-w-0 flex-1 p-2.5 sm:p-4">
                <div class="h-full overflow-hidden rounded-lg bg-[#f7f3ee] text-left text-stone-900">
                    <div class="build flex items-center justify-between border-b border-stone-900/[0.06] px-4 py-2.5 sm:px-6 sm:py-3" style="--b: 0">
                        <span class="font-serif text-[13px] italic sm:text-[15px]">Lumen Studio</span>
                        <div class="flex items-center gap-3 text-[10px] text-stone-500 sm:gap-4 sm:text-[11px]">
                            <span class="hidden sm:inline">Work</span>
                            <span class="hidden sm:inline">About</span>
                            <span class="rounded-full bg-stone-900 px-2.5 py-1 text-white">Book</span>
                        </div>
                    </div>

                    <div class="build px-4 pt-5 text-center sm:px-6 sm:pt-8" style="--b: 1">
                        <div class="text-[9px] font-semibold uppercase tracking-[0.2em] text-stone-500 sm:text-[10px]">Wedding &amp; portrait photography</div>
                        <div class="mt-1.5 font-serif text-[22px] italic leading-none sm:mt-2 sm:text-[34px] lg:text-[40px]">Moments, beautifully kept.</div>
                        <p class="mx-auto mt-2 hidden max-w-sm text-[11px] leading-relaxed text-stone-500 sm:block">Natural, unposed photography for couples who want to remember how it felt.</p>
                    </div>

                    <div class="build mt-4 grid grid-cols-3 gap-1.5 px-4 sm:mt-6 sm:grid-cols-4 sm:gap-2 sm:px-6" style="--b: 2">
                        <div class="h-14 rounded-md bg-gradient-to-br from-amber-200 to-rose-200 sm:h-20 lg:h-24"></div>
                        <div class="h-14 rounded-md bg-gradient-to-br from-stone-300 to-stone-200 sm:h-20 lg:h-24"></div>
                        <div class="h-14 rounded-md bg-gradient-to-br from-rose-200 to-orange-100 sm:h-20 lg:h-24"></div>
                        <div class="hidden h-20 rounded-md bg-gradient-to-br from-amber-100 to-stone-300 sm:block lg:h-24"></div>
                    </div>

                    <div class="build mx-4 mt-3 flex items-center gap-2.5 rounded-md bg-white/70 p-2.5 sm:mx-6 sm:mt-4" style="--b: 3">
                        <span class="size-6 shrink-0 rounded-full bg-gradient-to-br from-rose-200 to-amber-200"></span>
                        <span class="truncate font-serif text-[12px] italic text-stone-600">“She captured our day exactly as it felt.”</span>
                    </div>
                </div>
            </div>

            <!-- Theme panel -->
            <div class="hidden w-48 shrink-0 border-l border-white/[0.06] p-4 text-left lg:block">
                <div class="mb-3 text-[10px] font-semibold uppercase tracking-[0.18em] text-zinc-600">Theme</div>
                <div class="flex gap-2">
                    <span class="size-7 rounded-lg bg-[#f7f3ee] ring-2 ring-white/25 ring-offset-2 ring-offset-[#0c0c12]"></span>
                    <span class="size-7 rounded-lg bg-stone-900 ring-1 ring-white/10"></span>
                    <span class="size-7 rounded-lg bg-amber-200"></span>
                    <span class="size-7 rounded-lg bg-rose-200"></span>
                </div>
                <div class="mb-2 mt-5 text-[10px] font-semibold uppercase tracking-[0.18em] text-zinc-600">Heading font</div>
                <div class="flex items-center justify-between rounded-md border border-white/[0.07] bg-white/[0.03] px-3 py-2 text-xs text-zinc-300">Instrument Serif <span class="font-serif italic text-zinc-500">Aa</span></div>
                <div class="mb-2 mt-5 text-[10px] font-semibold uppercase tracking-[0.18em] text-zinc-600">Radius</div>
                <div class="grid grid-cols-3 gap-1 rounded-md bg-white/[0.03] p-1 text-center text-[11px]">
                    <span class="rounded py-1 text-zinc-500">sm</span>
                    <span class="rounded bg-white/10 py-1 text-white">md</span>
                    <span class="rounded py-1 text-zinc-500">lg</span>
                </div>
                <div class="build mt-6 flex items-center justify-center gap-1.5 rounded-md bg-white py-2 text-xs font-semibold text-zinc-900" style="--b: 4">
                    <Icon name="rocket" :stroke="1.8" class="size-3.5" /> Publish
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.mockup-frame {
    overflow: hidden;
    border-radius: 1rem;
    border: 1px solid rgb(255 255 255 / 0.1);
    background: #0c0c12;
    box-shadow:
        inset 0 1px 0 rgb(255 255 255 / 0.06),
        0 50px 100px -30px rgb(0 0 0 / 0.7),
        0 0 0 1px rgb(0 0 0 / 0.4);
}
.building-chip {
    align-items: center;
    gap: 0.35rem;
    border-radius: 9999px;
    border: 1px solid rgb(167 139 250 / 0.25);
    background: rgb(139 92 246 / 0.1);
    padding: 0.2rem 0.6rem;
    font-size: 11px;
    font-weight: 500;
    color: #c4b5fd;
}

/* Each `.build` element appears at its own beat (--b) and the whole scene loops */
.build {
    animation: assemble 10s cubic-bezier(0.22, 1, 0.36, 1) infinite both;
    animation-delay: calc(var(--b, 0) * 0.9s + 1.2s);
    animation-play-state: paused;
}
.is-playing .build { animation-play-state: running; }
@keyframes assemble {
    0% { opacity: 0; transform: translate3d(0, 8px, 0) scale(0.98); }
    6%, 80% { opacity: 1; transform: none; }
    88%, 100% { opacity: 0; transform: none; }
}
.shimmer {
    background: linear-gradient(90deg, #c4b5fd 0%, #ffffff 50%, #c4b5fd 100%) 0 0 / 200% 100%;
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
    animation: shimmer 2.4s linear infinite;
    animation-play-state: paused;
}
.is-playing .shimmer { animation-play-state: running; }
@keyframes shimmer { to { background-position: -200% 0; } }

@media (prefers-reduced-motion: reduce) {
    .build { animation: none !important; opacity: 1; }
}
</style>
