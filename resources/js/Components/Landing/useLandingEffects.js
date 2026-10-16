import { onMounted, onBeforeUnmount } from 'vue';

export const prefersReducedMotion = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;
export const hasFinePointer = () => window.matchMedia('(hover: hover) and (pointer: fine)').matches;

/**
 * Page-wide, attribute-driven effects. Nothing here touches Vue state, so
 * scrolling and pointer movement never trigger a re-render.
 *
 *   [data-reveal]    fades/slides in once when it enters the viewport
 *   [data-play]      gets `.is-playing` only while on screen (pauses loops)
 *   [data-counter]   counts up from 0 to data-to (data-decimals optional)
 *   [data-spotlight] child `.spot` cards get --mx / --my for a cursor glow
 *
 * Elements carrying these attributes must not have dynamic `:class`
 * bindings, otherwise Vue would overwrite the classes added here.
 */
export function useLandingEffects(rootRef) {
    const cleanups = [];

    onMounted(() => {
        const root = rootRef.value;
        const reduceMotion = prefersReducedMotion();
        const hasIO = 'IntersectionObserver' in window;

        // ---- Reveal on scroll ----
        if (hasIO && !reduceMotion) {
            root.classList.add('reveal-ready');
            const io = new IntersectionObserver((entries) => {
                for (const entry of entries) {
                    if (!entry.isIntersecting) continue;
                    entry.target.classList.add('is-visible');
                    io.unobserve(entry.target);
                }
            }, { rootMargin: '0px 0px -8% 0px' });
            root.querySelectorAll('[data-reveal]').forEach((el) => io.observe(el));
            cleanups.push(() => io.disconnect());
        }

        // ---- Play looping animations only while visible ----
        const playables = root.querySelectorAll('[data-play]');
        if (hasIO) {
            const io = new IntersectionObserver((entries) => {
                for (const entry of entries) entry.target.classList.toggle('is-playing', entry.isIntersecting);
            }, { rootMargin: '120px 0px' });
            playables.forEach((el) => io.observe(el));
            cleanups.push(() => io.disconnect());
        } else {
            playables.forEach((el) => el.classList.add('is-playing'));
        }

        // ---- Count-up numbers ----
        const count = (el) => {
            const to = parseFloat(el.dataset.to);
            const decimals = parseInt(el.dataset.decimals || '0', 10);
            if (reduceMotion) { el.textContent = to.toFixed(decimals); return; }
            const start = performance.now();
            const tick = (now) => {
                const t = Math.min(1, (now - start) / 1600);
                el.textContent = (to * (1 - Math.pow(1 - t, 4))).toFixed(decimals);
                if (t < 1) requestAnimationFrame(tick);
            };
            requestAnimationFrame(tick);
        };
        const counters = root.querySelectorAll('[data-counter]');
        if (hasIO) {
            const io = new IntersectionObserver((entries) => {
                for (const entry of entries) {
                    if (!entry.isIntersecting) continue;
                    count(entry.target);
                    io.unobserve(entry.target);
                }
            }, { threshold: 0.6 });
            counters.forEach((el) => io.observe(el));
            cleanups.push(() => io.disconnect());
        } else {
            counters.forEach(count);
        }

        // ---- Cursor spotlight on cards ----
        if (hasFinePointer()) {
            root.querySelectorAll('[data-spotlight]').forEach((group) => {
                const onMove = (e) => {
                    const card = e.target.closest('.spot');
                    if (!card) return;
                    const r = card.getBoundingClientRect();
                    card.style.setProperty('--mx', `${e.clientX - r.left}px`);
                    card.style.setProperty('--my', `${e.clientY - r.top}px`);
                };
                group.addEventListener('pointermove', onMove, { passive: true });
                cleanups.push(() => group.removeEventListener('pointermove', onMove));
            });
        }
    });

    onBeforeUnmount(() => {
        while (cleanups.length) cleanups.pop()();
    });
}
