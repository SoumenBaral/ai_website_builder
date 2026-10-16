<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import AuthInput from '@/Components/Auth/AuthInput.vue';
import AuthButton from '@/Components/Auth/AuthButton.vue';
import AuthAlert from '@/Components/Auth/AuthAlert.vue';
import Icon from '@/Components/Landing/Icon.vue';

defineProps({
    canResetPassword: { type: Boolean },
    status: { type: String },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <AuthLayout title="Welcome" accent="back." subtitle="Log in to keep building your websites.">
        <Head title="Log in" />

        <AuthAlert v-if="status" class="mb-6">{{ status }}</AuthAlert>

        <form class="space-y-5" novalidate @submit.prevent="submit">
            <AuthInput
                id="email"
                v-model="form.email"
                type="email"
                label="Email"
                icon="envelope"
                placeholder="you@example.com"
                autocomplete="username"
                :error="form.errors.email"
                required
                autofocus
            />

            <AuthInput
                id="password"
                v-model="form.password"
                type="password"
                label="Password"
                icon="lock"
                placeholder="Your password"
                autocomplete="current-password"
                :error="form.errors.password"
                required
            >
                <template v-if="canResetPassword" #label-end>
                    <Link :href="route('password.request')" class="text-sm font-medium text-lp-accent transition hover:opacity-80">Forgot password?</Link>
                </template>
            </AuthInput>

            <label class="flex w-fit cursor-pointer select-none items-center gap-2.5 text-sm text-lp-muted">
                <input v-model="form.remember" type="checkbox" name="remember" class="size-4 rounded border-lp-ink/25 bg-lp-surface text-violet-600 transition focus:ring-2 focus:ring-violet-500/40 focus:ring-offset-0" />
                Keep me signed in
            </label>

            <AuthButton :loading="form.processing" class="!mt-7">
                Log in <Icon name="arrow" :stroke="2.2" class="size-4" />
            </AuthButton>
        </form>

        <template #footer>
            Don't have an account?
            <Link :href="route('register')" class="font-semibold text-lp-fg underline decoration-lp-ink/20 underline-offset-4 transition hover:decoration-lp-accent">Create one free</Link>
        </template>
    </AuthLayout>
</template>
