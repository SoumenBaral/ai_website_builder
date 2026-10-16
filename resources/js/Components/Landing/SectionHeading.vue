<script setup>
defineProps({
    eyebrow: { type: String, required: true },
    align: { type: String, default: 'center' }, // 'center' | 'left'
});
</script>

<!-- Title text goes in the default slot; wrap the accent words in <em>. -->
<template>
    <div data-reveal :class="align === 'center' ? 'mx-auto max-w-2xl text-center' : 'max-w-xl'">
        <span class="eyebrow">{{ eyebrow }}</span>
        <h2 class="section-title heading mt-4"><slot /></h2>
        <p v-if="$slots.lead" class="mt-5 text-base leading-relaxed text-lp-muted sm:text-lg"><slot name="lead" /></p>
    </div>
</template>

<style scoped>
.heading :slotted(em) {
    font-family: 'Instrument Serif', ui-serif, Georgia, serif;
    font-style: italic;
    font-weight: 400;
    font-size: 1.08em;
    letter-spacing: -0.02em;
    padding-right: 0.06em;
    background: var(--lp-gradient);
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
}
</style>
