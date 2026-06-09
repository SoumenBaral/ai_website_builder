<script setup>
const props = defineProps({
    text: String,
    editable: { type: Boolean, default: false },
    id: String,
});
const emit = defineEmits(['update:props']);

function onEdit(e, key) {
    emit('update:props', { [key]: e.target.innerText });
}
</script>

<template>
    <footer class="py-8 px-6 text-center" :style="{ background: 'var(--secondary, #0f172a)', color: '#94a3b8' }">
        <p
            class="text-sm"
            :style="{ fontFamily: 'var(--font-body)' }"
            :contenteditable="editable"
            @blur="onEdit($event, 'text')"
        >{{ text }}</p>
    </footer>
</template>
