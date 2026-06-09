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

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div
                    v-for="(item, i) in items"
                    :key="i"
                    class="text-center p-6 rounded-lg border border-gray-100 hover:shadow-lg transition-shadow"
                    :style="{ borderRadius: 'var(--radius)' }"
                >
                    <div class="text-4xl mb-4">{{ item.icon }}</div>
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
    </section>
</template>
