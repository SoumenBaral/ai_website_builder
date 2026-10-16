<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import AuthInput from '@/Components/Auth/AuthInput.vue';
import AuthButton from '@/Components/Auth/AuthButton.vue';

const form = useForm({
    password: '',
});

const submit = () => {
    form.post(route('password.confirm'), {
        onFinish: () => form.reset(),
    });
};
</script>

<template>
    <AuthLayout icon="shield" title="Confirm it's" accent="you." subtitle="This is a secure area. Please confirm your password before continuing.">
        <Head title="Confirm password" />

        <form class="space-y-6" novalidate @submit.prevent="submit">
            <AuthInput
                id="password"
                v-model="form.password"
                type="password"
                label="Password"
                icon="lock"
                autocomplete="current-password"
                :error="form.errors.password"
                required
                autofocus
            />

            <AuthButton :loading="form.processing">Confirm</AuthButton>
        </form>
    </AuthLayout>
</template>
