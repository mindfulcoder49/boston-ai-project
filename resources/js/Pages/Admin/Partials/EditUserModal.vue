<template>
  <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" @click.self="closeModal">
    <div class="bg-white p-6 rounded-lg shadow-xl w-full max-w-xl max-h-[90vh] overflow-y-auto"> <!-- Increased max-w -->
      <h2 class="text-xl font-semibold mb-6">Edit User: {{ originalUserName }}</h2> <!-- Use original name for title -->
      <form @submit.prevent="submitForm" class="space-y-4">
        <div>
          <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
          <input type="text" id="name" v-model="form.name" class="mt-1 block w-full input-field" required>
          <div v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</div>
        </div>

        <div>
          <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
          <input type="email" id="email" v-model="form.email" class="mt-1 block w-full input-field" :disabled="isSelfEdit" required>
           <div v-if="form.errors.email" class="text-red-500 text-xs mt-1">{{ form.errors.email }}</div>
           <p v-if="isSelfEdit" class="text-xs text-gray-500 mt-1">Primary admin email cannot be changed here.</p>
        </div>

        <div>
          <label for="role" class="block text-sm font-medium text-gray-700">Role</label>
          <select id="role" v-model="form.role" class="mt-1 block w-full input-field" :disabled="isSelfEdit">
            <option v-for="roleOption in roles" :key="roleOption" :value="roleOption">{{ roleOption.charAt(0).toUpperCase() + roleOption.slice(1) }}</option>
          </select>
          <div v-if="form.errors.role" class="text-red-500 text-xs mt-1">{{ form.errors.role }}</div>
          <p v-if="isSelfEdit" class="text-xs text-gray-500 mt-1">Primary admin role cannot be changed here.</p>
        </div>
        
        <div>
          <label for="manual_subscription_tier" class="block text-sm font-medium text-gray-700">Manual Subscription Tier</label>
          <select id="manual_subscription_tier" v-model="form.manual_subscription_tier" class="mt-1 block w-full input-field">
            <option :value="null">None (Use Stripe/Default)</option>
            <option v-for="tierOption in tiers" :key="tierOption" :value="tierOption">{{ tierOption.charAt(0).toUpperCase() + tierOption.slice(1) }}</option>
          </select>
          <div v-if="form.errors.manual_subscription_tier" class="text-red-500 text-xs mt-1">{{ form.errors.manual_subscription_tier }}</div>
        </div>

        <hr class="my-6">
        <h3 class="text-md font-semibold text-gray-700">Change Password (Optional)</h3>
        <div>
          <label for="password" class="block text-sm font-medium text-gray-700">New Password</label>
          <input type="password" id="password" v-model="form.password" class="mt-1 block w-full input-field" autocomplete="new-password">
          <div v-if="form.errors.password" class="text-red-500 text-xs mt-1">{{ form.errors.password }}</div>
        </div>
        <div>
          <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirm New Password</label>
          <input type="password" id="password_confirmation" v-model="form.password_confirmation" class="mt-1 block w-full input-field" autocomplete="new-password">
        </div>
        
        <hr class="my-6">
        <h3 class="text-md font-semibold text-gray-700">Provider Information (Informational/Correction)</h3>
         <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label for="provider_name" class="block text-sm font-medium text-gray-700">Provider Name</label>
                <input type="text" id="provider_name" v-model="form.provider_name" class="mt-1 block w-full input-field">
                <div v-if="form.errors.provider_name" class="text-red-500 text-xs mt-1">{{ form.errors.provider_name }}</div>
            </div>
            <div>
                <label for="provider_id" class="block text-sm font-medium text-gray-700">Provider ID</label>
                <input type="text" id="provider_id" v-model="form.provider_id" class="mt-1 block w-full input-field">
                <div v-if="form.errors.provider_id" class="text-red-500 text-xs mt-1">{{ form.errors.provider_id }}</div>
            </div>
        </div>

        <hr class="my-6">
        <h3 class="text-md font-semibold text-gray-700">Email Verification</h3>
        <p class="text-xs text-gray-600 mb-2">Current status: {{ props.user?.email_verified_at ? `Verified on ${new Date(props.user.email_verified_at).toLocaleDateString()}` : 'Not Verified' }}</p>
        <div>
            <label for="email_verified_at_action" class="block text-sm font-medium text-gray-700">Action</label>
            <select id="email_verified_at_action" v-model="form.email_verified_at_action" class="mt-1 block w-full input-field">
                <option value="keep">Keep Current Status</option>
                <option value="verify">Mark as Verified</option>
                <option value="unverify">Mark as Unverified</option>
            </select>
            <div v-if="form.errors.email_verified_at_action" class="text-red-500 text-xs mt-1">{{ form.errors.email_verified_at_action }}</div>
        </div>


        <div class="mt-8 flex justify-end space-x-3">
          <button type="button" @click="closeModal" class="px-4 py-2 btn-secondary">Cancel</button>
          <button type="submit" :disabled="form.processing" class="px-4 py-2 btn-primary disabled:opacity-50">
            {{ form.processing ? 'Saving...' : 'Save Changes' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import { watch, ref, computed } from 'vue';

const props = defineProps({
  show: Boolean,
  user: Object,
  tiers: Array,
  roles: Array,
});

const emit = defineEmits(['close']);
const page = usePage();
const originalUserName = ref('');

const form = useForm({
  name: '',
  email: '',
  role: 'user',
  manual_subscription_tier: null,
  password: '',
  password_confirmation: '',
  provider_name: '',
  provider_id: '',
  email_verified_at_action: 'keep', // 'keep', 'verify', 'unverify'
});

const isSelfEdit = computed(() => {
    return props.user && page.props.auth.user.id === props.user.id && page.props.auth.user.email === page.props.config.admin_email;
});


watch(() => props.user, (newUser) => {
  if (newUser) {
    originalUserName.value = newUser.name; // Store original name for title
    form.name = newUser.name;
    form.email = newUser.email;
    form.role = newUser.role || 'user';
    form.manual_subscription_tier = newUser.manual_subscription_tier;
    form.provider_name = newUser.provider_name;
    form.provider_id = newUser.provider_id;
    form.password = ''; // Always clear password fields
    form.password_confirmation = '';
    form.email_verified_at_action = 'keep';
    form.errors = {};
  }
}, { immediate: true, deep: true });


const submitForm = () => {
  if (!props.user) return;
  form.put(route('admin.users.update', props.user.id), {
    preserveScroll: true,
    onSuccess: () => {
      closeModal();
    },
    onError: (errors) => {
      console.error("Error updating user:", errors);
    },
  });
};

const closeModal = () => {
  emit('close');
  form.reset();
  form.clearErrors();
  originalUserName.value = '';
};
</script>
<style scoped>
.input-field {
  border: 1px solid #d1d5db;
  border-radius: 0.375rem;
  padding: 0.5rem 0.75rem;
  box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
}
.input-field:focus {
  outline: 2px solid transparent;
  outline-offset: 2px;
  border-color: #6366f1;
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.5);
}
.btn-primary {
    background-color: #4f46e5; color: white; padding: 0.5rem 1rem; border-radius: 0.375rem;
}
.btn-primary:hover {
    background-color: #4338ca;
}
.btn-secondary {
    background-color: #e5e7eb; color: #374151; border: 1px solid #d1d5db; padding: 0.5rem 1rem; border-radius: 0.375rem;
}
.btn-secondary:hover {
    background-color: #d1d5db;
}
</style>
