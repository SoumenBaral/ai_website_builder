import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

// Where the call-to-action buttons should send the visitor.
export function useLandingLinks() {
    const page = usePage();
    const user = computed(() => page.props.auth?.user ?? null);
    const canLogin = computed(() => Boolean(page.props.canLogin));
    const canRegister = computed(() => Boolean(page.props.canRegister));

    const ctaHref = computed(() => {
        if (user.value) return route('dashboard');
        return canRegister.value ? route('register') : route('login');
    });
    const ctaLabel = computed(() => (user.value ? 'Go to dashboard' : 'Start building free'));

    return { user, canLogin, canRegister, ctaHref, ctaLabel };
}
