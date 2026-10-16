<script setup>
import Icon from '@/Components/Landing/Icon.vue';

defineProps({
    label: { type: String, required: true },
    value: { type: [String, Number], required: true },
    icon: { type: String, required: true },
    hint: { type: String, default: '' },
    progress: { type: Number, default: null }, // 0–100, draws a ring instead of the icon
});
</script>

<template>
    <div class="card flex flex-col items-start gap-3 p-4 sm:flex-row sm:items-center sm:gap-4 sm:p-5">
        <div class="relative flex size-12 shrink-0 items-center justify-center">
            <svg v-if="progress !== null" viewBox="0 0 48 48" class="absolute inset-0 -rotate-90" aria-hidden="true">
                <circle cx="24" cy="24" r="20" fill="none" stroke="currentColor" stroke-width="4" class="text-lp-ink/10" />
                <circle
                    cx="24" cy="24" r="20" fill="none" stroke="url(#ring)" stroke-width="4" stroke-linecap="round"
                    :stroke-dasharray="125.66" :stroke-dashoffset="125.66 * (1 - Math.min(progress, 100) / 100)"
                    class="transition-[stroke-dashoffset] duration-1000"
                />
                <defs><linearGradient id="ring" x1="0" x2="1"><stop offset="0" stop-color="#6366f1" /><stop offset="1" stop-color="#d946ef" /></linearGradient></defs>
            </svg>
            <span v-else class="icon-tile size-12 rounded-2xl"><Icon :name="icon" :stroke="1.6" class="size-5" /></span>
            <Icon v-if="progress !== null" :name="icon" :stroke="1.7" class="relative size-4 text-lp-accent" />
        </div>
        <div class="w-full min-w-0">
            <div class="truncate text-[11px] font-medium uppercase tracking-[0.12em] text-lp-subtle sm:text-xs">{{ label }}</div>
            <div class="mt-0.5 text-2xl font-bold tracking-tight text-lp-fg tabular-nums">{{ value }}</div>
            <div v-if="hint" class="truncate text-xs text-lp-muted">{{ hint }}</div>
        </div>
    </div>
</template>
