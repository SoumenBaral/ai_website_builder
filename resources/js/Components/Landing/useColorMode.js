import { ref, computed, watch, nextTick, onMounted, onBeforeUnmount } from 'vue';
import { prefersReducedMotion } from './useLandingEffects';

const STORAGE_KEY = 'aiwb-landing-theme';
const BG = { dark: '#07070b', light: '#fafafc' };

function readStored() {
    try {
        const value = localStorage.getItem(STORAGE_KEY);
        return value === 'light' || value === 'dark' ? value : null;
    } catch {
        return null; // storage blocked (private mode, etc.)
    }
}

function systemTheme() {
    return window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark';
}

// Shared, module-level state: the navbar toggle and the page root read the same ref.
// It is resolved synchronously before the first render, so there is no theme flash.
const theme = ref(readStored() ?? systemTheme());
let userChose = readStored() !== null;

function setTheme(value) {
    theme.value = value;
    userChose = true;
    try { localStorage.setItem(STORAGE_KEY, value); } catch { /* ignore */ }
}

export function useColorMode() {
    const isDark = computed(() => theme.value === 'dark');

    /**
     * Toggle with a circular "ink spread" from the click point using the
     * View Transitions API; falls back to an instant switch.
     */
    function toggle(event) {
        const next = isDark.value ? 'light' : 'dark';
        if (!document.startViewTransition || prefersReducedMotion()) {
            setTheme(next);
            return;
        }
        const rect = event?.currentTarget?.getBoundingClientRect?.();
        const x = rect ? rect.left + rect.width / 2 : window.innerWidth - 40;
        const y = rect ? rect.top + rect.height / 2 : 32;
        const radius = Math.hypot(Math.max(x, window.innerWidth - x), Math.max(y, window.innerHeight - y));

        const transition = document.startViewTransition(async () => {
            setTheme(next);
            await nextTick();
        });
        transition.ready.then(() => {
            document.documentElement.animate(
                { clipPath: [`circle(0px at ${x}px ${y}px)`, `circle(${radius}px at ${x}px ${y}px)`] },
                { duration: 650, easing: 'cubic-bezier(0.22, 1, 0.36, 1)', pseudoElement: '::view-transition-new(root)' },
            );
        }).catch(() => { /* transition skipped */ });
    }

    return { theme, isDark, toggle };
}

/**
 * Keeps <html> in sync with the landing theme (overscroll colour + browser
 * UI colour) and follows OS changes until the visitor picks a theme.
 * Call once from the page component.
 */
export function useDocumentTheme() {
    let meta;
    let prevBg = '';
    let stop;
    const media = window.matchMedia('(prefers-color-scheme: light)');
    const onSystemChange = (e) => { if (!userChose) theme.value = e.matches ? 'light' : 'dark'; };

    onMounted(() => {
        prevBg = document.documentElement.style.backgroundColor;
        meta = document.querySelector('meta[name="theme-color"]');
        if (!meta) {
            meta = document.createElement('meta');
            meta.name = 'theme-color';
            document.head.appendChild(meta);
        }
        stop = watch(theme, (value) => {
            document.documentElement.style.backgroundColor = BG[value];
            meta.content = BG[value];
        }, { immediate: true });
        media.addEventListener('change', onSystemChange);
    });

    onBeforeUnmount(() => {
        stop?.();
        media.removeEventListener('change', onSystemChange);
        document.documentElement.style.backgroundColor = prevBg;
        meta?.remove();
    });
}
