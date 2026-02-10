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

      <div v-if="Object.keys(reportGroups).length > 0" class="space-y-8">
        <div v-for="(dateGroups, city) in reportGroups" :key="city" class="bg-white shadow-md rounded-lg overflow-hidden">
          <h2 class="text-xl font-bold text-gray-700 bg-gray-50 p-4 border-b">{{ city }}</h2>
          <div v-for="(reports, dateKey) in dateGroups" :key="dateKey" class="border-b last:border-b-0">
            <div class="px-5 py-3 bg-gray-100/50">
              <h3 class="text-sm font-semibold text-gray-600">Data Date Range: {{ dateKey }}</h3>
            </div>
            <div class="overflow-x-auto">
              <table class="min-w-full leading-normal">
                <thead>
                  <tr>
                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                      Report Title
                    </th>
                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                      Resolution
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
                      <p class="text-gray-900 whitespace-no-wrap">{{ report.resolution }}</p>
                    </td>
                    <td class="px-5 py-4 border-b border-gray-200 bg-white text-sm">
                      <p class="text-gray-900 whitespace-no-wrap">{{ new Date(report.generated_at * 1000).toLocaleString() }}</p>
                    </td>
                    <td class="px-5 py-4 border-b border-gray-200 bg-white text-sm">
                      <div class="flex items-center space-x-2">
                        <Link :href="route('scoring-reports.show', { jobId: report.job_id, artifactName: report.artifact_name })" class="text-indigo-600 hover:text-indigo-900 text-xs">
                          View Report
                        </Link>
                        <button @click="showParameters(report.parameters)" class="text-gray-600 hover:text-gray-900 text-xs">
                          Parameters
                        </button>
                        <button @click="deleteReport(report)" class="text-red-600 hover:text-red-900 text-xs">
                          Delete
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
      <div v-else-if="!error" class="text-center py-10">
        <p class="text-gray-600 text-lg">
          No scoring reports found in storage.
        </p>
      </div>
    </div>

    <!-- Parameters Modal -->
    <div v-if="showParamsModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-75 p-4" @click.self="closeParamsModal">
      <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[80vh] flex flex-col">
        <div class="flex justify-between items-center p-4 border-b">
          <h3 class="text-lg font-semibold">Report Parameters</h3>
          <button @click="closeParamsModal" class="text-gray-500 hover:text-gray-700 text-2xl leading-none">&times;</button>
        </div>
        <div class="p-4 overflow-auto flex-grow">
          <JsonTree :data="selectedParameters" />
        </div>
      </div>
    </div>

  </PageTemplate>
</template>

<script setup>
import PageTemplate from '@/Components/PageTemplate.vue';
import JsonTree from '@/Components/JsonTree.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
  reportGroups: Object,
  error: String,
});

const isRefreshing = ref(false);
const showParamsModal = ref(false);
const selectedParameters = ref({});

const getCity = (report) => report.parameters?.city || 'N/A';

const getDateRange = (report) => {
  const range = report.parameters?.date_range;
  if (range && range.start_date && range.end_date) {
    const start = new Date(range.start_date).toLocaleDateString();
    const end = new Date(range.end_date).toLocaleDateString();
    return `${start} - ${end}`;
  }
  return 'N/A';
};

const getResolution = (report) => report.parameters?.h3_resolution || 'N/A';

const showParameters = (parameters) => {
  selectedParameters.value = parameters;
  showParamsModal.value = true;
};

const closeParamsModal = () => {
  showParamsModal.value = false;
  selectedParameters.value = {};
};

const refreshReports = () => {
  isRefreshing.value = true;
  router.post(route('admin.scoring-reports.refresh'), {}, {
    onFinish: () => {
      isRefreshing.value = false;
    },
    preserveState: false, // Force a full reload of page props
  });
};

const deleteReport = (report) => {
  if (confirm(`Are you sure you want to delete the report for Job ID: ${report.job_id}? This action cannot be undone.`)) {
    router.delete(route('admin.scoring-reports.destroy', { jobId: report.job_id, artifactName: report.artifact_name }), {
      preserveState: false, // Reload props to update the list
    });
  }
};
</script>
