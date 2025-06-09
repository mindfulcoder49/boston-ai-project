<template>
  <AdminLayout>
    <Head title="Admin - Pipeline File Logs" />
    <div class="container mx-auto">
      <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-semibold text-gray-800">Pipeline Run Logs (File-Based)</h1>
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
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Run ID</th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Start Time</th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">End Time</th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="run in pipelineRuns" :key="run.run_id">
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ run.run_id }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ run.name }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ formatDate(run.start_time) }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ formatDate(run.end_time) }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm">
                <span :class="statusClass(run.status)" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                  {{ run.status }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                <Link :href="route('admin.pipeline.fileLogs.show', run.run_id)" class="text-indigo-600 hover:text-indigo-900">View Details</Link>
                <button @click="confirmDeleteRun(run.run_id)" class="text-red-600 hover:text-red-900">Delete</button>
              </td>
            </tr>
            <tr v-if="!pipelineRuns || pipelineRuns.length === 0">
                <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">No pipeline runs found in history.</td>
            </tr>
          </tbody>
        </table>
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
