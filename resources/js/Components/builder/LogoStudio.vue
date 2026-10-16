<script setup>
import { ref, computed, watch } from 'vue';
import axios from 'axios';
import { useEditor } from '@/stores/editor.js';
import AppModal from '@/Components/App/AppModal.vue';
import Icon from '@/Components/Landing/Icon.vue';
import { useLogoStudio } from './useLogoStudio';
import { useToast } from './useToast';

const props = defineProps({
    projectId: { type: [Number, String], required: true },
});

const editor = useEditor();
const { notify } = useToast();
const { open, hint } = useLogoStudio();

const navbar = computed(() => editor.schema?.sections?.find((s) => s.type === 'navbar') || null);
const brand = computed(() => (navbar.value?.props?.brand || editor.schema?.meta?.title || 'My Brand').slice(0, 60));
const background = computed(() => navbar.value?.props?.sectionBg || editor.schema?.theme?.backgroundColor || '#ffffff');

const concepts = ref([]);
const summary = ref('');
const selected = ref(0);
const loading = ref(false);
const saving = ref(false);
const error = ref('');
const direction = ref('');

const svgUrl = (svg) => `data:image/svg+xml;charset=utf-8,${encodeURIComponent(svg)}`;

async function design() {
    if (!navbar.value) return;
    loading.value = true;
    error.value = '';
    try {
        const { data } = await axios.post(route('ai.logo'), {
            project_id: props.projectId,
            brand: brand.value,
            theme: editor.schema.theme,
            background: background.value,
            instruction: direction.value,
        });
        concepts.value = data.concepts || [];
        summary.value = data.summary || '';
        selected.value = 0;
    } catch (e) {
        error.value = e.response?.data?.error || e.response?.data?.message || 'Could not design logos right now. Please try again.';
    } finally {
        loading.value = false;
    }
}

async function useLogo() {
    const concept = concepts.value[selected.value];
    if (!concept || !navbar.value) return;
    saving.value = true;
    try {
        const { data } = await axios.post(route('projects.logo', props.projectId), {
            spec: concept.spec,
            brand: brand.value,
            theme: editor.schema.theme,
            background: background.value,
        });
        editor.updateProps(navbar.value.id, { logo: data.url, hideBrandText: data.containsName });
        editor.select(navbar.value.id);
        open.value = false;
        notify('Logo added to your navbar. Publish to update your live site.', 'success', { duration: 6000 });
    } catch (e) {
        notify(e.response?.data?.message || 'Could not save the logo. Please try again.', 'error');
    } finally {
        saving.value = false;
    }
}

// Opening the studio starts a design round (using any direction typed into the AI box).
watch(open, (isOpen) => {
    if (!isOpen) return;
    if (!navbar.value) {
        open.value = false;
        notify('Add a Navbar section first — the logo lives there.', 'info');
        return;
    }
    direction.value = /^(add|create|make|design|generate)?\s*(me\s+)?(a\s+)?(new\s+)?logo\.?$/i.test(hint.value.trim()) ? '' : hint.value.trim();
    concepts.value = [];
    design();
});
</script>

<template>
    <AppModal v-model="open" icon="sparkles" title="Design a logo" :description="`AI-designed logos for “${brand}”, drawn as crisp vector files in your site's fonts.`" size="lg">
        <!-- Concepts -->
        <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-2">
            <template v-if="loading">
                <div v-for="n in 4" :key="n" class="concept concept--loading" :style="{ background }">
                    <div class="h-8 w-3/4 animate-pulse rounded-lg bg-lp-ink/10"></div>
                </div>
            </template>
            <template v-else>
                <button
                    v-for="(c, i) in concepts"
                    :key="i"
                    type="button"
                    :class="['concept', selected === i && 'concept--active']"
                    :style="{ background }"
                    :aria-pressed="selected === i"
                    @click="selected = i"
                >
                    <img :src="svgUrl(c.svg)" :alt="`${c.spec.name} logo option`" class="h-10 w-auto max-w-full" />
                    <span class="concept-name">{{ c.spec.name }}</span>
                    <span v-if="selected === i" class="concept-check"><Icon name="check" :stroke="3" class="size-3" /></span>
                </button>
            </template>
        </div>

        <p v-if="error" class="mt-3 flex items-start gap-1.5 text-[13px] font-medium text-rose-500"><Icon name="exclamation" :stroke="1.8" class="mt-px size-4 shrink-0" />{{ error }}</p>
        <p v-else-if="summary && !loading" class="mt-3 flex items-start gap-1.5 text-[13px] text-lp-muted"><Icon name="sparkles" :stroke="1.8" class="mt-px size-4 shrink-0 text-lp-accent" />{{ summary }}</p>

        <!-- Direction + regenerate -->
        <form class="mt-5 flex gap-2" @submit.prevent="design">
            <input v-model="direction" type="text" maxlength="500" placeholder="Direction, e.g. “trophy icon, more premium, gold accents”" class="field-input" :disabled="loading" />
            <button type="submit" class="btn btn-ghost h-10 shrink-0 px-3.5 text-[13px]" :disabled="loading">
                <Icon name="redo" :stroke="1.8" class="size-4" />{{ concepts.length ? 'More ideas' : 'Design' }}
            </button>
        </form>

        <div class="mt-6 flex flex-col-reverse gap-2.5 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-[11px] text-lp-subtle">Saved to your project images as an SVG. You can still upload your own logo anytime.</p>
            <div class="flex gap-2.5">
                <button type="button" class="btn btn-ghost h-10 px-4 text-sm" @click="open = false">Cancel</button>
                <button type="button" class="btn btn-primary h-10 px-4 text-sm" :disabled="loading || saving || !concepts.length" @click="useLogo">
                    {{ saving ? 'Saving…' : 'Use this logo' }}
                </button>
            </div>
        </div>
    </AppModal>
</template>

<style scoped>
.concept {
    position: relative;
    display: flex;
    min-height: 6.5rem;
    flex-direction: column;
    align-items: flex-start;
    justify-content: center;
    gap: 0.6rem;
    overflow: hidden;
    border-radius: 0.9rem;
    border: 2px solid rgb(var(--lp-ink) / 0.08);
    padding: 1.1rem 1.1rem 0.9rem;
    text-align: left;
    transition: border-color 0.15s, box-shadow 0.15s, transform 0.15s;
}
.concept:hover { border-color: rgb(139 92 246 / 0.45); }
.concept--active { border-color: #8b5cf6; box-shadow: 0 0 0 4px rgb(139 92 246 / 0.18); }
.concept--loading { justify-content: center; }
.concept-name {
    border-radius: 9999px;
    background: rgb(var(--lp-surface) / 0.85);
    padding: 0.1rem 0.5rem;
    font-size: 11px;
    font-weight: 600;
    color: rgb(var(--lp-muted));
}
.concept-check {
    position: absolute;
    right: 0.6rem;
    top: 0.6rem;
    display: flex;
    width: 1.25rem;
    height: 1.25rem;
    align-items: center;
    justify-content: center;
    border-radius: 9999px;
    background: #8b5cf6;
    color: #fff;
}
.field-input {
    min-width: 0;
    flex: 1;
    height: 2.5rem;
    border-radius: 0.65rem;
    border: 1px solid rgb(var(--lp-ink) / 0.12) !important;
    background: rgb(var(--lp-ink) / 0.03) !important;
    padding: 0 0.8rem;
    font-size: 13px;
    color: rgb(var(--lp-fg));
    box-shadow: none !important;
}
.field-input:focus { outline: none; border-color: rgb(139 92 246 / 0.7) !important; box-shadow: 0 0 0 3px rgb(139 92 246 / 0.15) !important; }
.field-input::placeholder { color: rgb(var(--lp-subtle)); }
.btn:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }
</style>
