<script setup>
import { ref } from 'vue';
import { useImageStyle } from '@/composables/useImageStyle.js';

const props = defineProps({
    heading: String,
    items: Array,
    image: String,
    maxWidth: { type: String, default: 'md' },
    imageRadius: { type: String, default: '' },
    imageShadow: { type: String, default: '' },
    imageBorder: { type: String, default: '' },
    editable: { type: Boolean, default: false },
    id: String,
});

const widthClass = {
    sm: 'max-w-xl',
    md: 'max-w-3xl',
    lg: 'max-w-5xl',
    full: 'max-w-6xl',
};
const { imageClasses } = useImageStyle(props);
const emit = defineEmits(['update:props']);

const openIndex = ref(null);

function toggle(index) {
    openIndex.value = openIndex.value === index ? null : index;
}

function onEdit(e, key) {
    emit('update:props', { [key]: e.target.innerText });
}

function onEditItem(e, index, key) {
    const newItems = [...props.items];
    newItems[index] = { ...newItems[index], [key]: e.target.innerText };
    emit('update:props', { items: newItems });
}
</script>

<template>
    <section class="py-16 px-6" :style="{ background: 'var(--bg)', color: 'var(--text)' }">
        <div :class="[image ? 'max-w-6xl' : (widthClass[maxWidth] || 'max-w-3xl'), 'mx-auto']">
            <h2
                class="text-3xl md:text-4xl font-bold text-center mb-12"
                :style="{ fontFamily: 'var(--font-heading)' }"
                :contenteditable="editable"
                @blur="onEdit($event, 'heading')"
            >{{ heading }}</h2>

            <div :class="image ? 'grid grid-cols-1 md:grid-cols-2 gap-10 items-start' : ''">
            <img v-if="image" :src="image" alt="" class="w-full h-auto object-cover hidden md:block" :class="imageClasses" />
            <div class="space-y-4">
                <div
                    v-for="(item, i) in items"
                    :key="i"
                    class="border border-gray-200 overflow-hidden transition-all"
                    :style="{ borderRadius: 'var(--radius)' }"
                >
                    <button
                        class="w-full flex items-center justify-between p-5 text-left font-semibold hover:bg-black/5 transition-colors"
                        :style="{ fontFamily: 'var(--font-heading)' }"
                        @click="toggle(i)"
                    >
                        <span
                            :contenteditable="editable"
                            @blur="onEditItem($event, i, 'question')"
                            @click.stop
                        >{{ item.question }}</span>
                        <span
                            class="ml-4 text-xl transition-transform duration-200 flex-shrink-0"
                            :style="{ transform: openIndex === i ? 'rotate(45deg)' : 'rotate(0deg)' }"
                        >+</span>
                    </button>
                    <div
                        v-show="openIndex === i"
                        class="px-5 pb-5"
                    >
                        <p
                            class="opacity-70 leading-relaxed"
                            :style="{ fontFamily: 'var(--font-body)' }"
                            :contenteditable="editable"
                            @blur="onEditItem($event, i, 'answer')"
                        >{{ item.answer }}</p>
                    </div>
                </div>
            </div>
            </div>
        </div>
    </section>
</template>
