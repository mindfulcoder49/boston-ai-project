<template>
  <AdminLayout>
    <Head title="Admin - Manage Users" />
    <div class="container mx-auto">
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-2">
        <h1 class="text-2xl font-semibold text-gray-800">Manage Users</h1>
        <Link :href="route('admin.index')" class="text-sm text-indigo-600 hover:text-indigo-800 self-end sm:self-center">&larr; Back to Admin Dashboard</Link>
      </div>

      <div v-if="$page.props.flash.success" class="mb-4 p-4 bg-green-100 text-green-700 rounded-md">
        {{ $page.props.flash.success }}
      </div>
      <div v-if="$page.props.flash.error" class="mb-4 p-4 bg-red-100 text-red-700 rounded-md">
        {{ $page.props.flash.error }}
      </div>

      <div v-if="users && users.length > 0" class="space-y-4">
        <div v-for="user in users" :key="user.id" class="bg-white shadow-md rounded-lg p-4">
          <div class="flex flex-col sm:flex-row justify-between sm:items-start mb-3">
            <div>
              <h2 class="text-lg font-semibold text-indigo-700 truncate" :title="user.name">{{ user.name }}</h2>
              <p class="text-xs text-gray-500 truncate" :title="user.email">{{ user.email }}</p>
            </div>
            <span class="mt-1 sm:mt-0 text-xs font-semibold px-2 py-1 bg-gray-200 text-gray-700 rounded-full self-start sm:self-center">
              Role: {{ user.role }}
            </span>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-3 text-sm mb-3">
            <div>
              <strong class="text-gray-600 block">Subscription:</strong>
              <p class="text-gray-800">
                Effective: <span class="font-semibold">{{ user.effective_tier_details.planName }}</span>
                <span class="text-xs text-gray-500"> ({{ user.effective_tier_details.source }})</span>
              </p>
              <p class="text-xs text-gray-500">
                Manual Tier: <span class="font-semibold">{{ user.manual_subscription_tier || 'None' }}</span>
              </p>
              <div v-if="user.effective_tier_details.source === 'stripe'" class="text-xs mt-1">
                <p class="text-gray-500">Stripe Status: <span class="font-semibold">{{ user.effective_tier_details.status }}</span></p>
                <p v-if="user.effective_tier_details.isOnGracePeriod" class="text-yellow-600">Ends: {{ user.effective_tier_details.endsAt }} (Grace)</p>
                <p v-else-if="user.effective_tier_details.isCancelled && user.effective_tier_details.endsAt" class="text-red-600">Cancelled, Ends: {{ user.effective_tier_details.endsAt }}</p>
                <p v-else-if="user.effective_tier_details.currentPeriodEnd" class="text-gray-500">Renews/Ends: {{ user.effective_tier_details.currentPeriodEnd }}</p>
                <p v-if="user.effective_tier_details.isOnTrial" class="text-blue-600">Trial Ends: {{ user.effective_tier_details.trialEndsAt }}</p>
              </div>
            </div>
            
            <div>
              <strong class="text-gray-600 block">Details:</strong>
              <p class="text-xs text-gray-500">Registered: {{ user.created_at }}</p>
              <p class="text-xs text-gray-500">Email Verified: {{ user.email_verified_at || 'No' }}</p>
            </div>

            <div class="md:col-span-2">
              <strong class="text-gray-600 block mb-1">Locations ({{ user.locations.length }}):</strong>
              <div v-if="user.locations.length" class="max-h-24 overflow-y-auto text-xs space-y-1">
                <ul class="list-disc list-inside pl-1">
                    <li v-for="loc in user.locations" :key="loc.id" class="truncate" :title="loc.name + ' - ' + loc.address">{{ loc.name }}</li>
                </ul>
              </div>
              <p v-else class="text-xs text-gray-500">None</p>
            </div>

            <div class="md:col-span-2">
              <strong class="text-gray-600 block mb-1">Saved Maps ({{ user.saved_maps.length }}):</strong>
              <div v-if="user.saved_maps.length" class="max-h-24 overflow-y-auto text-xs space-y-1">
                 <ul class="list-disc list-inside pl-1">
                    <li v-for="map_item in user.saved_maps" :key="map_item.id" class="truncate">
                        <a :href="map_item.view_url" target="_blank" class="text-indigo-600 hover:underline" :title="map_item.name">{{ map_item.name }}</a>
                        <span class="text-gray-500 text-xxs"> ({{ map_item.is_public ? 'Public' : 'Private' }}{{ map_item.is_approved ? ', Approved' : '' }}{{ map_item.is_featured ? ', Featured' : '' }})</span>
                    </li>
                  </ul>
              </div>
              <p v-else class="text-xs text-gray-500">None</p>
            </div>
          </div>

          <div class="mt-4 flex flex-col sm:flex-row sm:space-x-2 space-y-2 sm:space-y-0">
            <button @click="openEditModal(user)" class="w-full sm:w-auto text-center px-3 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">Edit User</button>
            <button @click="confirmDeleteUser(user)" class="w-full sm:w-auto text-center px-3 py-2 text-sm font-medium text-white bg-red-600 rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">Delete User</button>
          </div>
        </div>
      </div>
      <div v-else class="bg-white shadow-md rounded-lg p-6 text-center">
        <p class="text-gray-500">No users found.</p>
      </div>
    </div>

    <EditUserModal 
        :show="showEditModal" 
        :user="selectedUser"
        :tiers="subscriptionTiers"
        :roles="userRoles"
        @close="closeEditModal" 
    />

  </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'; // Import AdminLayout
import EditUserModal from './Partials/EditUserModal.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
  users: Array,
  subscriptionTiers: Array,
  userRoles: Array,
});

const page = usePage();

const showEditModal = ref(false);
const selectedUser = ref(null);

const openEditModal = (user) => {
  selectedUser.value = JSON.parse(JSON.stringify(user)); // Deep copy to avoid reactive issues with original prop
  showEditModal.value = true;
};

const closeEditModal = () => {
  showEditModal.value = false;
  selectedUser.value = null;
};

const confirmDeleteUser = (user) => {
  if (confirm(`Are you sure you want to delete user "${user.name}" (${user.email})? This action cannot be undone and may affect their associated data.`)) {
    router.delete(route('admin.users.destroy', user.id), {
      preserveScroll: true,
      onSuccess: () => {
        page.props.flash.success = `User "${user.name}" deleted successfully.`;
      },
      onError: (errors) => {
        page.props.flash.error = Object.values(errors).join(' ') || 'Failed to delete user.';
      }
    });
  }
};
</script>
<style scoped>
.text-xxs {
    font-size: 0.65rem; /* smaller than text-xs */
}
</style>
