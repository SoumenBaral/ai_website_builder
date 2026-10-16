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
import { BLOCK_TYPES } from '@/schema/defaults.js';

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
const label = computed(() => BLOCK_TYPES[props.block.type]?.label || props.block.type);

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
        :data-block-id="editable ? block.id : undefined"
        :class="['relative', editable && 'group/block cursor-pointer']"
        :style="sectionStyle"
        @click.stop="editable && emit('select', block.id)"
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

        <!-- Editor-only selection chrome (overlay, never affects the site's layout) -->
        <template v-if="editable">
            <div
                class="pointer-events-none absolute inset-0 z-10 transition-[box-shadow] duration-150"
                :class="selected ? 'shadow-[inset_0_0_0_2px_#8b5cf6]' : 'group-hover/block:shadow-[inset_0_0_0_1px_rgba(139,92,246,0.6)]'"
            ></div>
            <div
                class="pointer-events-none absolute left-2 top-2 z-20 rounded-md px-2 py-0.5 text-[11px] font-semibold text-white shadow-lg transition-opacity duration-150"
                :class="selected ? 'bg-violet-600 opacity-100' : 'bg-violet-500/90 opacity-0 group-hover/block:opacity-100'"
                style="font-family: Figtree, ui-sans-serif, system-ui, sans-serif; letter-spacing: 0;"
            >
                {{ label }}
            </div>
        </template>
    </div>
</template>
