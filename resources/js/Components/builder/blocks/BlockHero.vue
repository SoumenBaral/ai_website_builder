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
    <section
        class="relative min-h-[500px] flex items-center bg-cover bg-center"
        :style="{ backgroundImage: image ? `url(${image})` : undefined }"
    >
        <div class="absolute inset-0 bg-black/50"></div>
        <div
            class="relative z-10 w-full max-w-4xl mx-auto px-6 py-20"
            :class="align === 'center' ? 'text-center' : 'text-left'"
        >
            <h1
                class="text-4xl md:text-6xl font-bold text-white mb-4 leading-tight"
                :style="{ fontFamily: 'var(--font-heading)' }"
                :contenteditable="editable"
                @blur="onEdit($event, 'heading')"
            >{{ heading }}</h1>

            <p
                class="text-lg md:text-xl text-white/80 mb-8 max-w-2xl"
                :class="align === 'center' ? 'mx-auto' : ''"
                :style="{ fontFamily: 'var(--font-body)' }"
                :contenteditable="editable"
                @blur="onEdit($event, 'subheading')"
            >{{ subheading }}</p>

            <a
                :href="editable ? undefined : ctaHref"
                class="inline-block px-8 py-3 text-white font-medium text-lg transition-opacity hover:opacity-90"
                :style="{ background: 'var(--primary)', borderRadius: 'var(--radius)' }"
                :contenteditable="editable"
                @blur="onEdit($event, 'ctaText')"
            >{{ ctaText }}</a>
        </div>
    </section>
</template>
