<template>
  <PageTemplate>
    <Head title="My Saved Maps" />
    <div
    v-if="$page.props.auth.user" 
    class="container mx-auto p-4 sm:p-6 lg:p-8">
      <h1 class="text-2xl font-semibold text-gray-800 mb-6">My Saved Maps</h1>

      <div v-if="$page.props.flash.success" class="mb-4 p-4 bg-green-100 text-green-700 rounded-md">
        {{ $page.props.flash.success }}
      </div>
      
      <div v-if="userSavedMaps.length === 0" class="text-gray-600">
        You haven't saved any maps yet.
      </div>

      <div v-else class="space-y-4">
        <div v-for="map in userSavedMaps" :key="map.id" class="bg-white shadow-md rounded-lg p-4 hover:shadow-lg transition-shadow">
          <div class="flex justify-between items-start">
            <div>
              <h2 class="text-xl font-semibold text-indigo-600">{{ map.name }}</h2>
              <p v-if="map.description" class="text-sm text-gray-500 mt-1">{{ map.description }}</p>
              <p class="text-xs text-gray-400 mt-2">
                Type: <span class="font-medium">{{ map.map_type === 'single' ? 'Single Data Type' : 'Combined Data Types' }}</span>
                <span v-if="map.map_type === 'single' && map.data_type"> ({{ formatDataTypeName(map.data_type) }})</span>
              </p>
              <p class="text-xs text-gray-400">
                Visibility: <span class="font-medium">{{ map.is_public ? 'Public' : 'Private' }}</span>
              </p>
              <p class="text-xs text-gray-400">Saved: {{ new Date(map.created_at).toLocaleDateString() }}</p>
            </div>
            <div class="flex space-x-2 mt-2 sm:mt-0">
              <Link :href="route('saved-maps.view', map.id)" class="px-3 py-1.5 text-sm bg-blue-500 text-white rounded-md hover:bg-blue-600">View</Link>
              <!-- Add Edit/Delete later if needed -->
              <!-- <Link :href="route('saved-maps.edit', map.id)" class="px-3 py-1.5 text-sm bg-yellow-500 text-white rounded-md hover:bg-yellow-600">Edit</Link> -->
              <button @click="confirmDelete(map)" class="px-3 py-1.5 text-sm bg-red-500 text-white rounded-md hover:bg-red-600">Delete</button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Public Maps Section -->
    <div class="container mx-auto p-4 sm:p-6 lg:p-8 mt-12">
      <h2 class="text-2xl font-semibold text-gray-800 mb-6">Public Maps</h2>

      <div class="mb-6 p-4 sm:p-6 bg-gradient-to-r from-blue-50 via-sky-50 to-indigo-50 border border-blue-200 rounded-lg shadow-sm text-sm">
        <h3 class="text-lg font-semibold text-blue-800 mb-2">Discover Community-Crafted Maps!</h3>
        <p class="text-blue-700">
          Explore insights shared by our users. The depth of historical data you see in these public maps is determined by the creator's subscription plan:
        </p>
        <ul class="list-disc list-inside mt-2 mb-3 text-blue-600 space-y-1 pl-2">
          <li><strong>Pro Insights Users:</strong> Can share maps with access to the <strong>full available data history</strong>.</li>
          <li><strong>Resident Awareness Users:</strong> Can share maps typically showing up to <strong>6 months of data</strong>.</li>
          <li><strong>Registered Users (Free Tier):</strong> Can share maps typically showing up to <strong>2 months of data</strong>.</li>
        </ul>
        <p class="text-blue-700">
          This means you get richer, more comprehensive views from maps shared by our premium subscribers!
        </p>

        <div v-if="!$page.props.auth.user" class="mt-4 pt-4 border-t border-blue-200">
          <p class="font-semibold text-indigo-700 mb-3">Want to create and share your own custom maps?</p>
          <div class="flex flex-col sm:flex-row gap-3 items-center">
            <a :href="route('socialite.redirect', 'google') + '?redirect_to=' + route('saved-maps.index')"
               class="w-full sm:w-auto flex items-center justify-center px-5 py-2.5 border border-transparent rounded-md shadow-sm text-sm font-medium text-indigo-700 bg-white hover:bg-indigo-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
              <img class="h-5 w-5 mr-2" src="https://upload.wikimedia.org/wikipedia/commons/c/c1/Google_%22G%22_logo.svg" alt="Google logo">
              Login with Google
            </a>
            <span class="text-gray-500 text-xs hidden sm:inline-block">or</span>
            <Link :href="route('register') + '?redirect_to=' + route('saved-maps.index')" class="w-full sm:w-auto text-center px-5 py-2.5 border border-indigo-300 rounded-md shadow-sm text-sm font-medium text-indigo-700 bg-indigo-50 hover:bg-indigo-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
              Register Manually
            </Link>
          </div>
          <p class="mt-3 text-xs text-gray-600">
            Already have an account? <Link :href="route('login') + '?redirect_to=' + route('saved-maps.index')" class="font-medium text-indigo-600 hover:text-indigo-500 hover:underline">Log in here</Link>.
          </p>
        </div>
      </div>

      <div v-if="publicSavedMaps.length === 0" class="text-gray-600">
        No public maps are available at the moment.
      </div>

      <div v-else class="space-y-4">
        <div v-for="map in publicSavedMaps" :key="map.id" class="bg-white shadow-md rounded-lg p-4 hover:shadow-lg transition-shadow">
          <div class="flex justify-between items-start">
            <div>
              <h2 class="text-xl font-semibold text-indigo-600">{{ map.name }}</h2>
              <p v-if="map.description" class="text-sm text-gray-500 mt-1">{{ map.description }}</p>
              <p class="text-xs text-gray-400 mt-2">
                Type: <span class="font-medium">{{ map.map_type === 'single' ? 'Single Data Type' : 'Combined Data Types' }}</span>
                <span v-if="map.map_type === 'single' && map.data_type"> ({{ formatDataTypeName(map.data_type) }})</span>
              </p>
              <p class="text-xs text-gray-400">
                Creator: <span class="font-medium">{{ map.creator_display_name || map.user?.name || 'Anonymous' }}</span>
                <span v-if="map.creator_tier_display_name" 
                      :class="['ml-1 px-1.5 py-0.5 rounded-full text-xs', getTierBadgeClass(map.creator_tier_name)]">
                  {{ map.creator_tier_display_name }}
                </span>
              </p>
              <p class="text-xs text-gray-400">
                Visibility: <span class="font-medium">{{ map.is_public ? 'Public' : 'Private' }}</span>
              </p>
              <p class="text-xs text-gray-400">Published: {{ new Date(map.created_at).toLocaleDateString() }}</p>
            </div>
            <div class="flex space-x-2 mt-2 sm:mt-0">
              <Link :href="route('saved-maps.view', map.id)" class="px-3 py-1.5 text-sm bg-blue-500 text-white rounded-md hover:bg-blue-600">View</Link>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div v-if="showDeleteConfirmModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full flex justify-center items-center z-50">
      <div class="bg-white p-6 rounded-lg shadow-xl w-full max-w-md">
        <h3 class="text-lg font-semibold mb-4">Confirm Deletion</h3>
        <p>Are you sure you want to delete the map "<strong>{{ mapToDelete?.name }}</strong>"? This action cannot be undone.</p>
        <div class="flex justify-end space-x-2 mt-6">
          <button type="button" @click="showDeleteConfirmModal = false; mapToDelete = null;" class="p-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">Cancel</button>
          <button @click="deleteMap" :disabled="isDeleting" class="p-2 bg-red-600 text-white rounded-md hover:bg-red-700 disabled:opacity-50">
            {{ isDeleting ? 'Deleting...' : 'Delete' }}
          </button>
        </div>
      </div>
    </div>

  </PageTemplate>
</template>

<script setup>
import PageTemplate from '@/Components/PageTemplate.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
  userSavedMaps: Array,
  publicSavedMaps: Array, // If you implement showing public maps from others
});

const page = usePage();

const showDeleteConfirmModal = ref(false);
const mapToDelete = ref(null);
const isDeleting = ref(false);

const formatDataTypeName = (dataType) => {
  if (!dataType) return '';
  return dataType.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
};

const getTierBadgeClass = (tierName) => {
  const tierLower = tierName?.toLowerCase() || '';
  if (tierLower.includes('pro')) return 'bg-purple-100 text-purple-800';
  if (tierLower.includes('basic')) return 'bg-green-100 text-green-800';
  if (tierLower.includes('free')) return 'bg-blue-100 text-blue-800';
  return 'bg-gray-100 text-gray-800';
};

const confirmDelete = (map) => {
  mapToDelete.value = map;
  showDeleteConfirmModal.value = true;
};

const deleteMap = () => {
  if (!mapToDelete.value) return;
  isDeleting.value = true;
  router.delete(route('saved-maps.destroy', mapToDelete.value.id), {
    preserveScroll: true,
    onSuccess: () => {
      showDeleteConfirmModal.value = false;
      mapToDelete.value = null;
      // Flash message will be shown if set by controller
    },
    onError: (errors) => {
      console.error("Error deleting map:", errors);
      alert('Failed to delete map. Please try again.'); // Simple error feedback
    },
    onFinish: () => {
      isDeleting.value = false;
    }
  });
};
</script>
