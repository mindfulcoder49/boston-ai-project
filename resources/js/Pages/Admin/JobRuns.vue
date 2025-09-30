<template>
  <AdminLayout>
    <Head title="Job Runs" />
    <div class="container mx-auto">
      <h1 class="text-2xl font-semibold text-gray-800 mb-6">Job Run History</h1>

      <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Job</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Related To</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Output</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Duration</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="job in jobRuns.data" :key="job.id">
                <td class="px-6 py-4 whitespace-nowrap">
                  <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full" :class="statusClass(job.status)">
                    {{ job.status }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ job.job_class_name }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    <div v-if="job.related">
                        {{ job.related.type }} #{{ job.related.id }}
                    </div>
                    <div v-else>N/A</div>
                </td>
                <td class="px-6 py-4 text-sm text-gray-500 max-w-md truncate" :title="job.output">{{ job.output }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ job.duration || 'N/A' }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ job.created_at }}</td>
              </tr>
              <tr v-if="jobRuns.data.length === 0">
                <td colspan="6" class="px-6 py-4 text-center text-gray-500">No job runs found.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="jobRuns.links.length > 3" class="mt-6 flex justify-between items-center">
        <div class="text-sm text-gray-700">
          Showing {{ jobRuns.from }} to {{ jobRuns.to }} of {{ jobRuns.total }} results
        </div>
        <div class="flex items-center">
          <Link
            v-for="(link, index) in jobRuns.links"
            :key="index"
            :href="link.url"
            class="px-3 py-2 text-sm font-medium rounded-md"
            :class="{
              'bg-indigo-500 text-white': link.active,
              'text-gray-700 hover:bg-gray-100': !link.active && link.url,
              'text-gray-400 cursor-not-allowed': !link.url
            }"
            v-html="link.label"
            :disabled="!link.url"
          />
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
  jobRuns: Object,
});

const statusClass = (status) => {
  const classes = {
    pending: 'bg-yellow-100 text-yellow-800',
    running: 'bg-blue-100 text-blue-800',
    completed: 'bg-green-100 text-green-800',
    failed: 'bg-red-100 text-red-800',
  };
  return classes[status] || 'bg-gray-100 text-gray-800';
};
</script>
