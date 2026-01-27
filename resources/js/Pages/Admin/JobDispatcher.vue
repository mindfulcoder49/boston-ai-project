<template>
  <AdminLayout>
    <Head title="Job Dispatcher" />
    <div class="container mx-auto">
      <h1 class="text-2xl font-semibold text-gray-800 mb-6">Job Dispatcher</h1>

      <div v-if="$page.props.flash.success" class="mb-4 p-4 bg-green-100 text-green-700 rounded-md">
        {{ $page.props.flash.success }}
      </div>
      <div v-if="$page.props.flash.error" class="mb-4 p-4 bg-red-100 text-red-700 rounded-md">
        {{ $page.props.flash.error }}
      </div>

      <div v-if="$page.props.flash.command_output" class="mb-6 bg-gray-800 text-white text-sm font-mono p-4 rounded-md overflow-x-auto max-h-96">
        <h3 class="text-lg font-semibold text-gray-300 mb-2">Last Command Output:</h3>
        <pre class="whitespace-pre-wrap">{{ $page.props.flash.command_output }}</pre>
      </div>

      <div class="space-y-8">
        <!-- Statistical Analysis -->
        <JobCard command="app:dispatch-statistical-analysis-jobs" title="Statistical Analysis Jobs" description="Dispatch jobs for H3-based trend and anomaly analysis." @dispatch="submitStatJob">
          <template #button-text><span v-if="statForm.processing">Dispatching...</span><span v-else>Dispatch Job</span></template>
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div>
              <label for="stat-model" class="block text-sm font-medium text-gray-700">Model</label>
              <select v-model="statForm.parameters.model" id="stat-model" class="mt-1 block w-full input">
                <option value="">All Models</option>
                <option v-for="(details, name) in modelDetails" :key="name" :value="name">{{ name }}</option>
              </select>
            </div>
            <div class="lg:col-span-2">
              <label for="stat-columns" class="block text-sm font-medium text-gray-700">Columns (leave blank for all)</label>
              <select v-model="statForm.parameters.columns" id="stat-columns" multiple class="mt-1 block w-full input h-24">
                <option v-for="col in availableStatColumns" :key="col" :value="col">{{ col }}</option>
              </select>
            </div>
            <div>
              <label for="stat-resolutions" class="block text-sm font-medium text-gray-700">Resolutions</label>
              <input type="text" v-model="statForm.parameters.resolutions" id="stat-resolutions" class="mt-1 block w-full input" placeholder="9,8,7,6,5">
            </div>
            <div>
              <label for="stat-trend-weeks" class="block text-sm font-medium text-gray-700">Trend Weeks</label>
              <input type="text" v-model="statForm.parameters.trendWeeks" id="stat-trend-weeks" class="mt-1 block w-full input" placeholder="4,26,52">
            </div>
            <div>
              <label for="stat-anomaly-weeks" class="block text-sm font-medium text-gray-700">Anomaly Weeks</label>
              <input type="number" v-model="statForm.parameters.anomalyWeeks" id="stat-anomaly-weeks" class="mt-1 block w-full input" placeholder="4">
            </div>
            <div>
              <label for="stat-export-timespan" class="block text-sm font-medium text-gray-700">Export Timespan (Weeks)</label>
              <input type="number" v-model="statForm.parameters.exportTimespan" id="stat-export-timespan" class="mt-1 block w-full input" placeholder="108">
              <p class="mt-1 text-xs text-gray-500">Total weeks of data to export, ending on the most recent record. Must be greater than the longest trend/anomaly window to provide historical data for comparison (e.g., 52-week trend needs >52 weeks of data).</p>
            </div>
            <div class="flex items-end gap-x-4">
              <label class="flex items-center"><input type="checkbox" v-model="statForm.parameters.fresh" class="checkbox" /><span class="ml-2 text-sm">--fresh</span></label>
              <label class="flex items-center"><input type="checkbox" v-model="statForm.parameters.plots" class="checkbox" /><span class="ml-2 text-sm">--plots</span></label>
            </div>
          </div>
        </JobCard>

        <!-- Yearly Count Comparison -->
        <JobCard command="app:dispatch-yearly-count-comparison-jobs" title="Yearly Count Comparison Jobs" description="Dispatch jobs for yearly data comparisons." @dispatch="submitYearlyJob">
          <template #button-text><span v-if="yearlyForm.processing">Dispatching...</span><span v-else>Dispatch Job</span></template>
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div>
              <label for="yearly-model" class="block text-sm font-medium text-gray-700">Model</label>
              <select v-model="yearlyForm.parameters.model" id="yearly-model" class="mt-1 block w-full input">
                <option value="">All Models</option>
                <option v-for="(details, name) in modelDetails" :key="name" :value="name">{{ name }}</option>
              </select>
            </div>
            <div class="lg:col-span-2">
              <label for="yearly-columns" class="block text-sm font-medium text-gray-700">Columns (leave blank for all)</label>
              <select v-model="yearlyForm.parameters.columns" id="yearly-columns" multiple class="mt-1 block w-full input h-24">
                <option v-for="col in availableYearlyColumns" :key="col" :value="col">{{ col }}</option>
              </select>
            </div>
            <div>
              <label for="yearly-baseline" class="block text-sm font-medium text-gray-700">Baseline Year</label>
              <input type="number" v-model="yearlyForm.parameters.baselineYear" id="yearly-baseline" class="mt-1 block w-full input" placeholder="2019" />
            </div>
            <div class="flex items-end">
              <label class="flex items-center"><input type="checkbox" v-model="yearlyForm.parameters.fresh" class="checkbox" /><span class="ml-2 text-sm">--fresh</span></label>
            </div>
          </div>
        </JobCard>

        <!-- News Article Generation -->
        <JobCard command="app:dispatch-news-article-generation-jobs" title="News Article Generation Jobs" description="Dispatch jobs to generate news articles from reports." @dispatch="submitNewsJob">
          <template #button-text><span v-if="newsForm.processing">Dispatching...</span><span v-else>Dispatch Job</span></template>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label for="news-model" class="block text-sm font-medium text-gray-700">Report Model</label>
              <select v-model="newsForm.parameters.model" id="news-model" class="mt-1 block w-full input">
                <option value="all">All Report Types</option>
                <option v-for="model in newsReportModels" :key="model" :value="model">{{ model }}</option>
              </select>
            </div>
            <div>
              <label for="news-config" class="block text-sm font-medium text-gray-700">Run Config Set</label>
              <select v-model="newsForm.parameters.runConfig" id="news-config" class="mt-1 block w-full input">
                <option value="">None</option>
                <option v-for="set in newsConfigSets" :key="set" :value="set">{{ set }}</option>
              </select>
            </div>
            <div>
              <label for="news-report-class" class="block text-sm font-medium text-gray-700">Specific Report Class</label>
              <input type="text" v-model="newsForm.parameters.reportClass" id="news-report-class" class="mt-1 block w-full input" placeholder="App\Models\Trend">
            </div>
            <div>
              <label for="news-report-id" class="block text-sm font-medium text-gray-700">Specific Report ID</label>
              <input type="number" v-model="newsForm.parameters.reportId" id="news-report-id" class="mt-1 block w-full input" placeholder="123">
            </div>
            <div class="flex items-end">
              <label class="flex items-center"><input type="checkbox" v-model="newsForm.parameters.fresh" class="checkbox" /><span class="ml-2 text-sm">--fresh</span></label>
            </div>
          </div>
        </JobCard>

        <!-- Location Reports -->
        <JobCard command="reports:send" title="Location Reports" description="Dispatch jobs to send reports for user-saved locations." @dispatch="submitLocationJob">
          <template #button-text><span v-if="locationForm.processing">Dispatching...</span><span v-else>Dispatch Job</span></template>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label for="loc-user-id" class="block text-sm font-medium text-gray-700">User ID (optional)</label>
              <input type="number" v-model="locationForm.parameters.userId" id="loc-user-id" class="mt-1 block w-full input" placeholder="e.g., 1">
            </div>
            <div>
              <label for="loc-location-id" class="block text-sm font-medium text-gray-700">Location ID (optional)</label>
              <input type="number" v-model="locationForm.parameters.locationId" id="loc-location-id" class="mt-1 block w-full input" placeholder="e.g., 42">
            </div>
            <div class="flex items-end">
              <label class="flex items-center"><input type="checkbox" v-model="locationForm.parameters.force" class="checkbox" /><span class="ml-2 text-sm">--force (ignore schedule)</span></label>
            </div>
          </div>
        </JobCard>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import JobCard from '@/Components/JobCard.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
  modelDetails: Object,
  newsReportModels: Array,
  newsConfigSets: Array,
});

const statForm = useForm({ command: 'app:dispatch-statistical-analysis-jobs', parameters: { model: '', columns: [], fresh: false, plots: false, resolutions: '9,8,7,6,5', trendWeeks: '4,26,52', anomalyWeeks: 4, exportTimespan: 108 } });
const yearlyForm = useForm({ command: 'app:dispatch-yearly-count-comparison-jobs', parameters: { model: '', columns: [], baselineYear: 2019, fresh: false } });
const newsForm = useForm({ command: 'app:dispatch-news-article-generation-jobs', parameters: { model: 'all', fresh: false, runConfig: '', reportClass: '', reportId: '' } });
const locationForm = useForm({ command: 'reports:send', parameters: { userId: '', locationId: '', force: false } });

const availableStatColumns = computed(() => statForm.parameters.model ? props.modelDetails[statForm.parameters.model]?.columns || [] : []);
const availableYearlyColumns = computed(() => yearlyForm.parameters.model ? props.modelDetails[yearlyForm.parameters.model]?.columns || [] : []);

const postForm = (form) => {
  const params = {};
  for (const [key, value] of Object.entries(form.parameters)) {
    if ((value !== '' && value !== false && !Array.isArray(value)) || (Array.isArray(value) && value.length > 0)) {
      const kebabKey = key.replace(/[A-Z]/g, m => `-${m.toLowerCase()}`);
      if (typeof value === 'boolean') {
        params[`--${kebabKey}`] = true;
      } else if (key === 'model' && form.command !== 'app:dispatch-news-article-generation-jobs') {
        params.model = value; // Positional argument
      } else if (Array.isArray(value)) {
        params[`--${kebabKey}`] = value.join(',');
      } else {
        params[`--${kebabKey}`] = value;
      }
    }
  }

  form.transform(() => ({ command: form.command, parameters: params }))
      .post(route('admin.job-dispatcher.dispatch'), { preserveScroll: true });
};

const submitStatJob = () => postForm(statForm);
const submitYearlyJob = () => postForm(yearlyForm);
const submitNewsJob = () => postForm(newsForm);
const submitLocationJob = () => postForm(locationForm);
</script>

<style scoped>
.input {
  @apply mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md;
}
.checkbox {
  @apply rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50;
}
</style>
