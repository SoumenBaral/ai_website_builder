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
// Split layout: the minimum height applies from tablets up (phones stack photo and text).
const splitHeightClass = computed(() => ({
    sm: 'md:min-h-[300px]',
    md: 'md:min-h-[400px]',
    lg: 'md:min-h-[500px]',
    full: 'md:min-h-screen',
}[props.minHeight] || 'md:min-h-[500px]'));
</script>

<template>
    <!-- Published twin: resources/views/export/blocks/hero.blade.php — keep the two in step. -->
    <!-- Split layout: image on one side, text on other -->
    <!-- Phones: photo on top, text below (no forced height). Tablets and up: side by side. -->
    <section v-if="isSplit" class="flex flex-col md:flex-row items-stretch" :class="splitHeightClass">
        <!-- Image side -->
        <div
            :class="layout === 'split-left' ? 'md:order-1' : 'md:order-2'"
            class="order-1 block w-full h-80 sm:h-[28rem] md:h-auto md:w-1/2 md:aspect-[5/4] bg-cover"
            :style="image ? { backgroundImage: `url(${image})`, backgroundPosition: '50% 18%' } : { background: 'var(--secondary, #e2e8f0)' }"
        ></div>
        <!-- Text side -->
        <div
            :class="layout === 'split-left' ? 'md:order-2' : 'md:order-1'"
            class="order-2 w-full md:w-1/2 flex items-center"
            :style="{ background: 'var(--bg)', color: 'var(--text)' }"
        >
            <div class="w-full max-w-xl mx-auto px-6 py-12 sm:px-8 md:py-16 lg:px-12" :class="textAlign">
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
                    class="inline-block px-8 py-3 font-medium text-lg transition-opacity hover:opacity-90"
                    :style="{ background: 'var(--primary)', color: 'var(--on-primary)', borderRadius: 'var(--radius)' }"
                    :contenteditable="editable"
                    @blur="onEdit($event, 'ctaText')"
                >{{ ctaText }}</a>
            </div>
        </div>
    </section>

    <!-- Full background layout -->
    <section
        v-else
        class="relative flex items-center bg-cover"
        :class="heightClass"
        :style="image ? { backgroundImage: `url(${image})`, backgroundPosition: '50% 30%' } : {}"
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
                class="inline-block px-8 py-3 font-medium text-lg transition-opacity hover:opacity-90"
                :style="{ background: 'var(--primary)', color: 'var(--on-primary)', borderRadius: 'var(--radius)' }"
                :contenteditable="editable"
                @blur="onEdit($event, 'ctaText')"
            >{{ ctaText }}</a>
        </div>
    </section>
</template>
