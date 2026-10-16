<script setup>
import { nextTick, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AppModal from '@/Components/App/AppModal.vue';
import AuthInput from '@/Components/Auth/AuthInput.vue';

const confirmingUserDeletion = ref(false);
const passwordInput = ref(null);

const form = useForm({
    password: '',
});

const confirmUserDeletion = () => {
    confirmingUserDeletion.value = true;
    nextTick(() => passwordInput.value?.focus());
};

const deleteUser = () => {
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value?.focus(),
        onFinish: () => form.reset(),
    });
};

const closeModal = () => {
    confirmingUserDeletion.value = false;
    form.clearErrors();
    form.reset();
};
</script>

<template>
    <div>
        <p class="text-sm leading-relaxed text-lp-muted">
            Once your account is deleted, all of your websites, pages, versions and data are permanently removed. Download anything you want to keep first.
        </p>
        <button type="button" class="btn mt-5 h-10 border border-rose-500/30 bg-rose-500/10 px-5 text-sm text-rose-500 hover:bg-rose-500/15" @click="confirmUserDeletion">Delete account</button>

        <AppModal :model-value="confirmingUserDeletion" tone="danger" icon="trash" title="Delete your account?" description="This permanently removes your account and every website in it. Enter your password to confirm." @update:model-value="(v) => !v && closeModal()">
            <form class="space-y-5" novalidate @submit.prevent="deleteUser">
                <AuthInput id="delete_password" ref="passwordInput" v-model="form.password" type="password" label="Password" icon="lock" autocomplete="current-password" :error="form.errors.password" />
                <div class="flex flex-col-reverse gap-2.5 sm:flex-row sm:justify-end">
                    <button type="button" class="btn btn-ghost h-11 px-5 text-sm" @click="closeModal">Cancel</button>
                    <button type="submit" class="btn h-11 bg-rose-600 px-5 text-sm text-white hover:bg-rose-500 disabled:opacity-60" :disabled="form.processing">{{ form.processing ? 'Deleting…' : 'Delete account' }}</button>
                </div>
            </form>
        </AppModal>
    </div>
</template>
