<script setup>
import { computed } from 'vue';

const props = defineProps({
    elements: { type: Array, default: () => [] },
    columns: { type: Number, default: 1 },
    gap: { type: String, default: 'md' },
    padding: { type: String, default: 'lg' },
    align: { type: String, default: 'center' },
    editable: { type: Boolean, default: false },
    id: String,
});
const emit = defineEmits(['update:props']);

const gridClass = computed(() => ({
    1: 'grid-cols-1',
    2: 'grid-cols-1 sm:grid-cols-2',
    3: 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3',
    4: 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-4',
}[props.columns] || 'grid-cols-1'));

const gapClass = computed(() => ({ sm: 'gap-2', md: 'gap-4', lg: 'gap-8', xl: 'gap-12' }[props.gap] || 'gap-4'));
const padClass = computed(() => ({ sm: 'py-8 px-4', md: 'py-12 px-6', lg: 'py-16 px-6', xl: 'py-24 px-8' }[props.padding] || 'py-16 px-6'));

// Element style helpers
function imgRadius(r) { return { none:'rounded-none', sm:'rounded-sm', md:'rounded-md', lg:'rounded-lg', xl:'rounded-xl', full:'rounded-full' }[r] || ''; }
function imgShadow(s) { return { none:'', sm:'shadow-sm', md:'shadow-md', lg:'shadow-lg', xl:'shadow-xl' }[s] || ''; }
function imgBorder(b) { return { none:'', thin:'border border-gray-200', thick:'border-2 border-gray-300' }[b] || ''; }
function textSize(s) { return { sm:'text-sm', base:'text-base', lg:'text-lg', xl:'text-xl' }[s] || 'text-base'; }
function headingSize(s) { return { xl:'text-xl', '2xl':'text-2xl', '3xl':'text-3xl', '4xl':'text-4xl' }[s] || 'text-2xl'; }
function iconSize(s) { return { '2xl':'text-2xl', '3xl':'text-3xl', '4xl':'text-4xl', '5xl':'text-5xl' }[s] || 'text-4xl'; }
function alignClass(a) { return { left:'text-left', center:'text-center', right:'text-right' }[a] || 'text-center'; }
function spacerHeight(h) { return { sm:'h-4', md:'h-8', lg:'h-16', xl:'h-24' }[h] || 'h-8'; }
function btnClass(v) {
    if (v === 'secondary') return 'inline-block px-6 py-3 font-medium transition-opacity hover:opacity-90 text-white';
    if (v === 'outline') return 'inline-block px-6 py-3 font-medium border-2 transition-opacity hover:opacity-90';
    return 'inline-block px-6 py-3 font-medium text-white transition-opacity hover:opacity-90';
}
function btnStyle(v) {
    if (v === 'secondary') return { background: 'var(--secondary)', borderRadius: 'var(--radius)' };
    if (v === 'outline') return { borderColor: 'var(--primary)', color: 'var(--primary)', borderRadius: 'var(--radius)' };
    return { background: 'var(--primary)', borderRadius: 'var(--radius)' };
}

function onEditElement(e, index, key) {
    const newElements = [...props.elements];
    newElements[index] = { ...newElements[index], [key]: e.target.innerText };
    emit('update:props', { elements: newElements });
}
</script>

<template>
    <section :class="padClass" :style="{ background: 'var(--bg)', color: 'var(--text)' }">
        <div class="max-w-6xl mx-auto">
            <!-- Empty state -->
            <div v-if="!elements?.length && editable" class="text-center py-12 text-gray-400 border-2 border-dashed border-gray-200 rounded-xl">
                <div class="text-3xl mb-2">🧩</div>
                <p class="text-sm font-medium">Custom Section</p>
                <p class="text-xs mt-1">Add elements from the Inspector panel</p>
            </div>

            <!-- Elements grid -->
            <div v-else class="grid" :class="[gridClass, gapClass]">
                <div
                    v-for="(el, i) in elements"
                    :key="i"
                    :class="el.type === 'divider' || el.type === 'spacer' ? 'col-span-full' : ''"
                >
                    <!-- Heading -->
                    <template v-if="el.type === 'heading'">
                        <h2
                            :class="[headingSize(el.size), alignClass(el.align), 'font-bold']"
                            :style="{ fontFamily: 'var(--font-heading)' }"
                            :contenteditable="editable"
                            @blur="onEditElement($event, i, 'text')"
                        >{{ el.text }}</h2>
                    </template>

                    <!-- Text -->
                    <template v-else-if="el.type === 'text'">
                        <p
                            :class="[textSize(el.size), alignClass(el.align), 'opacity-80 leading-relaxed']"
                            :style="{ fontFamily: 'var(--font-body)' }"
                            :contenteditable="editable"
                            @blur="onEditElement($event, i, 'text')"
                        >{{ el.text }}</p>
                    </template>

                    <!-- Image -->
                    <template v-else-if="el.type === 'image'">
                        <div :class="alignClass(el.align || 'center')">
                            <img
                                v-if="el.src"
                                :src="el.src"
                                :alt="el.alt || ''"
                                class="object-cover"
                                :class="[
                                    imgRadius(el.radius),
                                    imgShadow(el.shadow),
                                    imgBorder(el.border),
                                    el.width === 'half' ? 'w-1/2' : el.width === 'third' ? 'w-1/3' : 'w-full',
                                    'h-auto max-h-96',
                                ]"
                            />
                            <div v-else-if="editable" class="w-full h-40 bg-gray-100 border-2 border-dashed border-gray-200 rounded-lg flex items-center justify-center text-gray-400 text-sm">
                                Add image from Inspector
                            </div>
                        </div>
                    </template>

                    <!-- Icon -->
                    <template v-else-if="el.type === 'icon'">
                        <div :class="[alignClass(el.align || 'center'), iconSize(el.size)]">
                            {{ el.emoji }}
                        </div>
                    </template>

                    <!-- Button -->
                    <template v-else-if="el.type === 'button'">
                        <div :class="alignClass(el.align || 'center')">
                            <a
                                :href="editable ? undefined : (el.href || '#')"
                                :class="btnClass(el.variant)"
                                :style="btnStyle(el.variant)"
                                :contenteditable="editable"
                                @blur="onEditElement($event, i, 'text')"
                            >{{ el.text }}</a>
                        </div>
                    </template>

                    <!-- Divider -->
                    <template v-else-if="el.type === 'divider'">
                        <hr
                            class="border-current opacity-20"
                            :class="el.style === 'dashed' ? 'border-dashed' : el.style === 'dotted' ? 'border-dotted' : 'border-solid'"
                        />
                    </template>

                    <!-- Spacer -->
                    <template v-else-if="el.type === 'spacer'">
                        <div :class="spacerHeight(el.height)"></div>
                    </template>
                </div>
            </div>
        </div>
    </section>
</template>
