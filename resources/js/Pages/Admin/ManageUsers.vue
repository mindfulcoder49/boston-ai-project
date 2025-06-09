<template>
  <AdminLayout> <!-- Changed from PageTemplate -->
    <Head title="Admin - Manage Users" />
    <div class="container mx-auto"> <!-- Removed p-4 sm:p-6 lg:p-8 -->
      <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-semibold text-gray-800">Manage Users</h1>
        <Link :href="route('admin.index')" class="text-sm text-indigo-600 hover:text-indigo-800">&larr; Back to Admin Dashboard</Link>
      </div>

      <div v-if="$page.props.flash.success" class="mb-4 p-4 bg-green-100 text-green-700 rounded-md">
        {{ $page.props.flash.success }}
      </div>
      <div v-if="$page.props.flash.error" class="mb-4 p-4 bg-red-100 text-red-700 rounded-md">
        {{ $page.props.flash.error }}
      </div>

      <div class="bg-white shadow-md rounded-lg overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subscription</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Details</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="user in users" :key="user.id">
              <td class="px-4 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">{{ user.name }}</div>
                <div class="text-xs text-gray-500">{{ user.email }}</div>
                <div class="text-xs text-gray-500">Role: <span class="font-semibold">{{ user.role }}</span></div>
                <div class="text-xs text-gray-500">Registered: {{ user.created_at }}</div>
                <div class="text-xs text-gray-500">Email Verified: {{ user.email_verified_at }}</div>
              </td>
              <td class="px-4 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900">
                  Effective: <span class="font-semibold">{{ user.effective_tier_details.planName }}</span>
                  <span class="text-xs"> ({{ user.effective_tier_details.source }})</span>
                </div>
                <div class="text-xs text-gray-500">
                  Manual Tier: <span class="font-semibold">{{ user.manual_subscription_tier || 'None' }}</span>
                </div>
                 <div v-if="user.effective_tier_details.source === 'stripe'">
                    <div class="text-xs text-gray-500">Stripe Status: <span class="font-semibold">{{ user.effective_tier_details.status }}</span></div>
                    <div v-if="user.effective_tier_details.isOnGracePeriod" class="text-xs text-yellow-600">Ends: {{ user.effective_tier_details.endsAt }} (Grace)</div>
                    <div v-else-if="user.effective_tier_details.isCancelled && user.effective_tier_details.endsAt" class="text-xs text-red-600">Cancelled, Ends: {{ user.effective_tier_details.endsAt }}</div>
                     <div v-else-if="user.effective_tier_details.currentPeriodEnd" class="text-xs text-gray-500">Renews/Ends: {{ user.effective_tier_details.currentPeriodEnd }}</div>
                    <div v-if="user.effective_tier_details.isOnTrial" class="text-xs text-blue-600">Trial Ends: {{ user.effective_tier_details.trialEndsAt }}</div>
                </div>
              </td>
              <td class="px-4 py-4 whitespace-nowrap">
                <div class="text-xs text-gray-700">
                  <strong class="block">Locations ({{ user.locations.length }}):</strong>
                  <ul v-if="user.locations.length" class="list-disc list-inside max-h-20 overflow-y-auto">
                    <li v-for="loc in user.locations" :key="loc.id" class="truncate" :title="loc.name + ' - ' + loc.address">{{ loc.name }}</li>
                  </ul>
                  <span v-else>None</span>
                </div>
                <div class="text-xs text-gray-700 mt-2">
                  <strong class="block">Saved Maps ({{ user.saved_maps.length }}):</strong>
                   <ul v-if="user.saved_maps.length" class="list-disc list-inside max-h-20 overflow-y-auto">
                    <li v-for="map in user.saved_maps" :key="map.id" class="truncate">
                        <a :href="map.view_url" target="_blank" class="text-indigo-600 hover:underline" :title="map.name">{{ map.name }}</a>
                        <span class="text-gray-500 text-xxs"> ({{ map.is_public ? 'Public' : 'Private' }}{{ map.is_approved ? ', Approved' : '' }}{{ map.is_featured ? ', Featured' : '' }})</span>
                    </li>
                  </ul>
                  <span v-else>None</span>
                </div>
              </td>
              <td class="px-4 py-4 whitespace-nowrap text-sm font-medium">
                <div class="flex flex-col space-y-1 items-start">
                    <button @click="openEditModal(user)" class="px-2 py-1 text-xs bg-blue-500 text-white rounded hover:bg-blue-600">Edit User</button>
                    <button @click="confirmDeleteUser(user)" class="px-2 py-1 text-xs bg-red-500 text-white rounded hover:bg-red-600">Delete User</button>
                </div>
              </td>
            </tr>
             <tr v-if="users.length === 0">
                <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">No users found.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <EditUserModal 
        :show="showEditModal" 
        :user="selectedUser"
        :tiers="subscriptionTiers"
        :roles="userRoles"
        @close="closeEditModal" 
    />

  </AdminLayout> <!-- Changed from PageTemplate -->
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
