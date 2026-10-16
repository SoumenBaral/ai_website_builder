<script setup>
import Icon from '@/Components/Landing/Icon.vue';

defineProps({
    tone: { type: String, default: 'success' }, // 'success' | 'info'
});
</script>

<template>
    <div
        role="status"
        :class="[
            'flex items-start gap-3 rounded-xl border px-4 py-3 text-sm',
            tone === 'success'
                ? 'border-emerald-500/25 bg-emerald-500/10 text-emerald-700 [[data-theme=dark]_&]:text-emerald-300'
                : 'border-violet-500/25 bg-violet-500/10 text-lp-text',
        ]"
    >
        <Icon :name="tone === 'success' ? 'checkCircle' : 'sparkles'" :stroke="1.8" class="mt-px size-5 shrink-0" />
        <div><slot /></div>
    </div>
</template>
