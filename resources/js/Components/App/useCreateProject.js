import { ref } from 'vue';
import { router } from '@inertiajs/vue3';

// Shared open-state for the "New website" dialog, so the sidebar button
// (on any page) and the dashboard can both trigger it.
const open = ref(false);

export function useCreateProject() {
    function openCreate() {
        if (route().current('dashboard')) {
            open.value = true;
        } else {
            router.visit(route('dashboard', { new: 1 }));
        }
    }
    return { open, openCreate };
}
