<script setup>
import { computed } from 'vue';

const props = defineProps({
    heading: String,
    subheading: String,
    ctaText: String,
    ctaHref: String,
    image: String,
    align: { type: String, default: 'center' },
    layout: { type: String, default: 'center' },
    overlayOpacity: { type: Number, default: 50 },
    minHeight: { type: String, default: 'lg' },
    editable: { type: Boolean, default: false },
    id: String,
});
const emit = defineEmits(['update:props']);

function onEdit(e, key) {
    emit('update:props', { [key]: e.target.innerText });
}

const isSplit = computed(() => props.layout === 'split-left' || props.layout === 'split-right');
const textAlign = computed(() => {
    if (isSplit.value) return 'text-left';
    if (props.layout === 'left' || props.align === 'left') return 'text-left';
    if (props.layout === 'right') return 'text-right';
    return 'text-center';
});
const heightClass = computed(() => ({
    sm: 'min-h-[300px]',
    md: 'min-h-[400px]',
    lg: 'min-h-[500px]',
    full: 'min-h-screen',
}[props.minHeight] || 'min-h-[500px]'));
</script>

<template>
    <!-- Split layout: image on one side, text on other -->
    <section v-if="isSplit" class="flex items-stretch" :class="heightClass">
        <!-- Image side -->
        <div
            :class="layout === 'split-left' ? 'order-1' : 'order-2'"
            class="hidden md:block w-1/2 bg-cover bg-center"
            :style="image ? { backgroundImage: `url(${image})` } : { background: 'var(--secondary, #e2e8f0)' }"
        ></div>
        <!-- Text side -->
        <div
            :class="layout === 'split-left' ? 'order-2' : 'order-1'"
            class="w-full md:w-1/2 flex items-center"
            :style="{ background: 'var(--bg)', color: 'var(--text)' }"
        >
            <div class="w-full max-w-xl mx-auto px-8 py-16 lg:px-12" :class="textAlign">
                <h1
                    class="text-4xl md:text-5xl font-bold mb-4 leading-tight"
                    :style="{ fontFamily: 'var(--font-heading)' }"
                    :contenteditable="editable"
                    @blur="onEdit($event, 'heading')"
                >{{ heading }}</h1>
                <p
                    class="text-lg md:text-xl opacity-80 mb-8 max-w-lg"
                    :class="textAlign === 'text-center' ? 'mx-auto' : ''"
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
        </div>
    </section>

    <!-- Full background layout -->
    <section
        v-else
        class="relative flex items-center bg-cover bg-center"
        :class="heightClass"
        :style="image ? { backgroundImage: `url(${image})` } : {}"
    >
        <div class="absolute inset-0" :style="{ backgroundColor: `rgba(0,0,0,${overlayOpacity / 100})` }"></div>
        <div
            class="relative z-10 w-full max-w-4xl mx-auto px-6 py-20"
            :class="textAlign"
        >
            <h1
                class="text-4xl md:text-6xl font-bold text-white mb-4 leading-tight"
                :style="{ fontFamily: 'var(--font-heading)' }"
                :contenteditable="editable"
                @blur="onEdit($event, 'heading')"
            >{{ heading }}</h1>
            <p
                class="text-lg md:text-xl text-white/80 mb-8 max-w-2xl"
                :class="textAlign === 'text-center' ? 'mx-auto' : ''"
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
