<script setup>
const props = defineProps({
    heading: String,
    subheading: String,
    ctaText: String,
    ctaHref: String,
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
    <section class="py-20 px-6 text-white" :class="align === 'left' ? 'text-left' : 'text-center'" :style="{ background: 'var(--primary)' }">
        <div class="max-w-3xl mx-auto">
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
