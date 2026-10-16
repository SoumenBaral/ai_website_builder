<script setup>
import Icon from '@/Components/Landing/Icon.vue';
import { useToast } from './useToast';

const { toasts, dismiss } = useToast();
const icon = { success: 'checkCircle', error: 'exclamation', info: 'sparkles' };
</script>

<template>
    <div class="pointer-events-none fixed inset-x-3 top-16 z-[80] flex flex-col items-center gap-2 sm:inset-x-auto sm:right-4 sm:items-end" aria-live="polite">
        <TransitionGroup name="toast">
            <div v-for="t in toasts" :key="t.id" :class="['toast pointer-events-auto', `toast--${t.type}`]" role="status">
                <Icon :name="icon[t.type] || 'sparkles'" :stroke="1.8" class="toast-icon size-5 shrink-0" />
                <span class="min-w-0 flex-1 text-[13px] font-medium text-lp-fg">{{ t.message }}</span>
                <a v-if="t.action" :href="t.action.href" target="_blank" rel="noopener" class="shrink-0 text-[13px] font-semibold text-lp-accent hover:opacity-80">{{ t.action.label }}</a>
                <button type="button" class="ed-icon-btn -mr-1 size-6 shrink-0" aria-label="Dismiss" @click="dismiss(t.id)"><Icon name="xMark" :stroke="2" class="size-3.5" /></button>
            </div>
        </TransitionGroup>
    </div>
</template>

<style scoped>
.toast {
    display: flex;
    width: 100%;
    max-width: 24rem;
    align-items: center;
    gap: 0.65rem;
    border-radius: 0.9rem;
    border: 1px solid rgb(var(--lp-ink) / 0.1);
    background: rgb(var(--lp-surface));
    padding: 0.65rem 0.75rem;
    box-shadow: 0 20px 50px -12px rgb(0 0 0 / 0.4);
}
.toast--success .toast-icon { color: rgb(16 185 129); }
.toast--error { border-color: rgb(244 63 94 / 0.35); }
.toast--error .toast-icon { color: rgb(244 63 94); }
.toast--info .toast-icon { color: rgb(var(--lp-accent)); }
.toast-enter-active { transition: opacity 0.25s, transform 0.35s cubic-bezier(0.22, 1, 0.36, 1); }
.toast-leave-active { transition: opacity 0.2s, transform 0.2s; }
.toast-enter-from { opacity: 0; transform: translateY(-8px) scale(0.98); }
.toast-leave-to { opacity: 0; transform: translateX(12px); }
</style>
