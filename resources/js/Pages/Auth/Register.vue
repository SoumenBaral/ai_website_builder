<script setup>
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import AuthInput from '@/Components/Auth/AuthInput.vue';
import AuthButton from '@/Components/Auth/AuthButton.vue';
import PasswordStrength from '@/Components/Auth/PasswordStrength.vue';
import Icon from '@/Components/Landing/Icon.vue';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const matches = computed(() => form.password_confirmation.length > 0 && form.password === form.password_confirmation);

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <AuthLayout title="Create your" accent="account." subtitle="Start with 10 free AI generations — no credit card required.">
        <Head title="Create account" />

        <form class="space-y-5" novalidate @submit.prevent="submit">
            <AuthInput
                id="name"
                v-model="form.name"
                label="Full name"
                icon="user"
                placeholder="Jane Cooper"
                autocomplete="name"
                :error="form.errors.name"
                required
                autofocus
            />

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
            />

            <AuthInput
                id="password"
                v-model="form.password"
                type="password"
                label="Password"
                icon="lock"
                placeholder="At least 8 characters"
                autocomplete="new-password"
                :error="form.errors.password"
                required
            >
                <template v-if="form.password" #below>
                    <PasswordStrength :password="form.password" />
                </template>
            </AuthInput>

            <AuthInput
                id="password_confirmation"
                v-model="form.password_confirmation"
                type="password"
                label="Confirm password"
                icon="lock"
                placeholder="Repeat your password"
                autocomplete="new-password"
                :error="form.errors.password_confirmation"
                required
            >
                <template v-if="form.password_confirmation && !form.errors.password_confirmation" #below>
                    <p :class="['mt-2 flex items-center gap-1.5 text-xs font-medium', matches ? 'text-emerald-500' : 'text-lp-subtle']" aria-live="polite">
                        <Icon :name="matches ? 'checkCircle' : 'exclamation'" :stroke="1.8" class="size-4" />
                        {{ matches ? 'Passwords match' : "Passwords don't match yet" }}
                    </p>
                </template>
            </AuthInput>

            <AuthButton :loading="form.processing" class="!mt-7">
                Create account <Icon name="arrow" :stroke="2.2" class="size-4" />
            </AuthButton>
        </form>

        <template #footer>
            Already have an account?
            <Link :href="route('login')" class="font-semibold text-lp-fg underline decoration-lp-ink/20 underline-offset-4 transition hover:decoration-lp-accent">Log in</Link>
        </template>
    </AuthLayout>
</template>
