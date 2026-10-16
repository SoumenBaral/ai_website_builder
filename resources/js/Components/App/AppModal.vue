<script setup>
import { ref, watch, nextTick, onBeforeUnmount } from 'vue';
import Icon from '@/Components/Landing/Icon.vue';

/*
 * Themed dialog. Rendered in place (no Teleport) so it inherits the design
 * tokens from the `.landing` root; `position: fixed` takes it out of flow.
 */
const open = defineModel({ type: Boolean, default: false });
const props = defineProps({
    title: { type: String, required: true },
    description: { type: String, default: '' },
    icon: { type: String, default: '' },
    tone: { type: String, default: 'default' }, // 'default' | 'danger'
    size: { type: String, default: 'md' }, // 'md' | 'lg'
});

const panel = ref(null);
let lastFocused = null;

const onKey = (e) => { if (e.key === 'Escape') open.value = false; };

watch(open, async (value) => {
    if (value) {
        lastFocused = document.activeElement;
        document.body.style.overflow = 'hidden';
        window.addEventListener('keydown', onKey);
        await nextTick();
        panel.value?.querySelector('[autofocus], input, textarea, button:not([data-close])')?.focus();
    } else {
        document.body.style.overflow = '';
        window.removeEventListener('keydown', onKey);
        lastFocused?.focus?.();
    }
});

onBeforeUnmount(() => {
    document.body.style.overflow = '';
    window.removeEventListener('keydown', onKey);
});
</script>

<template>
    <Transition name="modal">
        <div v-if="open" class="fixed inset-0 z-[70] flex items-end justify-center p-0 sm:items-center sm:p-6" role="dialog" aria-modal="true" :aria-label="title">
            <div class="modal-backdrop absolute inset-0" @click="open = false"></div>

            <div ref="panel" :class="['modal-panel relative w-full overflow-hidden rounded-t-3xl sm:rounded-3xl', size === 'lg' ? 'sm:max-w-xl' : 'sm:max-w-md']">
                <div class="flex items-start gap-4 px-6 pt-6 sm:px-7 sm:pt-7">
                    <span v-if="icon" :class="['flex size-11 shrink-0 items-center justify-center rounded-2xl', tone === 'danger' ? 'bg-rose-500/10 text-rose-500' : 'icon-solid']">
                        <Icon :name="icon" :stroke="1.7" class="size-5" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <h2 class="text-lg font-semibold tracking-tight text-lp-fg">{{ title }}</h2>
                        <p v-if="description" class="mt-1 text-sm leading-relaxed text-lp-muted">{{ description }}</p>
                    </div>
                    <button type="button" data-close class="-mr-2 -mt-1 flex size-9 shrink-0 items-center justify-center rounded-lg text-lp-subtle transition hover:bg-lp-ink/5 hover:text-lp-fg" aria-label="Close" @click="open = false">
                        <Icon name="xMark" :stroke="2" class="size-5" />
                    </button>
                </div>
                <div class="px-6 pb-6 pt-5 sm:px-7 sm:pb-7">
                    <slot />
                </div>
            </div>
        </div>
    </Transition>
</template>

<style scoped>
.modal-backdrop { background: rgb(5 5 10 / 0.55); }
.modal-panel {
    border: 1px solid rgb(var(--lp-ink) / 0.1);
    background: rgb(var(--lp-surface));
    box-shadow: 0 40px 100px -20px rgb(0 0 0 / 0.5);
}
.modal-enter-active, .modal-leave-active { transition: opacity 0.25s ease; }
.modal-enter-active .modal-panel, .modal-leave-active .modal-panel { transition: transform 0.35s cubic-bezier(0.22, 1, 0.36, 1), opacity 0.25s; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
.modal-enter-from .modal-panel, .modal-leave-to .modal-panel { transform: translate3d(0, 24px, 0) scale(0.98); opacity: 0; }
</style>
