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
                v-if="heading"
                class="text-3xl md:text-4xl font-bold text-center mb-12"
                :style="{ fontFamily: 'var(--font-heading)' }"
                :contenteditable="editable"
                @blur="onEdit($event, 'heading')"
            >{{ heading }}</h2>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
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
