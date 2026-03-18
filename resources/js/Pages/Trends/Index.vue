<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import PageTemplate from '@/Components/PageTemplate.vue';
import { useH3Names } from '@/composables/useH3Names.js';

const props = defineProps({
  analyses: Array,
});

// Summaries are lazy-loaded per job when not already cached server-side.
const fetchedSummaries = ref({});
const loadingJobs      = ref(new Set());

async function fetchSummary(jobId) {
  if (!jobId || loadingJobs.value.has(jobId)) return;
  loadingJobs.value = new Set([...loadingJobs.value, jobId]);
  try {
    const res = await fetch(route('trends.summary', { jobId }));
    if (res.ok) {
      fetchedSummaries.value = { ...fetchedSummaries.value, [jobId]: await res.json() };
    }
  } catch (_) { /* card stays at zeros */ } finally {
    const next = new Set(loadingJobs.value);
    next.delete(jobId);
    loadingJobs.value = next;
  }
}

onMounted(() => {
  props.analyses
    .filter(a => a.summary === null && a.job_id)
    .forEach(a => fetchSummary(a.job_id));
});

// Merge prop summaries with lazily-fetched ones.
const enrichedAnalyses = computed(() =>
  props.analyses.map(a => ({
    ...a,
    summary: fetchedSummaries.value[a.job_id] ?? a.summary,
  }))
);

const { getName } = useH3Names();

const formatPValue = (p) => {
  if (p == null) return 'N/A';
  if (p === 0)   return '< 1e-15';
  if (p < 0.001) return p.toExponential(2);
  if (p < 0.01)  return p.toFixed(4);
  return p.toFixed(3);
};

const CITY_ORDER = ['Boston', 'Cambridge', 'Everett', 'Chicago', 'San Francisco', 'Seattle', 'Montgomery County, MD'];

// ---- filters ----
const filterCity       = ref('');
const filterType       = ref('all');
const filterResolution = ref(null);
const filterModel      = ref('');
const filterCategory   = ref('');

// ---- available options derived from data ----
const availableCities = computed(() => {
  const cities = [...new Set(enrichedAnalyses.value.map(a => a.city))];
  return cities.sort((a, b) => {
    const ai = CITY_ORDER.indexOf(a), bi = CITY_ORDER.indexOf(b);
    if (ai === -1 && bi === -1) return a.localeCompare(b);
    return (ai === -1 ? 999 : ai) - (bi === -1 ? 999 : bi);
  });
});

const availableResolutions = computed(() =>
  [...new Set(enrichedAnalyses.value.map(a => a.h3_resolution))].sort((a, b) => a - b)
);

const availableModels = computed(() => {
  const seen = new Set();
  return enrichedAnalyses.value
    .filter(a => { if (seen.has(a.model_key)) return false; seen.add(a.model_key); return true; })
    .map(a => ({ key: a.model_key, name: a.model_name }))
    .sort((a, b) => a.name.localeCompare(b.name));
});

const availableCategories = computed(() => {
  const pool = filterModel.value
    ? enrichedAnalyses.value.filter(a => a.model_key === filterModel.value)
    : enrichedAnalyses.value;
  return [...new Set(pool.map(a => a.column_label))].sort();
});

// ---- filtered for cards (all filters applied) ----
const filtered = computed(() =>
  enrichedAnalyses.value.filter(a => {
    if (filterCity.value       && a.city        !== filterCity.value)       return false;
    if (filterModel.value      && a.model_key   !== filterModel.value)      return false;
    if (filterCategory.value   && a.column_label !== filterCategory.value)  return false;
    if (filterResolution.value !== null && a.h3_resolution !== filterResolution.value) return false;
    if (filterType.value === 'anomaly' && (a.summary?.anomaly_count ?? 0) === 0) return false;
    if (filterType.value === 'trend'   && (a.summary?.trend_count   ?? 0) === 0) return false;
    return true;
  })
);

// Reset category when model changes so stale category doesn't hide results
watch(filterModel, () => { filterCategory.value = ''; });

// ---- stats: type + resolution filters applied, but NOT city filter so all cities always show ----
const statsBase = computed(() =>
  enrichedAnalyses.value.filter(a => {
    if (filterResolution.value !== null && a.h3_resolution !== filterResolution.value) return false;
    if (filterType.value === 'anomaly' && (a.summary?.anomaly_count ?? 0) === 0) return false;
    if (filterType.value === 'trend'   && (a.summary?.trend_count   ?? 0) === 0) return false;
    return true;
  })
);

const cityStats = computed(() => {
  const out = {};
  for (const city of availableCities.value) {
    const subset = statsBase.value.filter(a => a.city === city);
    out[city] = {
      anomalies:    subset.reduce((s, a) => s + (a.summary?.anomaly_count ?? 0), 0),
      trends:       subset.reduce((s, a) => s + (a.summary?.trend_count   ?? 0), 0),
      withFindings: subset.filter(a => (a.summary?.total_findings ?? 0) > 0).length,
      shown:        subset.length,
      total:        enrichedAnalyses.value.filter(a => a.city === city).length,
    };
  }
  return out;
});

// ---- pagination ----
const PAGE_SIZE   = 20;
const currentPage = ref(1);

// Reset to page 1 whenever any filter changes
watch([filterCity, filterType, filterResolution, filterModel, filterCategory], () => {
  currentPage.value = 1;
});

const totalPages = computed(() => Math.max(1, Math.ceil(filtered.value.length / PAGE_SIZE)));

const paginatedFiltered = computed(() => {
  const start = (currentPage.value - 1) * PAGE_SIZE;
  return filtered.value.slice(start, start + PAGE_SIZE);
});

// Page range: up to 7 buttons centred on current page
const pageRange = computed(() => {
  const cur  = currentPage.value;
  const last = totalPages.value;
  if (last <= 7) return Array.from({ length: last }, (_, i) => i + 1);
  const pages = new Set([1, last, cur, cur - 1, cur + 1, cur - 2, cur + 2]);
  return [...pages].filter(p => p >= 1 && p <= last).sort((a, b) => a - b);
});

// ---- card grouping ----
const groupedByCity = computed(() => {
  const groups = {};
  for (const a of paginatedFiltered.value) {
    groups[a.city] ??= [];
    groups[a.city].push(a);
  }
  return groups;
});

const orderedCities = computed(() =>
  Object.keys(groupedByCity.value).sort((a, b) => {
    const ai = CITY_ORDER.indexOf(a), bi = CITY_ORDER.indexOf(b);
    return (ai === -1 ? 999 : ai) - (bi === -1 ? 999 : bi);
  })
);

const significanceClass = (a) => {
  const n = a.summary?.total_findings ?? 0;
  if (n >= 100) return 'border-l-4 border-red-500';
  if (n >= 20)  return 'border-l-4 border-orange-400';
  if (n > 0)    return 'border-l-4 border-yellow-300';
  return 'border-l-4 border-gray-200';
};
</script>

<template>
  <PageTemplate>
    <Head title="Trends & Anomaly Reports" />

    <div class="py-10">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">

        <!-- Header -->
        <div class="flex items-start justify-between gap-4">
          <div>
            <h1 class="text-3xl font-bold text-gray-900">Trends & Anomaly Reports</h1>
            <p class="mt-1 text-sm text-gray-500">
              Statistical H3 spatial analysis across all data types. Sorted by number of significant findings.
            </p>
          </div>
          <Link
            :href="route('hotspots.index')"
            class="flex-shrink-0 inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors shadow-sm"
          >
            <span>🗺</span> Hotspot Map
          </Link>
        </div>

        <!-- Per-city stat cards -->
        <div v-if="enrichedAnalyses.length > 0" class="grid gap-3" :class="availableCities.length === 1 ? 'grid-cols-1' : availableCities.length === 2 ? 'grid-cols-2' : 'grid-cols-2 lg:grid-cols-3 xl:grid-cols-4'">
          <button
            v-for="city in availableCities"
            :key="city"
            @click="filterCity = filterCity === city ? '' : city"
            class="text-left bg-white rounded-lg border shadow-sm p-4 transition-all hover:shadow-md"
            :class="filterCity === city ? 'ring-2 ring-indigo-500 border-indigo-300' : 'border-gray-200'"
          >
            <p class="font-bold text-gray-800 mb-2">{{ city }}</p>
            <div class="space-y-1">
              <div class="flex justify-between items-center">
                <span class="text-xs text-gray-500">Anomalies</span>
                <span class="text-sm font-semibold text-amber-600">{{ cityStats[city].anomalies.toLocaleString() }}</span>
              </div>
              <div class="flex justify-between items-center">
                <span class="text-xs text-gray-500">Trends</span>
                <span class="text-sm font-semibold text-blue-600">{{ cityStats[city].trends.toLocaleString() }}</span>
              </div>
              <div class="flex justify-between items-center">
                <span class="text-xs text-gray-500">Reports</span>
                <span class="text-sm font-semibold text-gray-700">{{ cityStats[city].withFindings }} <span class="text-gray-400 font-normal">/ {{ cityStats[city].total }}</span></span>
              </div>
            </div>
          </button>
        </div>

        <!-- Filters -->
        <div class="space-y-2">
          <div class="flex flex-wrap gap-3 items-center">
            <!-- Type -->
            <div class="flex rounded-md shadow-sm border border-gray-300 overflow-hidden text-sm">
              <button @click="filterType = 'all'"     :class="filterType === 'all'     ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50'" class="px-3 py-1.5 transition-colors">All</button>
              <button @click="filterType = 'anomaly'" :class="filterType === 'anomaly' ? 'bg-amber-500 text-white' : 'bg-white text-gray-700 hover:bg-gray-50'" class="px-3 py-1.5 border-l border-gray-300 transition-colors">Anomalies</button>
              <button @click="filterType = 'trend'"   :class="filterType === 'trend'   ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50'" class="px-3 py-1.5 border-l border-gray-300 transition-colors">Trends</button>
            </div>

            <!-- Model -->
            <select v-model="filterModel" class="text-sm border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
              <option value="">All data sources</option>
              <option v-for="m in availableModels" :key="m.key" :value="m.key">{{ m.name }}</option>
            </select>

            <!-- Category -->
            <select v-model="filterCategory" class="text-sm border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
              <option value="">All categories</option>
              <option v-for="cat in availableCategories" :key="cat" :value="cat">{{ cat }}</option>
            </select>

            <span class="text-sm text-gray-500 ml-auto">{{ filtered.length }} report{{ filtered.length !== 1 ? 's' : '' }}</span>
            <span v-if="totalPages > 1" class="text-sm text-gray-400">· page {{ currentPage }} of {{ totalPages }}</span>
          </div>

          <!-- Resolution pills -->
          <div class="flex items-center gap-1.5 flex-wrap">
            <span class="text-xs text-gray-400 mr-1">Resolution:</span>
            <button
              @click="filterResolution = null"
              class="px-2.5 py-1 text-xs rounded-full border transition-colors"
              :class="filterResolution === null ? 'bg-gray-700 text-white border-gray-700' : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50'"
            >All</button>
            <button
              v-for="res in availableResolutions"
              :key="res"
              @click="filterResolution = filterResolution === res ? null : res"
              class="px-2.5 py-1 text-xs rounded-full border font-mono transition-colors"
              :class="filterResolution === res ? 'bg-gray-700 text-white border-gray-700' : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50'"
            >res {{ res }}</button>
          </div>
        </div>

        <!-- City sections -->
        <div v-if="filtered.length > 0" class="space-y-10">
          <div v-for="city in orderedCities" :key="city">

            <!-- City header -->
            <div class="flex items-center gap-4 mb-4">
              <h2 class="text-lg font-bold text-gray-800 shrink-0">{{ city }}</h2>
              <div class="flex-1 h-px bg-gray-200"></div>
              <span class="text-sm text-gray-400 shrink-0">
                {{ groupedByCity[city].length }} report{{ groupedByCity[city].length !== 1 ? 's' : '' }}
              </span>
            </div>

            <!-- Cards for this city -->
            <div class="space-y-3">
              <Link
                v-for="analysis in groupedByCity[city]"
                :key="analysis.job_id"
                :href="route('reports.statistical-analysis.show', { jobId: analysis.job_id })"
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
                      <h3 class="mt-1 text-lg font-semibold text-gray-800">{{ analysis.column_label }}</h3>

                      <!-- Top categories -->
                      <div v-if="analysis.summary?.top_categories?.length" class="mt-2 flex flex-wrap gap-1.5">
                        <span
                          v-for="cat in analysis.summary.top_categories"
                          :key="cat"
                          class="text-xs bg-gray-100 text-gray-700 rounded-full px-2.5 py-0.5"
                        >{{ cat }}</span>
                      </div>
                      <div v-else-if="loadingJobs.has(analysis.job_id)" class="mt-2 flex items-center gap-1.5 text-xs text-gray-400 italic">
                        <svg class="animate-spin h-3 w-3 text-gray-400" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                        Computing summary…
                      </div>
                      <div v-else-if="analysis.summary?.status === 'no_data'" class="mt-2 text-xs text-gray-400 italic">
                        Results not yet available in storage
                      </div>
                      <div v-else-if="analysis.summary?.status === 'error'" class="mt-2 text-xs text-red-400 italic">
                        Could not load summary
                      </div>

                      <!-- Top findings -->
                      <div v-if="analysis.summary?.top_anomalies?.length || analysis.summary?.top_trends_by_window" class="mt-3 space-y-2.5">
                        <div v-if="analysis.summary.top_anomalies?.length">
                          <p class="text-xs font-semibold text-amber-700 uppercase tracking-wide mb-1">⚠ Top Anomalies</p>
                          <div class="space-y-1">
                            <p v-for="a in analysis.summary.top_anomalies" :key="`${a.h3_index}-${a.secondary_group}-${a.week}`" class="text-sm text-gray-600 leading-snug pl-1">
                              <span :class="(a.z_score ?? 0) >= 0 ? 'text-red-500' : 'text-blue-500'" class="font-bold mr-1">{{ (a.z_score ?? 0) >= 0 ? '↑' : '↓' }}</span>
                              <span class="font-medium text-gray-800">{{ a.secondary_group }}</span>
                              <span class="text-gray-400"> · {{ a.week }}</span>
                              <span> · {{ a.count }} vs avg {{ a.historical_avg }}</span>
                              <span class="text-amber-700 font-semibold"> z={{ a.z_score }}</span>
                              <span v-if="a.h3_index" class="block pl-4 text-xs text-gray-400 mt-0.5">
                                {{ getName(a.h3_index) }}
                                <span class="font-mono text-gray-300 ml-1">{{ a.h3_index }}</span>
                              </span>
                            </p>
                          </div>
                        </div>
                        <template v-if="analysis.summary.top_trends_by_window && Object.keys(analysis.summary.top_trends_by_window).length">
                          <div v-for="(trends, window) in analysis.summary.top_trends_by_window" :key="window">
                            <p class="text-xs font-semibold text-blue-700 uppercase tracking-wide mb-1">↗ {{ window.replace('_', ' ') }} trends</p>
                            <div class="space-y-1">
                              <p v-for="t in trends" :key="`${t.h3_index}-${t.secondary_group}`" class="text-sm text-gray-600 leading-snug pl-1">
                                <span v-if="t.slope != null" :class="t.slope > 0 ? 'text-red-500' : 'text-blue-500'" class="font-bold mr-1">{{ t.slope > 0 ? '↑' : '↓' }}</span>
                                <span class="font-medium text-gray-800">{{ t.secondary_group }}</span>
                                <span v-if="t.slope != null" :class="t.slope > 0 ? 'text-red-600' : 'text-blue-600'" class="font-semibold"> slope {{ t.slope > 0 ? '+' : '' }}{{ t.slope.toFixed(2) }}</span>
                                <span class="text-gray-500"> p={{ formatPValue(t.p_value) }}</span>
                                <span v-if="t.h3_index" class="block pl-4 text-xs text-gray-400 mt-0.5">
                                  {{ getName(t.h3_index) }}
                                  <span class="font-mono text-gray-300 ml-1">{{ t.h3_index }}</span>
                                </span>
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
          </div>
        </div>

        <!-- Pagination controls -->
        <div v-if="totalPages > 1 && filtered.length > 0" class="flex items-center justify-center gap-1 pt-2">
          <button
            @click="currentPage--"
            :disabled="currentPage <= 1"
            class="px-3 py-1.5 rounded border text-sm font-medium transition-colors disabled:opacity-40 disabled:cursor-not-allowed hover:bg-gray-100"
          >← Prev</button>
          <template v-for="(p, idx) in pageRange" :key="p">
            <span v-if="idx > 0 && p > pageRange[idx - 1] + 1" class="px-1 text-gray-400">…</span>
            <button
              @click="currentPage = p"
              class="px-3 py-1.5 rounded border text-sm font-medium transition-colors"
              :class="p === currentPage ? 'bg-indigo-600 text-white border-indigo-600' : 'hover:bg-gray-100'"
            >{{ p }}</button>
          </template>
          <button
            @click="currentPage++"
            :disabled="currentPage >= totalPages"
            class="px-3 py-1.5 rounded border text-sm font-medium transition-colors disabled:opacity-40 disabled:cursor-not-allowed hover:bg-gray-100"
          >Next →</button>
        </div>

        <!-- Empty states -->
        <div v-else-if="enrichedAnalyses.length === 0" class="text-center py-16 bg-white rounded-lg border">
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
