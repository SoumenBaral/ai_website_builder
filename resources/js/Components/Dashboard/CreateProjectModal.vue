<script setup>
import { useForm } from '@inertiajs/vue3';
import AppModal from '@/Components/App/AppModal.vue';
import AuthInput from '@/Components/Auth/AuthInput.vue';
import Icon from '@/Components/Landing/Icon.vue';
import { showcase } from '@/Components/Landing/content';

const open = defineModel({ type: Boolean, default: false });

const form = useForm({ name: '', description: '' });

function useTemplate(s) {
    if (!form.name.trim()) form.name = s.brand;
    form.description = s.prompt;
}

function submit() {
    form.post(route('projects.store'), {
        onSuccess: () => { open.value = false; form.reset(); },
    });
}
</script>

<template>
    <AppModal v-model="open" icon="plus" title="New website" description="Name it now — you can generate and change everything in the editor." size="lg">
        <form class="space-y-5" novalidate @submit.prevent="submit">
            <AuthInput id="project-name" v-model="form.name" label="Website name" icon="globe" placeholder="My awesome website" :error="form.errors.name" required autofocus />

            <div>
                <label for="project-description" class="flex items-center justify-between text-sm font-medium text-lp-fg">
                    What's it about? <span class="text-xs font-normal text-lp-subtle">Optional — becomes your AI prompt</span>
                </label>
                <textarea
                    id="project-description"
                    v-model="form.description"
                    rows="3"
                    maxlength="1000"
                    placeholder="A calm, minimal site for my yoga studio with class schedule and pricing"
                    class="area mt-2"
                ></textarea>
                <p v-if="form.errors.description" class="mt-2 text-[13px] font-medium text-rose-500">{{ form.errors.description }}</p>
            </div>

            <div>
                <div class="text-xs font-medium uppercase tracking-[0.12em] text-lp-subtle">Start from an idea</div>
                <div class="mt-2.5 grid grid-cols-2 gap-2">
                    <button v-for="s in showcase" :key="s.key" type="button" class="template" @click="useTemplate(s)">
                        <span class="text-[13px] font-semibold text-lp-fg">{{ s.tab }}</span>
                        <span class="line-clamp-1 text-xs text-lp-subtle">{{ s.brand }}</span>
                    </button>
                </div>
            </div>

            <div class="flex flex-col-reverse gap-2.5 pt-1 sm:flex-row sm:justify-end">
                <button type="button" class="btn btn-ghost h-11 px-5 text-sm" @click="open = false">Cancel</button>
                <button type="submit" class="btn btn-primary h-11 px-5 text-sm" :disabled="form.processing">
                    {{ form.processing ? 'Creating…' : 'Create & open editor' }}
                    <Icon v-if="!form.processing" name="arrow" :stroke="2.2" class="size-4" />
                </button>
            </div>
        </form>
    </AppModal>
</template>

<style scoped>
.area {
    display: block;
    width: 100%;
    resize: none;
    border-radius: 0.75rem;
    border: 1px solid rgb(var(--lp-ink) / 0.12);
    background: rgb(var(--lp-surface));
    padding: 0.75rem 1rem;
    font-size: 15px;
    line-height: 1.5;
    color: rgb(var(--lp-fg));
    transition: border-color 0.2s, box-shadow 0.2s;
}
.area::placeholder { color: rgb(var(--lp-subtle) / 0.8); }
.area:focus { outline: none; border-color: rgb(139 92 246 / 0.75); box-shadow: 0 0 0 4px rgb(139 92 246 / 0.15); }
.template {
    display: flex;
    flex-direction: column;
    gap: 0.15rem;
    border-radius: 0.75rem;
    border: 1px solid rgb(var(--lp-ink) / 0.09);
    background: rgb(var(--lp-ink) / 0.02);
    padding: 0.6rem 0.8rem;
    text-align: left;
    transition: border-color 0.2s, background-color 0.2s;
}
.template:hover { border-color: rgb(139 92 246 / 0.45); background: rgb(139 92 246 / 0.06); }
</style>
