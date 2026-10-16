<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AuthInput from '@/Components/Auth/AuthInput.vue';
import PasswordStrength from '@/Components/Auth/PasswordStrength.vue';
import Icon from '@/Components/Landing/Icon.vue';

const passwordInput = ref(null);
const currentPasswordInput = ref(null);

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updatePassword = () => {
    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: () => {
            if (form.errors.password) {
                form.reset('password', 'password_confirmation');
                passwordInput.value.focus();
            }
            if (form.errors.current_password) {
                form.reset('current_password');
                currentPasswordInput.value.focus();
            }
        },
    });
};
</script>

<template>
    <form class="space-y-5" novalidate @submit.prevent="updatePassword">
        <AuthInput id="current_password" ref="currentPasswordInput" v-model="form.current_password" type="password" label="Current password" icon="lock" autocomplete="current-password" :error="form.errors.current_password" />
        <AuthInput id="password" ref="passwordInput" v-model="form.password" type="password" label="New password" icon="lock" placeholder="At least 8 characters" autocomplete="new-password" :error="form.errors.password">
            <template v-if="form.password" #below><PasswordStrength :password="form.password" /></template>
        </AuthInput>
        <AuthInput id="password_confirmation" v-model="form.password_confirmation" type="password" label="Confirm new password" icon="lock" autocomplete="new-password" :error="form.errors.password_confirmation" />

        <div class="flex items-center gap-4">
            <button type="submit" class="btn btn-primary h-10 px-5 text-sm" :disabled="form.processing">{{ form.processing ? 'Updating…' : 'Update password' }}</button>
            <Transition enter-active-class="transition duration-200" enter-from-class="opacity-0" leave-active-class="transition duration-300" leave-to-class="opacity-0">
                <p v-if="form.recentlySuccessful" class="flex items-center gap-1.5 text-sm font-medium text-emerald-500"><Icon name="checkCircle" :stroke="1.8" class="size-4" />Password updated</p>
            </Transition>
        </div>
    </form>
</template>
