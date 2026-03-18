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
        <!-- Data Pipeline -->
        <JobCard command="app:run-all-data-pipeline" title="Run Full Data Pipeline" description="Run all or specified download, processing, and seeding commands for the data pipeline." @dispatch="submitPipelineJob">
          <template #button-text><span v-if="pipelineForm.processing">Dispatching...</span><span v-else>Dispatch Job</span></template>
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-3">
              <label for="pipeline-stages" class="block text-sm font-medium text-gray-700">Stages (leave blank to run all)</label>
              <select v-model="pipelineForm.parameters.stages" id="pipeline-stages" multiple class="mt-1 block w-full input h-32">
                <option v-for="stage in pipelineStages" :key="stage" :value="stage">{{ stage }}</option>
              </select>
              <p class="mt-1 text-xs text-gray-500">Select stages to run. If none are selected, all stages will run.</p>
            </div>

            <template v-for="citySection in pipelineCitySections" :key="citySection.key">
              <div>
                <label :for="`${citySection.key}-primary`" class="block text-sm font-medium text-gray-700">{{ citySection.acquisition.label }}</label>
                <select v-model="pipelineForm.parameters[citySection.acquisition.parameter]" :id="`${citySection.key}-primary`" multiple class="mt-1 block w-full input h-24">
                  <option v-for="item in citySection.acquisition.items" :key="item" :value="item">{{ item }}</option>
                </select>
              </div>
              <div>
                <label :for="`${citySection.key}-secondary`" class="block text-sm font-medium text-gray-700">{{ citySection.seeding.label }}</label>
                <select v-model="pipelineForm.parameters[citySection.seeding.parameter]" :id="`${citySection.key}-secondary`" multiple class="mt-1 block w-full input h-24">
                  <option v-for="item in citySection.seeding.items" :key="item" :value="item">{{ item }}</option>
                </select>
              </div>
              <div class="p-4 bg-gray-50 rounded-lg">
                  <h4 class="text-md font-semibold text-gray-800">{{ citySection.label }}</h4>
                  <p class="text-xs text-gray-600 mt-1">{{ citySection.description }}</p>
              </div>
            </template>

            <template v-for="section in pipelineGeneralSections" :key="section.parameter">
              <div>
                <label :for="section.parameter" class="block text-sm font-medium text-gray-700">{{ section.label }}</label>
                <select v-model="pipelineForm.parameters[section.parameter]" :id="section.parameter" multiple class="mt-1 block w-full input h-24">
                  <option v-for="item in section.items" :key="item" :value="item">{{ item }}</option>
                </select>
              </div>
            </template>
            <div class="p-4 bg-gray-50 rounded-lg">
                <h4 class="text-md font-semibold text-gray-800">General</h4>
                <p class="text-xs text-gray-600 mt-1">Select specific aggregation, caching, or reporting steps.</p>
            </div>

          </div>
        </JobCard>

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

        <!-- Historical Scoring -->
        <JobCard command="app:dispatch-historical-scoring-jobs" title="Historical Scoring Jobs" description="Dispatch jobs for H3-based historical scoring from raw data." @dispatch="submitHistoricalJob">
          <template #button-text><span v-if="historicalForm.processing">Dispatching...</span><span v-else>Dispatch Job</span></template>
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div>
              <label for="historical-model" class="block text-sm font-medium text-gray-700">Model</label>
              <select v-model="historicalForm.parameters.model" id="historical-model" class="mt-1 block w-full input" @change="onHistoricalModelChange">
                <option value="">Select Model...</option>
                <option v-for="(details, name) in modelDetails" :key="name" :value="name">{{ name }}</option>
              </select>
            </div>
            <div>
              <label for="historical-column" class="block text-sm font-medium text-gray-700">Grouping Column</label>
              <select v-model="historicalForm.parameters.column" id="historical-column" class="mt-1 block w-full input" @change="fetchUniqueValuesForHistorical">
                <option value="">Select Column...</option>
                <option v-for="col in availableHistoricalColumns" :key="col" :value="col">{{ col }}</option>
              </select>
            </div>
            <div>
              <label for="historical-city" class="block text-sm font-medium text-gray-700">City Name</label>
              <input type="text" v-model="historicalForm.parameters.city" id="historical-city" class="mt-1 block w-full input" placeholder="Defaults to model name">
            </div>
            <div class="lg:col-span-2">
              <label for="historical-export-columns" class="block text-sm font-medium text-gray-700">Columns to Export (leave blank for all)</label>
              <select v-model="historicalForm.parameters.exportColumns" id="historical-export-columns" multiple class="mt-1 block w-full input h-24">
                <option v-for="col in availableHistoricalColumns" :key="col" :value="col">{{ col }}</option>
              </select>
            </div>
            <div>
              <label for="historical-resolution" class="block text-sm font-medium text-gray-700">H3 Resolution(s)</label>
              <input type="text" v-model="historicalForm.parameters.resolution" id="historical-resolution" class="mt-1 block w-full input" placeholder="8 or 8,7,6">
            </div>
            <div>
              <label for="historical-analysis-weeks" class="block text-sm font-medium text-gray-700">Analysis Period (Weeks)</label>
              <input type="number" v-model="historicalForm.parameters.analysisWeeks" id="historical-analysis-weeks" class="mt-1 block w-full input" placeholder="52">
            </div>
            <div>
              <label for="historical-export-timespan" class="block text-sm font-medium text-gray-700">Export Timespan (Weeks)</label>
              <input type="number" v-model="historicalForm.parameters.exportTimespan" id="historical-export-timespan" class="mt-1 block w-full input" placeholder="0 for all">
              <p class="mt-1 text-xs text-gray-500">Total weeks of data to export. 0 for all data.</p>
            </div>
            <div class="flex items-end">
              <label class="flex items-center"><input type="checkbox" v-model="historicalForm.parameters.fresh" class="checkbox" /><span class="ml-2 text-sm">--fresh (re-export data)</span></label>
            </div>

            <div class="lg:col-span-3 mt-4" v-if="historicalForm.parameters.column">
              <h4 class="text-md font-semibold text-gray-800">Group Weights</h4>
              <p class="text-xs text-gray-600 mt-1 mb-2">Assign a weight to each group. The average weekly count for each group will be multiplied by this weight.</p>
              <button type="button" @click="toggleWeightEditorMode" class="btn btn-sm mb-2">{{ isJsonEditorMode ? 'Switch to Form Builder' : 'Switch to JSON Editor' }}</button>

              <div v-if="isJsonEditorMode">
                <textarea v-model="historicalWeightsJson" rows="10" class="input w-full font-mono" placeholder='{ "GroupName1": 1.0, "GroupName2": -0.5 }'></textarea>
              </div>
              <div v-else class="space-y-2 max-h-60 overflow-y-auto pr-2">
                 <div v-for="(weight, name) in historicalForm.parameters.groupWeights" :key="name" class="flex items-center gap-2">
                   <input type="text" :value="name" readonly class="input bg-gray-100 flex-grow">
                   <input type="number" v-model="historicalForm.parameters.groupWeights[name]" step="0.1" class="input w-28">
                 </div>
              </div>
               <div class="mt-2">
                <label for="historical-default-weight" class="block text-sm font-medium text-gray-700">Default Group Weight</label>
                <input type="number" v-model="historicalForm.parameters.defaultWeight" id="historical-default-weight" step="0.1" class="mt-1 input w-28">
              </div>
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
import { computed, ref } from 'vue';
import axios from 'axios';

const props = defineProps({
  modelDetails: Object,
  newsReportModels: Array,
  newsConfigSets: Array,
  pipelineStages: Array,
  pipelineCitySections: Array,
  pipelineGeneralSections: Array,
});

const pipelineParameterDefaults = {
  stages: [],
  ...Object.fromEntries(
    [
      ...props.pipelineCitySections.flatMap((section) => [section.acquisition.parameter, section.seeding.parameter]),
      ...props.pipelineGeneralSections.map((section) => section.parameter),
    ].map((key) => [key, []])
  ),
};

const statForm = useForm({ command: 'app:dispatch-statistical-analysis-jobs', parameters: { model: '', columns: [], fresh: false, plots: false, resolutions: '9,8,7,6,5', trendWeeks: '4,26,52', anomalyWeeks: 4, exportTimespan: 108 } });
const historicalForm = useForm({ command: 'app:dispatch-historical-scoring-jobs', parameters: { model: '', column: '', city: '', exportColumns: [], resolution: '8', analysisWeeks: 52, exportTimespan: 0, groupWeights: {}, defaultWeight: 0.0, fresh: false } });
const yearlyForm = useForm({ command: 'app:dispatch-yearly-count-comparison-jobs', parameters: { model: '', columns: [], baselineYear: 2019, fresh: false } });
const newsForm = useForm({ command: 'app:dispatch-news-article-generation-jobs', parameters: { model: 'all', fresh: false, runConfig: '', reportClass: '', reportId: '' } });
const locationForm = useForm({ command: 'reports:send', parameters: { userId: '', locationId: '', force: false } });
const pipelineForm = useForm({
  command: 'app:run-all-data-pipeline',
  parameters: pipelineParameterDefaults,
});

const availableStatColumns = computed(() => statForm.parameters.model ? props.modelDetails[statForm.parameters.model]?.columns || [] : []);
const availableHistoricalColumns = computed(() => historicalForm.parameters.model ? props.modelDetails[historicalForm.parameters.model]?.columns || [] : []);
const availableYearlyColumns = computed(() => yearlyForm.parameters.model ? props.modelDetails[yearlyForm.parameters.model]?.columns || [] : []);

// --- Historical Scoring Logic ---
const isJsonEditorMode = ref(false);
const historicalWeightsJson = ref('{}');

function onHistoricalModelChange() {
  historicalForm.parameters.column = '';
  historicalForm.parameters.city = '';
  historicalForm.parameters.exportColumns = [];
  historicalForm.parameters.groupWeights = {};
  historicalWeightsJson.value = '{}';
}

async function fetchUniqueValuesForHistorical() {
  const model = historicalForm.parameters.model;
  const column = historicalForm.parameters.column;
  if (!model || !column) {
    historicalForm.parameters.groupWeights = {};
    historicalWeightsJson.value = '{}';
    return;
  }
  try {
    const response = await axios.post(route('admin.job-dispatcher.unique-values'), {
      model: model,
      column: column,
    });
    const weights = {};
    response.data.unique_values.forEach(val => {
      weights[val] = 1.0; // Default weight
    });
    historicalForm.parameters.groupWeights = weights;
    historicalWeightsJson.value = JSON.stringify(weights, null, 2);
  } catch (error) {
    console.error('Failed to fetch unique values:', error);
    alert('Could not fetch unique values for the selected column.');
  }
}

function toggleWeightEditorMode() {
  isJsonEditorMode.value = !isJsonEditorMode.value;
  if (isJsonEditorMode.value) {
    // Sync from form to JSON
    historicalWeightsJson.value = JSON.stringify(historicalForm.parameters.groupWeights, null, 2);
  } else {
    // Sync from JSON to form
    try {
      historicalForm.parameters.groupWeights = JSON.parse(historicalWeightsJson.value);
    } catch (e) {
      alert('Invalid JSON. Please correct it before switching back to the form builder.');
      isJsonEditorMode.value = true; // Stay in JSON mode
    }
  }
}

// --- End Historical Scoring Logic ---

const postForm = (form) => {
  const params = {};
  let customPayload = {};

  // Special handling for historical scoring to include weights as JSON
  if (form.command === 'app:dispatch-historical-scoring-jobs') {
    if (isJsonEditorMode.value) {
      try {
        // Ensure form data is updated from JSON editor before submitting
        form.parameters.groupWeights = JSON.parse(historicalWeightsJson.value);
      } catch (e) {
        alert('Invalid JSON in group weights. Please correct it before submitting.');
        return;
      }
    }
    // The command expects groupWeights as a JSON string option
    customPayload['--group-weights'] = JSON.stringify(form.parameters.groupWeights);
  }

  for (const [key, value] of Object.entries(form.parameters)) {
    if ((value !== '' && value !== false && !Array.isArray(value)) || (Array.isArray(value) && value.length > 0)) {
      if (key === 'groupWeights' && form.command === 'app:dispatch-historical-scoring-jobs') {
        continue; // Skip, handled in customPayload
      }
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

  form.transform(() => ({ command: form.command, parameters: { ...params, ...customPayload } }))
      .post(route('admin.job-dispatcher.dispatch'), { preserveScroll: true });
};

const submitStatJob = () => postForm(statForm);
const submitHistoricalJob = () => postForm(historicalForm);
const submitYearlyJob = () => postForm(yearlyForm);
const submitNewsJob = () => postForm(newsForm);
const submitPipelineJob = () => postForm(pipelineForm);
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
