<script setup>
import { computed } from 'vue';

const props = defineProps({
    heading: String,
    items: Array,
    image: String,
    columns: { type: Number, default: 4 },
    editable: { type: Boolean, default: false },
    id: String,
});

const gridClass = computed(() => ({
    2: 'grid-cols-2',
    3: 'grid-cols-2 md:grid-cols-3',
    4: 'grid-cols-2 md:grid-cols-4',
    5: 'grid-cols-2 md:grid-cols-5',
    6: 'grid-cols-2 md:grid-cols-3 lg:grid-cols-6',
}[props.columns] || 'grid-cols-2 md:grid-cols-4'));
const emit = defineEmits(['update:props']);

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
    <section class="relative py-16 px-6 overflow-hidden" :style="{ background: 'var(--bg)', color: 'var(--text)' }">
        <template v-if="image">
            <div class="absolute inset-0 bg-cover bg-center" :style="{ backgroundImage: `url(${image})` }"></div>
            <div class="absolute inset-0 bg-black/60"></div>
        </template>
        <div class="relative z-10 max-w-6xl mx-auto">
            <h2
                v-if="heading"
                class="text-3xl md:text-4xl font-bold text-center mb-12"
                :style="{ fontFamily: 'var(--font-heading)' }"
                :contenteditable="editable"
                @blur="onEdit($event, 'heading')"
            >{{ heading }}</h2>

            <div class="grid gap-8" :class="gridClass">
                <div
                    v-for="(item, i) in items"
                    :key="i"
                    class="text-center p-6"
                >
                    <div
                        class="text-4xl md:text-5xl font-bold mb-2"
                        :style="{ fontFamily: 'var(--font-heading)', color: 'var(--primary)' }"
                        :contenteditable="editable"
                        @blur="onEditItem($event, i, 'value')"
                    >{{ item.value }}</div>
                    <div
                        class="text-sm uppercase tracking-wider opacity-60 font-medium"
                        :style="{ fontFamily: 'var(--font-body)' }"
                        :contenteditable="editable"
                        @blur="onEditItem($event, i, 'label')"
                    >{{ item.label }}</div>
                </div>
            </div>
        </div>
    </section>
</template>
