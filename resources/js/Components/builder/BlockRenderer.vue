<script setup>
import { computed, ref, watch, onMounted, onBeforeUnmount, nextTick } from 'vue';
import { replayRequest, canonicalAnimation } from './animPreview';
import '@/site/animRuntime.js';
import '../../../css/site-animations.css';
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
import { useEditor } from '@/stores/editor.js';

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

/* ---------- Animation & hover — same runtime + CSS as the published site ---------- */
const animEl = ref(null);
const anim = computed(() => {
    const a = canonicalAnimation(props.block.props?.animation);
    return a && a !== 'none' ? a : null;
});
const animSpeed = computed(() => (['fast', 'slow'].includes(props.block.props?.animationSpeed) ? props.block.props.animationSpeed : undefined));
const animStagger = computed(() => !!props.block.props?.animationStagger);
const customJson = computed(() => (anim.value === 'custom' && props.block.props?.customAnimation ? JSON.stringify(props.block.props.customAnimation) : undefined));
const fxJson = computed(() => (props.block.props?.bgEffect?.kind ? JSON.stringify(props.block.props.bgEffect) : undefined));
const TEXT_FX = ['typing', 'typing-loop', 'words', 'letters', 'gradient', 'highlight'];
const textFx = computed(() => (TEXT_FX.includes(props.block.props?.textEffect) ? props.block.props.textEffect : undefined));
const textSpeed = computed(() => (textFx.value && ['fast', 'slow'].includes(props.block.props?.textSpeed) ? props.block.props.textSpeed : undefined));
const textPhrases = computed(() => (textFx.value === 'typing-loop' && props.block.props?.typingPhrases?.length ? JSON.stringify(props.block.props.typingPhrases) : undefined));
const buttonFx = computed(() => (['pulse', 'shine', 'glow', 'bounce', 'wiggle', 'pop'].includes(props.block.props?.buttonEffect) ? props.block.props.buttonEffect : undefined));
const hover = computed(() => (['lift', 'zoom', 'glow'].includes(props.block.props?.hoverEffect) ? props.block.props.hoverEffect : undefined));

async function play() {
    await nextTick(); // attributes and content must be in the DOM first
    if (!animEl.value || !window.SiteAnim) return;
    if (anim.value) window.SiteAnim.play(animEl.value);
    else window.SiteAnim.reset(animEl.value);
}

async function mountFx() {
    await nextTick();
    if (!animEl.value || !window.SiteAnim) return;
    if (fxJson.value) window.SiteAnim.mountFx(animEl.value);
    else window.SiteAnim.unmountFx(animEl.value);
}

/** Editor preview: plays once, then hands the heading's own nodes back to Vue. */
async function playText() {
    await nextTick();
    if (!animEl.value || !window.SiteAnim) return;
    if (textFx.value) window.SiteAnim.textFx(animEl.value, true);
    else window.SiteAnim.stopText(animEl.value);
}

async function markButtons() {
    await nextTick();
    if (animEl.value && window.SiteAnim) window.SiteAnim.markButtons(animEl.value);
}

onMounted(() => {
    if (textFx.value) playText();
    if (buttonFx.value) markButtons();
    if (fxJson.value) mountFx();
    if (anim.value) play();
});
onBeforeUnmount(() => {
    if (!animEl.value || !window.SiteAnim) return;
    window.SiteAnim.reset(animEl.value);
    window.SiteAnim.unmountFx(animEl.value);
    window.SiteAnim.stopText(animEl.value);
});
watch([textFx, textSpeed, textPhrases], playText);
// Shimmer is a lasting style (not a one-off preview): put it back after the heading is edited.
watch(() => textFx.value === 'gradient' && props.block.props?.heading, (v) => v !== false && playText());
watch([anim, animSpeed, animStagger, customJson], play);
watch(fxJson, mountFx);
watch(buttonFx, markButtons);
// Buttons can be added, restyled or recoloured while editing — re-mark them.
watch(() => buttonFx.value && JSON.stringify(props.block.props), (v) => v && markButtons());
// The effect layer lives inside the section's root element — re-attach if a re-render replaced it.
watch(() => props.block.props, () => fxJson.value && animEl.value?._fxLayer && !animEl.value._fxLayer.isConnected && mountFx(), { deep: true });
watch(replayRequest, (r) => {
    if (r.id !== props.block.id) return;
    play();
    if (textFx.value) playText();
});

/* ---------- colour helpers (mirror App\\Services\\Ai\\Color) ---------- */
const editorStore = useEditor();
const primaryColor = computed(() => editorStore.schema?.theme?.primaryColor || null);
function lum(hex) {
    const m = /^#?([0-9a-f]{6})$/i.exec(String(hex || '').trim());
    if (!m) return null;
    const [r, g, b] = [0, 2, 4].map((i) => parseInt(m[1].slice(i, i + 2), 16) / 255).map((c) => (c <= 0.03928 ? c / 12.92 : ((c + 0.055) / 1.055) ** 2.4));
    return 0.2126 * r + 0.7152 * g + 0.0722 * b;
}
function contrast(a, b) {
    const la = lum(a);
    const lb = lum(b);
    if (la === null || lb === null) return 21;
    return (Math.max(la, lb) + 0.05) / (Math.min(la, lb) + 0.05);
}
const readableOn = (bg) => ((lum(bg) ?? 1) > 0.4 ? '#111111' : '#ffffff');

function mixHex(a, b, amount) {
    const pa = /^#?([0-9a-f]{6})$/i.exec(a || '');
    const pb = /^#?([0-9a-f]{6})$/i.exec(b || '');
    if (!pa || !pb) return a;
    const ch = (h, i) => parseInt(h.slice(i, i + 2), 16);
    return '#' + [0, 2, 4].map((i) => Math.round(ch(pa[1], i) + (ch(pb[1], i) - ch(pa[1], i)) * amount).toString(16).padStart(2, '0')).join('');
}

const sectionStyle = computed(() => {
    const bg = props.block.props?.sectionBg;
    const text = props.block.props?.sectionText;
    const style = {};
    // Same as the published site: heading shimmer on a brand-coloured band uses a sheen of the text colour.
    const band = bg || (props.block.type === 'cta' ? primaryColor.value : null);
    if (props.block.props?.textEffect === 'gradient' && band && primaryColor.value && contrast(primaryColor.value, band) < 2.5) {
        style['--tx-accent'] = mixHex(text || readableOn(band), (lum(band) ?? 1) > 0.4 ? '#000000' : '#ffffff', 0.45);
    }
    if (!bg && !text) return style;
    if (bg) {
        style['--bg'] = bg;
        if (props.block.type === 'cta') style['--primary'] = bg;
        if (props.block.type === 'footer') style['--secondary'] = bg;
        // Same rule as the published site: brand-coloured accents must stay visible on this band.
        if (props.block.type !== 'cta' && primaryColor.value && contrast(primaryColor.value, bg) < 2.5) style['--primary'] = text || readableOn(bg);
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
        :class="['anim-on relative', editable && 'group/block cursor-pointer']"
        :style="sectionStyle"
        @click.stop="editable && emit('select', block.id)"
    >
        <div
            v-if="component"
            ref="animEl"
            :data-anim="anim || undefined"
            :data-anim-speed="anim ? animSpeed : undefined"
            :data-anim-stagger="anim && animStagger ? '' : undefined"
            :data-anim-whole="anim && block.type === 'navbar' ? '' : undefined"
            :data-anim-custom="customJson"
            :data-fx="fxJson"
            :data-hover="hover"
            :data-btn-fx="buttonFx"
            :data-text-fx="textFx"
            :data-text-speed="textSpeed"
            :data-text-phrases="textPhrases"
        >
            <component
                :is="component"
                v-bind="block.props"
                :editable="editable"
                :id="block.id"
                @update:props="(patch) => emit('update:props', block.id, patch)"
            />
        </div>
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
