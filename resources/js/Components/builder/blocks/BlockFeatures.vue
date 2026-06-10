<script setup>
import { computed } from 'vue';
import { useImageStyle } from '@/composables/useImageStyle.js';

const props = defineProps({
    heading: String,
    items: Array,
    image: String,
    columns: { type: Number, default: 3 },
    align: { type: String, default: 'center' },
    imageRadius: { type: String, default: '' },
    imageShadow: { type: String, default: '' },
    imageBorder: { type: String, default: '' },
    editable: { type: Boolean, default: false },
    id: String,
});
const emit = defineEmits(['update:props']);
const { imageClasses } = useImageStyle(props);

function onEdit(e, key) { emit('update:props', { [key]: e.target.innerText }); }
function onEditItem(e, index, key) {
    const newItems = [...props.items];
    newItems[index] = { ...newItems[index], [key]: e.target.innerText };
    emit('update:props', { items: newItems });
}

const gridClass = computed(() => ({
    1: 'grid-cols-1',
    2: 'grid-cols-1 sm:grid-cols-2',
    3: 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3',
    4: 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-4',
}[props.columns] || 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3'));
</script>

<template>
    <section class="py-16 px-6" :style="{ background: 'var(--bg)', color: 'var(--text)' }">
        <div class="max-w-6xl mx-auto">
            <!-- Header image -->
            <img v-if="image" :src="image" alt="" class="w-full h-48 object-cover mb-10" :class="imageClasses" />

            <h2
                class="text-3xl md:text-4xl font-bold mb-12"
                :class="align === 'left' ? 'text-left' : 'text-center'"
                :style="{ fontFamily: 'var(--font-heading)' }"
                :contenteditable="editable"
                @blur="onEdit($event, 'heading')"
            >{{ heading }}</h2>

            <div class="grid gap-8" :class="gridClass">
                <div
                    v-for="(item, i) in items" :key="i"
                    class="rounded-lg border border-gray-100 hover:shadow-lg transition-shadow overflow-hidden"
                    :class="align === 'left' ? 'text-left' : 'text-center'"
                    :style="{ borderRadius: 'var(--radius)' }"
                >
                    <!-- Item image -->
                    <img v-if="item.image" :src="item.image" :alt="item.title" class="w-full h-40 object-cover" />
                    <div class="p-6">
                        <div v-if="item.icon" class="text-4xl mb-4">{{ item.icon }}</div>
                        <h3
                            class="text-xl font-semibold mb-2"
                            :style="{ fontFamily: 'var(--font-heading)' }"
                            :contenteditable="editable"
                            @blur="onEditItem($event, i, 'title')"
                        >{{ item.title }}</h3>
                        <p
                            class="opacity-70"
                            :style="{ fontFamily: 'var(--font-body)' }"
                            :contenteditable="editable"
                            @blur="onEditItem($event, i, 'description')"
                        >{{ item.description }}</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
