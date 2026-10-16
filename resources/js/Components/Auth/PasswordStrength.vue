<script setup>
import { computed } from 'vue';

// Visual guide only — the server enforces the real rule (Password::defaults(): min 8 chars).
const props = defineProps({
    password: { type: String, default: '' },
    min: { type: Number, default: 8 },
});

const levels = [
    { label: 'Too short', color: 'bg-rose-500', text: 'text-rose-500' },
    { label: 'Weak', color: 'bg-orange-500', text: 'text-orange-500' },
    { label: 'Fair', color: 'bg-amber-500', text: 'text-amber-500' },
    { label: 'Good', color: 'bg-emerald-500', text: 'text-emerald-500' },
    { label: 'Strong', color: 'bg-emerald-500', text: 'text-emerald-500' },
];

const score = computed(() => {
    const p = props.password;
    if (p.length < props.min) return 0;
    let s = 1;
    if (p.length >= 12) s++;
    if (/[a-z]/.test(p) && /[A-Z]/.test(p)) s++;
    if (/\d/.test(p)) s++;
    if (/[^A-Za-z0-9]/.test(p)) s++;
    return Math.min(4, s);
});
const level = computed(() => levels[score.value]);
const hint = computed(() => {
    if (props.password.length < props.min) return `Use at least ${props.min} characters`;
    if (score.value < 3) return 'Add numbers, symbols or mixed case';
    return 'Great password';
});
</script>

<template>
    <div class="mt-2.5" aria-live="polite">
        <div class="grid grid-cols-4 gap-1.5" aria-hidden="true">
            <span
                v-for="i in 4"
                :key="i"
                :class="['h-1 rounded-full transition-colors duration-300', i <= Math.max(score, 1) ? level.color : 'bg-lp-ink/10']"
            ></span>
        </div>
        <p class="mt-2 flex justify-between text-xs">
            <span :class="['font-medium', level.text]">{{ level.label }}</span>
            <span class="text-lp-subtle">{{ hint }}</span>
        </p>
    </div>
</template>
