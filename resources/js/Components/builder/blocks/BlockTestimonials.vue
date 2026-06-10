<script setup>
import { computed } from 'vue';

const props = defineProps({
    heading: String,
    items: Array,
    columns: { type: Number, default: 3 },
    editable: { type: Boolean, default: false },
    id: String,
});

const gridClass = computed(() => ({
    1: 'grid-cols-1',
    2: 'grid-cols-1 sm:grid-cols-2',
    3: 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3',
}[props.columns] || 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3'));
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
    <section class="py-16 px-6" :style="{ background: 'var(--bg)', color: 'var(--text)' }">
        <div class="max-w-6xl mx-auto">
            <h2
                class="text-3xl md:text-4xl font-bold text-center mb-12"
                :style="{ fontFamily: 'var(--font-heading)' }"
                :contenteditable="editable"
                @blur="onEdit($event, 'heading')"
            >{{ heading }}</h2>

            <div class="grid gap-8" :class="gridClass">
                <div
                    v-for="(item, i) in items"
                    :key="i"
                    class="p-8 border border-gray-200 hover:shadow-lg transition-shadow flex flex-col"
                    :style="{ borderRadius: 'var(--radius)', background: 'var(--bg)' }"
                >
                    <div class="text-4xl mb-4 opacity-20" :style="{ color: 'var(--primary)' }">&ldquo;</div>
                    <p
                        class="italic opacity-80 flex-1 mb-6"
                        :style="{ fontFamily: 'var(--font-body)' }"
                        :contenteditable="editable"
                        @blur="onEditItem($event, i, 'quote')"
                    >{{ item.quote }}</p>
                    <div class="flex items-center gap-3 mt-auto">
                        <div
                            v-if="item.avatar"
                            class="w-12 h-12 rounded-full bg-gray-300 overflow-hidden flex-shrink-0"
                        >
                            <img :src="item.avatar" :alt="item.author" class="w-full h-full object-cover" />
                        </div>
                        <div
                            v-else
                            class="w-12 h-12 rounded-full flex items-center justify-center text-white font-bold flex-shrink-0"
                            :style="{ background: 'var(--primary)' }"
                        >{{ item.author?.charAt(0) }}</div>
                        <div>
                            <p
                                class="font-semibold text-sm"
                                :style="{ fontFamily: 'var(--font-heading)' }"
                                :contenteditable="editable"
                                @blur="onEditItem($event, i, 'author')"
                            >{{ item.author }}</p>
                            <p
                                class="text-sm opacity-60"
                                :style="{ fontFamily: 'var(--font-body)' }"
                                :contenteditable="editable"
                                @blur="onEditItem($event, i, 'role')"
                            >{{ item.role }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
