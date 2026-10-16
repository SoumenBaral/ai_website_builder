<script setup>
import { computed } from 'vue';

/*
 * A miniature "AI-generated" website rendered from a `showcase` entry in content.js.
 * Used by the landing Showcase section and the auth pages' brand panel.
 *
 * `compact` forces the small layout regardless of viewport width — Tailwind's
 * `sm:` variants follow the viewport, but the auth panel preview is narrow
 * even on large screens.
 */
const props = defineProps({
    site: { type: Object, required: true },
    compact: { type: Boolean, default: false },
});

const c = computed(() => props.compact);
const t = computed(() => props.site.theme);
const headlineClass = computed(() => {
    if (t.value.serif) return c.value ? 'font-serif text-[28px] italic' : 'font-serif text-[30px] italic sm:text-[44px]';
    return c.value ? 'text-[24px] font-bold tracking-tight' : 'text-[26px] font-bold tracking-tight sm:text-[38px]';
});
const pad = computed(() => (c.value ? 'px-5' : 'px-5 sm:px-8'));
const wide = computed(() => (c.value ? 'hidden' : 'hidden sm:inline'));
</script>

<template>
    <div :class="['h-full overflow-hidden', t.page, t.ink]">
        <!-- Nav -->
        <div :class="['flex items-center justify-between py-4', pad]">
            <span :class="t.serif ? 'font-serif text-lg italic' : 'text-[15px] font-bold tracking-tight'">{{ site.brand }}</span>
            <div :class="['flex items-center gap-4 text-xs', t.muted]">
                <span :class="wide">About</span>
                <span :class="wide">{{ site.sections[2] }}</span>
                <span :class="['rounded-full px-3 py-1.5 font-medium', t.btn]">{{ site.cta.split(' ')[0] }}</span>
            </div>
        </div>

        <!-- Hero -->
        <div :class="['text-center', pad, c ? 'pt-3' : 'pt-4 sm:pt-6']">
            <div :class="['text-[10px] font-semibold uppercase tracking-[0.2em]', t.muted]">{{ site.eyebrow }}</div>
            <div :class="['mx-auto mt-2 max-w-lg leading-[1.05]', headlineClass]">{{ site.headline }}</div>
            <p :class="['mx-auto mt-3 max-w-md text-[13px] leading-relaxed', t.muted, c ? 'block' : 'hidden sm:block']">{{ site.sub }}</p>
            <span :class="['mt-4 inline-flex rounded-full px-4 py-2 text-xs font-semibold', !c && 'sm:mt-5', t.btn]">{{ site.cta }}</span>
        </div>

        <!-- Layout-specific body -->
        <div :class="['mt-6', pad, !c && 'sm:mt-8']">
            <div v-if="site.layout === 'gallery'" :class="['grid gap-2', c ? 'grid-cols-4' : 'grid-cols-3 sm:grid-cols-4']">
                <div v-for="(tile, i) in site.tiles" :key="tile" :class="['rounded-lg bg-gradient-to-br', tile, c ? 'h-20' : 'h-24 sm:h-32', !c && i === 3 && 'hidden sm:block']"></div>
            </div>

            <div v-else-if="site.layout === 'features'" :class="['grid gap-2', c ? 'grid-cols-3' : 'sm:grid-cols-3 sm:gap-3']">
                <div v-for="(tile, i) in site.tiles" :key="tile.title" :class="['rounded-xl', c ? 'p-3' : 'p-3 sm:p-4', t.card, !c && i > 0 && 'hidden sm:block']">
                    <span class="mb-2 block size-6 rounded-md bg-gradient-to-br from-indigo-400 to-violet-500"></span>
                    <div class="text-[13px] font-semibold">{{ tile.title }}</div>
                    <div :class="['mt-0.5 text-[11px]', t.muted]">{{ tile.text }}</div>
                </div>
            </div>

            <div v-else-if="site.layout === 'menu'" :class="['mx-auto max-w-md rounded-xl p-4', t.card]">
                <div v-for="tile in site.tiles" :key="tile.title" class="flex items-baseline gap-2 py-1.5 text-[13px]">
                    <span class="font-serif text-[15px] italic">{{ tile.title }}</span>
                    <span class="flex-1 border-b border-dotted border-current opacity-25"></span>
                    <span class="font-semibold">{{ tile.price }}</span>
                </div>
            </div>

            <div v-else-if="site.layout === 'products'" :class="['grid gap-2', c ? 'grid-cols-3' : 'grid-cols-2 sm:grid-cols-3 sm:gap-3']">
                <div v-for="(tile, i) in site.tiles" :key="tile.title" :class="['rounded-xl p-2', t.card, !c && i === 2 && 'hidden sm:block']">
                    <div :class="['rounded-lg bg-gradient-to-br', tile.color, c ? 'h-16' : 'h-20 sm:h-24']"></div>
                    <div class="mt-2 flex items-center justify-between gap-1 px-1 text-[12px]">
                        <span class="truncate font-semibold">{{ tile.title }}</span>
                        <span :class="t.muted">{{ tile.price }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
