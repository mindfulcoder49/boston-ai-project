<template>
  <AdminLayout> <!-- Use AdminLayout -->
    <Head title="Admin Dashboard" />
    <div class="container mx-auto"> <!-- AdminLayout handles padding -->
      <h1 class="text-2xl font-semibold text-gray-800 mb-6">Admin Dashboard</h1>

      <div v-if="$page.props.flash.success" class="mb-4 p-4 bg-green-100 text-green-700 rounded-md">
        {{ $page.props.flash.success }}
      </div>
      <div v-if="$page.props.flash.error" class="mb-4 p-4 bg-red-100 text-red-700 rounded-md">
        {{ $page.props.flash.error }}
      </div>

      <div class="mb-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <Link :href="route('admin.maps.index')" class="block p-6 bg-white shadow-lg rounded-lg hover:shadow-xl transition-shadow duration-300">
          <h2 class="text-lg font-semibold text-indigo-600">Manage Maps</h2>
          <p class="text-sm text-gray-500 mt-1">View, edit, approve, and feature all maps.</p>
        </Link>
        <Link :href="route('admin.users.index')" class="block p-6 bg-white shadow-lg rounded-lg hover:shadow-xl transition-shadow duration-300">
          <h2 class="text-lg font-semibold text-indigo-600">Manage Users</h2>
          <p class="text-sm text-gray-500 mt-1">View users, edit details, and manage tiers.</p>
        </Link>
        <Link :href="route('admin.locations.index')" class="block p-6 bg-white shadow-lg rounded-lg hover:shadow-xl transition-shadow duration-300">
          <h2 class="text-lg font-semibold text-indigo-600">Manage Locations</h2>
          <p class="text-sm text-gray-500 mt-1">View, edit, and manage user-saved locations.</p>
        </Link>
        <Link :href="route('admin.pipeline.fileLogs.index')" class="block p-6 bg-white shadow-lg rounded-lg hover:shadow-xl transition-shadow duration-300">
          <h2 class="text-lg font-semibold text-indigo-600">Pipeline Log Viewer</h2>
          <p class="text-sm text-gray-500 mt-1">View data pipeline execution logs.</p>
        </Link>
        <Link :href="route('admin.job-runs.index')" class="block p-6 bg-white shadow-lg rounded-lg hover:shadow-xl transition-shadow duration-300">
          <h2 class="text-lg font-semibold text-indigo-600">Job Run History</h2>
          <p class="text-sm text-gray-500 mt-1">View status of background jobs.</p>
        </Link>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'; // Import AdminLayout
import { Head, Link, usePage } from '@inertiajs/vue3';

const page = usePage();

// No longer need defineOptions for layout if wrapping directly
// defineOptions({ layout: AdminLayout }); // Alternative way to set layout
</script>
