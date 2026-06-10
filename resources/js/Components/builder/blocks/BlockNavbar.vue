<script setup>
import { ref } from 'vue';

const props = defineProps({
    brand: String,
    links: Array,
    ctaText: String,
    ctaHref: String,
    editable: { type: Boolean, default: false },
    id: String,
});
const emit = defineEmits(['update:props']);

const mobileOpen = ref(false);

function onEdit(e, key) {
    emit('update:props', { [key]: e.target.innerText });
}
</script>

<template>
    <nav style="background: var(--bg); color: var(--text);">
        <!-- Main bar -->
        <div class="flex items-center justify-between px-6 py-4 shadow-lg shadow-black/5">
            <div
                class="text-xl font-bold"
                :style="{ fontFamily: 'var(--font-heading)' }"
                :contenteditable="editable"
                @blur="onEdit($event, 'brand')"
            >{{ brand }}</div>

            <!-- Desktop links -->
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
                    class="px-5 py-2 text-sm font-semibold text-white transition-opacity hover:opacity-90"
                    :style="{ background: 'var(--primary)', borderRadius: 'var(--radius)' }"
                    :contenteditable="editable"
                    @blur="onEdit($event, 'ctaText')"
                >{{ ctaText }}</a>
            </div>

            <!-- Hamburger button -->
            <button
                @click="mobileOpen = !mobileOpen"
                class="md:hidden flex items-center justify-center size-10 rounded-lg transition-colors hover:bg-black/5"
                aria-label="Toggle menu"
            >
                <svg v-if="!mobileOpen" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" />
                </svg>
                <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Mobile menu -->
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0 -translate-y-1"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 -translate-y-1"
        >
            <div
                v-if="mobileOpen"
                class="md:hidden border-t px-6 py-4 shadow-inner"
                :style="{ background: 'var(--bg)', borderColor: 'rgba(0,0,0,0.06)' }"
            >
                <div class="flex flex-col gap-3">
                    <a
                        v-for="link in links"
                        :key="link.label"
                        :href="editable ? undefined : link.href"
                        class="text-sm py-2 hover:opacity-70 transition-opacity"
                        :style="{ fontFamily: 'var(--font-body)' }"
                        @click="mobileOpen = false"
                    >{{ link.label }}</a>

                    <a
                        :href="editable ? undefined : ctaHref"
                        class="mt-1 block text-center px-5 py-2.5 text-sm font-semibold text-white transition-opacity hover:opacity-90"
                        :style="{ background: 'var(--primary)', borderRadius: 'var(--radius)' }"
                        @click="mobileOpen = false"
                    >{{ ctaText }}</a>
                </div>
            </div>
        </Transition>
    </nav>
</template>
