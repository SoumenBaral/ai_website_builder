<script setup>
import { computed } from 'vue';
import { useEditor } from '@/stores/editor.js';
import Icon from '@/Components/Landing/Icon.vue';
import { humanize } from './blockMeta';
import { ANIMATIONS, HOVER_EFFECTS, BUTTON_EFFECTS, TEXT_EFFECTS, replayAnimation, canonicalAnimation, bgPresets, presetKeyOf } from './animPreview';

const props = defineProps({
    block: { type: Object, required: true },
});
const emit = defineEmits(['ask']);

const editor = useEditor();
const p = computed(() => props.block.props || {});
const update = (patch) => editor.updateProps(props.block.id, patch);

/* ---------- Current values ---------- */
const entrance = computed(() => canonicalAnimation(p.value.animation) || 'none');
const textFx = computed(() => p.value.textEffect || 'none');
const buttonFx = computed(() => p.value.buttonEffect || 'none');
const hoverFx = computed(() => p.value.hoverEffect || 'none');
const effect = computed(() => (p.value.bgEffect?.kind ? p.value.bgEffect : null));
const presets = computed(() => bgPresets(editor.schema?.theme));
const presetKey = computed(() => presetKeyOf(effect.value, editor.schema?.theme));

const hasButtons = computed(() => ['navbar', 'hero', 'cta', 'pricing', 'contact', 'custom'].includes(props.block.type) || !!(p.value.ctaText || p.value.buttonText || p.value.button || p.value.cta));

const label = (list, value) => list.find((x) => x.value === value)?.label || humanize(value);

/* ---------- Tabs (the open tab is remembered while you move between sections) ---------- */
const TABS = [
    { key: 'entrance', label: 'Entrance', icon: 'play' },
    { key: 'text', label: 'Heading', icon: 'pencil' },
    { key: 'buttons', label: 'Buttons', icon: 'cursorClick' },
    { key: 'hover', label: 'Hover', icon: 'cursor' },
    { key: 'background', label: 'Background', icon: 'sparkles' },
];
const tab = computed({ get: () => activeTab.value, set: (v) => (activeTab.value = v) });

const activeIn = computed(() => ({
    entrance: entrance.value !== 'none',
    text: textFx.value !== 'none',
    buttons: buttonFx.value !== 'none',
    hover: hoverFx.value !== 'none',
    background: !!effect.value,
}));

/** "Fade up · Typing · Shine" — what this section does, at a glance. */
const summary = computed(() => {
    const items = [];
    if (activeIn.value.entrance) items.push({ tab: 'entrance', text: entrance.value === 'custom' ? p.value.customAnimation?.name || 'Custom' : label(ANIMATIONS, entrance.value) });
    if (activeIn.value.text) items.push({ tab: 'text', text: label(TEXT_EFFECTS, textFx.value) });
    if (activeIn.value.buttons) items.push({ tab: 'buttons', text: `${label(BUTTON_EFFECTS, buttonFx.value)} buttons` });
    if (activeIn.value.hover) items.push({ tab: 'hover', text: `${label(HOVER_EFFECTS, hoverFx.value)} on hover` });
    if (activeIn.value.background) items.push({ tab: 'background', text: effect.value.name });
    return items;
});

function clearAll() {
    update({ animation: 'none', animationStagger: false, textEffect: 'none', buttonEffect: 'none', hoverEffect: 'none', bgEffect: null });
}

/* ---------- Background effect ---------- */
const setEffect = (spec) => update({ bgEffect: spec ? JSON.parse(JSON.stringify(spec)) : null });
const tweakEffect = (patch) => effect.value && update({ bgEffect: { ...effect.value, ...patch } });

/* ---------- Typing phrases ---------- */
const phrasesText = computed(() => (p.value.typingPhrases || []).join('\n'));
const setPhrases = (value) => update({ typingPhrases: value.split('\n').map((l) => l.trim()).filter(Boolean).slice(0, 5) });

/* ---------- AI suggestions per tab ---------- */
const AI_IDEAS = {
    entrance: ['Spin this in with a bounce', 'Slide in from the left, one by one'],
    text: ['Typewriter effect that rotates 3 phrases', 'Shimmer the heading'],
    buttons: ['Make the buttons pulse', 'Neon glow on the buttons'],
    hover: ['Cards lift with a glow on hover'],
    background: ['Little twinkling stars in the background', 'Falling 🍂 leaves', 'Soft aurora glow'],
};
</script>

<script>
import { ref as vueRef } from 'vue';
// Module scope: stays on the same tab when you select another section.
const activeTab = vueRef('entrance');
</script>

<template>
    <div class="motion">
        <!-- At a glance -->
        <div class="flex min-h-[1.75rem] flex-wrap items-center gap-1.5">
            <template v-if="summary.length">
                <button v-for="s in summary" :key="s.tab" type="button" class="pill" :title="`Edit ${s.tab}`" @click="tab = s.tab">{{ s.text }}</button>
            </template>
            <span v-else class="text-[11px] text-lp-subtle">No motion on this section yet.</span>
            <div class="ml-auto flex items-center gap-0.5">
                <button v-if="summary.length" type="button" class="ed-icon-btn size-7" title="Preview all" @click="replayAnimation(block.id)"><Icon name="play" :stroke="1.9" class="size-3.5" /></button>
                <button v-if="summary.length" type="button" class="ed-icon-btn ed-icon-btn--danger size-7" title="Remove all motion from this section" @click="clearAll"><Icon name="xMark" :stroke="2" class="size-3.5" /></button>
            </div>
        </div>

        <!-- Tabs -->
        <div class="tabs mt-3" role="tablist">
            <button
                v-for="t in TABS"
                :key="t.key"
                type="button"
                role="tab"
                :aria-selected="tab === t.key"
                :class="['tab', tab === t.key && 'tab--on']"
                :title="t.label"
                @click="tab = t.key"
            >
                <span class="relative">
                    <Icon :name="t.icon" :stroke="1.8" class="size-4" />
                    <span v-if="activeIn[t.key]" class="dot"></span>
                </span>
                <span class="tab-label">{{ t.label }}</span>
            </button>
        </div>

        <div class="panel">
            <!-- ENTRANCE -->
            <template v-if="tab === 'entrance'">
                <p class="hint">How the content appears when visitors scroll here.</p>
                <div class="grid grid-cols-3 gap-1">
                    <button v-for="a in ANIMATIONS" :key="a.value" type="button" :class="['chip', entrance === a.value && 'chip--on']" @click="update({ animation: a.value })">
                        <span :class="['glyph', `glyph--${a.value}`]"></span>{{ a.label }}
                    </button>
                    <button v-if="entrance === 'custom'" type="button" class="chip chip--on col-span-3 !justify-center" @click="replayAnimation(block.id)">
                        ✨ {{ p.customAnimation?.name || 'Custom' }} <span class="font-normal opacity-70">· by AI</span>
                    </button>
                </div>
                <template v-if="entrance !== 'none'">
                    <div class="row">
                        <span class="row-label">Speed</span>
                        <div class="seg">
                            <button v-for="sp in ['fast', 'normal', 'slow']" :key="sp" type="button" :class="['chip !justify-center', (p.animationSpeed || 'normal') === sp && 'chip--on']" @click="update({ animationSpeed: sp })">{{ humanize(sp) }}</button>
                        </div>
                    </div>
                    <label class="row cursor-pointer">
                        <span class="row-label !w-auto flex-1">One by one <span class="font-normal text-lp-subtle">— heading, text, then each card</span></span>
                        <button type="button" role="switch" :aria-checked="!!p.animationStagger" :class="['toggle', p.animationStagger && 'toggle--on']" @click="update({ animationStagger: !p.animationStagger })"><span></span></button>
                    </label>
                </template>
            </template>

            <!-- HEADING -->
            <template v-else-if="tab === 'text'">
                <p class="hint">An effect on this section's main heading.</p>
                <div class="grid grid-cols-2 gap-1">
                    <button v-for="t in TEXT_EFFECTS" :key="t.value" type="button" :title="t.hint" :class="['chip', t.value === 'none' && 'col-span-2 !justify-center', textFx === t.value && 'chip--on']" @click="update({ textEffect: t.value })">
                        <span v-if="t.value !== 'none'" class="tx-demo" :data-demo="t.value">Aa</span>{{ t.label }}
                    </button>
                </div>
                <template v-if="textFx !== 'none'">
                    <p class="hint !mt-2">{{ TEXT_EFFECTS.find((t) => t.value === textFx)?.hint }}.</p>
                    <div v-if="textFx !== 'gradient'" class="row">
                        <span class="row-label">Speed</span>
                        <div class="seg">
                            <button v-for="sp in ['fast', 'normal', 'slow']" :key="sp" type="button" :class="['chip !justify-center', (p.textSpeed || 'normal') === sp && 'chip--on']" @click="update({ textSpeed: sp })">{{ humanize(sp) }}</button>
                        </div>
                    </div>
                    <div v-if="textFx === 'typing-loop'" class="mt-3">
                        <label class="ed-label !text-[11px]">Phrases typed after your heading</label>
                        <textarea :value="phrasesText" rows="3" placeholder="One per line, e.g.&#10;Brands that stand out&#10;Stores that grow" class="ed-textarea ed-input-sm" @change="setPhrases($event.target.value)"></textarea>
                        <p v-if="!p.typingPhrases?.length" class="mt-1 text-[11px] text-amber-600">Add a few phrases, or ask the AI to write them.</p>
                    </div>
                </template>
            </template>

            <!-- BUTTONS -->
            <template v-else-if="tab === 'buttons'">
                <template v-if="hasButtons || buttonFx !== 'none'">
                    <p class="hint">Draws the eye to every button in this section.</p>
                    <div class="grid grid-cols-2 gap-1">
                        <button v-for="b in BUTTON_EFFECTS" :key="b.value" type="button" :class="['chip', b.value === 'none' && 'col-span-2 !justify-center', buttonFx === b.value && 'chip--on']" @click="update({ buttonEffect: b.value })">
                            <span v-if="b.value !== 'none'" :class="['btn-demo', `btn-demo--${b.value}`]"></span>
                            <span class="truncate">{{ b.label }}</span>
                            <span v-if="b.value !== 'none'" class="ml-auto truncate pl-1 text-[10px] font-normal opacity-60">{{ b.hint.split(' ').slice(0, 3).join(' ') }}</span>
                        </button>
                    </div>
                </template>
                <p v-else class="empty">This section has no buttons. Button effects live in the Site style tab for the whole site.</p>
            </template>

            <!-- HOVER -->
            <template v-else-if="tab === 'hover'">
                <p class="hint">What cards and photos do under the mouse.</p>
                <div class="grid grid-cols-2 gap-1">
                    <button v-for="h in HOVER_EFFECTS" :key="h.value" type="button" :class="['chip', hoverFx === h.value && 'chip--on']" @click="update({ hoverEffect: h.value })">
                        <span :class="['hv-demo', `hv-demo--${h.value}`]"></span>{{ h.label }}
                    </button>
                </div>
            </template>

            <!-- BACKGROUND -->
            <template v-else>
                <p class="hint">An animated layer over this section's background.</p>
                <div class="grid grid-cols-4 gap-1">
                    <button type="button" :class="['tile', !effect && 'tile--on']" title="None" @click="setEffect(null)">
                        <span class="tile-icon">⊘</span><span class="tile-label">None</span>
                    </button>
                    <button v-for="pr in presets" :key="pr.key" type="button" :class="['tile', presetKey === pr.key && 'tile--on']" :title="pr.label" @click="setEffect(pr.spec)">
                        <span class="tile-icon">{{ pr.icon }}</span><span class="tile-label">{{ pr.short }}</span>
                    </button>
                </div>
                <div v-if="effect && presetKey === null" class="chip chip--on mt-1.5 !justify-center">✨ {{ effect.name }} <span class="font-normal opacity-70">· by AI</span></div>

                <div v-if="effect" class="tune">
                    <div v-if="effect.kind === 'particles'" class="row !mt-0">
                        <span class="row-label">Amount</span>
                        <input type="range" min="3" max="120" :value="effect.count" class="flex-1 accent-violet-500" @change="tweakEffect({ count: Number($event.target.value) })" />
                        <span class="w-8 text-right text-[11px] tabular-nums text-lp-subtle">{{ effect.count }}</span>
                    </div>
                    <div :class="['row', effect.kind !== 'particles' && '!mt-0']">
                        <span class="row-label">Strength</span>
                        <input type="range" min="5" max="100" :value="effect.opacity" class="flex-1 accent-violet-500" @change="tweakEffect({ opacity: Number($event.target.value) })" />
                        <span class="w-8 text-right text-[11px] tabular-nums text-lp-subtle">{{ effect.opacity }}%</span>
                    </div>
                    <div class="row">
                        <span class="row-label">Speed</span>
                        <div class="seg">
                            <button v-for="sp in ['slow', 'normal', 'fast']" :key="sp" type="button" :class="['chip !justify-center', (effect.speed || 'normal') === sp && 'chip--on']" @click="tweakEffect({ speed: sp })">{{ humanize(sp) }}</button>
                        </div>
                    </div>
                    <div class="row">
                        <span class="row-label">Colours</span>
                        <div class="flex flex-1 items-center gap-1.5">
                            <input v-for="(c, ci) in effect.colors" :key="ci" type="color" :value="c" class="ed-color !size-7" @change="tweakEffect({ colors: effect.colors.map((x, xi) => (xi === ci ? $event.target.value : x)) })" />
                        </div>
                        <label v-if="effect.kind === 'particles'" class="flex items-center gap-1.5 text-[11px] text-lp-muted">Glow
                            <button type="button" role="switch" :aria-checked="!!effect.glow" :class="['toggle', effect.glow && 'toggle--on']" @click="tweakEffect({ glow: !effect.glow })"><span></span></button>
                        </label>
                    </div>
                </div>
            </template>

            <!-- Ask the AI for anything not in the list -->
            <div class="ideas">
                <span class="text-[10.5px] font-semibold uppercase tracking-wider text-lp-subtle">Ask AI</span>
                <button v-for="idea in AI_IDEAS[tab]" :key="idea" type="button" class="idea" @click="emit('ask', idea)">
                    <Icon name="sparkles" :stroke="1.8" class="size-3 shrink-0" />{{ idea }}
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* ---------- Summary ---------- */
.pill {
    border-radius: 9999px;
    border: 1px solid rgb(139 92 246 / 0.3);
    background: rgb(139 92 246 / 0.1);
    padding: 0.15rem 0.55rem;
    font-size: 11px;
    font-weight: 600;
    color: rgb(var(--lp-accent));
    transition: background-color 0.15s;
}
.pill:hover { background: rgb(139 92 246 / 0.18); }

/* ---------- Tabs ---------- */
.tabs {
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: 2px;
    border-radius: 0.7rem;
    background: rgb(var(--lp-ink) / 0.05);
    padding: 3px;
}
.tab {
    display: flex;
    min-width: 0;
    flex-direction: column;
    align-items: center;
    gap: 0.15rem;
    border-radius: 0.5rem;
    padding: 0.4rem 0 0.35rem;
    color: rgb(var(--lp-muted));
    transition: background-color 0.15s, color 0.15s, box-shadow 0.15s;
}
.tab:hover { color: rgb(var(--lp-text)); }
.tab--on {
    background: rgb(var(--lp-surface));
    color: rgb(var(--lp-accent));
    box-shadow: 0 1px 3px rgb(0 0 0 / 0.12), 0 0 0 1px rgb(var(--lp-ink) / 0.06);
}
.tab-label { max-width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: 9.5px; font-weight: 600; letter-spacing: -0.015em; }
.dot {
    position: absolute;
    right: -4px;
    top: -2px;
    width: 6px;
    height: 6px;
    border-radius: 9999px;
    background: #8b5cf6;
    box-shadow: 0 0 0 2px rgb(var(--lp-surface));
}

/* ---------- Panel ---------- */
.panel {
    margin-top: 0.5rem;
    border-radius: 0.8rem;
    border: 1px solid rgb(var(--lp-ink) / 0.07);
    padding: 0.75rem;
}
.hint { margin: 0 0 0.6rem; font-size: 11px; line-height: 1.45; color: rgb(var(--lp-subtle)); }
.empty { margin: 0.25rem 0; font-size: 11.5px; line-height: 1.5; color: rgb(var(--lp-muted)); }
.row { margin-top: 0.75rem; display: flex; align-items: center; gap: 0.5rem; }
.row-label { width: 3.5rem; flex-shrink: 0; font-size: 11px; font-weight: 500; color: rgb(var(--lp-muted)); }
.seg { display: grid; flex: 1; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 0.25rem; }
.tune { margin-top: 0.75rem; border-top: 1px solid rgb(var(--lp-ink) / 0.07); padding-top: 0.75rem; }

.chip {
    display: inline-flex;
    min-width: 0;
    align-items: center;
    gap: 0.35rem;
    border-radius: 0.5rem;
    border: 1px solid rgb(var(--lp-ink) / 0.09);
    background: rgb(var(--lp-ink) / 0.02);
    padding: 0.4rem 0.45rem;
    font-size: 11px;
    font-weight: 500;
    white-space: nowrap;
    color: rgb(var(--lp-muted));
    transition: border-color 0.15s, background-color 0.15s, color 0.15s;
}
.chip:hover { border-color: rgb(139 92 246 / 0.4); color: rgb(var(--lp-text)); }
.chip--on { border-color: #8b5cf6; background: rgb(139 92 246 / 0.1); color: rgb(var(--lp-accent)); font-weight: 600; }

.tile {
    display: flex;
    min-width: 0;
    flex-direction: column;
    align-items: center;
    gap: 0.15rem;
    border-radius: 0.55rem;
    border: 1px solid rgb(var(--lp-ink) / 0.09);
    background: rgb(var(--lp-ink) / 0.02);
    padding: 0.45rem 0.15rem 0.35rem;
    color: rgb(var(--lp-muted));
    transition: border-color 0.15s, background-color 0.15s, transform 0.15s;
}
.tile:hover { border-color: rgb(139 92 246 / 0.4); transform: translateY(-1px); }
.tile--on { border-color: #8b5cf6; background: rgb(139 92 246 / 0.1); color: rgb(var(--lp-accent)); }
.tile-icon { font-size: 15px; line-height: 1.1; }
.tile-label { max-width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: 9.5px; font-weight: 600; }

.ideas { margin-top: 0.85rem; display: flex; flex-wrap: wrap; align-items: center; gap: 0.3rem; border-top: 1px dashed rgb(var(--lp-ink) / 0.1); padding-top: 0.7rem; }
.idea {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    border-radius: 9999px;
    background: linear-gradient(180deg, rgb(139 92 246 / 0.12), rgb(139 92 246 / 0.05));
    padding: 0.2rem 0.55rem;
    font-size: 10.5px;
    font-weight: 500;
    color: rgb(var(--lp-accent));
    transition: background-color 0.15s;
}
.idea:hover { background: rgb(139 92 246 / 0.2); }

.toggle { position: relative; width: 2.25rem; height: 1.25rem; flex-shrink: 0; border-radius: 9999px; background: rgb(var(--lp-ink) / 0.15); transition: background-color 0.2s; }
.toggle span { position: absolute; left: 2px; top: 2px; width: 1rem; height: 1rem; border-radius: 9999px; background: #fff; box-shadow: 0 1px 3px rgb(0 0 0 / 0.3); transition: transform 0.2s cubic-bezier(0.22, 1, 0.36, 1); }
.toggle--on { background: #8b5cf6; }
.toggle--on span { transform: translateX(1rem); }

/* ---------- Tiny live demos (play on hover / when selected) ---------- */
.glyph { width: 0.6rem; height: 0.6rem; flex-shrink: 0; border-radius: 0.15rem; background: currentColor; opacity: 0.75; }
.chip:hover .glyph, .chip--on .glyph { animation: 1.4s cubic-bezier(0.16, 1, 0.3, 1) infinite var(--glyph); }
.glyph--none { background: none; border: 1.5px solid currentColor; }
.glyph--fade { --glyph: g-fade; }
.glyph--fade-up { --glyph: g-up; }
.glyph--fade-down { --glyph: g-down; }
.glyph--from-right { --glyph: g-left; }
.glyph--from-left { --glyph: g-right; }
.glyph--zoom { --glyph: g-zoom; }
.glyph--blur { --glyph: g-blur; }
.glyph--flip { --glyph: g-flip; }
@keyframes g-fade { 0% { opacity: 0; } 60%, 100% { opacity: 0.75; } }
@keyframes g-up { 0% { opacity: 0; transform: translateY(5px); } 60%, 100% { opacity: 0.75; transform: none; } }
@keyframes g-down { 0% { opacity: 0; transform: translateY(-5px); } 60%, 100% { opacity: 0.75; transform: none; } }
@keyframes g-left { 0% { opacity: 0; transform: translateX(6px); } 60%, 100% { opacity: 0.75; transform: none; } }
@keyframes g-right { 0% { opacity: 0; transform: translateX(-6px); } 60%, 100% { opacity: 0.75; transform: none; } }
@keyframes g-zoom { 0% { opacity: 0; transform: scale(0.4); } 60%, 100% { opacity: 0.75; transform: none; } }
@keyframes g-blur { 0% { opacity: 0; filter: blur(3px); } 60%, 100% { opacity: 0.75; filter: none; } }
@keyframes g-flip { 0% { opacity: 0; transform: perspective(40px) rotateX(60deg); } 60%, 100% { opacity: 0.75; transform: none; } }

.tx-demo { display: inline-block; width: 1.1rem; flex-shrink: 0; font-size: 10px; font-weight: 700; line-height: 1; text-align: center; }
.chip:hover .tx-demo[data-demo^='typing'], .chip--on .tx-demo[data-demo^='typing'] { overflow: hidden; white-space: nowrap; animation: demo-type 1.6s steps(2) infinite; }
.chip:hover .tx-demo[data-demo='words'], .chip--on .tx-demo[data-demo='words'] { animation: g-up 1.4s ease infinite; }
.chip:hover .tx-demo[data-demo='letters'], .chip--on .tx-demo[data-demo='letters'] { animation: g-down 1.4s ease infinite; }
.tx-demo[data-demo='gradient'] { background: linear-gradient(90deg, currentColor, #a78bfa, currentColor); background-size: 200% 100%; -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent; }
.chip:hover .tx-demo[data-demo='gradient'], .chip--on .tx-demo[data-demo='gradient'] { animation: demo-grad 1.5s linear infinite; }
.tx-demo[data-demo='highlight'] { background: linear-gradient(rgb(139 92 246 / 0.35), rgb(139 92 246 / 0.35)) no-repeat 0 90% / 100% 40%; }
@keyframes demo-type { from { width: 0; } to { width: 1.1rem; } }
@keyframes demo-grad { to { background-position: 200% 0; } }

.btn-demo { position: relative; display: inline-block; width: 0.75rem; height: 0.45rem; flex-shrink: 0; overflow: hidden; border-radius: 0.15rem; background: currentColor; opacity: 0.8; }
.chip:hover .btn-demo--pulse, .chip--on .btn-demo--pulse { overflow: visible; animation: demo-pulse 1.4s ease-out infinite; }
.btn-demo--shine::after { content: ''; position: absolute; inset: 0 auto 0 -60%; width: 40%; background: rgb(255 255 255 / 0.85); transform: skewX(-20deg); }
.chip:hover .btn-demo--shine::after, .chip--on .btn-demo--shine::after { animation: demo-shine 1.4s ease-in-out infinite; }
.chip:hover .btn-demo--glow, .chip--on .btn-demo--glow { overflow: visible; animation: demo-glow 1.2s ease-in-out infinite alternate; }
.chip:hover .btn-demo--bounce, .chip--on .btn-demo--bounce { animation: demo-bounce 1.2s ease infinite; }
.chip:hover .btn-demo--wiggle, .chip--on .btn-demo--wiggle { animation: demo-wiggle 1.2s ease-in-out infinite; }
.chip:hover .btn-demo--pop, .chip--on .btn-demo--pop { animation: demo-pop 1.2s ease infinite; }
@keyframes demo-pulse { from { box-shadow: 0 0 0 0 currentColor; } to { box-shadow: 0 0 0 5px transparent; } }
@keyframes demo-shine { 0% { left: -60%; } 60%, 100% { left: 130%; } }
@keyframes demo-glow { from { box-shadow: 0 0 1px currentColor; } to { box-shadow: 0 0 7px currentColor; } }
@keyframes demo-bounce { 0%, 40%, 100% { transform: translateY(0); } 20% { transform: translateY(-3px); } }
@keyframes demo-wiggle { 0%, 50%, 100% { transform: rotate(0); } 15% { transform: rotate(-12deg); } 35% { transform: rotate(12deg); } }
@keyframes demo-pop { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.3); } }

.hv-demo { width: 0.7rem; height: 0.55rem; flex-shrink: 0; border-radius: 0.15rem; border: 1.5px solid currentColor; opacity: 0.8; }
.hv-demo--none { border-style: dashed; }
.chip:hover .hv-demo--lift, .chip--on .hv-demo--lift { animation: demo-lift 1.2s ease infinite alternate; }
.chip:hover .hv-demo--zoom, .chip--on .hv-demo--zoom { animation: demo-pop 1.2s ease infinite; }
.chip:hover .hv-demo--glow, .chip--on .hv-demo--glow { animation: demo-glow 1.2s ease-in-out infinite alternate; }
@keyframes demo-lift { from { transform: none; box-shadow: none; } to { transform: translateY(-2px); box-shadow: 0 3px 4px -2px currentColor; } }

@media (prefers-reduced-motion: reduce) {
    .glyph, .tx-demo, .btn-demo, .btn-demo::after, .hv-demo { animation: none !important; }
    .tx-demo { width: 1.1rem !important; }
}
</style>
