<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import AuthInput from '@/Components/Auth/AuthInput.vue';
import AuthButton from '@/Components/Auth/AuthButton.vue';
import PasswordStrength from '@/Components/Auth/PasswordStrength.vue';
import Icon from '@/Components/Landing/Icon.vue';

const props = defineProps({
    email: { type: String, required: true },
    token: { type: String, required: true },
});

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <AuthLayout icon="shield" title="Choose a new" accent="password." subtitle="Make it something you haven't used before.">
        <Head title="Reset password" />

        <form class="space-y-5" novalidate @submit.prevent="submit">
            <AuthInput
                id="email"
                v-model="form.email"
                type="email"
                label="Email"
                icon="envelope"
                autocomplete="username"
                :error="form.errors.email"
                required
            />

            <AuthInput
                id="password"
                v-model="form.password"
                type="password"
                label="New password"
                icon="lock"
                placeholder="At least 8 characters"
                autocomplete="new-password"
                :error="form.errors.password"
                required
                autofocus
            >
                <template v-if="form.password" #below>
                    <PasswordStrength :password="form.password" />
                </template>
            </AuthInput>

            <AuthInput
                id="password_confirmation"
                v-model="form.password_confirmation"
                type="password"
                label="Confirm new password"
                icon="lock"
                placeholder="Repeat your password"
                autocomplete="new-password"
                :error="form.errors.password_confirmation"
                required
            />

            <AuthButton :loading="form.processing" class="!mt-7">
                Reset password <Icon name="arrow" :stroke="2.2" class="size-4" />
            </AuthButton>
        </form>
    </AuthLayout>
</template>
