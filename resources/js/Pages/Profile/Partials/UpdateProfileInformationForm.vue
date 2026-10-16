<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';
import AuthInput from '@/Components/Auth/AuthInput.vue';
import Icon from '@/Components/Landing/Icon.vue';

defineProps({
    mustVerifyEmail: { type: Boolean },
    status: { type: String },
});

const user = usePage().props.auth.user;

const form = useForm({
    name: user.name,
    email: user.email,
});
</script>

<template>
    <form class="space-y-5" novalidate @submit.prevent="form.patch(route('profile.update'), { preserveScroll: true })">
        <AuthInput id="name" v-model="form.name" label="Full name" icon="user" autocomplete="name" :error="form.errors.name" required />
        <AuthInput id="email" v-model="form.email" type="email" label="Email" icon="envelope" autocomplete="username" :error="form.errors.email" required />

        <div v-if="mustVerifyEmail && user.email_verified_at === null" class="rounded-xl border border-amber-500/25 bg-amber-500/10 px-4 py-3 text-sm text-lp-text">
            Your email address is unverified.
            <Link :href="route('verification.send')" method="post" as="button" class="font-semibold underline underline-offset-4">Resend the verification email.</Link>
            <p v-show="status === 'verification-link-sent'" class="mt-2 font-medium text-emerald-500">A new verification link has been sent to your email address.</p>
        </div>

        <div class="flex items-center gap-4">
            <button type="submit" class="btn btn-primary h-10 px-5 text-sm" :disabled="form.processing">{{ form.processing ? 'Saving…' : 'Save changes' }}</button>
            <Transition enter-active-class="transition duration-200" enter-from-class="opacity-0" leave-active-class="transition duration-300" leave-to-class="opacity-0">
                <p v-if="form.recentlySuccessful" class="flex items-center gap-1.5 text-sm font-medium text-emerald-500"><Icon name="checkCircle" :stroke="1.8" class="size-4" />Saved</p>
            </Transition>
        </div>
    </form>
</template>
