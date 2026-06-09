<script setup>
const props = defineProps({
    brand: String,
    links: Array,
    ctaText: String,
    ctaHref: String,
    editable: { type: Boolean, default: false },
    id: String,
});
const emit = defineEmits(['update:props']);

function onEdit(e, key) {
    emit('update:props', { [key]: e.target.innerText });
}
</script>

<template>
    <nav class="flex items-center justify-between px-6 py-4 bg-white shadow-sm" style="background: var(--bg); color: var(--text);">
        <div
            class="text-xl font-bold"
            :style="{ fontFamily: 'var(--font-heading)' }"
            :contenteditable="editable"
            @blur="onEdit($event, 'brand')"
        >{{ brand }}</div>

        <div class="hidden md:flex items-center gap-6">
            <a
                v-for="link in links"
                :key="link.label"
                :href="editable ? undefined : link.href"
                class="text-sm hover:opacity-70 transition-opacity"
                :style="{ fontFamily: 'var(--font-body)' }"
            >{{ link.label }}</a>

            <a
                :href="editable ? undefined : ctaHref"
                class="px-4 py-2 text-sm font-medium text-white rounded-md transition-opacity hover:opacity-90"
                :style="{ background: 'var(--primary)', borderRadius: 'var(--radius)' }"
                :contenteditable="editable"
                @blur="onEdit($event, 'ctaText')"
            >{{ ctaText }}</a>
        </div>
    </nav>
</template>
