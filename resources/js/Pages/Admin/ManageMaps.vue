<template>
  <AdminLayout>
    <Head title="Admin - Manage Maps" />
    <div class="container mx-auto">
      <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-semibold text-gray-800">Manage All Maps</h1>
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
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Map Name</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Creator</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Updated</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="map in maps.data" :key="map.id">
              <td class="px-4 py-4">
                <div class="text-sm font-medium text-gray-900 hover:text-indigo-600">
                    <a :href="map.view_url" target="_blank" :title="map.description || map.name">{{ map.name }}</a>
                </div>
                <div class="text-xs text-gray-500 truncate max-w-xs" :title="map.description">{{ map.description }}</div>
              </td>
              <td class="px-4 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900">{{ map.user_name }}</div>
                <div class="text-xs text-gray-500">{{ map.user_email }}</div>
                <div class="text-xs text-gray-500">Display: {{ map.creator_display_name || '(Not set)' }}</div>
              </td>
              <td class="px-4 py-4 whitespace-nowrap">
                <span :class="map.is_public ? 'text-green-600' : 'text-red-600'" class="text-xs font-semibold block">
                  {{ map.is_public ? 'Public' : 'Private' }}
                </span>
                <span :class="map.is_approved ? 'text-green-600' : 'text-yellow-600'" class="text-xs font-semibold block">
                  {{ map.is_approved ? 'Approved' : 'Pending Approval' }}
                </span>
                <span :class="map.is_featured ? 'text-purple-600' : 'text-gray-500'" class="text-xs font-semibold block">
                  {{ map.is_featured ? 'Featured' : 'Not Featured' }}
                </span>
              </td>
              <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">{{ formatDate(map.updated_at) }}</td>
              <td class="px-4 py-4 whitespace-nowrap text-sm font-medium">
                <div class="flex flex-col space-y-1 items-start md:flex-row md:space-y-0 md:space-x-1">
                    <button @click="openEditModal(map)" class="px-2 py-1 text-xs bg-blue-500 text-white rounded hover:bg-blue-600 w-full md:w-auto text-center">Edit</button>
                    <button v-if="!map.is_approved && map.is_public" @click="approveMap(map.id)" class="px-2 py-1 text-xs bg-green-500 text-white rounded hover:bg-green-600 w-full md:w-auto text-center">Approve</button>
                    <button v-if="map.is_approved" @click="unapproveMap(map.id)" class="px-2 py-1 text-xs bg-yellow-500 text-white rounded hover:bg-yellow-600 w-full md:w-auto text-center">Unapprove</button>
                    <button v-if="map.is_public && map.is_approved && !map.is_featured" @click="featureMap(map.id)" class="px-2 py-1 text-xs bg-purple-500 text-white rounded hover:bg-purple-600 w-full md:w-auto text-center">Feature</button>
                    <button v-if="map.is_featured" @click="unfeatureMap(map.id)" class="px-2 py-1 text-xs bg-gray-600 text-white rounded hover:bg-gray-700 w-full md:w-auto text-center">Unfeature</button>
                    <button @click="confirmDeleteMap(map)" class="px-2 py-1 text-xs bg-red-500 text-white rounded hover:bg-red-600 w-full md:w-auto text-center">Delete</button>
                </div>
              </td>
            </tr>
            <tr v-if="maps.data.length === 0">
              <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">No maps found.</td>
            </tr>
          </tbody>
        </table>
      </div>
        <!-- Pagination -->
      <div v-if="maps.links.length > 3" class="mt-6 flex justify-center">
        <div class="flex flex-wrap -mb-1">
          <template v-for="(link, key) in maps.links" :key="key">
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

const openEditModal = (map) => {
  selectedMap.value = JSON.parse(JSON.stringify(map)); // Deep copy
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

const confirmDeleteMap = (map) => {
  if (confirm(`Are you sure you want to delete the map "${map.name}"? This action cannot be undone.`)) {
    router.delete(route('admin.maps.destroy', map.id), {
      preserveScroll: true,
      onSuccess: () => page.props.flash.success = `Map "${map.name}" deleted successfully.`,
      onError: (errors) => page.props.flash.error = Object.values(errors).join(' ') || 'Failed to delete map.',
    });
  }
};
</script>
