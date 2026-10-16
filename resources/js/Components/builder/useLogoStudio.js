import { ref } from 'vue';

// Shared open-state for the Logo Studio, so the Inspector button, the AI
// assist box and the prompt dock can all open it.
const open = ref(false);
const hint = ref('');

export function useLogoStudio() {
    function openStudio(text = '') {
        hint.value = text;
        open.value = true;
    }
    return { open, hint, openStudio };
}
