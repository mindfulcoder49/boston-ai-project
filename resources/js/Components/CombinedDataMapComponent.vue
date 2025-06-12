<template>
  <div class="combined-data-map-container p-4">
    <h2 class="text-2xl font-bold text-center mb-6">Combined Data Map</h2>

    <!-- Save Map Button -->
    <div v-if="!isReadOnly && page.props.auth.user" class="mb-4 text-right">
      <button @click="showSaveMapModal = true" class="p-2 bg-blue-500 text-white rounded-md text-sm hover:bg-blue-600">
        Save Current Map View
      </button>
    </div>

    <!-- NLP Query Section -->
    <div v-if="!isReadOnly" class="nlp-query-section mb-6 p-4 border rounded-md shadow-sm bg-white">
      <h3 class="text-xl font-semibold mb-3">Natural Language Query (Combined)</h3>
      <p class="text-sm text-gray-600 mb-2">
        Select data models and ask a question (e.g., "Show incidents and violations last week").
      </p>
      <div class="flex items-center space-x-2 mb-3">
        <input 
          v-model="nlpQueryText" 
          type="text" 
          placeholder="Query selected data models..."
          class="p-2 border rounded-md w-full text-sm"
          @keyup.enter="submitCombinedNlpQuery"
        >
        <button 
          @click="submitCombinedNlpQuery" 
          class="p-2 bg-indigo-500 text-white rounded-md text-sm hover:bg-indigo-600 whitespace-nowrap"
          :disabled="isGlobalLoading || nlpSelectedModels.length === 0"
        >
          {{ isGlobalLoading && nlpQuerySubmitted ? 'Processing...' : 'Submit Query' }}
        </button>
      </div>
      <div class="mb-3">
        <span class="text-sm font-medium mr-2">Query Targets:</span>
        <label v-for="modelKey in availableModels" :key="`nlp-${modelKey}`" class="mr-4 text-sm inline-flex items-center">
          <input type="checkbox" :value="modelKey" v-model="nlpSelectedModels" class="mr-1 form-checkbox h-4 w-4 text-indigo-600">
          <span
            v-if="getIconClassForModelKey(modelKey)"
            :class="[getIconClassForModelKey(modelKey), 'checkbox-icon-display']"
          ></span>
          {{ getModelNameForHumans(modelKey) }}
        </label>
      </div>
      <div v-if="Object.keys(nlpErrorsByModel).length > 0" class="mt-2 text-red-500 text-sm">
        <p v-for="(error, modelKey) in nlpErrorsByModel" :key="`nlp-error-${modelKey}`">
          <span v-if="error">
            Error for {{ getModelNameForHumans(modelKey) }}: {{ error }}
          </span>
        </p>
      </div>
    </div>

    <!-- Map Data Model Selection Section -->
    <div class="map-data-selection mb-6 p-4 border rounded-md shadow-sm bg-white">
        <h3 class="text-xl font-semibold mb-3">Display Data Models on Map</h3>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-2">
        <label v-for="modelKey in availableModels" :key="`map-select-${modelKey}`" class="text-sm inline-flex items-center p-2 border rounded-md hover:bg-gray-50 cursor-pointer">
            <input type="checkbox" :value="modelKey" v-model="mapSelectedModels" class="mr-2 form-checkbox h-4 w-4 text-indigo-600" >
            <span
              v-if="getIconClassForModelKey(modelKey)"
              :class="[getIconClassForModelKey(modelKey), 'checkbox-icon-display']"
            ></span>
            {{ getModelNameForHumans(modelKey) }}
        </label>
    </div>
    </div>

    <!-- Map Display Section -->
    <div class="mb-6">
      <DataMapDisplay
        ref="dataMapDisplayRef"
        :mapCenterCoordinates="mapCenter"
        :dataPointsToDisplay="combinedDataPointsForMap"
        :externalIdFieldProp="'alcivartech_external_id'" 
        @marker-data-point-clicked="handleMarkerClick"
        @map-initialized-internal="handleMapInitialized"
        class="h-[70vh] w-full rounded-md shadow-md generic-map" 
      />
    </div>
    
    <!-- Filters Section - Tabbed Interface -->
    <div class="filters-tab-section mb-6 p-4 border rounded-md shadow-sm bg-white">
        <h3 class="text-xl font-semibold mb-3">Filters</h3>
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-4 overflow-x-auto" aria-label="Tabs">
                <button
                    v-for="modelKey in availableModels"
                    :key="`tab-${modelKey}`"
                    @click="activeFilterTab = modelKey"
                    :class="[
                        'whitespace-nowrap py-3 px-4 border-b-2 font-medium text-sm',
                        activeFilterTab === modelKey
                            ? 'border-indigo-500 text-indigo-600'
                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                    ]"
                >
                <span :class="getIconClassForModelKey(modelKey)" class="toolbar-icon-display"></span>
                    {{ getModelNameForHumans(modelKey) }} 
                    <span class="text-xs bg-gray-200 text-gray-700 rounded-full px-1.5 py-0.5 ml-1">
                        {{ (allDataPointsByModel[modelKey] || []).length }}
                    </span>
                </button>
            </nav>
        </div>
        <div class="mt-4">
            <div v-for="modelKey in availableModels" :key="`filter-content-${modelKey}`">
                <GenericFiltersControl
                    v-show="activeFilterTab === modelKey"
                    :filter-fields-description="filterFieldsForReadOnlyView(modelKey)"
                    :initial-filters="currentFiltersByModel[modelKey] || {}"
                    :date-field="allDataTypeDetailsProp[modelKey]?.dateField"
                    :data-type="modelKey" 
                    :is-read-only="isReadOnly"
                    @filters-updated="(newFilters) => handleFiltersUpdatedForModel(modelKey, newFilters)"
                    class="mt-2"
                />
            </div>
        </div>
    </div>
    
    <!-- Selected Item Details -->
    <div class="selected-item-details p-4 border rounded-md shadow-sm bg-white my-6 max-h-[40vh] overflow-y-auto">
      <h3 class="text-lg font-semibold mb-3">Selected Item Details (Model: {{ selectedDataPoint?.alcivartech_model ? getModelNameForHumans(selectedDataPoint.alcivartech_model) : 'N/A' }})</h3>
      <div v-if="selectedDataPoint" class="text-sm">
        <div v-for="(value, key) in selectedDataPoint" :key="key" class="mb-1">
          <strong class="capitalize">{{ key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()) }}:</strong> 
          <span v-if="typeof value === 'object'">{{ JSON.stringify(value) }}</span>
          <span v-else>{{ value }}</span>
        </div>
      </div>
      <div v-else class="text-gray-500">Click a marker or list item to see details.</div>
    </div>

    <!-- AI Assistant -->
    <AiAssistant 
      v-if="combinedDataPointsForMap.length > 0 && !isGlobalLoading && !isReadOnly"
      :context="combinedDataPointsForMap" 
      :language_codes="language_codes" 
      :currentMapLanguage="currentReportLanguage"
      class="my-6"
    />

    <!-- Data List Section -->
    <GenericDataList
      :totalData="combinedDataPointsForMap"
      :itemsPerPage="10"
      @handle-goto-marker="handleListItemClick"
      :language_codes="language_codes"
      class="mt-6"
    />

    <!-- Download CSV Button -->
    <div class="mt-6 text-right">
      <button 
        v-if="!isReadOnly"
        @click="downloadCombinedCSV" 
        class="p-2 bg-green-500 text-white rounded-md text-sm hover:bg-green-600"
        :disabled="combinedDataPointsForMap.length === 0"
      >
        Download Displayed Data as CSV
      </button>
    </div>

     <!-- Save Map Modal -->
    <div v-if="showSaveMapModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full flex justify-center items-center z-50">
      <div class="bg-white p-6 rounded-lg shadow-xl w-full max-w-md">
        <h3 class="text-lg font-semibold mb-4">Save Combined Map</h3>
        <form @submit.prevent="handleSaveMap">
          <div class="mb-4">
            <label for="mapNameCombined" class="block text-sm font-medium text-gray-700">Map Name*</label>
            <input type="text" v-model="saveMapForm.name" id="mapNameCombined" required class="mt-1 p-2 border rounded-md w-full">
          </div>
          <div class="mb-4">
            <label for="mapDescriptionCombined" class="block text-sm font-medium text-gray-700">Description</label>
            <textarea v-model="saveMapForm.description" id="mapDescriptionCombined" rows="3" class="mt-1 p-2 border rounded-md w-full"></textarea>
          </div>

          <div class="mb-4 border-t pt-4">
            <h4 class="text-sm font-medium text-gray-700 mb-2">Configurable Filters for Viewers:</h4>
            <p class="text-xs text-gray-500 mb-2">Select which filters viewers of this saved map can adjust (client-side) for each data model.</p>
            <div class="max-h-60 overflow-y-auto space-y-3">
              <div v-for="modelKey in availableModels" :key="`config-filter-${modelKey}`">
                <h5 class="text-xs font-semibold text-gray-600 mb-1">{{ getModelNameForHumans(modelKey) }}</h5>
                <div class="pl-2 space-y-1">
                    <label v-for="field in availableFilterFieldsForModal[modelKey]" :key="`${modelKey}-${field.name}`" class="flex items-center text-sm">
                        <input 
                            type="checkbox" 
                            :value="field.name" 
                            v-model="saveMapForm.configurable_filter_fields[modelKey]" 
                            @change="() => { if (!saveMapForm.configurable_filter_fields[modelKey]) saveMapForm.configurable_filter_fields[modelKey] = [] }"
                            class="form-checkbox h-4 w-4 text-indigo-600 mr-2">
                        {{ field.label }}
                    </label>
                     <p v-if="!availableFilterFieldsForModal[modelKey] || availableFilterFieldsForModal[modelKey].length === 0" class="text-xs text-gray-400">No filterable fields for this model.</p>
                </div>
              </div>
            </div>
          </div>

           <div class="mb-4">
            <label for="creatorDisplayNameCombined" class="block text-sm font-medium text-gray-700">Creator Display Name (Optional)</label>
            <input type="text" v-model="saveMapForm.creator_display_name" id="creatorDisplayNameCombined" placeholder="Leave blank to use your account name" class="mt-1 p-2 border rounded-md w-full">
            <p class="text-xs text-gray-500 mt-1">This name will be shown if the map is public.</p>
          </div>
           <div class="mb-4">
            <label class="inline-flex items-center">
              <input type="checkbox" v-model="saveMapForm.is_public" class="form-checkbox h-5 w-5 text-indigo-600">
              <span class="ml-2 text-sm text-gray-700">Make this map public?</span>
            </label>
          </div>
          <div v-if="saveMapError" class="mb-4 text-sm text-red-500">{{ saveMapError }}</div>
          <div class="flex justify-end space-x-2">
            <button type="button" @click="showSaveMapModal = false" class="p-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">Cancel</button>
            <button type="submit" :disabled="isSavingMap" class="p-2 bg-indigo-500 text-white rounded-md hover:bg-indigo-600 disabled:opacity-50">
              {{ isSavingMap ? 'Saving...' : 'Save Map' }}
            </button>
          </div>
        </form>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch, inject } from 'vue';
import axios from 'axios';
import { usePage, router } from '@inertiajs/vue3'; 

import DataMapDisplay from '@/Components/DataMapDisplay.vue';
import GenericFiltersControl from '@/Components/GenericFiltersControl.vue';
import AiAssistant from '@/Components/AiAssistant.vue';
import GenericDataList from '@/Components/GenericDataList.vue';
import { applyClientFilters } from '@/Utils/clientDataFilter.js'; 

const props = defineProps({
  modelMappingProp: Object, // Keys are model identifiers e.g. 'crime', 'everett_crime'
  initialDataTypeProp: String, // Effectively the initialModelKeyProp
  initialFiltersProp: Object, 
  allDataTypeDetailsProp: Object, // This now contains the richer configuration
  isReadOnly: { 
    type: Boolean,
    default: false,
  },
  initialMapSettings: { 
    type: Object,
    default: () => ({ center: [42.3601, -71.0589], zoom: 12, selected_data_types: [] }) // selected_data_types here means selected_models
  },
  initialDataSetsProp: { // Pre-loaded data for multiple models in read-only mode, keyed by modelKey
    type: Object,
    default: () => ({})
  },
  configurableFilterFieldsForView: { // { modelKey: ['field1', 'field2'] }
    type: Object,
    default: () => ({})
  }
});

const page = usePage();
const csrfToken = computed(() => page.props.csrf_token || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'));

const dataMapDisplayRef = ref(null);
const allDataPointsByModel = ref({}); 
const clientFilteredDataPointsByModel = ref({}); 

const currentFiltersByModel = ref({}); 
const isLoadingByModel = ref({}); 
const nlpErrorsByModel = ref({}); 

const nlpQueryText = ref('');
const nlpSelectedModels = ref([]); 
const nlpQuerySubmitted = ref(false);

const selectedDataPoint = ref(null);
const mapCenter = ref([42.3601, -71.0589]);
const isMapInitialized = ref(false);
const language_codes = ref(['en-US']);

const activeFilterTab = ref(''); // Stores the active modelKey for filtering
const mapSelectedModels = ref([]); // Stores modelKeys for map display

// Save Map Modal State
const showSaveMapModal = ref(false);
const saveMapForm = ref({
  name: '',
  description: '',
  creator_display_name: '', 
  map_type: 'combined',
  data_type: null, // For combined maps, this might be an array of modelKeys or null. Backend handles interpretation.
  filters: {}, 
  map_settings: {},
  is_public: false,
  configurable_filter_fields: {}, // { modelKey1: ['fieldA'], modelKey2: ['fieldB'] }
});
const isSavingMap = ref(false);
const saveMapError = ref('');


const availableModels = computed(() => Object.keys(props.modelMappingProp || {}));

const isGlobalLoading = computed(() => {
  return Object.values(isLoadingByModel.value).some(loading => loading) || nlpQuerySubmitted.value;
});

// modelKey is e.g., 'crime', 'everett_crime'. This function provides a hint for UI elements.
// The actual map icons are driven by `alcivartech_type` on the data point, handled in DataMapDisplay.
const getIconClassForModelKey = (modelKey) => {
  if (!modelKey || !props.allDataTypeDetailsProp[modelKey]) return '';
  return props.allDataTypeDetailsProp[modelKey].iconClass || ''; 
};

const getModelNameForHumans = (modelKey) => {
    if (!modelKey || !props.allDataTypeDetailsProp[modelKey]) {
        return modelKey.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
    }
    return props.allDataTypeDetailsProp[modelKey].humanName || props.allDataTypeDetailsProp[modelKey].modelNameForHumans || modelKey.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
};

const currentReportLanguage = computed(() => {
  const locale = language_codes.value[0] || 'en-US';
  const mapping = { 'en-US': 'en', 'es-MX': 'es', /* ... */ };
  return mapping[locale] || 'en';
});

const availableFilterFieldsForModal = computed(() => {
  const fields = {};
  const allDetails = props.allDataTypeDetailsProp;

  if (!allDetails) return fields;

  for (const modelKey in allDetails) {
    let desc = allDetails[modelKey]?.filterFieldsDescription;
    if (typeof desc === 'string') {
      try { desc = JSON.parse(desc); } catch (e) { desc = []; }
    }
    if (!desc) desc = [];

    if (Array.isArray(desc)) {
      fields[modelKey] = desc;
    } else if (typeof desc === 'object' && desc !== null) {
      fields[modelKey] = Object.entries(desc).map(([key, value]) => ({
        name: key,
        label: value.label || key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()),
        type: value.type || 'text',
        options: value.options || [],
      }));
    } else {
      fields[modelKey] = [];
    }
  }
  return fields;
});

const filterFieldsForReadOnlyView = (modelKey) => {
  const modelConfig = props.allDataTypeDetailsProp[modelKey];
  if (!modelConfig) return [];
  
  let baseDescriptionArray = [];
  let descriptionProp = modelConfig.filterFieldsDescription;

  if (descriptionProp) {
    if (typeof descriptionProp === 'string') {
      try { descriptionProp = JSON.parse(descriptionProp); } catch (e) { descriptionProp = []; }
    }
    if (Array.isArray(descriptionProp)) {
      baseDescriptionArray = descriptionProp;
    } else if (typeof descriptionProp === 'object' && descriptionProp !== null) {
      baseDescriptionArray = Object.entries(descriptionProp).map(([key, value]) => ({
        name: key,
        label: value.label || key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()),
        type: value.type || 'text',
        options: value.options || [],
        ...(typeof value === 'object' && value !== null && value)
      }));
    }
  }
  
  if (!props.isReadOnly) return baseDescriptionArray; 

  const configurableForModel = props.configurableFilterFieldsForView?.[modelKey];
  if (!configurableForModel || configurableForModel.length === 0) return []; 

  const configurableSet = new Set(configurableForModel);
  let filteredDesc = baseDescriptionArray.filter(field => configurableSet.has(field.name));

  if (configurableSet.has('limit') && !filteredDesc.some(field => field.name === 'limit')) {
    filteredDesc.push({ name: 'limit', label: 'Record Limit', type: 'number', placeholder: 'e.g., 1000' });
  }
  return filteredDesc;
};


const combinedDataPointsForMap = computed(() => {
  let combined = [];
  const sourceData = props.isReadOnly ? clientFilteredDataPointsByModel.value : allDataPointsByModel.value;

  for (const modelKey of mapSelectedModels.value) { 
    if (availableModels.value.includes(modelKey) && props.allDataTypeDetailsProp[modelKey]) { 
        const points = sourceData[modelKey] || [];
        const externalIdField = props.allDataTypeDetailsProp[modelKey].externalIdField || 'id';
        combined.push(...points.map(p => ({ 
            ...p, 
            // alcivartech_model and alcivartech_type are already on 'p' from backend.
            // DataMapDisplay uses alcivartech_model for grouping and alcivartech_type for styling.
            alcivartech_external_id: `${modelKey}-${p[externalIdField]}`,
            // alcivartech_type_display is used for UI in this component, like selected item details.
            // It should show the human-readable name of the model.
            alcivartech_type_display: getModelNameForHumans(modelKey) 
        })));
    }
  }
  return combined;
});

const fetchDataForModel = async (modelKey, filters) => { 
  if (props.isReadOnly) { 
    applyClientSideFiltersForModel(modelKey, filters); 
    return;
  }
  isLoadingByModel.value = { ...isLoadingByModel.value, [modelKey]: true }; 
  nlpErrorsByModel.value = { ...nlpErrorsByModel.value, [modelKey]: '' }; 

  try {
    const response = await axios.post(`/api/data/${modelKey}`, { 
      filters: filters,
    }, {
      headers: { 'X-CSRF-TOKEN': csrfToken.value },
    });
    const fetchedData = response.data.data || [];
    allDataPointsByModel.value = { ...allDataPointsByModel.value, [modelKey]: fetchedData }; 
    if (props.isReadOnly) { 
        clientFilteredDataPointsByModel.value = { ...clientFilteredDataPointsByModel.value, [modelKey]: fetchedData }; 
    }
    currentFiltersByModel.value = { ...currentFiltersByModel.value, [modelKey]: filters }; 
  } catch (error) {
    console.error(`Error fetching ${modelKey} data:`, error);
    nlpErrorsByModel.value = { ...nlpErrorsByModel.value, [modelKey]: `Failed to fetch data. ${error.response?.data?.error || error.message}` }; 
    allDataPointsByModel.value = { ...allDataPointsByModel.value, [modelKey]: [] }; 
  } finally {
    isLoadingByModel.value = { ...isLoadingByModel.value, [modelKey]: false }; 
  }
};

const submitCombinedNlpQuery = async () => {
  if (props.isReadOnly || !nlpQueryText.value.trim() || nlpSelectedModels.value.length === 0) return; 
  
  nlpQuerySubmitted.value = true;
  nlpErrorsByModel.value = {}; 

  const queryPromises = nlpSelectedModels.value.map(async (modelKey) => { 
    isLoadingByModel.value = { ...isLoadingByModel.value, [modelKey]: true }; 
    try {
      const response = await axios.post(`/api/natural-language-query/${modelKey}`, { 
        query: nlpQueryText.value,
      }, {
        headers: { 'X-CSRF-TOKEN': csrfToken.value },
      });
      const nlpData = response.data.data || [];
      allDataPointsByModel.value = { ...allDataPointsByModel.value, [modelKey]: nlpData }; 
      if (props.isReadOnly) { 
          clientFilteredDataPointsByModel.value = { ...clientFilteredDataPointsByModel.value, [modelKey]: nlpData }; 
      }
      if (response.data.filtersApplied) {
        currentFiltersByModel.value = { ...currentFiltersByModel.value, [modelKey]: response.data.filtersApplied }; 
      }
    } catch (error) {
      console.error(`Error processing NLP query for ${modelKey}:`, error);
      nlpErrorsByModel.value = { ...nlpErrorsByModel.value, [modelKey]: `NLP query failed. ${error.response?.data?.error || error.message}` }; 
      allDataPointsByModel.value = { ...allDataPointsByModel.value, [modelKey]: [] }; 
    } finally {
      isLoadingByModel.value = { ...isLoadingByModel.value, [modelKey]: false }; 
    }
  });

  await Promise.all(queryPromises);
  nlpQuerySubmitted.value = false;
  
  const firstModelWithData = nlpSelectedModels.value.find(mk => (allDataPointsByModel.value[mk] || []).length > 0); 
  if (firstModelWithData) {
      const firstPoint = (allDataPointsByModel.value[firstModelWithData] || [])[0]; 
      if (firstPoint && firstPoint.latitude && firstPoint.longitude && dataMapDisplayRef.value?.getMapInstance()) {
          mapCenter.value = [parseFloat(firstPoint.latitude), parseFloat(firstPoint.longitude)];
          dataMapDisplayRef.value.getMapInstance().setView(mapCenter.value, 13);
      }
  }
};

const applyClientSideFiltersForModel = (modelKey, filters) => { 
  if (!props.isReadOnly || !allDataPointsByModel.value[modelKey]) return; 

  const modelDetails = props.allDataTypeDetailsProp[modelKey]; 
  if (!modelDetails) {
    console.warn(`No modelDetails found for ${modelKey} in applyClientSideFiltersForModel.`);
    clientFilteredDataPointsByModel.value = { 
      ...clientFilteredDataPointsByModel.value, 
      [modelKey]: [...(allDataPointsByModel.value[modelKey] || [])], 
    };
    return;
  }
  
  const modelSpecificDetails = { 
    dateField: modelDetails.dateField,
    searchableColumns: modelDetails.searchableColumns || [], 
    filterFieldsDescription: modelDetails.filterFieldsDescription 
  };

  const filteredData = applyClientFilters(
    allDataPointsByModel.value[modelKey] || [], 
    filters,
    modelSpecificDetails 
  );

  clientFilteredDataPointsByModel.value = { 
    ...clientFilteredDataPointsByModel.value, 
    [modelKey]: filteredData,
  };
};


const handleFiltersUpdatedForModel = (modelKey, newFilters) => { 
  currentFiltersByModel.value = { ...currentFiltersByModel.value, [modelKey]: newFilters }; 
  if (props.isReadOnly) {
    applyClientSideFiltersForModel(modelKey, newFilters); 
  } else {
    fetchDataForModel(modelKey, newFilters); 
  }
};

const handleMarkerClick = (dataPoint) => {
  selectedDataPoint.value = dataPoint; 
};

const handleListItemClick = (dataPoint) => {
  selectedDataPoint.value = dataPoint;
  const lat = parseFloat(dataPoint.latitude); 
  const lon = parseFloat(dataPoint.longitude); 

  if (dataMapDisplayRef.value && !isNaN(lat) && !isNaN(lon)) {
    dataMapDisplayRef.value.panToAndOpenPopup(dataPoint, 'alcivartech_external_id');
  }
};

const escapeCSVField = (field) => {
  if (field === null || typeof field === 'undefined') return '';
  let stringField = String(field);
  if (stringField.includes(',') || stringField.includes('"') || stringField.includes('\n')) {
    stringField = stringField.replace(/"/g, '""');
    return `"${stringField}"`;
  }
  return stringField;
};

const downloadCombinedCSV = () => {
  const dataToDownload = combinedDataPointsForMap.value;
  if (dataToDownload.length === 0) return;

  const allHeaders = new Set();
  dataToDownload.forEach(row => Object.keys(row).forEach(key => allHeaders.add(key)));
  const headersArray = Array.from(allHeaders);

  const csvRows = [
    headersArray.map(escapeCSVField).join(','),
    ...dataToDownload.map(row => 
      headersArray.map(header => escapeCSVField(row[header])).join(',')
    )
  ];
  
  const csvString = csvRows.join('\n');
  const blob = new Blob([csvString], { type: 'text/csv;charset=utf-8;' });
  const link = document.createElement('a');
  const url = URL.createObjectURL(blob);
  link.setAttribute('href', url);
  link.setAttribute('download', `combined_data_map_export.csv`);
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
};

const handleSaveMap = async () => {
  if (props.isReadOnly) return;
  isSavingMap.value = true;
  saveMapError.value = '';

  const mapInstance = dataMapDisplayRef.value?.getMapInstance();
  let currentMapCenterValue = props.initialMapSettings.center;
  let currentMapZoom = props.initialMapSettings.zoom;

  if (mapInstance) {
    currentMapCenterValue = [mapInstance.getCenter().lat, mapInstance.getCenter().lng];
    currentMapZoom = mapInstance.getZoom();
  }
  
  saveMapForm.value.filters = { ...currentFiltersByModel.value }; 
  saveMapForm.value.map_settings = {
    center: currentMapCenterValue,
    zoom: currentMapZoom,
    selected_data_types: [...mapSelectedModels.value], // Backend expects 'selected_data_types' key for models
    active_filter_tab: activeFilterTab.value, 
  };
  saveMapForm.value.data_type = null; // For combined map, specific data_type is less relevant at top level

  try {
    router.post(route('saved-maps.store'), saveMapForm.value, {
      onSuccess: () => {
        showSaveMapModal.value = false;
        saveMapForm.value.name = '';
        saveMapForm.value.description = '';
        saveMapForm.value.creator_display_name = ''; 
        saveMapForm.value.is_public = false;
        // configurable_filter_fields is reset via watch on modelMappingProp if needed, or clear here:
        // saveMapForm.value.configurable_filter_fields = {};
      },
      onError: (errors) => {
        const errorKeys = Object.keys(errors);
        if (errorKeys.length > 0) {
            saveMapError.value = errors[errorKeys[0]];
        } else {
            saveMapError.value = 'An unknown error occurred while saving the map.';
        }
      },
      onFinish: () => {
        isSavingMap.value = false;
      }
    });
  } catch (error) {
    console.error('Failed to save map:', error);
    saveMapError.value = error.response?.data?.message || 'Failed to save map.';
    isSavingMap.value = false;
  }
};


watch(() => props.modelMappingProp, (newMapping) => {
  const newConfigurableFields = {};
  if (newMapping) {
    Object.keys(newMapping).forEach(modelKey => { 
      newConfigurableFields[modelKey] = saveMapForm.value.configurable_filter_fields[modelKey] || [];
    });
  }
  saveMapForm.value.configurable_filter_fields = newConfigurableFields;
}, { immediate: true, deep: true });

onMounted(async () => {
  mapCenter.value = props.initialMapSettings?.center || [42.3601, -71.0589];

  availableModels.value.forEach(modelKey => { 
    if (!saveMapForm.value.configurable_filter_fields[modelKey]) { 
         saveMapForm.value.configurable_filter_fields[modelKey] = []; 
    }
  });


  if (props.isReadOnly) {
    allDataPointsByModel.value = { ...(props.initialDataSetsProp || {}) }; 
    clientFilteredDataPointsByModel.value = { ...(props.initialDataSetsProp || {}) }; 
    
    currentFiltersByModel.value = {}; 
    
    mapSelectedModels.value = (props.initialMapSettings?.selected_data_types && props.initialMapSettings.selected_data_types.length > 0) 
                                  ? [...props.initialMapSettings.selected_data_types] // These are modelKeys
                                  : [...availableModels.value]; 
                                  
    activeFilterTab.value = props.initialMapSettings?.active_filter_tab || availableModels.value[0] || ''; 

    availableModels.value.forEach(modelKey => { 
        const savedModelFilters = props.initialFiltersProp?.[modelKey] || {}; 
        const configurableForModel = props.configurableFilterFieldsForView?.[modelKey] || []; 
        const initialActiveConfigurableFilters = {};

        if (configurableForModel.length > 0) {
            Object.keys(savedModelFilters).forEach(key => {
                const dateField = props.allDataTypeDetailsProp[modelKey]?.dateField; 
                if (configurableForModel.includes(key) || (key === 'date_range' && (configurableForModel.includes(dateField) || configurableForModel.includes('date_range')))) {
                    initialActiveConfigurableFilters[key] = savedModelFilters[key];
                }
            });
        }
        currentFiltersByModel.value[modelKey] = initialActiveConfigurableFilters; 
        if (Object.keys(initialActiveConfigurableFilters).length > 0) {
            applyClientSideFiltersForModel(modelKey, initialActiveConfigurableFilters); 
        }
    });
    
    if (isMapInitialized.value && dataMapDisplayRef.value?.getMapInstance()) {
        dataMapDisplayRef.value.getMapInstance().setView(mapCenter.value, props.initialMapSettings?.zoom || 12);
    }
    return; 
  }

  if (Object.keys(props.initialDataSetsProp).length > 0) {
    allDataPointsByModel.value = { ...props.initialDataSetsProp }; 
  }

  activeFilterTab.value = props.initialDataTypeProp || availableModels.value[0] || ''; // initialDataTypeProp is the initialModelKey
  
  mapSelectedModels.value = (props.initialMapSettings?.selected_data_types && props.initialMapSettings.selected_data_types.length > 0) 
                                ? [...props.initialMapSettings.selected_data_types] // These are modelKeys
                                : [...availableModels.value]; 

  nlpSelectedModels.value = [...availableModels.value]; 

  const fetchPromises = [];
  availableModels.value.forEach(modelKey => { 
    currentFiltersByModel.value[modelKey] = { 
        ...(props.initialFiltersProp?.[modelKey] || props.initialFiltersProp || { limit: 100 }) 
    };
    
    if (!allDataPointsByModel.value[modelKey]) { 
      console.log(`CombinedDataMapComponent: Initial fetch for model ${modelKey}. Filters:`, currentFiltersByModel.value[modelKey]); 
      fetchPromises.push(fetchDataForModel(modelKey, currentFiltersByModel.value[modelKey])); 
    } else {
      console.log(`CombinedDataMapComponent: Data for model ${modelKey} was pre-loaded.`);
    }
  });

  if (fetchPromises.length > 0) {
    await Promise.all(fetchPromises);
  }

  let centered = false;
  // Use initialDataTypeProp (as initialModelKey) to center map if data exists for it
  const initialModelKey = props.initialDataTypeProp;
  if (initialModelKey && (allDataPointsByModel.value[initialModelKey] || []).length > 0) { 
      const firstPoint = allDataPointsByModel.value[initialModelKey][0]; 
      if (firstPoint && firstPoint.latitude && firstPoint.longitude) {
          mapCenter.value = [parseFloat(firstPoint.latitude), parseFloat(firstPoint.longitude)];
          centered = true;
      }
  }
  // Fallback: center on first model in mapSelectedModels that has data
  if (!centered && mapSelectedModels.value.length > 0) { 
    for (const modelKey of mapSelectedModels.value) { 
        const dataForModel = allDataPointsByModel.value[modelKey] || []; 
        if (dataForModel.length > 0) {
            const firstPoint = dataForModel[0];
            if (firstPoint.latitude && firstPoint.longitude) {
                mapCenter.value = [parseFloat(firstPoint.latitude), parseFloat(firstPoint.longitude)];
                centered = true;
                break;
            }
        }
    }
  }
});

const handleMapInitialized = (map) => {
    isMapInitialized.value = true;
    const zoomToUse = props.initialMapSettings?.zoom || 13;
    if (mapCenter.value && map) {
        map.setView(mapCenter.value, zoomToUse);
    }
};

</script>

<style scoped>
.combined-data-map-container {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}
.generic-map {
  height: 70vh;
  width: 100%;
  border-radius: 0.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}
.checkbox-icon-display {
  display: inline-block;
  width: 1em; 
  height: 1em; 
  margin-right: 0.25em; 
  background-size: contain;
  background-repeat: no-repeat;
  background-position: center;
  vertical-align: middle; 
}
.toolbar-icon-display { 
  display: inline-block;
  width: 1em; 
  height: 1em; 
  margin-right: 0.25em;
  background-size: contain;
  background-repeat: no-repeat;
  background-position: center;
  vertical-align: text-bottom; 
}
</style>
