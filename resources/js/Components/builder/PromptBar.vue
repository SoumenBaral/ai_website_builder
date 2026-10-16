<script setup>
import { ref, computed, nextTick, watch } from 'vue';
import { useEditor } from '@/stores/editor.js';
import { BLOCK_TYPES } from '@/schema/defaults.js';
import Icon from '@/Components/Landing/Icon.vue';
import { showcase } from '@/Components/Landing/content';
import axios from 'axios';

const props = defineProps({
    projectId: [Number, String],
    // Pre-fills the prompt (e.g. the description typed on the dashboard) without generating.
    initialPrompt: { type: String, default: '' },
});

const emit = defineEmits(['generated', 'error']);
const editor = useEditor();

const prompt = ref(props.initialPrompt);
const loading = ref(false);
const statusText = ref('');
const input = ref(null);

const hasSchema = computed(() => editor.schema?.sections?.length > 0);
const selectedBlock = computed(() => editor.selectedBlock);
const selectedLabel = computed(() => {
    if (!selectedBlock.value) return '';
    const def = BLOCK_TYPES[selectedBlock.value.type];
    return def?.label || selectedBlock.value.type;
});

// Determine mode: if site exists and a block is selected, default to editing that section
const mode = computed(() => {
    if (!hasSchema.value) return 'generate';
    if (selectedBlock.value) return 'section';
    return 'full';
});

const placeholderText = computed(() => {
    if (mode.value === 'generate') {
        return 'A photography portfolio for a wedding photographer with a gallery and contact section…';
    }
    if (mode.value === 'section') {
        return `Tell AI how to change the ${selectedLabel.value.toLowerCase()}…`;
    }
    return 'Ask AI to change anything…';
});

const ideas = showcase.map((s) => ({ label: s.tab, prompt: s.prompt }));

const generateStatusMessages = [
    'Analyzing your request...',
    'Choosing the perfect layout...',
    'Writing compelling copy...',
    'Selecting a color palette...',
    'Arranging sections...',
    'Adding finishing touches...',
];

const editStatusMessages = [
    'Understanding your changes...',
    'Rewriting content...',
    'Polishing the result...',
];

let statusInterval = null;

function startStatusCycle(messages) {
    let i = 0;
    statusText.value = messages[0];
    statusInterval = setInterval(() => {
        i = (i + 1) % messages.length;
        statusText.value = messages[i];
    }, 2000);
}

function stopStatusCycle() {
    clearInterval(statusInterval);
    statusText.value = '';
}

async function submit() {
    if (!prompt.value.trim() || loading.value) return;

    loading.value = true;

    try {
        if (mode.value === 'generate') {
            // Generate new site
            startStatusCycle(generateStatusMessages);
            const { data } = await axios.post(route('ai.generate'), {
                prompt: prompt.value,
                project_id: props.projectId,
            });
            emit('generated', data.schema);
        } else if (mode.value === 'section' && selectedBlock.value) {
            // Edit selected section only
            startStatusCycle(editStatusMessages);
            const { data } = await axios.post(route('ai.editSection'), {
                section: selectedBlock.value,
                theme: editor.schema.theme,
                instruction: prompt.value,
            });
            editor.updateSection(selectedBlock.value.id, data.section);
        } else {
            // Edit full site
            startStatusCycle(editStatusMessages);
            const { data } = await axios.post(route('ai.edit'), {
                schema: editor.schema,
                instruction: prompt.value,
            });
            editor.setSchema(data.schema);
        }
        prompt.value = '';
    } catch (err) {
        const message = err.response?.data?.error || 'AI request failed. Please try again.';
        emit('error', message);
    } finally {
        loading.value = false;
        stopStatusCycle();
    }
}

/* Enter sends, Shift+Enter adds a new line */
function onKeydown(e) {
    if (e.key === 'Enter' && !e.shiftKey && !e.isComposing) {
        e.preventDefault();
        submit();
    }
}

/* Auto-grow the textarea up to a few lines */
function autosize() {
    const el = input.value;
    if (!el) return;
    el.style.height = 'auto';
    el.style.height = `${Math.min(el.scrollHeight, hasSchema.value ? 132 : 180)}px`;
}
watch(prompt, () => nextTick(autosize));
watch(hasSchema, () => nextTick(autosize));

function useIdea(text) {
    prompt.value = text;
    nextTick(() => input.value?.focus());
}

defineExpose({ focus: () => input.value?.focus() });
</script>

<template>
    <!-- Empty site: centred composer -->
    <div v-if="!hasSchema" class="pointer-events-none absolute inset-0 z-10 flex items-center justify-center overflow-y-auto p-4 sm:p-8">
        <div class="pointer-events-auto w-full max-w-2xl text-center">
            <span class="icon-solid mx-auto size-14 rounded-2xl"><Icon name="sparkles" :stroke="1.6" class="size-7" /></span>
            <h2 class="mt-6 text-[1.75rem] font-bold leading-tight tracking-[-0.03em] text-lp-fg sm:text-4xl">
                Describe your website, <span class="serif text-gradient pr-1 text-[1.08em]">we'll build it.</span>
            </h2>
            <p class="mx-auto mt-3 max-w-md text-[15px] text-lp-muted">One sentence is enough. AI writes the copy, picks colors and images, and lays out every section.</p>

            <form :class="['composer mt-8 text-left', loading && 'composer--busy']" @submit.prevent="submit">
                <textarea
                    ref="input"
                    v-model="prompt"
                    :disabled="loading"
                    rows="3"
                    :placeholder="placeholderText"
                    aria-label="Describe your website"
                    class="composer-input min-h-[6rem]"
                    @keydown="onKeydown"
                ></textarea>
                <div class="flex items-center justify-between gap-3 px-3 pb-3">
                    <span class="truncate text-xs text-lp-subtle">
                        <template v-if="loading"><span class="status-dot"></span>{{ statusText }}</template>
                        <template v-else><kbd class="kbd">Enter</kbd> to generate · <kbd class="kbd">Shift</kbd>+<kbd class="kbd">Enter</kbd> new line</template>
                    </span>
                    <button type="submit" class="btn btn-primary h-10 shrink-0 px-4 text-sm" :disabled="loading || !prompt.trim()">
                        <svg v-if="loading" viewBox="0 0 24 24" fill="none" class="size-4 animate-spin"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-opacity=".25" stroke-width="3" /><path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round" /></svg>
                        <Icon v-else name="sparkles" :stroke="1.8" class="size-4" />
                        {{ loading ? 'Generating…' : 'Generate website' }}
                    </button>
                </div>
            </form>

            <div v-if="!loading" class="mt-5 flex flex-wrap items-center justify-center gap-2">
                <span class="text-xs text-lp-subtle">Try:</span>
                <button v-for="idea in ideas" :key="idea.label" type="button" class="ed-option rounded-full" @click="useIdea(idea.prompt)">{{ idea.label }}</button>
            </div>
            <p class="mt-6 text-xs text-lp-subtle">Prefer to start by hand? Use <span class="font-medium text-lp-muted">Add section</span> in the Layers panel.</p>
        </div>
    </div>

    <!-- Site exists: floating command dock -->
    <div v-else class="pointer-events-none absolute inset-x-0 bottom-0 z-20 flex justify-center px-3 pb-4 sm:px-6 sm:pb-5">
        <form :class="['dock pointer-events-auto w-full max-w-2xl', loading && 'composer--busy']" @submit.prevent="submit">
            <div class="flex items-center gap-2 px-3 pt-2.5">
                <span v-if="mode === 'section'" class="scope scope--section">
                    <Icon name="cursorClick" :stroke="1.8" class="size-3.5" />{{ selectedLabel }}
                    <button type="button" class="-mr-1 ml-0.5 rounded p-0.5 hover:bg-white/15" title="Edit the full site instead" aria-label="Clear selection" @click="editor.select(null)">
                        <Icon name="xMark" :stroke="2.2" class="size-3" />
                    </button>
                </span>
                <span v-else class="scope"><Icon name="globe" :stroke="1.8" class="size-3.5" />Full site</span>
                <span class="truncate text-[11px] text-lp-subtle">
                    <template v-if="loading"><span class="status-dot"></span>{{ statusText }}</template>
                    <template v-else-if="mode === 'section'">AI will only change this section</template>
                    <template v-else>Select a section to edit just that part</template>
                </span>
            </div>
            <div class="flex items-end gap-2 p-2 pl-1">
                <textarea
                    ref="input"
                    v-model="prompt"
                    :disabled="loading"
                    rows="1"
                    :placeholder="placeholderText"
                    aria-label="Ask AI to change your website"
                    class="composer-input min-h-[2.5rem] py-2.5"
                    @keydown="onKeydown"
                ></textarea>
                <button type="submit" class="btn btn-primary size-10 shrink-0 !rounded-xl !p-0" :disabled="loading || !prompt.trim()" :aria-label="mode === 'section' ? 'Edit section with AI' : 'Edit site with AI'">
                    <svg v-if="loading" viewBox="0 0 24 24" fill="none" class="size-4 animate-spin"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-opacity=".25" stroke-width="3" /><path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round" /></svg>
                    <Icon v-else name="send" :stroke="1.8" class="size-4" />
                </button>
            </div>
        </form>
    </div>
</template>

<style scoped>
.composer,
.dock {
    position: relative;
    border-radius: 1.25rem;
    border: 1px solid rgb(var(--lp-ink) / 0.12);
    background: rgb(var(--lp-surface));
    transition: border-color 0.2s, box-shadow 0.2s;
}
.composer { box-shadow: 0 30px 70px -30px rgb(139 92 246 / 0.45), 0 0 0 6px rgb(139 92 246 / 0.05); }
.dock { box-shadow: 0 20px 50px -12px rgb(0 0 0 / 0.35), 0 0 0 1px rgb(0 0 0 / 0.03); }
.composer:focus-within,
.dock:focus-within { border-color: rgb(139 92 246 / 0.6); box-shadow: 0 0 0 4px rgb(139 92 246 / 0.14), 0 24px 60px -20px rgb(139 92 246 / 0.45); }

.composer-input {
    display: block;
    width: 100%;
    resize: none;
    border: 0 !important;
    background: transparent !important;
    padding: 0.9rem 1rem;
    font-size: 15px;
    line-height: 1.5;
    color: rgb(var(--lp-fg));
    box-shadow: none !important;
    outline: none !important;
}
.composer-input::placeholder { color: rgb(var(--lp-subtle) / 0.85); }
.composer-input:disabled { opacity: 0.6; }

/* Working: rotating gradient border (custom property animation, only while busy) */
@property --angle {
    syntax: '<angle>';
    initial-value: 0deg;
    inherits: false;
}
.composer--busy {
    border-color: transparent !important;
    background:
        linear-gradient(rgb(var(--lp-surface)), rgb(var(--lp-surface))) padding-box,
        conic-gradient(from var(--angle), #6366f1, #a855f7, #ec4899, #6366f1) border-box;
    animation: spin-border 2.4s linear infinite;
}
@keyframes spin-border { to { --angle: 360deg; } }

.scope {
    display: inline-flex;
    flex-shrink: 0;
    align-items: center;
    gap: 0.35rem;
    border-radius: 9999px;
    background: rgb(var(--lp-ink) / 0.07);
    padding: 0.2rem 0.6rem;
    font-size: 11px;
    font-weight: 600;
    color: rgb(var(--lp-text));
}
.scope--section { background: linear-gradient(100deg, #6366f1, #a855f7); color: #fff; }

.status-dot {
    display: inline-block;
    width: 6px;
    height: 6px;
    margin-right: 0.4rem;
    border-radius: 9999px;
    background: #a855f7;
    vertical-align: 1px;
    animation: pulse 1.2s ease-in-out infinite;
}
@keyframes pulse { 50% { opacity: 0.3; } }

.kbd {
    border-radius: 0.3rem;
    border: 1px solid rgb(var(--lp-ink) / 0.15);
    padding: 0 0.3rem;
    font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
    font-size: 10px;
}
.btn:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }

@media (prefers-reduced-motion: reduce) {
    .composer--busy { animation: none; }
}
</style>
