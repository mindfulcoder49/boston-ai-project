<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import PageTemplate from '@/Components/PageTemplate.vue';

const props = defineProps({
  analyses: Array,
});

const filterModel = ref('');
const filterType = ref('all'); // 'all' | 'anomaly' | 'trend'

const formatPValue = (p) => {
  if (p == null) return 'N/A';
  if (p === 0)   return '< 1e-15';
  if (p < 0.001) return p.toExponential(2);
  if (p < 0.01)  return p.toFixed(4);
  return p.toFixed(3);
};

const modelOptions = computed(() => {
  const seen = new Set();
  return props.analyses
    .filter(a => { if (seen.has(a.model_key)) return false; seen.add(a.model_key); return true; })
    .map(a => ({ key: a.model_key, name: a.model_name }));
});

const filtered = computed(() => {
  return props.analyses.filter(a => {
    if (filterModel.value && a.model_key !== filterModel.value) return false;
    if (filterType.value === 'anomaly' && (a.summary?.anomaly_count ?? 0) === 0) return false;
    if (filterType.value === 'trend' && (a.summary?.trend_count ?? 0) === 0) return false;
    return true;
  });
});

const totalAnomalies = computed(() => props.analyses.reduce((s, a) => s + (a.summary?.anomaly_count ?? 0), 0));
const totalTrends = computed(() => props.analyses.reduce((s, a) => s + (a.summary?.trend_count ?? 0), 0));
const reportsWithFindings = computed(() => props.analyses.filter(a => (a.summary?.total_findings ?? 0) > 0).length);

const significanceClass = (a) => {
  const n = a.summary?.total_findings ?? 0;
  if (n >= 100) return 'border-l-4 border-red-500';
  if (n >= 20) return 'border-l-4 border-orange-400';
  if (n > 0) return 'border-l-4 border-yellow-300';
  return 'border-l-4 border-gray-200';
};
</script>

<template>
  <PageTemplate>
    <Head title="Trends & Anomaly Reports" />

    <div class="py-10">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">

        <!-- Header -->
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Trends & Anomaly Reports</h1>
          <p class="mt-1 text-sm text-gray-500">
            Statistical H3 spatial analysis across all data types. Sorted by number of significant findings.
          </p>
        </div>

        <!-- Stat bar -->
        <div class="grid grid-cols-3 gap-4" v-if="analyses.length > 0">
          <div class="bg-white rounded-lg shadow-sm border p-4 text-center">
            <p class="text-3xl font-bold text-amber-600">{{ totalAnomalies.toLocaleString() }}</p>
            <p class="text-xs text-gray-500 mt-1 uppercase tracking-wide">Significant Anomalies</p>
          </div>
          <div class="bg-white rounded-lg shadow-sm border p-4 text-center">
            <p class="text-3xl font-bold text-blue-600">{{ totalTrends.toLocaleString() }}</p>
            <p class="text-xs text-gray-500 mt-1 uppercase tracking-wide">Significant Trends</p>
          </div>
          <div class="bg-white rounded-lg shadow-sm border p-4 text-center">
            <p class="text-3xl font-bold text-gray-700">{{ reportsWithFindings }}</p>
            <p class="text-xs text-gray-500 mt-1 uppercase tracking-wide">Reports with Findings</p>
            <p class="text-xs text-gray-400">of {{ analyses.length }} total</p>
          </div>
        </div>

        <!-- Filters -->
        <div class="flex flex-wrap gap-3 items-center">
          <select v-model="filterModel" class="text-sm border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
            <option value="">All Data Types</option>
            <option v-for="m in modelOptions" :key="m.key" :value="m.key">{{ m.name }}</option>
          </select>
          <div class="flex rounded-md shadow-sm border border-gray-300 overflow-hidden text-sm">
            <button @click="filterType = 'all'" :class="filterType === 'all' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50'" class="px-3 py-1.5 transition-colors">All</button>
            <button @click="filterType = 'anomaly'" :class="filterType === 'anomaly' ? 'bg-amber-500 text-white' : 'bg-white text-gray-700 hover:bg-gray-50'" class="px-3 py-1.5 border-l border-gray-300 transition-colors">Anomalies</button>
            <button @click="filterType = 'trend'" :class="filterType === 'trend' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50'" class="px-3 py-1.5 border-l border-gray-300 transition-colors">Trends</button>
          </div>
          <span class="text-sm text-gray-500">{{ filtered.length }} report{{ filtered.length !== 1 ? 's' : '' }}</span>
        </div>

        <!-- Report cards -->
        <div v-if="filtered.length > 0" class="space-y-3">
          <Link
            v-for="analysis in filtered"
            :key="analysis.trend_id"
            :href="route('reports.statistical-analysis.show', { trendId: analysis.trend_id })"
            class="block bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow"
            :class="significanceClass(analysis)"
          >
            <div class="p-5">
              <div class="flex items-start justify-between gap-4">

                <!-- Left: identity -->
                <div class="flex-1 min-w-0">
                  <div class="flex items-center gap-2 flex-wrap">
                    <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">{{ analysis.model_name }}</span>
                    <span class="text-gray-300">·</span>
                    <span class="text-xs bg-gray-100 text-gray-600 rounded px-2 py-0.5 font-mono">res {{ analysis.h3_resolution }}</span>
                    <span class="text-xs bg-indigo-50 text-indigo-700 rounded px-2 py-0.5">{{ analysis.analysis_weeks_anomaly }}w anomaly window</span>
                  </div>
                  <h2 class="mt-1 text-lg font-semibold text-gray-800">
                    {{ analysis.column_label }}
                  </h2>

                  <!-- Top categories -->
                  <div v-if="analysis.summary?.top_categories?.length" class="mt-2 flex flex-wrap gap-1.5">
                    <span
                      v-for="cat in analysis.summary.top_categories"
                      :key="cat"
                      class="text-xs bg-gray-100 text-gray-700 rounded-full px-2.5 py-0.5"
                    >{{ cat }}</span>
                  </div>
                  <div v-else-if="analysis.summary?.status === 'no_data'" class="mt-2 text-xs text-gray-400 italic">
                    Results not yet available in storage
                  </div>
                  <div v-else-if="analysis.summary?.status === 'error'" class="mt-2 text-xs text-red-400 italic">
                    Could not load summary
                  </div>

                  <!-- Top findings -->
                  <div v-if="analysis.summary?.top_anomalies?.length || analysis.summary?.top_trends?.length" class="mt-3 space-y-2.5">
                    <div v-if="analysis.summary.top_anomalies?.length">
                      <p class="text-xs font-semibold text-amber-700 uppercase tracking-wide mb-1">⚠ Top Anomalies</p>
                      <div class="space-y-0.5">
                        <p v-for="a in analysis.summary.top_anomalies" :key="`${a.secondary_group}-${a.week}`" class="text-sm text-gray-600 leading-snug pl-1">
                          <span :class="(a.z_score ?? 0) >= 0 ? 'text-red-500' : 'text-blue-500'" class="font-bold mr-1">{{ (a.z_score ?? 0) >= 0 ? '↑' : '↓' }}</span>
                          <span class="font-medium text-gray-800">{{ a.secondary_group }}</span>
                          <span class="text-gray-400"> · {{ a.week }}</span>
                          <span> · {{ a.count }} vs avg {{ a.historical_avg }}</span>
                          <span class="text-amber-700 font-semibold"> z={{ a.z_score }}</span>
                        </p>
                      </div>
                    </div>
                    <template v-if="analysis.summary.top_trends_by_window && Object.keys(analysis.summary.top_trends_by_window).length">
                      <div v-for="(trends, window) in analysis.summary.top_trends_by_window" :key="window">
                        <p class="text-xs font-semibold text-blue-700 uppercase tracking-wide mb-1">↗ {{ window.replace('_', ' ') }} trends</p>
                        <div class="space-y-0.5">
                          <p v-for="t in trends" :key="t.secondary_group" class="text-sm text-gray-600 leading-snug pl-1">
                            <span v-if="t.slope != null" :class="t.slope > 0 ? 'text-red-500' : 'text-blue-500'" class="font-bold mr-1">{{ t.slope > 0 ? '↑' : '↓' }}</span>
                            <span class="font-medium text-gray-800">{{ t.secondary_group }}</span>
                            <span v-if="t.slope != null" :class="t.slope > 0 ? 'text-red-600' : 'text-blue-600'" class="font-semibold"> {{ t.slope > 0 ? '+' : '' }}{{ t.slope.toFixed(2) }}/wk</span>
                            <span class="text-gray-500"> p={{ formatPValue(t.p_value) }}</span>
                          </p>
                        </div>
                      </div>
                    </template>
                  </div>
                </div>

                <!-- Right: counts -->
                <div class="flex flex-col items-end gap-2 shrink-0">
                  <div class="flex gap-2">
                    <span
                      class="inline-flex items-center gap-1 text-sm font-semibold px-3 py-1 rounded-full"
                      :class="(analysis.summary?.anomaly_count ?? 0) > 0 ? 'bg-amber-100 text-amber-800' : 'bg-gray-100 text-gray-400'"
                    >
                      <span class="text-base leading-none">⚠</span>
                      {{ (analysis.summary?.anomaly_count ?? 0).toLocaleString() }}
                    </span>
                    <span
                      class="inline-flex items-center gap-1 text-sm font-semibold px-3 py-1 rounded-full"
                      :class="(analysis.summary?.trend_count ?? 0) > 0 ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-400'"
                    >
                      <span class="text-base leading-none">↗</span>
                      {{ (analysis.summary?.trend_count ?? 0).toLocaleString() }}
                    </span>
                  </div>
                  <div v-if="(analysis.summary?.affected_h3_count ?? 0) > 0" class="text-xs text-gray-400">
                    {{ analysis.summary.affected_h3_count }} affected area{{ analysis.summary.affected_h3_count !== 1 ? 's' : '' }}
                  </div>
                  <div class="text-xs text-gray-400">Last run {{ analysis.last_run }}</div>
                </div>

              </div>
            </div>
          </Link>
        </div>

        <!-- Empty states -->
        <div v-else-if="analyses.length === 0" class="text-center py-16 bg-white rounded-lg border">
          <p class="text-gray-500 text-lg">No analysis reports have been generated yet.</p>
          <p class="text-sm text-gray-400 mt-2 font-mono">php artisan app:dispatch-statistical-analysis-jobs</p>
        </div>
        <div v-else class="text-center py-10 text-gray-500">
          No reports match the current filters.
        </div>

      </div>
    </div>
  </PageTemplate>
</template>
