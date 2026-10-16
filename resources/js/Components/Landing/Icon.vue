<script setup>
import { icons } from './icons';

defineProps({
    name: { type: String, required: true },
    stroke: { type: [Number, String], default: 1.6 },
    solid: { type: Boolean, default: false },
});
</script>

<template>
    <svg viewBox="0 0 24 24" aria-hidden="true" :fill="solid ? 'currentColor' : 'none'" :stroke="solid ? 'none' : 'currentColor'" :stroke-width="stroke">
        <path stroke-linecap="round" stroke-linejoin="round" :d="icons[name]" />
    </svg>
</template>
