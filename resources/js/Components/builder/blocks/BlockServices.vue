<script setup>
const props = defineProps({
    heading: String,
    items: Array,
    editable: { type: Boolean, default: false },
    id: String,
});
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

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <div
                    v-for="(item, i) in items"
                    :key="i"
                    class="p-8 border border-gray-200 hover:shadow-xl transition-all duration-300 group"
                    :style="{ borderRadius: 'var(--radius)', background: 'var(--bg)' }"
                >
                    <div
                        class="w-14 h-14 flex items-center justify-center text-3xl mb-5 rounded-lg"
                        :style="{ background: 'color-mix(in srgb, var(--primary) 12%, transparent)', borderRadius: 'var(--radius)' }"
                    >{{ item.icon }}</div>
                    <h3
                        class="text-xl font-semibold mb-2"
                        :style="{ fontFamily: 'var(--font-heading)' }"
                        :contenteditable="editable"
                        @blur="onEditItem($event, i, 'title')"
                    >{{ item.title }}</h3>
                    <p
                        class="opacity-70 mb-4"
                        :style="{ fontFamily: 'var(--font-body)' }"
                        :contenteditable="editable"
                        @blur="onEditItem($event, i, 'description')"
                    >{{ item.description }}</p>
                    <p
                        v-if="item.price"
                        class="text-lg font-bold"
                        :style="{ color: 'var(--primary)', fontFamily: 'var(--font-heading)' }"
                        :contenteditable="editable"
                        @blur="onEditItem($event, i, 'price')"
                    >{{ item.price }}</p>
                </div>
            </div>
        </div>
    </section>
</template>
