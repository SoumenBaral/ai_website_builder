<script setup>
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import AuthButton from '@/Components/Auth/AuthButton.vue';
import AuthAlert from '@/Components/Auth/AuthAlert.vue';

const props = defineProps({
    status: { type: String },
});

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};

const verificationLinkSent = computed(() => props.status === 'verification-link-sent');
</script>

<template>
    <AuthLayout
        icon="envelope"
        title="Check your"
        accent="inbox."
        subtitle="Thanks for signing up! Please verify your email address by clicking the link we just sent you. Didn't get it? We'll gladly send another."
    >
        <Head title="Verify email" />

        <AuthAlert v-if="verificationLinkSent" class="mb-6">
            A new verification link has been sent to the email address you provided during registration.
        </AuthAlert>

        <form @submit.prevent="submit">
            <AuthButton :loading="form.processing">Resend verification email</AuthButton>
        </form>

        <template #footer>
            Wrong account?
            <Link :href="route('logout')" method="post" as="button" class="font-semibold text-lp-fg underline decoration-lp-ink/20 underline-offset-4 transition hover:decoration-lp-accent">Log out</Link>
        </template>
    </AuthLayout>
</template>
