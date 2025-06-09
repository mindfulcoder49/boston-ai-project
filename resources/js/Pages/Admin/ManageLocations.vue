<template>
  <AdminLayout>
    <Head title="Admin - Manage Locations" />
    <div class="container mx-auto">
      <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-semibold text-gray-800">Manage Locations</h1>
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
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name / Address</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Coordinates</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Language</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="location in locations.data" :key="location.id">
              <td class="px-4 py-4">
                <div class="text-sm font-medium text-gray-900">{{ location.name }}</div>
                <div class="text-xs text-gray-500 truncate max-w-xs" :title="location.address">{{ location.address }}</div>
              </td>
              <td class="px-4 py-4 whitespace-nowrap text-xs text-gray-500">
                Lat: {{ location.latitude?.toFixed(5) }}<br>Lng: {{ location.longitude?.toFixed(5) }}
              </td>
              <td class="px-4 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900">{{ location.user_name }}</div>
                <div class="text-xs text-gray-500">{{ location.user_email }}</div>
              </td>
              <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">{{ location.language || 'N/A' }}</td>
              <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">{{ formatDate(location.created_at) }}</td>
              <td class="px-4 py-4 whitespace-nowrap text-sm font-medium">
                <div class="flex space-x-2">
                    <button @click="openEditModal(location)" class="px-2 py-1 text-xs bg-blue-500 text-white rounded hover:bg-blue-600">Edit</button>
                    <button @click="confirmDeleteLocation(location)" class="px-2 py-1 text-xs bg-red-500 text-white rounded hover:bg-red-600">Delete</button>
                </div>
              </td>
            </tr>
            <tr v-if="locations.data.length === 0">
              <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">No locations found.</td>
            </tr>
          </tbody>
        </table>
      </div>
        <!-- Pagination -->
      <div v-if="locations.links.length > 3" class="mt-6 flex justify-center">
        <div class="flex flex-wrap -mb-1">
          <template v-for="(link, key) in locations.links" :key="key">
            <div
              v-if="link.url === null"
              class="mr-1 mb-1 px-4 py-3 text-sm leading-4 text-gray-400 border rounded"
              v-html="link.label"
            />
            <Link
              v-else
              class="mr-1 mb-1 px-4 py-3 text-sm leading-4 border rounded hover:bg-white focus:border-indigo-500 focus:text-indigo-500"
              :class="{ 'bg-white': link.active }"
              :href="link.url"
              v-html="link.label"
            />
          </template>
        </div>
      </div>
    </div>

    <EditLocationModal
        :show="showEditLocationModal"
        :location_data="selectedLocation"
        :usersForSelect="usersForSelect"
        @close="closeEditModal"
    />
  </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import EditLocationModal from './Partials/EditLocationModal.vue'; 
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
  locations: Object, // Paginated result
  usersForSelect: Array,
});

const page = usePage();

const showEditLocationModal = ref(false);
const selectedLocation = ref(null);

const formatDate = (dateString) => {
  if (!dateString) return 'N/A';
  return new Date(dateString).toLocaleString();
};

const openEditModal = (location) => {
  selectedLocation.value = JSON.parse(JSON.stringify(location)); // Deep copy
  showEditLocationModal.value = true;
};

const closeEditModal = () => {
  showEditLocationModal.value = false;
  selectedLocation.value = null;
};

const confirmDeleteLocation = (location) => {
  if (confirm(`Are you sure you want to delete the location "${location.name}"? This action cannot be undone.`)) {
    router.delete(route('admin.locations.destroy', location.id), {
      preserveScroll: true,
      onSuccess: () => page.props.flash.success = `Location "${location.name}" deleted successfully.`,
      onError: (errors) => page.props.flash.error = Object.values(errors).join(' ') || 'Failed to delete location.',
    });
  }
};
</script>
