<script setup>
defineProps({
    loading: { type: Boolean, default: false },
    variant: { type: String, default: 'primary' }, // 'primary' | 'ghost'
    type: { type: String, default: 'submit' },
});
</script>

<template>
    <button
        :type="type"
        :disabled="loading"
        :aria-busy="loading"
        :class="['btn relative h-12 w-full text-[15px] disabled:cursor-wait', variant === 'ghost' ? 'btn-ghost' : 'btn-primary']"
    >
        <span :class="['inline-flex items-center gap-2 transition-opacity', loading && 'opacity-0']"><slot /></span>
        <span v-if="loading" class="absolute inset-0 flex items-center justify-center" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" class="size-5 animate-spin">
                <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-opacity="0.25" stroke-width="3" />
                <path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
            </svg>
        </span>
    </button>
</template>
