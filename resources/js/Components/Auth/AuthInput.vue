<script setup>
import { ref, computed, onMounted } from 'vue';
import Icon from '@/Components/Landing/Icon.vue';

const model = defineModel({ type: String, default: '' });

const props = defineProps({
    id: { type: String, required: true },
    label: { type: String, required: true },
    type: { type: String, default: 'text' },
    icon: { type: String, default: null },
    error: { type: String, default: null },
    placeholder: { type: String, default: null },
    autocomplete: { type: String, default: null },
    autofocus: { type: Boolean, default: false },
    required: { type: Boolean, default: false },
});

const input = ref(null);
const revealed = ref(false);
const isPassword = computed(() => props.type === 'password');
const inputType = computed(() => (isPassword.value && revealed.value ? 'text' : props.type));

// Native `autofocus` doesn't fire on Inertia (client-side) page visits.
onMounted(() => { if (props.autofocus) input.value?.focus(); });
defineExpose({ focus: () => input.value?.focus() });
</script>

<template>
    <div>
        <div class="flex items-center justify-between gap-3">
            <label :for="id" class="text-sm font-medium text-lp-fg">{{ label }}</label>
            <slot name="label-end" />
        </div>

        <div :class="['field mt-2', { 'field--error': error }]">
            <Icon v-if="icon" :name="icon" :stroke="1.6" class="field-icon" />
            <input
                :id="id"
                ref="input"
                v-model="model"
                :type="inputType"
                :autocomplete="autocomplete"
                :placeholder="placeholder"
                :required="required"
                :aria-invalid="error ? 'true' : undefined"
                :aria-describedby="error ? `${id}-error` : undefined"
                :class="['field-input', icon && 'has-icon', isPassword && 'has-action']"
            />
            <button
                v-if="isPassword"
                type="button"
                class="field-action"
                :aria-label="revealed ? 'Hide password' : 'Show password'"
                :aria-pressed="revealed"
                @click="revealed = !revealed"
            >
                <Icon :name="revealed ? 'eyeSlash' : 'eye'" :stroke="1.6" class="size-[18px]" />
            </button>
        </div>

        <slot name="below" />

        <Transition name="err">
            <p v-if="error" :id="`${id}-error`" role="alert" class="mt-2 flex items-start gap-1.5 text-[13px] font-medium text-rose-500">
                <Icon name="exclamation" :stroke="1.8" class="mt-px size-4 shrink-0" />{{ error }}
            </p>
        </Transition>
    </div>
</template>

<style scoped>
/* The wrapper carries the border so the focus ring can't be overridden by @tailwindcss/forms */
.field {
    position: relative;
    display: flex;
    align-items: center;
    height: 3rem;
    border-radius: 0.75rem;
    border: 1px solid rgb(var(--lp-ink) / 0.12);
    background: rgb(var(--lp-surface));
    box-shadow: 0 1px 2px rgb(0 0 0 / 0.04);
    transition: border-color 0.2s, box-shadow 0.2s, background-color 0.2s;
}
.field:hover { border-color: rgb(var(--lp-ink) / 0.2); }
.field:focus-within {
    border-color: rgb(139 92 246 / 0.75);
    box-shadow: 0 0 0 4px rgb(139 92 246 / 0.15);
}
.field--error,
.field--error:hover { border-color: rgb(244 63 94 / 0.7); }
.field--error:focus-within { border-color: rgb(244 63 94 / 0.8); box-shadow: 0 0 0 4px rgb(244 63 94 / 0.14); }

.field-icon {
    position: absolute;
    left: 0.95rem;
    width: 18px;
    height: 18px;
    color: rgb(var(--lp-subtle));
    pointer-events: none;
    transition: color 0.2s;
}
.field:focus-within .field-icon { color: rgb(var(--lp-accent)); }

.field-input {
    width: 100%;
    height: 100%;
    border: 0 !important;
    border-radius: inherit;
    background: transparent !important;
    padding: 0 1rem;
    font-size: 15px;
    color: rgb(var(--lp-fg));
    box-shadow: none !important;
    outline: none !important;
}
.field-input.has-icon { padding-left: 2.75rem; }
.field-input.has-action { padding-right: 3rem; }
.field-input::placeholder { color: rgb(var(--lp-subtle) / 0.8); }

/* Keep browser autofill on-theme (Chrome paints a light-yellow box otherwise) */
.field-input:-webkit-autofill,
.field-input:-webkit-autofill:hover,
.field-input:-webkit-autofill:focus {
    -webkit-text-fill-color: rgb(var(--lp-fg));
    -webkit-box-shadow: 0 0 0 1000px rgb(var(--lp-surface)) inset !important;
    caret-color: rgb(var(--lp-fg));
    transition: background-color 9999s;
}

.field-action {
    position: absolute;
    right: 0.4rem;
    display: flex;
    width: 2.25rem;
    height: 2.25rem;
    align-items: center;
    justify-content: center;
    border-radius: 0.55rem;
    color: rgb(var(--lp-subtle));
    transition: color 0.2s, background-color 0.2s;
}
.field-action:hover { color: rgb(var(--lp-fg)); background: rgb(var(--lp-ink) / 0.06); }

.err-enter-active { transition: opacity 0.25s, transform 0.25s; }
.err-enter-from { opacity: 0; transform: translateY(-4px); }
</style>
