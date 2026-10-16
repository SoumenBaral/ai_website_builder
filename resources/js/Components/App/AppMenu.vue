<script setup>
import { ref, onBeforeUnmount } from 'vue';

/*
 * Small popover menu. The trigger goes in the `trigger` slot (receives
 * { open, toggle }); items go in the default slot (receives { close }).
 * Closes on outside click, Escape, or after an item is chosen.
 */
const props = defineProps({
    align: { type: String, default: 'right' }, // 'right' | 'left'
    placement: { type: String, default: 'bottom' }, // 'bottom' | 'top'
    width: { type: String, default: 'w-52' },
});

const emit = defineEmits(['open-change']);
const open = ref(false);
const root = ref(null);

const onDocClick = (e) => { if (!root.value?.contains(e.target)) close(); };
const onKey = (e) => { if (e.key === 'Escape') close(); };

function toggle() { open.value ? close() : show(); }
function show() {
    open.value = true;
    emit('open-change', true);
    document.addEventListener('pointerdown', onDocClick);
    document.addEventListener('keydown', onKey);
}
function close() {
    if (open.value) emit('open-change', false);
    open.value = false;
    document.removeEventListener('pointerdown', onDocClick);
    document.removeEventListener('keydown', onKey);
}
onBeforeUnmount(close);
</script>

<template>
    <div ref="root" class="relative">
        <slot name="trigger" :open="open" :toggle="toggle" />
        <Transition name="menu">
            <div
                v-if="open"
                role="menu"
                :class="[
                    'menu-panel absolute z-50 p-1.5',
                    width,
                    align === 'right' ? 'right-0' : 'left-0',
                    placement === 'top' ? 'bottom-full mb-2 origin-bottom' : 'top-full mt-2 origin-top',
                ]"
            >
                <slot :close="close" />
            </div>
        </Transition>
    </div>
</template>

<style scoped>
.menu-panel {
    border-radius: 0.9rem;
    border: 1px solid rgb(var(--lp-ink) / 0.1);
    background: rgb(var(--lp-surface));
    box-shadow: 0 20px 50px -12px rgb(0 0 0 / 0.35), 0 0 0 1px rgb(0 0 0 / 0.02);
}
.menu-enter-active { transition: opacity 0.15s ease, transform 0.2s cubic-bezier(0.22, 1, 0.36, 1); }
.menu-leave-active { transition: opacity 0.1s ease; }
.menu-enter-from { opacity: 0; transform: scale(0.96); }
.menu-leave-to { opacity: 0; }

/* Shared item style for anything rendered inside a menu */
:slotted(.menu-item) {
    display: flex;
    width: 100%;
    align-items: center;
    gap: 0.65rem;
    border-radius: 0.55rem;
    padding: 0.5rem 0.65rem;
    text-align: left;
    font-size: 0.875rem;
    color: rgb(var(--lp-text));
    transition: background-color 0.15s, color 0.15s;
}
:slotted(.menu-item:hover), :slotted(.menu-item:focus-visible) { background: rgb(var(--lp-ink) / 0.06); color: rgb(var(--lp-fg)); outline: none; }
:slotted(.menu-item--danger) { color: rgb(244 63 94); }
:slotted(.menu-item--danger:hover) { background: rgb(244 63 94 / 0.1); color: rgb(244 63 94); }
:slotted(.menu-sep) { margin: 0.35rem 0.25rem; height: 1px; background: rgb(var(--lp-ink) / 0.08); }
</style>
