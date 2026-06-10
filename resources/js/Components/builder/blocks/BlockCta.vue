<script setup>
const props = defineProps({
    heading: String,
    subheading: String,
    ctaText: String,
    ctaHref: String,
    image: String,
    align: { type: String, default: 'center' },
    editable: { type: Boolean, default: false },
    id: String,
});
const emit = defineEmits(['update:props']);

function onEdit(e, key) {
    emit('update:props', { [key]: e.target.innerText });
}
</script>

<template>
    <section class="relative py-20 px-6 text-white overflow-hidden" :class="align === 'left' ? 'text-left' : 'text-center'" :style="{ background: 'var(--primary)' }">
        <!-- Background image if provided -->
        <template v-if="image">
            <div class="absolute inset-0 bg-cover bg-center" :style="{ backgroundImage: `url(${image})` }"></div>
            <div class="absolute inset-0 bg-black/50"></div>
        </template>

        <div class="relative z-10 max-w-3xl" :class="align === 'left' ? '' : 'mx-auto'">
            <h2
                class="text-3xl md:text-4xl font-bold mb-4"
                :style="{ fontFamily: 'var(--font-heading)' }"
                :contenteditable="editable"
                @blur="onEdit($event, 'heading')"
            >{{ heading }}</h2>
            <p
                class="text-lg opacity-90 mb-8"
                :style="{ fontFamily: 'var(--font-body)' }"
                :contenteditable="editable"
                @blur="onEdit($event, 'subheading')"
            >{{ subheading }}</p>
            <a
                :href="editable ? undefined : ctaHref"
                class="inline-block px-8 py-3 bg-white font-medium text-lg transition-opacity hover:opacity-90"
                :style="{ color: 'var(--primary)', borderRadius: 'var(--radius)' }"
                :contenteditable="editable"
                @blur="onEdit($event, 'ctaText')"
            >{{ ctaText }}</a>
        </div>
    </section>
</template>
