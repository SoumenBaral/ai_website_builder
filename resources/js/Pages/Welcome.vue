<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, reactive, onMounted, onUnmounted, nextTick, computed } from 'vue';

defineProps({
    canLogin: { type: Boolean },
    canRegister: { type: Boolean },
    laravelVersion: { type: String, required: true },
    phpVersion: { type: String, required: true },
});

const mobileMenuOpen = ref(false);
const navScrolled = ref(false);
const mouse = reactive({ x: 0, y: 0 });
const scrollY = ref(0);

// Typing animation for hero subtitle
const typedTexts = [
    'a photography portfolio',
    'a SaaS landing page',
    'a restaurant website',
    'an online store',
    'a startup homepage',
];
const currentTypedIndex = ref(0);
const currentTypedText = ref('');
const isDeleting = ref(false);
let typeTimer = null;

function typeEffect() {
    const fullText = typedTexts[currentTypedIndex.value];
    if (!isDeleting.value) {
        currentTypedText.value = fullText.substring(0, currentTypedText.value.length + 1);
        if (currentTypedText.value === fullText) {
            setTimeout(() => { isDeleting.value = true; typeEffect(); }, 2000);
            return;
        }
    } else {
        currentTypedText.value = fullText.substring(0, currentTypedText.value.length - 1);
        if (currentTypedText.value === '') {
            isDeleting.value = false;
            currentTypedIndex.value = (currentTypedIndex.value + 1) % typedTexts.length;
        }
    }
    typeTimer = setTimeout(typeEffect, isDeleting.value ? 40 : 80);
}

function handleScroll() {
    navScrolled.value = window.scrollY > 20;
    scrollY.value = window.scrollY;
}

function handleMouseMove(e) {
    mouse.x = (e.clientX / window.innerWidth - 0.5) * 2;
    mouse.y = (e.clientY / window.innerHeight - 0.5) * 2;
}

// Parallax for hero elements
const heroParallax = computed(() => scrollY.value * 0.3);

onMounted(() => {
    window.addEventListener('scroll', handleScroll, { passive: true });
    window.addEventListener('mousemove', handleMouseMove, { passive: true });
    handleScroll();
    typeEffect();

    nextTick(() => {
        const observer = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('animate-visible');
                        observer.unobserve(entry.target);
                    }
                });
            },
            { threshold: 0.08, rootMargin: '0px 0px -40px 0px' }
        );
        document.querySelectorAll('.animate-on-scroll').forEach((el) => observer.observe(el));
    });
});

onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll);
    window.removeEventListener('mousemove', handleMouseMove);
    if (typeTimer) clearTimeout(typeTimer);
});

const features = [
    {
        icon: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-7"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456Z" /></svg>`,
        title: 'AI-Powered Generation',
        desc: 'Describe your website in plain English and watch AI build it in seconds. No coding skills required.',
        color: 'indigo',
    },
    {
        icon: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-7"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg>`,
        title: 'Visual Editor',
        desc: 'Fine-tune every detail with our intuitive visual editor. Inline editing, drag & drop, and real-time preview.',
        color: 'violet',
    },
    {
        icon: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-7"><path stroke-linecap="round" stroke-linejoin="round" d="M15.59 14.37a6 6 0 0 1-5.84 7.38v-4.8m5.84-2.58a14.98 14.98 0 0 0 6.16-12.12A14.98 14.98 0 0 0 9.631 8.41m5.96 5.96a14.926 14.926 0 0 1-5.841 2.58m-.119-8.54a6 6 0 0 0-7.381 5.84h4.8m2.58-5.84a14.927 14.927 0 0 1-2.58 5.84m2.699 2.7c-.103.021-.207.041-.311.06a15.09 15.09 0 0 1-2.448-2.448 14.9 14.9 0 0 1 .06-.312m-2.24 2.39a4.493 4.493 0 0 0-1.757 4.306 4.493 4.493 0 0 0 4.306-1.758M16.5 9a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Z" /></svg>`,
        title: 'One-Click Publish',
        desc: 'Export as HTML/ZIP or publish instantly with a shareable link. Your site goes live in one click.',
        color: 'emerald',
    },
    {
        icon: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-7"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25a2.25 2.25 0 0 1-2.25-2.25v-2.25Z" /></svg>`,
        title: '14+ Block Types',
        desc: 'Navbar, hero, features, gallery, pricing, testimonials, FAQ, team, and more — all ready to use.',
        color: 'blue',
    },
    {
        icon: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-7"><path stroke-linecap="round" stroke-linejoin="round" d="M4.098 19.902a3.75 3.75 0 0 0 5.304 0l6.401-6.402M6.75 21A3.75 3.75 0 0 1 3 17.25V4.125C3 3.504 3.504 3 4.125 3h5.25c.621 0 1.125.504 1.125 1.125v4.072M6.75 21a3.75 3.75 0 0 0 3.75-3.75V8.197M6.75 21h13.125c.621 0 1.125-.504 1.125-1.125v-5.25c0-.621-.504-1.125-1.125-1.125h-4.072M10.5 8.197l2.88-2.88c.438-.439 1.15-.439 1.59 0l3.712 3.713c.44.44.44 1.152 0 1.59l-2.879 2.88M6.75 17.25h.008v.008H6.75v-.008Z" /></svg>`,
        title: 'Theme Customization',
        desc: 'Colors, fonts, border radius, dark/light mode — customize every aspect of your site\'s look and feel.',
        color: 'pink',
    },
    {
        icon: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-7"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>`,
        title: 'Version History',
        desc: 'Every change is saved. Browse your revision history and restore any previous version with one click.',
        color: 'amber',
    },
];

const featureColors = {
    indigo: { bg: 'bg-indigo-50', border: 'border-indigo-100', text: 'text-indigo-600', shadow: 'shadow-indigo-500/15', glow: 'from-indigo-100/60 to-indigo-50/30' },
    violet: { bg: 'bg-violet-50', border: 'border-violet-100', text: 'text-violet-600', shadow: 'shadow-violet-500/15', glow: 'from-violet-100/60 to-violet-50/30' },
    emerald: { bg: 'bg-emerald-50', border: 'border-emerald-100', text: 'text-emerald-600', shadow: 'shadow-emerald-500/15', glow: 'from-emerald-100/60 to-emerald-50/30' },
    blue: { bg: 'bg-blue-50', border: 'border-blue-100', text: 'text-blue-600', shadow: 'shadow-blue-500/15', glow: 'from-blue-100/60 to-blue-50/30' },
    pink: { bg: 'bg-pink-50', border: 'border-pink-100', text: 'text-pink-600', shadow: 'shadow-pink-500/15', glow: 'from-pink-100/60 to-pink-50/30' },
    amber: { bg: 'bg-amber-50', border: 'border-amber-100', text: 'text-amber-600', shadow: 'shadow-amber-500/15', glow: 'from-amber-100/60 to-amber-50/30' },
};

const steps = [
    { num: '01', title: 'Describe Your Website', desc: 'Type a prompt like "A modern portfolio for a photographer" and let AI understand your vision.', icon: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-8"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" /></svg>` },
    { num: '02', title: 'AI Builds It Instantly', desc: 'Our AI generates a complete, multi-section website with real content, images, and styling.', icon: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-8"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456Z" /></svg>` },
    { num: '03', title: 'Customize & Publish', desc: 'Fine-tune with the visual editor, adjust themes, then publish or export with one click.', icon: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-8"><path stroke-linecap="round" stroke-linejoin="round" d="M15.59 14.37a6 6 0 0 1-5.84 7.38v-4.8m5.84-2.58a14.98 14.98 0 0 0 6.16-12.12A14.98 14.98 0 0 0 9.631 8.41m5.96 5.96a14.926 14.926 0 0 1-5.841 2.58m-.119-8.54a6 6 0 0 0-7.381 5.84h4.8m2.58-5.84a14.927 14.927 0 0 1-2.58 5.84" /></svg>` },
];

const plans = [
    { name: 'Free', price: '$0', period: '/month', features: ['10 AI generations/mo', 'All 14+ block types', 'HTML/ZIP export', 'Publish with shared link', 'Version history'], cta: 'Get Started Free', highlight: false },
    { name: 'Pro', price: '$19', period: '/month', features: ['100 AI generations/mo', 'Everything in Free', 'Priority AI processing', 'Analytics dashboard', 'Custom themes'], cta: 'Upgrade to Pro', highlight: true },
    { name: 'Team', price: '$49', period: '/month', features: ['500 AI generations/mo', 'Everything in Pro', 'Team collaboration', 'Priority support', 'Custom branding'], cta: 'Start Team Plan', highlight: false },
];

const stats = [
    { value: '10K+', label: 'Websites Built', icon: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5a17.92 17.92 0 0 1-8.716-2.247m0 0A9.015 9.015 0 0 1 3 12c0-1.605.42-3.113 1.157-4.418" /></svg>` },
    { value: '14+', label: 'Block Types', icon: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25a2.25 2.25 0 0 1-2.25-2.25v-2.25Z" /></svg>` },
    { value: '< 30s', label: 'Build Time', icon: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75Z" /></svg>` },
    { value: '99.9%', label: 'Uptime', icon: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" /></svg>` },
];

function scrollTo(id) {
    document.getElementById(id)?.scrollIntoView({ behavior: 'smooth' });
    mobileMenuOpen.value = false;
}
</script>

<template>
    <Head title="AiWebBuilder — Build Stunning Websites with AI in Seconds" />

    <div class="min-h-screen bg-white antialiased text-gray-900 overflow-x-hidden">
        <!-- ===== ANIMATED BACKGROUND ===== -->
        <div class="fixed inset-0 -z-10 overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-b from-white via-slate-50/50 to-white"></div>
            <div class="orb orb-1"></div>
            <div class="orb orb-2"></div>
            <div class="orb orb-3"></div>
            <div class="orb orb-4"></div>
            <div class="orb orb-5"></div>
            <div class="absolute inset-0 dot-grid"></div>
            <div class="aurora aurora-1"></div>
            <div class="aurora aurora-2"></div>
        </div>

        <!-- Floating sparkle particles -->
        <div class="fixed inset-0 -z-[5] overflow-hidden pointer-events-none">
            <div v-for="n in 12" :key="n" :class="`particle particle-${n}`"></div>
        </div>

        <!-- ===== NAVBAR ===== -->
        <nav :class="[
            'fixed top-0 z-50 w-full transition-all duration-700',
            navScrolled
                ? 'py-2 border-b border-gray-200/50 bg-white/70 backdrop-blur-2xl shadow-[0_8px_30px_rgba(99,102,241,0.06)]'
                : 'py-1 bg-transparent'
        ]">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-3">
                <Link href="/" class="group flex items-center gap-2.5">
                    <div class="logo-3d relative flex size-11 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-600 via-violet-600 to-purple-600 shadow-xl shadow-indigo-500/30 transition-all duration-500 group-hover:shadow-indigo-500/50 group-hover:scale-110 group-hover:-rotate-3">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="white" class="size-5 drop-shadow-sm">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456Z" />
                        </svg>
                    </div>
                    <span class="text-xl font-bold tracking-tight">Ai<span class="bg-gradient-to-r from-indigo-600 to-violet-600 bg-clip-text text-transparent">Web</span>Builder</span>
                </Link>

                <div class="hidden items-center gap-8 md:flex">
                    <button v-for="item in [{id:'features',l:'Features'},{id:'how-it-works',l:'How It Works'},{id:'pricing',l:'Pricing'}]" :key="item.id" @click="scrollTo(item.id)" class="nav-link text-sm font-medium text-gray-500 transition hover:text-gray-900">{{ item.l }}</button>
                    <a href="/demo" target="_blank" rel="noopener" class="nav-link text-sm font-medium text-gray-500 transition hover:text-gray-900">Demo</a>
                </div>

                <div class="hidden items-center gap-3 md:flex" v-if="canLogin">
                    <Link v-if="$page.props.auth.user" :href="route('dashboard')" class="btn-3d rounded-xl px-6 py-2.5 text-sm font-semibold text-white">Dashboard</Link>
                    <template v-else>
                        <Link :href="route('login')" class="rounded-xl px-5 py-2.5 text-sm font-semibold text-gray-600 transition-all hover:text-indigo-600 hover:bg-indigo-50/80">Log in</Link>
                        <Link v-if="canRegister" :href="route('register')" class="btn-3d rounded-xl px-6 py-2.5 text-sm font-semibold text-white">Get Started</Link>
                    </template>
                </div>

                <button @click="mobileMenuOpen = !mobileMenuOpen" class="md:hidden rounded-xl p-2.5 text-gray-500 transition hover:bg-gray-100">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                        <path v-if="!mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                        <path v-else stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <Transition enter-active-class="transition duration-300 ease-out" enter-from-class="opacity-0 -translate-y-3 scale-95" enter-to-class="opacity-100 translate-y-0 scale-100" leave-active-class="transition duration-200 ease-in" leave-from-class="opacity-100 translate-y-0 scale-100" leave-to-class="opacity-0 -translate-y-3 scale-95">
                <div v-if="mobileMenuOpen" class="border-t border-gray-100 bg-white/95 backdrop-blur-2xl px-6 py-6 md:hidden shadow-2xl shadow-gray-200/20">
                    <div class="flex flex-col gap-4">
                        <button @click="scrollTo('features')" class="text-left text-base font-medium text-gray-600">Features</button>
                        <button @click="scrollTo('how-it-works')" class="text-left text-base font-medium text-gray-600">How It Works</button>
                        <button @click="scrollTo('pricing')" class="text-left text-base font-medium text-gray-600">Pricing</button>
                        <a href="/demo" target="_blank" rel="noopener" class="text-base font-medium text-gray-600">Demo</a>
                        <div v-if="canLogin" class="mt-3 flex flex-col gap-3 border-t border-gray-100 pt-5">
                            <Link v-if="$page.props.auth.user" :href="route('dashboard')" class="btn-3d rounded-xl px-5 py-3 text-center text-sm font-semibold text-white">Dashboard</Link>
                            <template v-else>
                                <Link :href="route('login')" class="rounded-xl border border-gray-200 px-5 py-3 text-center text-sm font-semibold text-gray-600">Log in</Link>
                                <Link v-if="canRegister" :href="route('register')" class="btn-3d rounded-xl px-5 py-3 text-center text-sm font-semibold text-white">Get Started</Link>
                            </template>
                        </div>
                    </div>
                </div>
            </Transition>
        </nav>

        <!-- ===== HERO ===== -->
        <section class="relative pt-36 pb-20 lg:pt-52 lg:pb-40 overflow-hidden">
            <div class="mx-auto max-w-7xl px-6 text-center" :style="{ transform: `translateY(${-heroParallax * 0.15}px)` }">
                <!-- Badge -->
                <div class="hero-badge inline-flex items-center gap-2.5 rounded-full border border-indigo-200/70 bg-white/90 backdrop-blur-md px-5 py-2.5 text-sm font-medium text-indigo-600 shadow-xl shadow-indigo-500/[0.06] mb-10 hover:shadow-indigo-500/10 transition-shadow duration-500">
                    <span class="relative flex size-2">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-green-500 opacity-75"></span>
                        <span class="relative inline-flex size-2 rounded-full bg-green-500"></span>
                    </span>
                    Best Website Builder with AI
                </div>

                <h1 class="hero-title mx-auto max-w-5xl text-5xl font-extrabold tracking-tight sm:text-6xl lg:text-8xl leading-[1.05]">
                    <span class="text-gray-900 drop-shadow-sm">Build Stunning</span>
                    <br />
                    <span class="text-gray-900 drop-shadow-sm">Websites </span>
                    <span class="hero-gradient-text bg-gradient-to-r from-indigo-600 via-violet-600 to-purple-500 bg-clip-text text-transparent drop-shadow-sm">with AI</span>
                </h1>

                <!-- Typing animation subtitle -->
                <div class="hero-subtitle mx-auto mt-8 max-w-2xl text-lg text-gray-500 leading-relaxed sm:text-xl">
                    <p>Describe your dream website and watch AI create it instantly.</p>
                    <p class="mt-2">
                        Try: <span class="font-medium text-indigo-600">"Build me {{ currentTypedText }}"</span><span class="typing-cursor">|</span>
                    </p>
                </div>

                <div class="hero-buttons mt-12 flex flex-col items-center gap-4 sm:flex-row sm:justify-center">
                    <Link v-if="canRegister" :href="route('register')" class="btn-3d-hero group relative inline-flex items-center gap-2 overflow-hidden rounded-2xl px-10 py-5 text-lg font-semibold text-white transition-all duration-300 hover:-translate-y-1.5">
                        <span class="relative z-10">Start Building Free</span>
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="relative z-10 size-5 transition-transform duration-300 group-hover:translate-x-1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                        </svg>
                        <div class="btn-shine absolute inset-0"></div>
                    </Link>
                    <a href="/demo" target="_blank" rel="noopener" class="group inline-flex items-center gap-3 rounded-2xl border border-gray-200/80 bg-white/80 backdrop-blur-sm px-10 py-5 text-lg font-semibold text-gray-700 shadow-xl shadow-gray-200/40 transition-all duration-300 hover:border-indigo-200 hover:shadow-indigo-100/50 hover:bg-white hover:text-indigo-600 hover:-translate-y-1">
                        <div class="flex size-10 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-50 to-violet-50 shadow-inner transition-all duration-300 group-hover:shadow-indigo-200/50 group-hover:scale-110">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-5 text-indigo-600">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 0 1 0 1.972l-11.54 6.347a1.125 1.125 0 0 1-1.667-.986V5.653Z" />
                            </svg>
                        </div>
                        See Demo
                    </a>
                </div>

                <!-- Stats with 3D cards -->
                <div class="hero-stats mx-auto mt-20 grid max-w-4xl grid-cols-2 gap-5 sm:grid-cols-4">
                    <div v-for="(stat, i) in stats" :key="stat.label"
                         class="stat-card group relative rounded-2xl bg-white/80 backdrop-blur-md border border-gray-100/80 px-5 py-6 text-center transition-all duration-500 hover:-translate-y-2 cursor-default"
                         :style="{ animationDelay: `${0.7 + i * 0.1}s` }">
                        <div class="absolute inset-0 rounded-2xl bg-gradient-to-br from-indigo-50/50 to-violet-50/30 opacity-0 transition-opacity duration-500 group-hover:opacity-100"></div>
                        <div class="relative">
                            <div class="mx-auto mb-2 flex size-10 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-50 to-violet-50 text-indigo-500 transition-transform duration-300 group-hover:scale-110 group-hover:rotate-6" v-html="stat.icon"></div>
                            <div class="text-2xl font-extrabold bg-gradient-to-r from-indigo-600 to-violet-600 bg-clip-text text-transparent">{{ stat.value }}</div>
                            <div class="mt-1 text-xs font-medium text-gray-400 uppercase tracking-wider">{{ stat.label }}</div>
                        </div>
                    </div>
                </div>

                <!-- Editor mockup with 3D tilt -->
                <div class="hero-mockup relative mx-auto mt-24 max-w-5xl"
                     :style="{ transform: `perspective(1400px) rotateX(${mouse.y * 1.5}deg) rotateY(${mouse.x * -1.5}deg)` }">
                    <div class="mockup-3d rounded-3xl border border-gray-200/60 bg-gray-950 p-1.5 sm:p-2">
                        <div class="flex items-center gap-2 px-4 py-3">
                            <div class="size-3.5 rounded-full bg-[#ff5f57] shadow-sm shadow-red-500/30"></div>
                            <div class="size-3.5 rounded-full bg-[#febc2e] shadow-sm shadow-yellow-500/30"></div>
                            <div class="size-3.5 rounded-full bg-[#28c840] shadow-sm shadow-green-500/30"></div>
                            <div class="ml-4 flex-1 rounded-lg bg-gray-800/80 px-4 py-1.5 text-xs text-gray-400">
                                <span class="text-gray-500">https://</span>aiwebbuilder.app/editor
                            </div>
                        </div>
                        <div class="flex rounded-b-2xl bg-gray-900 overflow-hidden" style="height: 380px;">
                            <div class="hidden w-56 border-r border-gray-800/80 p-4 sm:block">
                                <div class="mb-4 text-[10px] font-bold uppercase tracking-[0.2em] text-gray-600">Blocks</div>
                                <div class="space-y-1.5">
                                    <div class="mockup-block-active rounded-lg bg-violet-500/10 px-3 py-2.5 text-sm text-violet-400 border border-violet-500/20 flex items-center gap-2 shadow-sm shadow-violet-500/10">
                                        <div class="size-2 rounded-full bg-violet-400 shadow-sm shadow-violet-400/50"></div>
                                        Navbar
                                    </div>
                                    <div v-for="block in ['Hero', 'Features', 'Pricing', 'Contact', 'Footer']" :key="block" class="rounded-lg bg-white/[0.02] px-3 py-2.5 text-sm text-gray-500 flex items-center gap-2 transition hover:bg-white/[0.04]">
                                        <div class="size-2 rounded-full bg-gray-700"></div>
                                        {{ block }}
                                    </div>
                                </div>
                            </div>
                            <div class="flex-1 p-3 sm:p-4">
                                <div class="h-full rounded-2xl bg-white p-4 sm:p-6 overflow-hidden shadow-inner">
                                    <div class="flex items-center justify-between mb-6">
                                        <div class="text-sm font-bold text-gray-900">MyBrand</div>
                                        <div class="hidden sm:flex gap-4">
                                            <div class="h-2 w-12 rounded-full bg-gray-200"></div>
                                            <div class="h-2 w-12 rounded-full bg-gray-200"></div>
                                            <div class="h-2 w-12 rounded-full bg-gray-200"></div>
                                        </div>
                                    </div>
                                    <div class="text-center">
                                        <div class="mx-auto h-4 w-52 rounded-full bg-gradient-to-r from-indigo-200 via-violet-200 to-purple-200 mb-3 shadow-sm"></div>
                                        <div class="mx-auto h-2.5 w-64 rounded-full bg-gray-100 mb-2"></div>
                                        <div class="mx-auto h-2.5 w-52 rounded-full bg-gray-100 mb-7"></div>
                                        <div class="mx-auto h-10 w-32 rounded-xl bg-gradient-to-r from-indigo-500 to-violet-500 shadow-lg shadow-indigo-500/20"></div>
                                    </div>
                                    <div class="mt-10 grid grid-cols-3 gap-3">
                                        <div v-for="c in ['indigo', 'violet', 'blue']" :key="c" class="rounded-xl bg-gray-50/80 p-3 border border-gray-100 shadow-sm">
                                            <div :class="`mb-2 size-8 rounded-lg bg-${c}-100 shadow-inner`"></div>
                                            <div class="h-2 w-full rounded-full bg-gray-200 mb-1.5"></div>
                                            <div class="h-1.5 w-3/4 rounded-full bg-gray-100"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="hidden w-56 border-l border-gray-800/80 p-4 lg:block">
                                <div class="mb-4 text-[10px] font-bold uppercase tracking-[0.2em] text-gray-600">Theme</div>
                                <div class="space-y-3">
                                    <div v-for="c in [{bg:'bg-indigo-500 shadow-indigo-500/40', l:'Primary'}, {bg:'bg-violet-500 shadow-violet-500/40', l:'Secondary'}, {bg:'bg-gray-100 ring-1 ring-gray-600', l:'Text'}]" :key="c.l" class="flex items-center gap-3">
                                        <div :class="`size-7 rounded-lg ${c.bg} shadow-md`"></div>
                                        <div class="text-xs text-gray-500">{{ c.l }}</div>
                                    </div>
                                    <div class="mt-6 mb-2 text-[10px] font-bold uppercase tracking-[0.2em] text-gray-600">Font</div>
                                    <div class="rounded-lg bg-white/[0.03] px-3 py-2.5 text-xs text-gray-400 border border-gray-700/40">Inter</div>
                                    <div class="mt-6 mb-2 text-[10px] font-bold uppercase tracking-[0.2em] text-gray-600">Radius</div>
                                    <div class="flex gap-1.5">
                                        <div class="rounded-md bg-gray-800/50 px-3 py-1.5 text-xs text-gray-500">sm</div>
                                        <div class="rounded-md bg-violet-500/15 px-3 py-1.5 text-xs text-violet-400 border border-violet-500/20 shadow-sm shadow-violet-500/10">md</div>
                                        <div class="rounded-md bg-gray-800/50 px-3 py-1.5 text-xs text-gray-500">lg</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mockup-glow absolute -inset-10 -z-10 rounded-[2rem] bg-gradient-to-r from-indigo-400/8 via-violet-400/8 to-purple-400/8 blur-3xl"></div>
                    <div class="absolute -bottom-px left-[8%] right-[8%] h-px bg-gradient-to-r from-transparent via-indigo-400/30 to-transparent"></div>
                </div>
            </div>
        </section>

        <!-- ===== FEATURES ===== -->
        <section id="features" class="relative py-28 lg:py-44 overflow-hidden">
            <div class="section-glow-left"></div>
            <div class="relative mx-auto max-w-7xl px-6">
                <div class="text-center animate-on-scroll">
                    <div class="inline-flex items-center gap-2 rounded-full border border-indigo-200/70 bg-white/90 backdrop-blur-sm px-4 py-1.5 text-sm font-medium text-indigo-600 shadow-lg shadow-indigo-500/[0.04] mb-5">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75Z" /></svg>
                        Features
                    </div>
                    <h2 class="text-3xl font-bold text-gray-900 sm:text-4xl lg:text-5xl leading-tight">Everything you need to build<br class="hidden sm:block" /> amazing websites</h2>
                    <p class="mt-5 mx-auto max-w-2xl text-lg text-gray-500">From AI generation to visual editing to one-click publishing — we've got every step covered.</p>
                </div>

                <div class="mt-20 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    <div v-for="(feature, i) in features" :key="feature.title"
                        class="animate-on-scroll feature-card group relative rounded-2xl border border-gray-100/80 bg-white/80 backdrop-blur-sm p-8 transition-all duration-500 hover:-translate-y-3"
                        :style="{ transitionDelay: `${i * 80}ms` }">
                        <!-- Hover glow -->
                        <div :class="`absolute -inset-px rounded-2xl bg-gradient-to-br ${featureColors[feature.color].glow} opacity-0 transition-opacity duration-500 group-hover:opacity-100 -z-10 blur-xl`"></div>
                        <div class="absolute inset-0 rounded-2xl bg-gradient-to-br from-white to-gray-50/50 opacity-0 transition-opacity duration-500 group-hover:opacity-100 border border-gray-200/50"></div>
                        <div class="relative">
                            <div :class="`inline-flex size-16 items-center justify-center rounded-2xl ${featureColors[feature.color].bg} ${featureColors[feature.color].text} border ${featureColors[feature.color].border} shadow-lg ${featureColors[feature.color].shadow} transition-all duration-500 group-hover:scale-110 group-hover:rotate-3 group-hover:shadow-xl`" v-html="feature.icon"></div>
                            <h3 class="mt-6 text-lg font-bold text-gray-900 group-hover:text-gray-800 transition-colors">{{ feature.title }}</h3>
                            <p class="mt-3 text-gray-500 leading-relaxed text-[0.9rem]">{{ feature.desc }}</p>
                            <!-- Arrow indicator -->
                            <div class="mt-5 flex items-center gap-1.5 text-sm font-medium opacity-0 -translate-x-2 transition-all duration-300 group-hover:opacity-100 group-hover:translate-x-0" :class="featureColors[feature.color].text">
                                Learn more
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-4"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ===== HOW IT WORKS ===== -->
        <section id="how-it-works" class="relative py-28 lg:py-44 overflow-hidden">
            <div class="section-glow-right"></div>
            <div class="mx-auto max-w-7xl px-6">
                <div class="text-center animate-on-scroll">
                    <div class="inline-flex items-center gap-2 rounded-full border border-indigo-200/70 bg-white/90 backdrop-blur-sm px-4 py-1.5 text-sm font-medium text-indigo-600 shadow-lg shadow-indigo-500/[0.04] mb-5">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z" /></svg>
                        How It Works
                    </div>
                    <h2 class="text-3xl font-bold text-gray-900 sm:text-4xl lg:text-5xl leading-tight">Three steps to your<br class="hidden sm:block" /> dream website</h2>
                </div>

                <div class="mt-20 grid gap-8 lg:grid-cols-3 lg:gap-6">
                    <div v-for="(step, i) in steps" :key="step.num"
                        class="animate-on-scroll group relative text-center"
                        :style="{ transitionDelay: `${i * 150}ms` }">
                        <!-- Connector -->
                        <div v-if="i < steps.length - 1" class="hidden lg:block absolute top-14 left-[60%] w-[80%] h-px">
                            <div class="h-full bg-gradient-to-r from-indigo-300/50 to-transparent connector-line"></div>
                            <div class="connector-dot absolute right-0 top-1/2 -translate-y-1/2 size-2 rounded-full bg-indigo-300/50"></div>
                        </div>

                        <div class="relative mx-auto flex size-28 items-center justify-center">
                            <!-- Outer ring -->
                            <div class="absolute inset-0 rounded-[1.75rem] border-2 border-dashed border-indigo-200/40 transition-all duration-700 group-hover:border-indigo-300/70 group-hover:rotate-[30deg] group-hover:scale-110"></div>
                            <!-- BG glow -->
                            <div class="absolute inset-2 rounded-3xl bg-gradient-to-br from-indigo-100/60 to-violet-100/60 transition-all duration-500 group-hover:from-indigo-100 group-hover:to-violet-100 group-hover:scale-105 group-hover:shadow-xl group-hover:shadow-indigo-200/30"></div>
                            <div class="relative flex size-16 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-600 to-violet-600 text-white shadow-xl shadow-indigo-500/25 transition-all duration-500 group-hover:scale-110 group-hover:shadow-2xl group-hover:shadow-indigo-500/40 group-hover:-rotate-3" v-html="step.icon"></div>
                        </div>
                        <div class="mt-4 text-xs font-bold uppercase tracking-[0.2em] text-indigo-400/80">Step {{ step.num }}</div>
                        <h3 class="mt-3 text-xl font-bold text-gray-900">{{ step.title }}</h3>
                        <p class="mt-3 text-gray-500 leading-relaxed max-w-xs mx-auto text-sm">{{ step.desc }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ===== PRICING ===== -->
        <section id="pricing" class="relative py-28 lg:py-44 overflow-hidden">
            <div class="section-glow-center"></div>
            <div class="relative mx-auto max-w-7xl px-6">
                <div class="text-center animate-on-scroll">
                    <div class="inline-flex items-center gap-2 rounded-full border border-indigo-200/70 bg-white/90 backdrop-blur-sm px-4 py-1.5 text-sm font-medium text-indigo-600 shadow-lg shadow-indigo-500/[0.04] mb-5">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" /></svg>
                        Pricing
                    </div>
                    <h2 class="text-3xl font-bold text-gray-900 sm:text-4xl lg:text-5xl leading-tight">Simple, transparent pricing</h2>
                    <p class="mt-5 mx-auto max-w-2xl text-lg text-gray-500">Start for free, upgrade as you grow. No hidden fees.</p>
                </div>

                <div class="mt-20 grid gap-6 lg:grid-cols-3 items-center">
                    <div v-for="(plan, i) in plans" :key="plan.name"
                        class="animate-on-scroll group relative"
                        :style="{ transitionDelay: `${i * 100}ms` }">
                        <div :class="[
                            'pricing-card relative rounded-3xl border p-8 lg:p-10 transition-all duration-500',
                            plan.highlight
                                ? 'border-indigo-200/80 bg-white shadow-[0_20px_60px_-15px_rgba(99,102,241,0.15)] ring-1 ring-indigo-100 lg:scale-105 hover:-translate-y-2'
                                : 'border-gray-100 bg-white/80 backdrop-blur-sm shadow-xl shadow-gray-200/30 hover:shadow-2xl hover:shadow-indigo-100/30 hover:-translate-y-2 hover:border-gray-200'
                        ]">
                            <div v-if="plan.highlight" class="absolute -top-4 left-1/2 -translate-x-1/2">
                                <div class="rounded-full bg-gradient-to-r from-indigo-600 to-violet-600 px-5 py-1.5 text-xs font-bold text-white shadow-xl shadow-indigo-500/30 uppercase tracking-wider">Most Popular</div>
                            </div>
                            <h3 class="text-xl font-bold text-gray-900">{{ plan.name }}</h3>
                            <div class="mt-5 flex items-baseline gap-1">
                                <span class="text-5xl font-extrabold tracking-tight text-gray-900">{{ plan.price }}</span>
                                <span class="text-lg text-gray-400 font-medium">{{ plan.period }}</span>
                            </div>
                            <div class="mt-6 h-px bg-gradient-to-r from-transparent via-gray-200 to-transparent"></div>
                            <ul class="mt-6 space-y-4">
                                <li v-for="(f, fi) in plan.features" :key="f" class="flex items-center gap-3 feature-list-item" :style="{ animationDelay: `${fi * 0.05}s` }">
                                    <div class="flex size-6 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-indigo-50 to-violet-50 shadow-sm border border-indigo-100/50">
                                        <svg class="size-3.5 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                    </div>
                                    <span class="text-sm text-gray-600">{{ f }}</span>
                                </li>
                            </ul>
                            <Link v-if="canRegister" :href="route('register')"
                                :class="[
                                    'mt-10 block w-full rounded-2xl py-4 text-center text-sm font-semibold transition-all duration-300',
                                    plan.highlight
                                        ? 'btn-3d text-white hover:-translate-y-0.5'
                                        : 'border border-gray-200 text-gray-700 hover:border-indigo-200 hover:text-indigo-600 hover:bg-indigo-50/50 hover:shadow-lg hover:shadow-indigo-100/30'
                                ]">
                                {{ plan.cta }}
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ===== CTA ===== -->
        <section class="py-28 lg:py-44 animate-on-scroll">
            <div class="mx-auto max-w-5xl px-6 text-center">
                <div class="cta-card relative overflow-hidden rounded-[2.5rem] bg-gradient-to-br from-indigo-600 via-violet-600 to-purple-600 px-8 py-24 sm:px-16">
                    <!-- Decorative -->
                    <div class="absolute inset-0 overflow-hidden">
                        <div class="absolute top-0 left-1/2 -translate-x-1/2 -translate-y-1/2 size-[700px] rounded-full bg-white/[0.07] blur-3xl"></div>
                        <div class="absolute bottom-0 right-0 translate-x-1/4 translate-y-1/4 size-[500px] rounded-full bg-purple-400/10 blur-3xl"></div>
                        <div class="absolute inset-0 bg-[linear-gradient(to_right,#ffffff06_1px,transparent_1px),linear-gradient(to_bottom,#ffffff06_1px,transparent_1px)] bg-[size:3rem_3rem]"></div>
                        <!-- Floating rings -->
                        <div class="cta-ring absolute top-10 left-10 size-20 rounded-full border border-white/10"></div>
                        <div class="cta-ring-2 absolute bottom-10 right-16 size-32 rounded-full border border-white/[0.06]"></div>
                    </div>
                    <div class="relative">
                        <h2 class="text-3xl font-bold text-white sm:text-4xl lg:text-5xl leading-tight drop-shadow-lg">Ready to build your<br /> dream website?</h2>
                        <p class="mt-6 text-lg text-indigo-100/90 max-w-2xl mx-auto">Join thousands of creators building beautiful websites with AI. Start free, no credit card required.</p>
                        <div class="mt-10 flex flex-col items-center gap-4 sm:flex-row sm:justify-center">
                            <Link v-if="canRegister" :href="route('register')" class="group inline-flex items-center gap-2 rounded-2xl bg-white px-9 py-4.5 text-base font-semibold text-indigo-600 shadow-2xl shadow-black/10 transition-all duration-300 hover:-translate-y-1.5 hover:shadow-[0_25px_60px_-12px_rgba(0,0,0,0.2)]">
                                Get Started Free
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="size-5 transition-transform duration-300 group-hover:translate-x-1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
                            </Link>
                            <a href="/demo" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-2xl border border-white/20 bg-white/10 backdrop-blur px-9 py-4.5 text-base font-semibold text-white transition-all duration-300 hover:bg-white/20 hover:-translate-y-0.5">View Demo</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ===== FOOTER ===== -->
        <footer class="border-t border-gray-100 bg-gradient-to-b from-gray-50/50 to-white py-16">
            <div class="mx-auto max-w-7xl px-6">
                <div class="flex flex-col items-center gap-8">
                    <Link href="/" class="flex items-center gap-2.5 group">
                        <div class="flex size-10 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-600 to-violet-600 shadow-xl shadow-indigo-500/25 transition-all duration-300 group-hover:scale-110 group-hover:rotate-3">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="white" class="size-5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z" /></svg>
                        </div>
                        <span class="text-lg font-bold">Ai<span class="bg-gradient-to-r from-indigo-600 to-violet-600 bg-clip-text text-transparent">Web</span>Builder</span>
                    </Link>
                    <div class="flex flex-wrap justify-center gap-8">
                        <button @click="scrollTo('features')" class="text-sm text-gray-500 transition hover:text-indigo-600">Features</button>
                        <button @click="scrollTo('how-it-works')" class="text-sm text-gray-500 transition hover:text-indigo-600">How It Works</button>
                        <button @click="scrollTo('pricing')" class="text-sm text-gray-500 transition hover:text-indigo-600">Pricing</button>
                        <a href="/demo" target="_blank" rel="noopener" class="text-sm text-gray-500 transition hover:text-indigo-600">Demo</a>
                    </div>
                    <div class="h-px w-full max-w-md bg-gradient-to-r from-transparent via-gray-200 to-transparent"></div>
                    <p class="text-sm text-gray-400">&copy; 2026 AiWebBuilder. All rights reserved.</p>
                </div>
            </div>
        </footer>
    </div>
</template>

<style scoped>
/* ============================================
   3D BUTTONS
   ============================================ */
.btn-3d {
    background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
    box-shadow:
        0 1px 2px rgba(79,70,229,0.3),
        0 4px 8px rgba(79,70,229,0.2),
        0 8px 20px rgba(79,70,229,0.15),
        inset 0 1px 0 rgba(255,255,255,0.15);
    transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.btn-3d:hover {
    transform: translateY(-2px);
    box-shadow:
        0 2px 4px rgba(79,70,229,0.3),
        0 8px 16px rgba(79,70,229,0.25),
        0 16px 40px rgba(79,70,229,0.2),
        inset 0 1px 0 rgba(255,255,255,0.2);
}

.btn-3d-hero {
    background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 50%, #6d28d9 100%);
    box-shadow:
        0 2px 4px rgba(79,70,229,0.3),
        0 8px 20px rgba(79,70,229,0.25),
        0 16px 50px rgba(79,70,229,0.2),
        inset 0 2px 0 rgba(255,255,255,0.1),
        inset 0 -2px 0 rgba(0,0,0,0.1);
}
.btn-3d-hero:hover {
    box-shadow:
        0 4px 8px rgba(79,70,229,0.3),
        0 12px 30px rgba(79,70,229,0.3),
        0 24px 60px rgba(79,70,229,0.2),
        inset 0 2px 0 rgba(255,255,255,0.15),
        inset 0 -2px 0 rgba(0,0,0,0.1);
}

/* ============================================
   3D LOGO
   ============================================ */
.logo-3d {
    box-shadow:
        0 2px 4px rgba(79,70,229,0.3),
        0 6px 16px rgba(79,70,229,0.2),
        inset 0 1px 0 rgba(255,255,255,0.2);
}

/* ============================================
   3D MOCKUP
   ============================================ */
.mockup-3d {
    box-shadow:
        0 4px 8px rgba(0,0,0,0.1),
        0 16px 40px rgba(0,0,0,0.15),
        0 40px 80px rgba(99,102,241,0.08),
        0 60px 120px rgba(0,0,0,0.1);
}

/* ============================================
   FEATURE CARDS 3D
   ============================================ */
.feature-card {
    box-shadow:
        0 1px 3px rgba(0,0,0,0.04),
        0 4px 12px rgba(0,0,0,0.03);
}
.feature-card:hover {
    box-shadow:
        0 4px 8px rgba(99,102,241,0.06),
        0 12px 30px rgba(99,102,241,0.08),
        0 30px 60px rgba(99,102,241,0.04);
}

/* ============================================
   STAT CARDS
   ============================================ */
.stat-card {
    box-shadow:
        0 2px 8px rgba(0,0,0,0.04),
        0 8px 24px rgba(99,102,241,0.04);
    animation: fadeInUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) both;
}
.stat-card:hover {
    box-shadow:
        0 4px 12px rgba(99,102,241,0.08),
        0 16px 40px rgba(99,102,241,0.06);
}

/* ============================================
   PRICING CARDS
   ============================================ */
.pricing-card {
    box-shadow:
        0 2px 8px rgba(0,0,0,0.04),
        0 8px 30px rgba(0,0,0,0.04);
}

/* ============================================
   CTA CARD
   ============================================ */
.cta-card {
    box-shadow:
        0 8px 20px rgba(79,70,229,0.15),
        0 25px 60px rgba(79,70,229,0.15),
        0 50px 100px rgba(79,70,229,0.1);
}

/* ============================================
   CTA RINGS
   ============================================ */
.cta-ring {
    animation: ringFloat 8s ease-in-out infinite;
}
.cta-ring-2 {
    animation: ringFloat 12s ease-in-out infinite reverse;
}
@keyframes ringFloat {
    0%, 100% { transform: translate(0, 0) rotate(0deg); }
    50% { transform: translate(15px, -20px) rotate(90deg); }
}

/* ============================================
   TYPING CURSOR
   ============================================ */
.typing-cursor {
    animation: blink 0.8s step-end infinite;
    color: #6366f1;
    font-weight: 300;
}
@keyframes blink {
    0%, 100% { opacity: 1; }
    50% { opacity: 0; }
}

/* ============================================
   ANIMATED BG ORBS
   ============================================ */
.orb { position: absolute; border-radius: 50%; filter: blur(80px); will-change: transform; opacity: 0.5; }
.orb-1 { width: 800px; height: 800px; background: radial-gradient(circle, rgba(129,140,248,0.2) 0%, transparent 70%); top: -15%; left: 10%; animation: orbFloat1 22s ease-in-out infinite; }
.orb-2 { width: 600px; height: 600px; background: radial-gradient(circle, rgba(167,139,250,0.18) 0%, transparent 70%); top: 20%; right: -10%; animation: orbFloat2 28s ease-in-out infinite; }
.orb-3 { width: 550px; height: 550px; background: radial-gradient(circle, rgba(244,114,182,0.12) 0%, transparent 70%); bottom: 25%; left: -8%; animation: orbFloat3 24s ease-in-out infinite; }
.orb-4 { width: 450px; height: 450px; background: radial-gradient(circle, rgba(96,165,250,0.15) 0%, transparent 70%); top: 50%; left: 40%; animation: orbFloat4 20s ease-in-out infinite; }
.orb-5 { width: 700px; height: 700px; background: radial-gradient(circle, rgba(192,132,252,0.12) 0%, transparent 70%); bottom: -12%; right: 10%; animation: orbFloat5 30s ease-in-out infinite; }

@keyframes orbFloat1 { 0%,100%{transform:translate(0,0) scale(1)} 25%{transform:translate(120px,90px) scale(1.1)} 50%{transform:translate(-70px,160px) scale(.95)} 75%{transform:translate(90px,-50px) scale(1.05)} }
@keyframes orbFloat2 { 0%,100%{transform:translate(0,0) scale(1)} 33%{transform:translate(-100px,110px) scale(1.08)} 66%{transform:translate(70px,-90px) scale(.92)} }
@keyframes orbFloat3 { 0%,100%{transform:translate(0,0) scale(1)} 25%{transform:translate(80px,-70px) scale(1.12)} 50%{transform:translate(-50px,-110px) scale(.9)} 75%{transform:translate(-70px,60px) scale(1.05)} }
@keyframes orbFloat4 { 0%,100%{transform:translate(0,0) scale(1)} 50%{transform:translate(-140px,70px) scale(1.15)} }
@keyframes orbFloat5 { 0%,100%{transform:translate(0,0) scale(1)} 33%{transform:translate(90px,-100px) scale(1.1)} 66%{transform:translate(-110px,50px) scale(.95)} }

/* ============================================
   DOT GRID
   ============================================ */
.dot-grid {
    background-image: radial-gradient(circle, rgba(99,102,241,0.06) 1px, transparent 1px);
    background-size: 36px 36px;
    animation: dotDrift 50s linear infinite;
}
@keyframes dotDrift { 0%{background-position:0 0} 100%{background-position:36px 36px} }

/* ============================================
   AURORA
   ============================================ */
.aurora { position: absolute; filter: blur(70px); will-change: transform; }
.aurora-1 {
    top: 15%; left: -15%; width: 130%; height: 250px;
    background: linear-gradient(90deg, transparent, rgba(129,140,248,0.06) 15%, rgba(167,139,250,0.08) 35%, rgba(244,114,182,0.04) 55%, rgba(96,165,250,0.06) 75%, transparent);
    transform: rotate(-6deg);
    animation: auroraDrift1 18s ease-in-out infinite;
}
.aurora-2 {
    bottom: 25%; left: -10%; width: 120%; height: 200px;
    background: linear-gradient(90deg, transparent, rgba(96,165,250,0.05) 20%, rgba(192,132,252,0.06) 50%, rgba(129,140,248,0.04) 80%, transparent);
    transform: rotate(4deg);
    animation: auroraDrift2 22s ease-in-out infinite;
}
@keyframes auroraDrift1 { 0%,100%{transform:rotate(-6deg) translateX(0);opacity:.5} 50%{transform:rotate(-3deg) translateX(100px);opacity:.9} }
@keyframes auroraDrift2 { 0%,100%{transform:rotate(4deg) translateX(0);opacity:.4} 50%{transform:rotate(2deg) translateX(-80px);opacity:.8} }

/* ============================================
   PARTICLES
   ============================================ */
.particle { position: absolute; border-radius: 50%; pointer-events: none; }
.particle-1  { width:5px; height:5px; background:rgba(129,140,248,0.5); top:12%; left:8%;   animation: pF 10s ease-in-out infinite, pO 10s ease-in-out infinite; }
.particle-2  { width:4px; height:4px; background:rgba(167,139,250,0.4); top:30%; right:15%;  animation: pF 12s ease-in-out infinite 1s, pO 12s ease-in-out infinite 1s; }
.particle-3  { width:6px; height:6px; background:rgba(244,114,182,0.35);top:50%; left:6%;   animation: pF 14s ease-in-out infinite 2s, pO 14s ease-in-out infinite 2s; }
.particle-4  { width:3px; height:3px; background:rgba(96,165,250,0.5);  top:20%; right:6%;  animation: pF 11s ease-in-out infinite 3s, pO 11s ease-in-out infinite 3s; }
.particle-5  { width:5px; height:5px; background:rgba(192,132,252,0.35);top:60%; right:22%; animation: pF 13s ease-in-out infinite 4s, pO 13s ease-in-out infinite 4s; }
.particle-6  { width:4px; height:4px; background:rgba(129,140,248,0.4); top:40%; left:25%;  animation: pF 9s ease-in-out infinite 2.5s, pO 9s ease-in-out infinite 2.5s; }
.particle-7  { width:3px; height:3px; background:rgba(244,114,182,0.4); top:10%; left:50%;  animation: pF 15s ease-in-out infinite 1.5s, pO 15s ease-in-out infinite 1.5s; }
.particle-8  { width:5px; height:5px; background:rgba(96,165,250,0.35); top:70%; left:60%;  animation: pF 11s ease-in-out infinite 3.5s, pO 11s ease-in-out infinite 3.5s; }
.particle-9  { width:4px; height:4px; background:rgba(167,139,250,0.4); top:80%; left:15%;  animation: pF 10s ease-in-out infinite 5s, pO 10s ease-in-out infinite 5s; }
.particle-10 { width:6px; height:6px; background:rgba(129,140,248,0.3); top:85%; right:10%; animation: pF 16s ease-in-out infinite 2s, pO 16s ease-in-out infinite 2s; }
.particle-11 { width:3px; height:3px; background:rgba(192,132,252,0.45);top:5%;  left:35%;  animation: pF 8s ease-in-out infinite 1s, pO 8s ease-in-out infinite 1s; }
.particle-12 { width:4px; height:4px; background:rgba(244,114,182,0.35);top:45%; right:35%; animation: pF 13s ease-in-out infinite 4.5s, pO 13s ease-in-out infinite 4.5s; }

@keyframes pF { 0%,100%{transform:translate(0,0)} 25%{transform:translate(30px,-60px)} 50%{transform:translate(-20px,-120px)} 75%{transform:translate(40px,-60px)} }
@keyframes pO { 0%,100%{opacity:0} 12%,88%{opacity:1} }

/* ============================================
   SECTION GLOWS
   ============================================ */
.section-glow-left { position:absolute; width:600px; height:600px; left:-300px; top:50%; transform:translateY(-50%); background:radial-gradient(circle,rgba(129,140,248,0.06) 0%,transparent 70%); filter:blur(60px); animation:sG 9s ease-in-out infinite; }
.section-glow-right { position:absolute; width:600px; height:600px; right:-300px; top:50%; transform:translateY(-50%); background:radial-gradient(circle,rgba(167,139,250,0.06) 0%,transparent 70%); filter:blur(60px); animation:sG 11s ease-in-out infinite; }
.section-glow-center { position:absolute; width:700px; height:700px; left:50%; top:50%; transform:translate(-50%,-50%); background:radial-gradient(circle,rgba(192,132,252,0.05) 0%,transparent 70%); filter:blur(80px); animation:sGC 13s ease-in-out infinite; }
@keyframes sG { 0%,100%{opacity:.4;transform:translateY(-50%) scale(1)} 50%{opacity:.8;transform:translateY(-50%) scale(1.2)} }
@keyframes sGC { 0%,100%{opacity:.3;transform:translate(-50%,-50%) scale(1)} 50%{opacity:.7;transform:translate(-50%,-50%) scale(1.2)} }

/* ============================================
   CONNECTOR
   ============================================ */
.connector-line { animation: cP 3s ease-in-out infinite; }
.connector-dot { animation: cP 3s ease-in-out infinite 0.5s; }
@keyframes cP { 0%,100%{opacity:.2} 50%{opacity:.7} }

/* ============================================
   SCROLL ANIMATIONS
   ============================================ */
.animate-on-scroll { opacity:0; transform:translateY(50px) scale(0.97); transition: opacity 1s cubic-bezier(0.16,1,0.3,1), transform 1s cubic-bezier(0.16,1,0.3,1); }
.animate-visible { opacity:1; transform:translateY(0) scale(1); }

/* ============================================
   HERO ENTRANCE
   ============================================ */
.hero-badge { animation: fadeInUp .8s cubic-bezier(.16,1,.3,1) .1s both; }
.hero-title { animation: heroReveal 1s cubic-bezier(.16,1,.3,1) .25s both; }
.hero-subtitle { animation: fadeInUp .8s cubic-bezier(.16,1,.3,1) .45s both; }
.hero-buttons { animation: fadeInUp .8s cubic-bezier(.16,1,.3,1) .6s both; }
.hero-stats { animation: fadeInUp .8s cubic-bezier(.16,1,.3,1) .75s both; }
.hero-mockup { animation: mockupReveal 1.4s cubic-bezier(.16,1,.3,1) .95s both; transition: transform .2s ease-out; }

@keyframes fadeInUp { from{opacity:0;transform:translateY(50px)} to{opacity:1;transform:translateY(0)} }
@keyframes heroReveal { from{opacity:0;transform:translateY(60px) scale(0.95);filter:blur(8px)} to{opacity:1;transform:translateY(0) scale(1);filter:blur(0)} }
@keyframes mockupReveal { from{opacity:0;transform:translateY(80px) scale(0.92) perspective(1400px) rotateX(5deg)} to{opacity:1;transform:translateY(0) scale(1) perspective(1400px) rotateX(0)} }

/* ============================================
   GRADIENT SHIMMER
   ============================================ */
.hero-gradient-text { background-size:200% auto; animation:gS 5s ease-in-out infinite; }
@keyframes gS { 0%,100%{background-position:0% center} 50%{background-position:100% center} }

/* ============================================
   BUTTON SHINE
   ============================================ */
.btn-shine::after { content:''; position:absolute; top:0; left:-100%; width:50%; height:100%; background:linear-gradient(90deg,transparent,rgba(255,255,255,0.2),transparent); animation:bS 4s ease-in-out infinite; }
@keyframes bS { 0%,70%,100%{left:-100%} 40%{left:150%} }

/* ============================================
   MOCKUP GLOW
   ============================================ */
.mockup-glow { animation:mG 5s ease-in-out infinite; }
@keyframes mG { 0%,100%{opacity:.3} 50%{opacity:.6} }

.mockup-block-active { animation:bP 2.5s ease-in-out infinite; }
@keyframes bP { 0%,100%{border-color:rgba(139,92,246,.2)} 50%{border-color:rgba(139,92,246,.5)} }

/* ============================================
   NAV LINK
   ============================================ */
.nav-link { position:relative; }
.nav-link::after { content:''; position:absolute; bottom:-4px; left:0; width:0; height:2px; background:linear-gradient(to right,#6366f1,#a78bfa); border-radius:1px; transition:width .3s ease; }
.nav-link:hover::after { width:100%; }
</style>
