<script setup>
const props = defineProps({
    heading: String,
    columns: { type: Number, default: 3 },
    images: Array,
    editable: { type: Boolean, default: false },
    id: String,
});
const emit = defineEmits(['update:props']);

function onEdit(e, key) {
    emit('update:props', { [key]: e.target.innerText });
}

const gridClass = {
    2: 'grid-cols-1 md:grid-cols-2',
    3: 'grid-cols-1 md:grid-cols-2 lg:grid-cols-3',
    4: 'grid-cols-1 md:grid-cols-2 lg:grid-cols-4',
};
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

            <div class="grid gap-4" :class="gridClass[columns] || gridClass[3]">
                <div
                    v-for="(img, i) in images"
                    :key="i"
                    class="group relative overflow-hidden"
                    :style="{ borderRadius: 'var(--radius)' }"
                >
                    <img
                        :src="img.src"
                        :alt="img.caption"
                        class="w-full h-64 object-cover transition-transform duration-300 group-hover:scale-105"
                    />
                    <div class="absolute inset-0 bg-black/0 group-hover:bg-black/40 transition-all flex items-end">
                        <p
                            v-if="img.caption"
                            class="p-4 text-white text-sm opacity-0 group-hover:opacity-100 transition-opacity"
                            :style="{ fontFamily: 'var(--font-body)' }"
                        >{{ img.caption }}</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
