<script setup>
import '../../css/landing.css';
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import LandingNav from '@/Components/Landing/LandingNav.vue';
import HeroSection from '@/Components/Landing/HeroSection.vue';
import AudienceMarquee from '@/Components/Landing/AudienceMarquee.vue';
import ShowcaseSection from '@/Components/Landing/ShowcaseSection.vue';
import FeaturesSection from '@/Components/Landing/FeaturesSection.vue';
import StepsSection from '@/Components/Landing/StepsSection.vue';
import ComparisonSection from '@/Components/Landing/ComparisonSection.vue';
import PricingSection from '@/Components/Landing/PricingSection.vue';
import FaqSection from '@/Components/Landing/FaqSection.vue';
import CtaSection from '@/Components/Landing/CtaSection.vue';
import LandingFooter from '@/Components/Landing/LandingFooter.vue';
import { useColorMode, useDocumentTheme } from '@/Components/Landing/useColorMode';
import { useLandingEffects } from '@/Components/Landing/useLandingEffects';

defineProps({
    canLogin: { type: Boolean },
    canRegister: { type: Boolean },
    laravelVersion: { type: String, required: true },
    phpVersion: { type: String, required: true },
});

const root = ref(null);
const { theme } = useColorMode();
useDocumentTheme();
useLandingEffects(root);

function scrollToSection(id) {
    document.getElementById(id)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}
</script>

<template>
    <Head title="AiWebBuilder — Build stunning websites with AI in seconds">
        <meta name="description" content="Describe your website in plain English and AiWebBuilder designs, writes and assembles it in seconds. Edit visually, then publish in one click." />
    </Head>

    <div ref="root" class="landing relative min-h-screen overflow-x-clip font-sans antialiased" :data-theme="theme">
        <LandingNav @navigate="scrollToSection" />
        <main>
            <HeroSection />
            <AudienceMarquee />
            <ShowcaseSection />
            <FeaturesSection />
            <StepsSection />
            <ComparisonSection />
            <PricingSection />
            <FaqSection />
            <CtaSection />
        </main>
        <LandingFooter @navigate="scrollToSection" />
    </div>
</template>

<style>
/* Anchor jumps land below the fixed navbar */
.landing section[id] { scroll-margin-top: 4.5rem; }
</style>
