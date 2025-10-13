<template>
  <div class="min-h-screen bg-gray-100 flex flex-col">
    <nav class="bg-white border-b border-gray-200">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <div class="flex">
            <div class="flex-shrink-0 flex items-center">
              <Link :href="route('admin.index')">
                <ApplicationLogo class="block h-9 w-auto" />
              </Link>
              <span class="ml-2 font-semibold text-xl text-gray-800">Admin Panel</span>
            </div>
          </div>
          <div class="hidden sm:ml-6 sm:flex sm:items-center">
             <Link :href="route('map.index')" class="text-sm text-gray-700 hover:text-indigo-600 mr-4">View Site</Link>
            <!-- User Dropdown -->
            <div class="ml-3 relative">
              <Dropdown align="right" width="48">
                <template #trigger>
                  <button v-if="$page.props.auth.user" class="flex items-center text-sm font-medium text-gray-500 hover:text-gray-700 focus:outline-none focus:text-gray-700 transition duration-150 ease-in-out">
                    <div>{{ $page.props.auth.user.name }}</div>
                    <div class="ml-1">
                      <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                      </svg>
                    </div>
                  </button>
                </template>
                <template #content>
                  <DropdownLink :href="route('profile.edit')"> Profile </DropdownLink>
                  <DropdownLink :href="route('logout')" method="post" as="button"> Log Out </DropdownLink>
                </template>
              </Dropdown>
            </div>
          </div>
        </div>
      </div>
    </nav>

    <div class="flex flex-1">
      <!-- Sidebar -->
      <aside class="w-64 bg-white border-r border-gray-200 p-4 space-y-2 hidden md:block">
        <NavLink :href="route('admin.index')" :active="route().current('admin.index')">Dashboard</NavLink>
        <NavLink :href="route('admin.maps.index')" :active="route().current('admin.maps.index') || route().current('admin.maps.edit')">Manage Maps</NavLink>
        <NavLink :href="route('admin.users.index')" :active="route().current('admin.users.index')">Manage Users</NavLink>
        <NavLink :href="route('admin.locations.index')" :active="route().current('admin.locations.index')">Manage Locations</NavLink>
        <NavLink :href="route('admin.pipeline.fileLogs.index')" :active="route().current('admin.pipeline.fileLogs.index') || route().current('admin.pipeline.fileLogs.show')">Pipeline Logs</NavLink>
        <NavLink :href="route('admin.job-runs.index')" :active="route().current('admin.job-runs.index')">Job Run History</NavLink>
        <NavLink :href="route('admin.job-dispatcher.index')" :active="route().current('admin.job-dispatcher.index')">Job Dispatcher</NavLink>
      </aside>

      <!-- Main Content -->
      <main class="flex-1 p-4 sm:p-6 lg:p-8 overflow-y-auto">
        <slot />
      </main>
    </div>
  </div>
</template>

<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue'; // Assuming you have a NavLink component for active states

const page = usePage();
</script>

<style scoped>
/* Scoped styles for AdminLayout if needed */
</style>
