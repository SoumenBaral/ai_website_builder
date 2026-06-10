<script setup>
import { useImageStyle } from '@/composables/useImageStyle.js';

const props = defineProps({
    heading: String,
    body: String,
    image: String,
    imagePosition: { type: String, default: 'right' },
    imageRadius: { type: String, default: '' },
    imageShadow: { type: String, default: '' },
    imageBorder: { type: String, default: '' },
    editable: { type: Boolean, default: false },
    id: String,
});

const { imageClasses } = useImageStyle(props);
const emit = defineEmits(['update:props']);

function onEdit(e, key) {
    emit('update:props', { [key]: e.target.innerText });
}
</script>

<template>
    <section class="py-16 px-6" :style="{ background: 'var(--bg)', color: 'var(--text)' }">
        <div class="max-w-6xl mx-auto grid grid-cols-1 md:grid-cols-2 gap-12 items-center">
            <div :class="imagePosition === 'left' ? 'md:order-2' : ''">
                <h2
                    class="text-3xl md:text-4xl font-bold mb-6"
                    :style="{ fontFamily: 'var(--font-heading)' }"
                    :contenteditable="editable"
                    @blur="onEdit($event, 'heading')"
                >{{ heading }}</h2>
                <p
                    class="text-lg leading-relaxed opacity-80"
                    :style="{ fontFamily: 'var(--font-body)' }"
                    :contenteditable="editable"
                    @blur="onEdit($event, 'body')"
                >{{ body }}</p>
            </div>
            <div v-if="image" :class="imagePosition === 'left' ? 'md:order-1' : ''">
                <img
                    :src="image"
                    :alt="heading"
                    class="w-full h-80 object-cover"
                    :class="imageClasses || 'shadow-lg'"
                    :style="imageClasses ? {} : { borderRadius: 'var(--radius)' }"
                />
            </div>
        </div>
    </section>
</template>
