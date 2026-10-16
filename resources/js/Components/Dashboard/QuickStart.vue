<script setup>
import { computed } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import Icon from '@/Components/Landing/Icon.vue';
import { showcase } from '@/Components/Landing/content';
import { nameFromPrompt } from './format';

/*
 * "Describe it" entry point. Creates the project with the prompt as its
 * description; the editor then opens with the prompt pre-filled, so the
 * visitor decides when to spend a generation.
 */
const props = defineProps({
    hero: { type: Boolean, default: false }, // larger variant for the empty state
});

const usage = computed(() => usePage().props.usage);
const outOfGenerations = computed(() => usage.value && usage.value.remaining <= 0);

const form = useForm({ name: '', description: '' });
const ideas = showcase.map((s) => ({ label: s.tab, prompt: s.prompt }));

function submit() {
    const prompt = form.description.trim();
    if (!prompt) return;
    form.name = nameFromPrompt(prompt);
    form.description = prompt;
    form.post(route('projects.store'));
}
</script>

<template>
    <section :class="['quick force-dark relative overflow-hidden rounded-3xl', hero ? 'px-6 py-10 sm:px-12 sm:py-14' : 'p-6 sm:p-8']">
        <div class="quick-bg pointer-events-none absolute inset-0" aria-hidden="true"></div>

        <div :class="['relative', hero && 'mx-auto max-w-2xl text-center']">
            <span class="eyebrow"><Icon name="sparkles" class="size-3.5" />Build with AI</span>
            <h2 :class="['mt-4 font-bold tracking-[-0.03em] text-white', hero ? 'text-3xl sm:text-[2.6rem] sm:leading-[1.1]' : 'text-2xl sm:text-[1.75rem]']">
                What will you <span class="serif text-gradient pr-1 text-[1.08em]">build today?</span>
            </h2>
            <p class="mt-2 text-sm text-zinc-400 sm:text-[15px]">Describe your website in a sentence — we'll set up the project and open the editor with your prompt ready.</p>

            <form class="prompt-box mt-6 flex flex-col gap-2 p-2 sm:flex-row sm:items-center" @submit.prevent="submit">
                <label for="quick-prompt" class="sr-only">Describe your website</label>
                <div class="flex min-w-0 flex-1 items-center gap-3 px-3">
                    <Icon name="sparkles" :stroke="1.7" class="size-5 shrink-0 text-violet-300" />
                    <input
                        id="quick-prompt"
                        v-model="form.description"
                        type="text"
                        maxlength="1000"
                        autocomplete="off"
                        placeholder="A portfolio for a wedding photographer…"
                        class="h-11 w-full min-w-0 border-0 bg-transparent p-0 text-[15px] text-white placeholder:text-zinc-500 focus:ring-0"
                    />
                </div>
                <button type="submit" class="btn btn-primary h-11 shrink-0 px-5 text-sm" :disabled="form.processing || !form.description.trim()">
                    <template v-if="form.processing">Creating…</template>
                    <template v-else>Create website <Icon name="arrow" :stroke="2.2" class="size-4" /></template>
                </button>
            </form>
            <p v-if="form.errors.name || form.errors.description" class="mt-2 text-sm text-rose-400">{{ form.errors.name || form.errors.description }}</p>

            <div :class="['mt-4 flex flex-wrap items-center gap-2', hero && 'justify-center']">
                <span class="text-xs text-zinc-500">Try:</span>
                <button v-for="idea in ideas" :key="idea.label" type="button" class="idea" @click="form.description = idea.prompt">{{ idea.label }}</button>
            </div>

            <p v-if="usage" :class="['mt-5 flex items-center gap-1.5 text-xs', hero && 'justify-center', outOfGenerations ? 'text-rose-400' : 'text-zinc-500']">
                <Icon :name="outOfGenerations ? 'exclamation' : 'bolt'" :stroke="1.8" class="size-3.5" />
                <template v-if="outOfGenerations">You've used all {{ usage.limit }} AI generations this month. You can still create and edit websites manually.</template>
                <template v-else>{{ usage.remaining }} of {{ usage.limit }} AI generations left this month</template>
            </p>
        </div>
    </section>
</template>

<style scoped>
.quick {
    border: 1px solid rgb(255 255 255 / 0.08);
    background: #0b0b12;
    box-shadow: 0 30px 80px -40px rgb(139 92 246 / 0.55);
}
.quick-bg {
    background:
        radial-gradient(ellipse 60% 80% at 100% 0%, rgb(139 92 246 / 0.32), transparent 70%),
        radial-gradient(ellipse 40% 60% at 0% 100%, rgb(99 102 241 / 0.16), transparent 70%),
        linear-gradient(to right, rgb(255 255 255 / 0.035) 1px, transparent 1px) 0 0 / 40px 40px,
        linear-gradient(to bottom, rgb(255 255 255 / 0.035) 1px, transparent 1px) 0 0 / 40px 40px;
    -webkit-mask-image: linear-gradient(to bottom, #000, rgb(0 0 0 / 0.6));
    mask-image: linear-gradient(to bottom, #000, rgb(0 0 0 / 0.6));
}
.prompt-box {
    border-radius: 1rem;
    border: 1px solid rgb(255 255 255 / 0.12);
    background: rgb(255 255 255 / 0.05);
    transition: border-color 0.2s, box-shadow 0.2s;
}
.prompt-box:focus-within { border-color: rgb(167 139 250 / 0.7); box-shadow: 0 0 0 4px rgb(139 92 246 / 0.18); }
.prompt-box input { box-shadow: none !important; outline: none !important; }
.prompt-box .btn:disabled { opacity: 0.55; cursor: not-allowed; transform: none; }
.idea {
    border-radius: 9999px;
    border: 1px solid rgb(255 255 255 / 0.1);
    background: rgb(255 255 255 / 0.04);
    padding: 0.3rem 0.75rem;
    font-size: 12px;
    font-weight: 500;
    color: #d4d4d8;
    transition: border-color 0.2s, background-color 0.2s, color 0.2s;
}
.idea:hover { border-color: rgb(167 139 250 / 0.5); background: rgb(139 92 246 / 0.12); color: #fff; }
</style>
