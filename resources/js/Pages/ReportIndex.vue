<template>
  <PageTemplate>
    <Head>
      <title>Available Report Maps</title>
    </Head>

    <div class="max-w-5xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
      <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-900">
          Report Maps
        </h1>
      </div>

      <div class="bg-white shadow overflow-hidden sm:rounded-md">
        <ul role="list" class="divide-y divide-gray-200">
          <li v-if="reports.length === 0">
            <div class="p-6 text-center text-gray-500">
              No reports have been generated yet.
            </div>
          </li>
          <li v-for="report in reports" :key="report.filename">
            <Link :href="getReportUrlWithFilters(report)" class="block hover:bg-gray-50">
              <div class="px-4 py-4 sm:px-6">
                <div class="flex items-center justify-between">
                  <p class="text-lg font-medium text-indigo-600 truncate">
                    {{ report.name }}
                  </p>
                  <div class="ml-2 flex-shrink-0 flex">
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                      View Map
                    </span>
                  </div>
                </div>
                <div class="mt-2 sm:flex sm:justify-between">
                  <div class="sm:flex">
                    <p class="flex items-center text-sm text-gray-500">
                      <svg class="flex-shrink-0 mr-1.5 h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                      </svg>
                      {{ report.description }}
                    </p>
                  </div>
                  <div class="mt-2 flex items-center text-sm text-gray-500 sm:mt-0">
                    <svg class="flex-shrink-0 mr-1.5 h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                      <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
                    </svg>
                    <p>
                      Generated on <time :datetime="report.generated_at">{{ report.generated_at_formatted }}</time>
                    </p>
                  </div>
                </div>
              </div>
            </Link>
          </li>
        </ul>
      </div>
    </div>
  </PageTemplate>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3';
import PageTemplate from '@/Components/PageTemplate.vue';

const props = defineProps({
  reports: {
    type: Array,
    required: true,
  },
});

const getReportUrlWithFilters = (report) => {
  const baseUrl = route('reports.map.show', { filename: report.filename });
  
  if (report.default_filters && Object.keys(report.default_filters).length > 0) {
    const queryParams = new URLSearchParams(report.default_filters).toString();
    return `${baseUrl}?${queryParams}`;
  }
  
  return baseUrl;
};
</script>
