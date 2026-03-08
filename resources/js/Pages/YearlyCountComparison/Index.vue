<script setup>
import { Head, Link } from '@inertiajs/vue3';
import PageTemplate from '@/Components/PageTemplate.vue';

const props = defineProps({
  reportsByModel: Array,
});
</script>

<template>
  <PageTemplate>
    <Head title="Yearly Count Comparison Reports" />

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 text-gray-900">
            <div class="flex items-center justify-between mb-6 border-b pb-4">
              <h1 class="text-3xl font-bold">Yearly Count Comparison Reports</h1>
              <div class="flex items-center gap-3">
                <Link :href="route('news.index')" class="inline-block bg-blue-500 text-white font-bold py-2 px-4 rounded hover:bg-blue-600 transition-colors">
                  View News Articles
                </Link>
              </div>
            </div>

            <div v-if="reportsByModel.length > 0" class="space-y-8">
              <div v-for="model in reportsByModel" :key="model.model_key" class="p-6 border rounded-lg shadow-md bg-gray-50">
                <h2 class="text-2xl font-semibold text-gray-800 mb-4">{{ model.model_name }}</h2>
                <ul class="space-y-2 list-disc list-inside">
                  <li v-for="analysis in model.analyses" :key="analysis.job_id">
                    <Link
                      :href="route('reports.yearly-comparison.show', { jobId: analysis.job_id })"
                      class="text-indigo-600 hover:text-indigo-800 hover:underline"
                    >
                      Comparison by {{ analysis.group_by_label }} (Baseline: {{ analysis.baseline_year }})
                    </Link>
                  </li>
                </ul>
              </div>
            </div>
            <div v-else class="text-center py-10">
              <p class="text-gray-500">No yearly comparison reports have been generated yet.</p>
              <p class="text-sm text-gray-400 mt-2">Run <code>php artisan app:dispatch-yearly-count-comparison-jobs</code> to generate reports.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </PageTemplate>
</template>
