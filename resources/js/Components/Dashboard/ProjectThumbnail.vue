<script setup>
import { ref, computed } from 'vue';
import Icon from '@/Components/Landing/Icon.vue';

/*
 * A miniature of the project's real home page, drawn from the `preview`
 * summary built in ProjectController (theme colours + hero content + section list).
 */
const props = defineProps({
    preview: { type: Object, required: true },
    name: { type: String, required: true },
});

const imageFailed = ref(false);
const p = computed(() => props.preview);
const isEmpty = computed(() => !p.value.sections?.length);
const fontStack = computed(() => (p.value.font ? `'${p.value.font}', ui-sans-serif, system-ui, sans-serif` : undefined));
const below = computed(() => (p.value.sections || []).filter((s) => !['navbar', 'hero'].includes(s)).slice(0, 4));
const showImage = computed(() => p.value.image && !imageFailed.value);
</script>

<template>
    <!-- Empty project: invite the first generation -->
    <div v-if="isEmpty" class="thumb-empty relative flex aspect-[16/10] flex-col items-center justify-center gap-2 text-center">
        <span class="icon-solid size-10 rounded-xl"><Icon name="sparkles" :stroke="1.7" class="size-5" /></span>
        <span class="text-[13px] font-semibold text-lp-fg">Ready for AI</span>
        <span class="text-[11px] text-lp-subtle">Open the editor to generate</span>
    </div>

    <!-- Generated project: real colours, brand, headline and image -->
    <div v-else class="relative aspect-[16/10] overflow-hidden" :style="{ background: p.background, color: p.text, fontFamily: fontStack }" aria-hidden="true">
        <div class="flex items-center justify-between px-4 pt-3">
            <span class="max-w-[50%] truncate text-[11px] font-bold">{{ p.brand || name }}</span>
            <div class="flex items-center gap-2">
                <span class="h-1 w-5 rounded-full opacity-25" :style="{ background: p.text }"></span>
                <span class="h-1 w-5 rounded-full opacity-25" :style="{ background: p.text }"></span>
                <span class="h-3.5 w-9 rounded-full" :style="{ background: p.primary }"></span>
            </div>
        </div>

        <div :class="['mt-3 grid items-center gap-3 px-4', showImage ? 'grid-cols-[1.15fr_1fr]' : 'grid-cols-1 text-center']">
            <div class="min-w-0">
                <div class="line-clamp-3 text-[15px] font-bold leading-[1.15] tracking-tight">{{ p.heading || name }}</div>
                <span class="mt-2.5 inline-flex max-w-full truncate rounded-md px-2.5 py-1 text-[9px] font-semibold text-white" :style="{ background: p.primary }">{{ p.cta || 'Get started' }}</span>
            </div>
            <img
                v-if="showImage"
                :src="p.image"
                alt=""
                loading="lazy"
                decoding="async"
                referrerpolicy="no-referrer"
                class="aspect-[4/3] w-full rounded-lg object-cover"
                @error="imageFailed = true"
            />
        </div>

        <div class="absolute inset-x-4 bottom-3 flex flex-wrap gap-1">
            <span v-for="s in below" :key="s" class="rounded px-1.5 py-0.5 text-[9px] font-medium capitalize" :style="{ background: `${p.text}12`, color: p.text }">{{ s }}</span>
        </div>
    </div>
</template>

<style scoped>
.thumb-empty {
    background:
        radial-gradient(ellipse 60% 70% at 50% 0%, rgb(139 92 246 / 0.16), transparent 70%),
        repeating-linear-gradient(45deg, rgb(var(--lp-ink) / 0.03) 0 10px, transparent 10px 20px),
        rgb(var(--lp-ink) / 0.02);
}
</style>
