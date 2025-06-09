<template>
  <AdminLayout>
    <Head title="Admin - Manage Maps" />
    <div class="container mx-auto">
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-2">
        <h1 class="text-2xl font-semibold text-gray-800">Manage All Maps</h1>
        <Link :href="route('admin.index')" class="text-sm text-indigo-600 hover:text-indigo-800 self-end sm:self-center">&larr; Back to Admin Dashboard</Link>
      </div>

      <div v-if="$page.props.flash.success" class="mb-4 p-4 bg-green-100 text-green-700 rounded-md">
        {{ $page.props.flash.success }}
      </div>
      <div v-if="$page.props.flash.error" class="mb-4 p-4 bg-red-100 text-red-700 rounded-md">
        {{ $page.props.flash.error }}
      </div>

      <div v-if="maps.data && maps.data.length > 0" class="space-y-4">
        <div v-for="map_item in maps.data" :key="map_item.id" class="bg-white shadow-md rounded-lg p-4">
          <div class="mb-3">
            <a :href="map_item.view_url" target="_blank" class="text-lg font-semibold text-indigo-700 hover:underline truncate block" :title="map_item.name">{{ map_item.name }}</a>
            <p class="text-xs text-gray-500 truncate" :title="map_item.description">{{ map_item.description || 'No description' }}</p>
          </div>
          
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-2 text-sm mb-3">
            <div>
              <strong class="text-gray-600">Creator:</strong>
              <p class="text-gray-800 truncate" :title="`${map_item.user_name} (${map_item.user_email})`">
                {{ map_item.user_name }} <span class="text-gray-500">({{ map_item.user_email }})</span>
              </p>
              <p class="text-xs text-gray-500">Display: {{ map_item.creator_display_name || '(Not set)' }}</p>
            </div>
            <div>
              <strong class="text-gray-600">Status:</strong>
              <div class="text-xs space-y-0.5 mt-0.5">
                <span :class="map_item.is_public ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'" class="px-2 py-0.5 inline-flex font-semibold rounded-full">
                  {{ map_item.is_public ? 'Public' : 'Private' }}
                </span>
                <span :class="map_item.is_approved ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'" class="ml-1 px-2 py-0.5 inline-flex font-semibold rounded-full">
                  {{ map_item.is_approved ? 'Approved' : 'Pending' }}
                </span>
                <span :class="map_item.is_featured ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-800'" class="ml-1 px-2 py-0.5 inline-flex font-semibold rounded-full">
                  {{ map_item.is_featured ? 'Featured' : 'Not Featured' }}
                </span>
              </div>
            </div>
            <div>
              <strong class="text-gray-600">Last Updated:</strong>
              <p class="text-gray-800">{{ formatDate(map_item.updated_at) }}</p>
            </div>
             <div>
              <strong class="text-gray-600">Views:</strong>
              <p class="text-gray-800">{{ map_item.view_count }}</p>
            </div>
          </div>

          <div class="mt-4 flex flex-col space-y-2 sm:flex-row sm:flex-wrap sm:space-y-0 sm:gap-2">
            <button @click="openEditModal(map_item)" class="w-full sm:w-auto text-center px-3 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700">Edit</button>
            <button v-if="!map_item.is_approved && map_item.is_public" @click="approveMap(map_item.id)" class="w-full sm:w-auto text-center px-3 py-2 text-sm font-medium text-white bg-green-500 rounded-md hover:bg-green-600">Approve</button>
            <button v-if="map_item.is_approved" @click="unapproveMap(map_item.id)" class="w-full sm:w-auto text-center px-3 py-2 text-sm font-medium text-white bg-yellow-500 rounded-md hover:bg-yellow-600">Unapprove</button>
            <button v-if="map_item.is_public && map_item.is_approved && !map_item.is_featured" @click="featureMap(map_item.id)" class="w-full sm:w-auto text-center px-3 py-2 text-sm font-medium text-white bg-purple-500 rounded-md hover:bg-purple-600">Feature</button>
            <button v-if="map_item.is_featured" @click="unfeatureMap(map_item.id)" class="w-full sm:w-auto text-center px-3 py-2 text-sm font-medium text-white bg-gray-600 rounded-md hover:bg-gray-700">Unfeature</button>
            <button @click="confirmDeleteMap(map_item)" class="w-full sm:w-auto text-center px-3 py-2 text-sm font-medium text-white bg-red-600 rounded-md hover:bg-red-700">Delete</button>
          </div>
        </div>
      </div>
      <div v-else class="bg-white shadow-md rounded-lg p-6 text-center">
        <p class="text-gray-500">No maps found.</p>
      </div>

      <!-- Pagination -->
      <div v-if="maps.links.length > 3" class="mt-6 flex justify-center">
        <div class="flex flex-wrap -mb-1">
          <template v-for="(link, key) in maps.links" :key="key">
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

    <EditMapModal
        :show="showEditMapModal"
        :map_data="selectedMap"
        @close="closeEditModal"
    />
  </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import EditMapModal from './Partials/EditMapModal.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
  maps: Object, // Paginated result
});

const page = usePage();

const showEditMapModal = ref(false);
const selectedMap = ref(null);

const formatDate = (dateString) => {
  if (!dateString) return 'N/A';
  return new Date(dateString).toLocaleString();
};

const openEditModal = (map_item) => {
  selectedMap.value = JSON.parse(JSON.stringify(map_item)); // Deep copy
  showEditMapModal.value = true;
};

const closeEditModal = () => {
  showEditMapModal.value = false;
  selectedMap.value = null;
};

const approveMap = (mapId) => {
  if (confirm('Are you sure you want to approve this map for public viewing?')) {
    router.post(route('admin.maps.approve', mapId), {}, { preserveScroll: true });
  }
};

const unapproveMap = (mapId) => {
  if (confirm('Are you sure you want to unapprove this map? It will no longer be publicly visible and will be unfeatured.')) {
    router.post(route('admin.maps.unapprove', mapId), {}, { preserveScroll: true });
  }
};

const featureMap = (mapId) => {
  if (confirm('Are you sure you want to feature this map?')) {
    router.post(route('admin.maps.feature', mapId), {}, { preserveScroll: true });
  }
};

const unfeatureMap = (mapId) => {
  if (confirm('Are you sure you want to unfeature this map?')) {
    router.post(route('admin.maps.unfeature', mapId), {}, { preserveScroll: true });
  }
};

const confirmDeleteMap = (map_item) => {
  if (confirm(`Are you sure you want to delete the map "${map_item.name}"? This action cannot be undone.`)) {
    router.delete(route('admin.maps.destroy', map_item.id), {
      preserveScroll: true,
      onSuccess: () => page.props.flash.success = `Map "${map_item.name}" deleted successfully.`,
      onError: (errors) => page.props.flash.error = Object.values(errors).join(' ') || 'Failed to delete map.',
    });
  }
};
</script>
