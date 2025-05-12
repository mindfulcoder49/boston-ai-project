<script setup>
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm, usePage } from '@inertiajs/vue3'; // Import usePage
import { nextTick, ref, computed } from 'vue'; // Import computed

const page = usePage(); // Get page props
const confirmingUserDeletion = ref(false);
const passwordInput = ref(null);

// Determine if the user likely signed up via social media
// This assumes 'provider_id' is available in the auth.user object
const isSocialUser = computed(() => !!page.props.auth.user?.provider_id);
// A more robust check might be if they have a provider_id AND their password field is the known "random" one,
// or if you have a specific flag like 'has_set_password: false' for social users.
// For now, checking provider_id is a good indicator.

const form = useForm({
    password: '', // Password will only be sent if not a social user
});

const confirmUserDeletion = () => {
    confirmingUserDeletion.value = true;
    // Only focus password input if it's relevant
    if (!isSocialUser.value) {
        nextTick(() => passwordInput.value?.focus());
    }
};

const deleteUser = () => {
    // If it's a social user, we don't send the password.
    // The backend will handle the logic for not requiring it.
    const payload = isSocialUser.value ? {} : { password: form.password };

    form.transform(() => payload) // Ensure only relevant data is sent
        .delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: (errors) => {
            if (errors.password && !isSocialUser.value) {
                passwordInput.value?.focus();
            }
            // Handle other errors if necessary
        },
        onFinish: () => form.reset(),
    });
};

const closeModal = () => {
    confirmingUserDeletion.value = false;
    form.reset();
};
</script>

<template>
    <section class="space-y-6">
        <header>
            <h2 class="text-lg font-medium text-gray-900">Delete Account</h2>
            <p class="mt-1 text-sm text-gray-600">
                Once your account is deleted, all of its resources and data will be permanently deleted.
                <span v-if="!isSocialUser">
                    Before deleting your account, please download any data or information that you wish to retain.
                </span>
            </p>
        </header>

        <DangerButton @click="confirmUserDeletion">Delete Account</DangerButton>

        <Modal :show="confirmingUserDeletion" @close="closeModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900">
                    Are you sure you want to delete your account?
                </h2>

                <p class="mt-1 text-sm text-gray-600">
                    Once your account is deleted, all of its resources and data will be permanently deleted.
                    <span v-if="!isSocialUser">
                        Please enter your password to confirm you would like to permanently delete your account.
                    </span>
                     <span v-else>
                        Clicking "Delete Account" below will permanently remove your account. This action cannot be undone.
                    </span>
                </p>

                <div class="mt-6" v-if="!isSocialUser"> {/* Conditionally show password input */}
                    <InputLabel for="password" value="Password" class="sr-only" />
                    <TextInput
                        id="password"
                        ref="passwordInput"
                        v-model="form.password"
                        type="password"
                        class="mt-1 block w-3/4"
                        placeholder="Password"
                        @keyup.enter="deleteUser"
                    />
                    <InputError :message="form.errors.password" class="mt-2" />
                </div>

                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="closeModal"> Cancel </SecondaryButton>
                    <DangerButton
                        class="ml-3"
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                        @click="deleteUser"
                    >
                        Delete Account
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </section>
</template>