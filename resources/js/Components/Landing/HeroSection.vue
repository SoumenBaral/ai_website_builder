<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { Link } from '@inertiajs/vue3';
import Icon from './Icon.vue';
import EditorMockup from './EditorMockup.vue';
import { heroPrompts, stats } from './content';
import { useLandingLinks } from './useLandingLinks';
import { prefersReducedMotion, hasFinePointer } from './useLandingEffects';

const { user, ctaHref } = useLandingLinks();

const section = ref(null);
const tilt = ref(null);
const typed = ref(null);

const headline = [
    { text: 'Stunning' },
    { text: 'websites,' },
    { br: true },
    { text: 'built by AI', accent: true },
    { text: 'in' },
    { text: 'seconds.' },
];

const cleanups = [];
const isPlaying = () => section.value?.classList.contains('is-playing') ?? true;

onMounted(() => {
    const reduce = prefersReducedMotion();

    /* Typing prompt — writes textContent directly, idles while off-screen */
    if (reduce) {
        typed.value.textContent = heroPrompts[0];
    } else {
        let p = 0, len = 0, deleting = false, timer = 0;
        const step = () => {
            if (!isPlaying()) { timer = setTimeout(step, 400); return; }
            const text = heroPrompts[p];
            len += deleting ? -1 : 1;
            typed.value.textContent = text.slice(0, len);
            let delay = deleting ? 28 : 55 + Math.random() * 45;
            if (!deleting && len === text.length) { deleting = true; delay = 2200; }
            else if (deleting && len === 0) { deleting = false; p = (p + 1) % heroPrompts.length; delay = 350; }
            timer = setTimeout(step, delay);
        };
        timer = setTimeout(step, 1000);
        cleanups.push(() => clearTimeout(timer));
    }

    /* Mockup tilt: unfolds as you scroll, follows the pointer on desktop */
    if (reduce) return;
    const el = tilt.value;
    const pointer = { x: 0, y: 0 };
    let frame = 0;
    const render = () => {
        frame = 0;
        if (!isPlaying()) return;
        const unfold = Math.max(0, 1 - window.scrollY / 520);
        el.style.setProperty('--rx', `${(unfold * 12 - pointer.y * 3).toFixed(2)}deg`);
        el.style.setProperty('--ry', `${(pointer.x * 4).toFixed(2)}deg`);
    };
    const schedule = () => { if (!frame) frame = requestAnimationFrame(render); };
    window.addEventListener('scroll', schedule, { passive: true });
    cleanups.push(() => { window.removeEventListener('scroll', schedule); cancelAnimationFrame(frame); });

    if (hasFinePointer()) {
        const onMove = (e) => {
            pointer.x = e.clientX / window.innerWidth - 0.5;
            pointer.y = e.clientY / window.innerHeight - 0.5;
            schedule();
        };
        const onLeave = () => { pointer.x = 0; pointer.y = 0; schedule(); };
        section.value.addEventListener('pointermove', onMove, { passive: true });
        section.value.addEventListener('pointerleave', onLeave);
        cleanups.push(() => {
            section.value?.removeEventListener('pointermove', onMove);
            section.value?.removeEventListener('pointerleave', onLeave);
        });
    }
    render();
});

onBeforeUnmount(() => { while (cleanups.length) cleanups.pop()(); });
</script>

<template>
    <section ref="section" data-play class="hero relative isolate overflow-hidden pb-16 pt-28 sm:pb-24 sm:pt-36 lg:pt-40">
        <!-- Background: static gradients + two transform-only blobs (no blur filters) -->
        <div class="pointer-events-none absolute inset-0 -z-10" aria-hidden="true">
            <div class="hero-grid absolute inset-0"></div>
            <div class="hero-beam absolute inset-x-0 top-0 h-[760px]"></div>
            <div class="blob blob-a"></div>
            <div class="blob blob-b"></div>
            <div class="noise absolute inset-0"></div>
            <div class="absolute inset-x-0 bottom-0 h-40 bg-gradient-to-b from-transparent to-lp-bg"></div>
        </div>

        <div class="mx-auto max-w-6xl px-4 text-center sm:px-6">
            <Link :href="ctaHref" class="intro badge group" style="--d: 0ms">
                <span class="badge-tag">New</span>
                <span class="text-lp-text">Edit any section with AI</span>
                <Icon name="arrow" :stroke="2" class="size-3.5 text-lp-subtle transition-transform duration-300 group-hover:translate-x-0.5" />
            </Link>

            <h1 class="display mx-auto mt-7 max-w-4xl">
                <template v-for="(w, i) in headline" :key="i">
                    <br v-if="w.br" class="hidden sm:block" />
                    <span v-else :class="['word', w.accent && 'serif text-gradient']" :style="{ '--i': i }">{{ w.text }}</span>
                </template>
            </h1>

            <p class="intro mx-auto mt-6 max-w-xl text-base leading-relaxed text-lp-muted sm:text-lg" style="--d: 380ms">
                Describe your idea in plain English. VibeWeb designs, writes and assembles a complete, editable website — ready to publish.
            </p>

            <!-- Prompt box with a slowly rotating gradient border -->
            <div class="intro mx-auto mt-9 max-w-2xl" style="--d: 480ms">
                <div class="prompt">
                    <div class="flex min-w-0 flex-1 items-start gap-3 px-4 py-3.5 text-left sm:items-center">
                        <Icon name="sparkles" class="mt-0.5 size-5 shrink-0 text-lp-accent sm:mt-0" />
                        <p class="min-h-[3rem] min-w-0 text-[15px] leading-6 text-lp-fg sm:min-h-0 sm:truncate">
                            <span class="text-lp-subtle">Build me </span><span ref="typed"></span><span class="caret" aria-hidden="true"></span>
                        </p>
                    </div>
                    <Link :href="ctaHref" class="btn btn-primary m-1.5 h-11 shrink-0 px-5 text-sm sm:m-0 sm:mr-1.5">
                        {{ user ? 'Open dashboard' : 'Generate' }}
                        <Icon name="arrow" :stroke="2.2" class="size-4" />
                    </Link>
                </div>
            </div>

            <div class="intro mt-6 flex flex-wrap items-center justify-center gap-x-5 gap-y-3 text-sm text-lp-subtle" style="--d: 580ms">
                <a href="/demo" target="_blank" rel="noopener" class="group inline-flex items-center gap-2 font-medium text-lp-text transition hover:text-lp-fg">
                    <span class="flex size-7 items-center justify-center rounded-full border border-lp-ink/10 bg-lp-ink/5 transition group-hover:border-lp-ink/20">
                        <Icon name="play" solid class="ml-0.5 size-3" />
                    </span>
                    See live demo
                </a>
                <span class="hidden h-4 w-px bg-lp-ink/10 sm:block"></span>
                <span class="inline-flex items-center gap-1.5">
                    <Icon name="check" :stroke="2.4" class="size-3.5 text-emerald-500" />
                    Free plan · No credit card
                </span>
            </div>

            <!-- Editor mockup + floating AI status cards -->
            <div class="intro relative mx-auto mt-14 max-w-5xl sm:mt-20" style="--d: 700ms">
                <div class="mockup-glow" aria-hidden="true"></div>
                <div ref="tilt" class="tilt">
                    <EditorMockup />
                </div>

                <div class="float-card float-1 hidden lg:flex" aria-hidden="true">
                    <span class="icon-solid size-8 rounded-lg"><Icon name="grid" :stroke="1.8" class="size-4" /></span>
                    <span class="text-left"><span class="block text-[13px] font-semibold text-lp-fg">Layout planned</span><span class="block text-[11px] text-lp-subtle">6 sections · 1.2s</span></span>
                </div>
                <div class="float-card float-2 hidden lg:flex" aria-hidden="true">
                    <span class="icon-solid size-8 rounded-lg"><Icon name="pencil" :stroke="1.8" class="size-4" /></span>
                    <span class="text-left"><span class="block text-[13px] font-semibold text-lp-fg">Copy written</span><span class="block text-[11px] text-lp-subtle">Tone: warm &amp; personal</span></span>
                </div>
                <div class="float-card float-3 hidden lg:flex" aria-hidden="true">
                    <span class="relative flex size-2.5"><span class="live-ping absolute inset-0 rounded-full bg-emerald-400"></span><span class="relative size-2.5 rounded-full bg-emerald-400"></span></span>
                    <span class="text-left"><span class="block text-[13px] font-semibold text-lp-fg">Published</span><span class="block text-[11px] text-lp-subtle">lumen.vibeweb.app</span></span>
                </div>
            </div>

            <!-- Stats -->
            <dl class="mx-auto mt-16 grid max-w-4xl grid-cols-2 gap-y-8 sm:mt-24 sm:grid-cols-4">
                <div v-for="(s, i) in stats" :key="s.label" data-reveal class="stat" :style="{ '--d': `${i * 70}ms` }">
                    <dt class="order-2 mt-1 text-xs font-medium uppercase tracking-[0.14em] text-lp-subtle">{{ s.label }}</dt>
                    <dd class="order-1 text-3xl font-bold tracking-tight text-lp-fg tabular-nums sm:text-4xl">
                        {{ s.prefix || '' }}<span data-counter :data-to="s.to" :data-decimals="s.decimals || 0">{{ s.to.toFixed(s.decimals || 0) }}</span>{{ s.suffix }}
                    </dd>
                </div>
            </dl>
        </div>
    </section>
</template>

<style scoped>
/* ---------- Background ---------- */
.hero-grid {
    background-image:
        linear-gradient(to right, var(--lp-grid) 1px, transparent 1px),
        linear-gradient(to bottom, var(--lp-grid) 1px, transparent 1px);
    background-size: 64px 64px;
    -webkit-mask-image: radial-gradient(ellipse 70% 55% at 50% 0%, #000 30%, transparent 100%);
    mask-image: radial-gradient(ellipse 70% 55% at 50% 0%, #000 30%, transparent 100%);
}
.hero-beam {
    background:
        radial-gradient(ellipse 50% 60% at 50% -10%, var(--lp-beam), transparent 70%),
        radial-gradient(ellipse 30% 40% at 50% -5%, var(--lp-beam-core), transparent 70%);
}
.blob {
    position: absolute;
    border-radius: 9999px;
    will-change: transform;
    animation: drift 24s ease-in-out infinite alternate;
    animation-play-state: paused;
}
.hero.is-playing .blob { animation-play-state: running; }
.blob-a { width: 46rem; height: 46rem; left: -18rem; top: 6rem; background: radial-gradient(circle, var(--lp-blob-a), transparent 62%); }
.blob-b {
    width: 40rem; height: 40rem; right: -16rem; top: 14rem;
    background: radial-gradient(circle, var(--lp-blob-b), transparent 62%);
    animation-duration: 30s;
    animation-direction: alternate-reverse;
}
@keyframes drift {
    from { transform: translate3d(0, 0, 0) scale(1); }
    to { transform: translate3d(6rem, -3rem, 0) scale(1.12); }
}
.noise {
    opacity: var(--lp-noise);
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='160' height='160'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='.9' numOctaves='2' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E");
}

/* ---------- Headline ---------- */
.word {
    display: inline-block;
    margin-right: 0.22em;
    animation: lp-rise 1s var(--ease-out) both;
    animation-delay: calc(120ms + var(--i) * 70ms);
}
.word:last-child { margin-right: 0; }

.badge {
    display: inline-flex;
    align-items: center;
    gap: 0.6rem;
    border-radius: 9999px;
    border: 1px solid rgb(var(--lp-ink) / 0.1);
    background: rgb(var(--lp-surface) / 0.6);
    padding: 0.3rem 0.85rem 0.3rem 0.3rem;
    font-size: 0.8125rem;
    transition: border-color 0.2s, background-color 0.2s;
}
.badge:hover { border-color: rgb(var(--lp-ink) / 0.2); }
.badge-tag {
    border-radius: 9999px;
    background: linear-gradient(100deg, #6366f1, #a855f7);
    padding: 0.15rem 0.55rem;
    font-size: 0.6875rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: #fff;
}

/* ---------- Prompt box: rotating conic border (only while on screen) ---------- */
@property --angle {
    syntax: '<angle>';
    initial-value: 0deg;
    inherits: false;
}
.prompt {
    display: flex;
    flex-direction: column;
    align-items: stretch;
    border-radius: 1rem;
    border: 1px solid transparent;
    background:
        linear-gradient(rgb(var(--lp-surface)), rgb(var(--lp-surface))) padding-box,
        conic-gradient(from var(--angle), rgb(var(--lp-ink) / 0.08) 0%, #818cf8 12%, #e879f9 24%, rgb(var(--lp-ink) / 0.08) 40%, rgb(var(--lp-ink) / 0.08) 100%) border-box;
    box-shadow: 0 24px 60px -24px rgb(139 92 246 / 0.5), 0 0 0 6px rgb(139 92 246 / 0.05);
    animation: spin-border 6s linear infinite;
    animation-play-state: paused;
}
.hero.is-playing .prompt { animation-play-state: running; }
@keyframes spin-border { to { --angle: 360deg; } }
@media (min-width: 640px) {
    .prompt { flex-direction: row; align-items: center; height: 3.75rem; }
}

/* ---------- Mockup ---------- */
.tilt {
    --rx: 12deg;
    --ry: 0deg;
    transform: perspective(1800px) rotateX(var(--rx)) rotateY(var(--ry));
    transform-origin: 50% 0%;
    transition: transform 0.5s var(--ease-out);
    will-change: transform;
}
.mockup-glow {
    position: absolute;
    inset: 8% 4% -8%;
    z-index: -1;
    border-radius: 9999px;
    background: radial-gradient(ellipse at center, rgb(139 92 246 / 0.35), rgb(99 102 241 / 0.12) 45%, transparent 70%);
}
.float-card {
    position: absolute;
    z-index: 2;
    align-items: center;
    gap: 0.75rem;
    border-radius: 0.9rem;
    border: 1px solid rgb(var(--lp-ink) / 0.1);
    background: rgb(var(--lp-surface) / 0.92);
    padding: 0.6rem 1rem 0.6rem 0.6rem;
    box-shadow: 0 20px 40px -16px rgb(0 0 0 / 0.35);
    animation: lp-float 5s ease-in-out infinite alternate;
    animation-play-state: paused;
}
.hero.is-playing .float-card { animation-play-state: running; }
.float-1 { left: -2.5rem; top: -1.75rem; }
.float-2 { right: -3rem; bottom: 8%; animation-duration: 6s; animation-delay: -2s; }
.float-3 { left: -2.5rem; bottom: 8%; animation-duration: 7s; animation-delay: -4s; }
.float-3 { padding-left: 1rem; }

/* ---------- Stats ---------- */
.stat { display: flex; flex-direction: column; align-items: center; padding: 0 0.5rem; }
@media (min-width: 640px) {
    .stat + .stat { border-left: 1px solid rgb(var(--lp-ink) / 0.08); }
}

@media (max-width: 767px) {
    .blob { animation: none; }
    .tilt { transition: none; }
    .hero-grid { background-size: 44px 44px; }
}
@media (prefers-reduced-motion: reduce) {
    .tilt { --rx: 0deg !important; transform: none; }
}
</style>
