<template>
  <AdminLayout>
    <Head title="Admin - Manage Locations" />
    <div class="container mx-auto">
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-2">
        <h1 class="text-2xl font-semibold text-gray-800">Manage Locations</h1>
         <Link :href="route('admin.index')" class="text-sm text-indigo-600 hover:text-indigo-800 self-end sm:self-center">&larr; Back to Admin Dashboard</Link>
      </div>

      <div v-if="$page.props.flash.success" class="mb-4 p-4 bg-green-100 text-green-700 rounded-md">
        {{ $page.props.flash.success }}
      </div>
      <div v-if="$page.props.flash.error" class="mb-4 p-4 bg-red-100 text-red-700 rounded-md">
        {{ $page.props.flash.error }}
      </div>

      <div v-if="locations.data && locations.data.length > 0" class="space-y-4">
        <div v-for="location in locations.data" :key="location.id" class="bg-white shadow-md rounded-lg p-4">
          <h2 class="text-lg font-semibold text-indigo-700 mb-2 truncate" :title="location.name">{{ location.name }}</h2>
          
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-2 text-sm mb-3">
            <div>
              <strong class="text-gray-600">Address:</strong>
              <p class="text-gray-800 truncate" :title="location.address">{{ location.address }}</p>
            </div>
            <div>
              <strong class="text-gray-600">Coordinates:</strong>
              <p class="text-gray-800">Lat: {{ location.latitude?.toFixed(5) }}, Lng: {{ location.longitude?.toFixed(5) }}</p>
            </div>
            <div>
              <strong class="text-gray-600">User:</strong>
              <p class="text-gray-800 truncate" :title="`${location.user_name} (${location.user_email})`">{{ location.user_name }} <span class="text-gray-500">({{ location.user_email }})</span></p>
            </div>
            <div>
              <strong class="text-gray-600">Language:</strong>
              <p class="text-gray-800">{{ location.language || 'N/A' }}</p>
            </div>
            <div>
              <strong class="text-gray-600">Created:</strong>
              <p class="text-gray-800">{{ formatDate(location.created_at) }}</p>
            </div>
          </div>

          <div class="mt-4 flex flex-col sm:flex-row sm:space-x-2 space-y-2 sm:space-y-0">
            <button @click="openEditModal(location)" class="w-full sm:w-auto text-center px-3 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">Edit</button>
            <button @click="confirmDeleteLocation(location)" class="w-full sm:w-auto text-center px-3 py-2 text-sm font-medium text-white bg-red-600 rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">Delete</button>
          </div>
        </div>
      </div>
      <div v-else class="bg-white shadow-md rounded-lg p-6 text-center">
        <p class="text-gray-500">No locations found.</p>
      </div>

        <!-- Pagination -->
      <div v-if="locations.links.length > 3" class="mt-6 flex justify-center">
        <div class="flex flex-wrap -mb-1">
          <template v-for="(link, key) in locations.links" :key="key">
            <div
              v-if="link.url === null"
              class="mr-1 mb-1 px-3 py-2 sm:px-4 sm:py-3 text-xs sm:text-sm leading-4 text-gray-400 border rounded"
              v-html="link.label"
            />
            <Link
              v-else
              class="mr-1 mb-1 px-3 py-2 sm:px-4 sm:py-3 text-xs sm:text-sm leading-4 border rounded hover:bg-white focus:border-indigo-500 focus:text-indigo-500"
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
