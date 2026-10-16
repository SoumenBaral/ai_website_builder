<script setup>
import { computed } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';

defineProps({
    mustVerifyEmail: { type: Boolean },
    status: { type: String },
});

const page = usePage();
const user = computed(() => page.props.auth.user);
const usage = computed(() => page.props.usage);
const initials = computed(() => (user.value?.name || '?').split(/\s+/).map((w) => w[0]).slice(0, 2).join('').toUpperCase());
const memberSince = computed(() => user.value?.created_at ? new Date(user.value.created_at).toLocaleDateString('en-US', { month: 'long', year: 'numeric' }) : '');
const plan = computed(() => {
    const p = usage.value?.plan || 'free';
    return p.charAt(0).toUpperCase() + p.slice(1);
});

const sections = [
    { key: 'profile', title: 'Profile', description: 'Your name and the email address you use to sign in.' },
    { key: 'password', title: 'Password', description: 'Use a long, unique password to keep your websites safe.' },
    { key: 'danger', title: 'Delete account', description: 'Permanently remove your account and all of its data.' },
];
</script>

<template>
    <Head title="Settings" />

    <AuthenticatedLayout>
        <div class="mx-auto max-w-5xl px-4 pb-16 pt-8 sm:px-6 lg:px-10 lg:pt-10">
            <div class="intro">
                <p class="text-sm text-lp-subtle">Account</p>
                <h1 class="mt-1 text-[1.9rem] font-bold tracking-[-0.03em] text-lp-fg sm:text-[2.2rem]">Settings</h1>
            </div>

            <!-- Account summary -->
            <div class="intro card mt-8 flex flex-col gap-5 p-5 sm:flex-row sm:items-center sm:p-6" style="--d: 60ms">
                <span class="avatar">{{ initials }}</span>
                <div class="min-w-0 flex-1">
                    <div class="truncate text-lg font-semibold text-lp-fg">{{ user.name }}</div>
                    <div class="truncate text-sm text-lp-muted">{{ user.email }}</div>
                </div>
                <div class="flex flex-wrap gap-2">
                    <span class="chip !text-lp-text"><span class="size-1.5 rounded-full bg-gradient-to-r from-indigo-500 to-fuchsia-500"></span>{{ plan }} plan</span>
                    <span v-if="usage" class="chip">{{ usage.remaining }}/{{ usage.limit }} AI generations left</span>
                    <span v-if="memberSince" class="chip">Member since {{ memberSince }}</span>
                </div>
            </div>

            <div class="mt-10 divide-y divide-lp-ink/[0.08]">
                <section v-for="(s, i) in sections" :key="s.key" class="intro grid gap-5 py-8 first:pt-0 lg:grid-cols-[15rem_1fr] lg:gap-10" :style="{ '--d': `${120 + i * 60}ms` }">
                    <div>
                        <h2 :class="['text-base font-semibold', s.key === 'danger' ? 'text-rose-500' : 'text-lp-fg']">{{ s.title }}</h2>
                        <p class="mt-1 text-sm leading-relaxed text-lp-muted">{{ s.description }}</p>
                    </div>
                    <div :class="['card p-5 sm:p-7', s.key === 'danger' && 'border-rose-500/20']">
                        <UpdateProfileInformationForm v-if="s.key === 'profile'" :must-verify-email="mustVerifyEmail" :status="status" class="max-w-lg" />
                        <UpdatePasswordForm v-else-if="s.key === 'password'" class="max-w-lg" />
                        <DeleteUserForm v-else class="max-w-lg" />
                    </div>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.avatar {
    display: flex;
    width: 3.5rem;
    height: 3.5rem;
    flex-shrink: 0;
    align-items: center;
    justify-content: center;
    border-radius: 9999px;
    background: linear-gradient(135deg, #6366f1, #a855f7 60%, #ec4899);
    font-size: 1.1rem;
    font-weight: 700;
    color: #fff;
    box-shadow: 0 0 0 3px rgb(var(--lp-bg)), 0 0 0 4px rgb(var(--lp-ink) / 0.1);
}
</style>
