<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import Icon from '@/Components/Landing/Icon.vue';
import MiniSite from '@/Components/Landing/MiniSite.vue';
import { showcase } from '@/Components/Landing/content';
import { prefersReducedMotion } from '@/Components/Landing/useLandingEffects';

const perks = ['10 free AI generations / month', 'No credit card required', 'Publish in one click'];

const active = ref(0);
const site = computed(() => showcase[active.value]);
const paused = ref(false);
let timer = 0;

function show(i) {
    active.value = i;
    restart();
}
function restart() {
    clearInterval(timer);
    if (prefersReducedMotion()) return;
    timer = setInterval(() => {
        if (!paused.value && document.visibilityState === 'visible') {
            active.value = (active.value + 1) % showcase.length;
        }
    }, 5000);
}

onMounted(restart);
onBeforeUnmount(() => clearInterval(timer));
</script>

<!-- Always dark (`force-dark`) so the brand moment looks the same in both themes. -->
<template>
    <aside class="force-dark sticky top-0 h-screen p-3" aria-label="What you can build with VibeWeb">
        <div class="panel-shell relative flex h-full flex-col overflow-hidden rounded-[1.75rem] px-10 py-10 xl:px-14 xl:py-12">
            <div class="pointer-events-none absolute inset-0" aria-hidden="true">
                <div class="panel-grid absolute inset-0"></div>
                <div class="panel-beam absolute inset-0"></div>
            </div>

            <div class="relative">
                <span class="eyebrow"><Icon name="sparkles" class="size-3.5" />AI website builder</span>
                <h2 class="mt-6 text-[2.5rem] font-bold leading-[1.05] tracking-[-0.04em] text-white xl:text-5xl">
                    Describe it.<br /><span class="serif text-gradient pr-1 text-[1.1em]">We'll build it.</span>
                </h2>
                <p class="panel-lead mt-4 max-w-md text-[15px] leading-relaxed text-zinc-400">
                    Join creators turning a single sentence into a complete, editable website.
                </p>
            </div>

            <!-- Live preview -->
            <div class="relative mt-8 flex min-h-0 flex-1 flex-col" @mouseenter="paused = true" @mouseleave="paused = false">
                <div class="flex items-center gap-2.5 rounded-xl border border-white/10 bg-white/[0.04] px-3.5 py-2.5 text-[13px]">
                    <Icon name="sparkles" class="size-4 shrink-0 text-violet-300" />
                    <Transition name="fade" mode="out-in">
                        <span :key="site.key" class="truncate text-zinc-300"><span class="text-zinc-500">Build me </span>{{ site.prompt.charAt(0).toLowerCase() + site.prompt.slice(1) }}</span>
                    </Transition>
                </div>

                <div class="preview mt-3 flex min-h-0 flex-1 flex-col overflow-hidden rounded-xl">
                    <div class="flex items-center gap-2 border-b border-white/[0.06] px-3 py-2">
                        <span class="size-2 rounded-full bg-white/15"></span>
                        <span class="size-2 rounded-full bg-white/15"></span>
                        <span class="size-2 rounded-full bg-white/15"></span>
                        <span class="mx-auto truncate rounded bg-white/[0.05] px-2.5 py-0.5 text-[10px] text-zinc-500">{{ site.url }}</span>
                    </div>
                    <div class="relative min-h-0 flex-1">
                        <Transition name="swap">
                            <MiniSite :key="site.key" :site="site" compact class="absolute inset-0" />
                        </Transition>
                    </div>
                </div>

                <div class="mt-4 flex items-center justify-center gap-2">
                    <button
                        v-for="(s, i) in showcase"
                        :key="s.key"
                        type="button"
                        :aria-label="`Show ${s.tab} example`"
                        :aria-current="i === active"
                        :class="['dot', i === active && 'dot--active']"
                        @click="show(i)"
                    ></button>
                </div>
            </div>

            <ul class="perks relative mt-8 grid gap-3 text-sm text-zinc-300 xl:grid-cols-3 xl:gap-4">
                <li v-for="p in perks" :key="p" class="flex items-center gap-2.5">
                    <span class="icon-solid size-5 rounded-full"><Icon name="check" :stroke="3" class="size-3" /></span>
                    {{ p }}
                </li>
            </ul>
        </div>
    </aside>
</template>

<style scoped>
.panel-shell {
    background: #0b0b12;
    border: 1px solid rgb(255 255 255 / 0.07);
}
.panel-grid {
    background-image:
        linear-gradient(to right, rgb(255 255 255 / 0.04) 1px, transparent 1px),
        linear-gradient(to bottom, rgb(255 255 255 / 0.04) 1px, transparent 1px);
    background-size: 48px 48px;
    -webkit-mask-image: radial-gradient(ellipse 80% 60% at 70% 0%, #000 30%, transparent 100%);
    mask-image: radial-gradient(ellipse 80% 60% at 70% 0%, #000 30%, transparent 100%);
}
.panel-beam {
    background:
        radial-gradient(ellipse 70% 50% at 80% -5%, rgb(139 92 246 / 0.4), transparent 70%),
        radial-gradient(ellipse 50% 40% at 0% 100%, rgb(99 102 241 / 0.18), transparent 70%),
        radial-gradient(ellipse 40% 30% at 100% 100%, rgb(217 70 239 / 0.12), transparent 70%);
}
.preview {
    max-height: 26rem;
    border: 1px solid rgb(255 255 255 / 0.1);
    background: #0f0f17;
    box-shadow: 0 30px 70px -30px rgb(0 0 0 / 0.8), 0 0 0 6px rgb(255 255 255 / 0.02);
}

.dot {
    width: 0.45rem;
    height: 0.45rem;
    border-radius: 9999px;
    background: rgb(255 255 255 / 0.2);
    transition: width 0.4s cubic-bezier(0.22, 1, 0.36, 1), background-color 0.3s;
}
.dot:hover { background: rgb(255 255 255 / 0.4); }
.dot--active { width: 1.5rem; background: linear-gradient(90deg, #818cf8, #e879f9); }

.swap-enter-active { transition: opacity 0.6s cubic-bezier(0.22, 1, 0.36, 1), transform 0.6s cubic-bezier(0.22, 1, 0.36, 1); }
.swap-leave-active { transition: opacity 0.35s ease; }
.swap-enter-from { opacity: 0; transform: translate3d(0, 14px, 0); }
.swap-leave-to { opacity: 0; }
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

/* Short laptop screens: drop secondary copy so the preview keeps its room */
@media (max-height: 780px) {
    .panel-lead { display: none; }
    .perks { display: none; }
}
</style>
