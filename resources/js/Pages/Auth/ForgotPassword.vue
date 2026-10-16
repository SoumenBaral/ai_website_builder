<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import AuthInput from '@/Components/Auth/AuthInput.vue';
import AuthButton from '@/Components/Auth/AuthButton.vue';
import AuthAlert from '@/Components/Auth/AuthAlert.vue';
import Icon from '@/Components/Landing/Icon.vue';

defineProps({
    status: { type: String },
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <AuthLayout
        icon="lock"
        title="Forgot your"
        accent="password?"
        subtitle="No problem. Enter your email and we'll send you a link to choose a new one."
    >
        <Head title="Forgot password" />

        <AuthAlert v-if="status" class="mb-6">{{ status }}</AuthAlert>

        <form class="space-y-6" novalidate @submit.prevent="submit">
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

            <AuthButton :loading="form.processing">
                Email reset link <Icon name="arrow" :stroke="2.2" class="size-4" />
            </AuthButton>
        </form>

        <template #footer>
            <Link :href="route('login')" class="inline-flex items-center gap-1.5 font-semibold text-lp-fg transition hover:text-lp-accent">
                <Icon name="arrowLeft" :stroke="2" class="size-4" />Back to log in
            </Link>
        </template>
    </AuthLayout>
</template>
