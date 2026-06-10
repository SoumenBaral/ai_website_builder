<script setup>
import { computed } from 'vue';
import BlockNavbar from './blocks/BlockNavbar.vue';
import BlockHero from './blocks/BlockHero.vue';
import BlockFeatures from './blocks/BlockFeatures.vue';
import BlockGallery from './blocks/BlockGallery.vue';
import BlockAbout from './blocks/BlockAbout.vue';
import BlockContact from './blocks/BlockContact.vue';
import BlockCta from './blocks/BlockCta.vue';
import BlockFooter from './blocks/BlockFooter.vue';
import BlockServices from './blocks/BlockServices.vue';
import BlockTestimonials from './blocks/BlockTestimonials.vue';
import BlockPricing from './blocks/BlockPricing.vue';
import BlockStats from './blocks/BlockStats.vue';
import BlockFaq from './blocks/BlockFaq.vue';
import BlockTeam from './blocks/BlockTeam.vue';
import BlockCustom from './blocks/BlockCustom.vue';

const blockMap = {
    navbar: BlockNavbar,
    hero: BlockHero,
    features: BlockFeatures,
    gallery: BlockGallery,
    about: BlockAbout,
    contact: BlockContact,
    cta: BlockCta,
    footer: BlockFooter,
    services: BlockServices,
    testimonials: BlockTestimonials,
    pricing: BlockPricing,
    stats: BlockStats,
    faq: BlockFaq,
    team: BlockTeam,
    custom: BlockCustom,
};

const props = defineProps({
    block: Object,
    editable: { type: Boolean, default: false },
    selected: { type: Boolean, default: false },
});

const emit = defineEmits(['update:props', 'select']);
const component = computed(() => blockMap[props.block.type]);

const sectionStyle = computed(() => {
    const bg = props.block.props?.sectionBg;
    const text = props.block.props?.sectionText;
    if (!bg && !text) return {};
    const style = {};
    if (bg) {
        style['--bg'] = bg;
        if (props.block.type === 'cta') style['--primary'] = bg;
        if (props.block.type === 'footer') style['--secondary'] = bg;
    }
    if (text) {
        style['--text'] = text;
        style['color'] = text;
    }
    return style;
});
</script>

<template>
    <div
        @click.stop="emit('select', block.id)"
        class="relative group transition-all"
        :class="{ 'ring-2 ring-blue-500 ring-offset-2': selected }"
        :style="sectionStyle"
    >
        <component
            v-if="component"
            :is="component"
            v-bind="block.props"
            :editable="editable"
            :id="block.id"
            @update:props="(patch) => emit('update:props', block.id, patch)"
        />
        <div v-else class="p-8 text-center text-red-500 bg-red-50">
            Unknown block type: {{ block.type }}
        </div>

        <!-- Block type label on hover -->
        <div
            v-if="editable"
            class="absolute top-0 left-0 px-2 py-0.5 text-xs font-medium text-white bg-blue-500 rounded-br opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-10"
        >
            {{ block.type }}
        </div>
    </div>
</template>
