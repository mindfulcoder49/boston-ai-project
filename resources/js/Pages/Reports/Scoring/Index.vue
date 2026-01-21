<template>
  <PageTemplate>
    <Head title="Neighborhood Scoring Reports" />

    <div class="container mx-auto px-4 py-8">
      <div class="flex justify-between items-center mb-10">
        <h1 class="text-3xl font-bold text-gray-800">
          Neighborhood Scoring Reports
        </h1>
        <button @click="refreshReports" :disabled="isRefreshing" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 disabled:bg-indigo-300 transition-colors">
          <span v-if="isRefreshing">Refreshing...</span>
          <span v-else>Update Report Listing</span>
        </button>
      </div>

      <div v-if="$page.props.flash.status" class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6" role="alert">
        <span class="block sm:inline">{{ $page.props.flash.status }}</span>
      </div>

      <div v-if="error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
        <strong class="font-bold">Error:</strong>
        <span class="block sm:inline">{{ error }}</span>
      </div>

      <div v-if="reports.length > 0" class="bg-white shadow-md rounded-lg">
        <div class="overflow-x-auto">
          <table class="min-w-full leading-normal">
            <thead>
              <tr>
                <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                  Report Title
                </th>
                <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                  Generated At
                </th>
                <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                  Actions
                </th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="report in reports" :key="report.job_id + report.artifact_name" class="hover:bg-gray-50">
                <td class="px-5 py-4 border-b border-gray-200 bg-white text-sm">
                  <p class="text-gray-900 whitespace-no-wrap">{{ report.title }}</p>
                  <p class="text-gray-600 text-xs whitespace-no-wrap">Job ID: {{ report.job_id }}</p>
                </td>
                <td class="px-5 py-4 border-b border-gray-200 bg-white text-sm">
                  <p class="text-gray-900 whitespace-no-wrap">{{ new Date(report.generated_at * 1000).toLocaleString() }}</p>
                </td>
                <td class="px-5 py-4 border-b border-gray-200 bg-white text-sm">
                  <Link :href="route('scoring-reports.show', { jobId: report.job_id, artifactName: report.artifact_name })" class="text-indigo-600 hover:text-indigo-900">
                    View Report
                  </Link>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <div v-else-if="!error" class="text-center py-10">
        <p class="text-gray-600 text-lg">
          No scoring reports found in storage.
        </p>
      </div>
    </div>
  </PageTemplate>
</template>

<script setup>
import PageTemplate from '@/Components/PageTemplate.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
  reports: Array,
  error: String,
});

const isRefreshing = ref(false);

const refreshReports = () => {
  isRefreshing.value = true;
  router.post(route('scoring-reports.refresh'), {}, {
    onFinish: () => {
      isRefreshing.value = false;
    },
    preserveState: false, // Force a full reload of page props
  });
};
</script>
