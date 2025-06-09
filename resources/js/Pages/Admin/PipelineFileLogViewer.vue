<template>
  <AdminLayout>
    <Head title="Admin - Pipeline File Logs" />
    <div class="container mx-auto">
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-2">
        <h1 class="text-2xl font-semibold text-gray-800">Pipeline Run Logs (File-Based)</h1>
        <Link :href="route('admin.index')" class="text-sm text-indigo-600 hover:text-indigo-800 self-end sm:self-center">&larr; Back to Admin Dashboard</Link>
      </div>

      <div v-if="$page.props.flash.success" class="mb-4 p-4 bg-green-100 text-green-700 rounded-md">
        {{ $page.props.flash.success }}
      </div>
      <div v-if="$page.props.flash.error" class="mb-4 p-4 bg-red-100 text-red-700 rounded-md">
        {{ $page.props.flash.error }}
      </div>

      <div v-if="pipelineRuns && pipelineRuns.length > 0" class="space-y-4">
        <div v-for="run in pipelineRuns" :key="run.run_id" class="bg-white shadow-md rounded-lg p-4">
          <div class="flex flex-col sm:flex-row justify-between sm:items-center mb-3">
            <h2 class="text-lg font-semibold text-indigo-700 truncate min-w-0 break-all" :title="run.run_id">Run ID: {{ run.run_id }}</h2>
            <span :class="statusClass(run.status)" class="px-2 py-0.5 mt-1 sm:mt-0 inline-flex text-xs leading-5 font-semibold rounded-full self-start sm:self-center sm:ml-2">
              {{ run.status }}
            </span>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-2 text-sm mb-3">
            <div>
              <strong class="text-gray-600">Name:</strong>
              <p class="text-gray-800">{{ run.name }}</p>
            </div>
            <div>
              <strong class="text-gray-600">Start Time:</strong>
              <p class="text-gray-800">{{ formatDate(run.start_time) }}</p>
            </div>
            <div>
              <strong class="text-gray-600">End Time:</strong>
              <p class="text-gray-800">{{ formatDate(run.end_time) }}</p>
            </div>
          </div>

          <div class="mt-4 flex flex-col sm:flex-row sm:space-x-2 space-y-2 sm:space-y-0">
            <Link :href="route('admin.pipeline.fileLogs.show', run.run_id)" class="w-full sm:w-auto text-center px-3 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">View Details</Link>
            <button @click="confirmDeleteRun(run.run_id)" class="w-full sm:w-auto text-center px-3 py-2 text-sm font-medium text-white bg-red-600 rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">Delete</button>
          </div>
        </div>
      </div>
      <div v-else class="bg-white shadow-md rounded-lg p-6 text-center">
        <p class="text-gray-500">No pipeline runs found in history.</p>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';

const props = defineProps({
  pipelineRuns: Array,
});

const page = usePage();

const formatDate = (dateString) => {
  if (!dateString) return 'N/A';
  return new Date(dateString).toLocaleString();
};

const statusClass = (status) => {
  if (status === 'completed') return 'bg-green-100 text-green-800';
  if (status === 'failed') return 'bg-red-100 text-red-800';
  if (status === 'running') return 'bg-yellow-100 text-yellow-800';
  return 'bg-gray-100 text-gray-800';
};

const confirmDeleteRun = (runId) => {
  if (confirm(`Are you sure you want to delete all logs for run ID: ${runId}? This action cannot be undone.`)) {
    router.delete(route('admin.pipeline.fileLogs.delete', runId), {
      preserveScroll: true,
      onSuccess: () => {
        page.props.flash.success = 'Pipeline run logs deleted successfully.';
      },
      onError: (errors) => {
        page.props.flash.error = errors.message || 'Failed to delete pipeline run logs.';
      }
    });
  }
};
</script>
