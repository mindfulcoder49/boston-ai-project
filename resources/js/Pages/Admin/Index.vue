<template>
  <PageTemplate>
    <Head title="Admin - Manage Public Maps" />
    <div class="container mx-auto p-4 sm:p-6 lg:p-8">
      <h1 class="text-2xl font-semibold text-gray-800 mb-6">Manage Public Maps</h1>

      <div v-if="$page.props.flash.success" class="mb-4 p-4 bg-green-100 text-green-700 rounded-md">
        {{ $page.props.flash.success }}
      </div>
      <div v-if="$page.props.flash.error" class="mb-4 p-4 bg-red-100 text-red-700 rounded-md">
        {{ $page.props.flash.error }}
      </div>

      <div v-if="mapsForApproval.length === 0" class="text-gray-600">
        No maps are currently marked as public by users or awaiting approval.
      </div>

      <div v-else class="space-y-4">
        <div v-for="map in mapsForApproval" :key="map.id" class="bg-white shadow-md rounded-lg p-4">
          <div class="flex flex-col sm:flex-row justify-between sm:items-start">
            <div class="mb-3 sm:mb-0">
              <h2 class="text-xl font-semibold text-indigo-600">{{ map.name }}</h2>
              <p v-if="map.description" class="text-sm text-gray-500 mt-1">{{ map.description }}</p>
              <p class="text-xs text-gray-400 mt-2">
                Account Creator: <span class="font-medium">{{ map.user?.name || 'N/A' }}</span>
              </p>
              <p class="text-xs text-gray-400">
                Display Name: <span class="font-medium">{{ map.creator_display_name || '(Not set)' }}</span>
              </p>
              <p class="text-xs text-gray-400">
                User Public Status: <span :class="map.is_public ? 'text-green-600 font-semibold' : 'text-red-600 font-semibold'">{{ map.is_public ? 'Public' : 'Private' }}</span>
              </p>
              <p class="text-xs text-gray-400">
                Approval Status:
                <span :class="map.is_approved ? 'text-green-600 font-semibold' : 'text-yellow-600 font-semibold'">
                  {{ map.is_approved ? 'Approved' : 'Pending Approval' }}
                </span>
              </p>
              <p class="text-xs text-gray-400">
                Featured Status:
                <span :class="map.is_featured ? 'text-purple-600 font-semibold' : 'text-gray-500 font-semibold'">
                  {{ map.is_featured ? 'Featured' : 'Not Featured' }}
                </span>
              </p>
              <p class="text-xs text-gray-400">Last Updated: {{ new Date(map.updated_at).toLocaleString() }}</p>
            </div>
            <div class="flex flex-col sm:flex-row sm:space-x-2 space-y-2 sm:space-y-0 items-start sm:items-center">
              <Link :href="route('saved-maps.view', map.id)" class="px-3 py-1.5 text-sm bg-blue-500 text-white rounded-md hover:bg-blue-600 text-center" as="button">View Map</Link>
              <button
                v-if="!map.is_approved && map.is_public"
                @click="approveMap(map.id)"
                class="px-3 py-1.5 text-sm bg-green-500 text-white rounded-md hover:bg-green-600"
              >
                Approve
              </button>
              <button
                v-if="map.is_approved"
                @click="unapproveMap(map.id)"
                class="px-3 py-1.5 text-sm bg-yellow-500 text-white rounded-md hover:bg-yellow-600"
              >
                Unapprove
              </button>
              <button
                v-if="map.is_public && map.is_approved && !map.is_featured"
                @click="featureMap(map.id)"
                class="px-3 py-1.5 text-sm bg-purple-500 text-white rounded-md hover:bg-purple-600"
              >
                Feature
              </button>
              <button
                v-if="map.is_featured"
                @click="unfeatureMap(map.id)"
                class="px-3 py-1.5 text-sm bg-gray-500 text-white rounded-md hover:bg-gray-600"
              >
                Unfeature
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </PageTemplate>
</template>

<script setup>
import PageTemplate from '@/Components/PageTemplate.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';

const props = defineProps({
  mapsForApproval: Array,
});

const page = usePage();

const approveMap = (mapId) => {
  if (confirm('Are you sure you want to approve this map for public viewing?')) {
    router.post(route('admin.maps.approve', mapId), {}, {
      preserveScroll: true,
    });
  }
};

const unapproveMap = (mapId) => {
  if (confirm('Are you sure you want to unapprove this map? It will no longer be publicly visible and will be unfeatured.')) {
    router.post(route('admin.maps.unapprove', mapId), {}, {
      preserveScroll: true,
    });
  }
};

const featureMap = (mapId) => {
  if (confirm('Are you sure you want to feature this map?')) {
    router.post(route('admin.maps.feature', mapId), {}, {
      preserveScroll: true,
    });
  }
};

const unfeatureMap = (mapId) => {
  if (confirm('Are you sure you want to unfeature this map?')) {
    router.post(route('admin.maps.unfeature', mapId), {}, {
      preserveScroll: true,
    });
  }
};
</script>
