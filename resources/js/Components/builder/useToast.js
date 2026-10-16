import { ref } from 'vue';

// Tiny shared toast queue for the editor (replaces window.alert()).
const toasts = ref([]);
let nextId = 1;

export function useToast() {
    function notify(message, type = 'info', { duration = 4000, action = null } = {}) {
        const id = nextId++;
        toasts.value.push({ id, message, type, action });
        setTimeout(() => dismiss(id), duration);
        return id;
    }
    function dismiss(id) {
        toasts.value = toasts.value.filter((t) => t.id !== id);
    }
    return { toasts, notify, dismiss };
}
