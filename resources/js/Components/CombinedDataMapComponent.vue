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
        Select data types and ask a question (e.g., "Show incidents and violations last week").
      </p>
      <div class="flex items-center space-x-2 mb-3">
        <input 
          v-model="nlpQueryText" 
          type="text" 
          placeholder="Query selected data types..."
          class="p-2 border rounded-md w-full text-sm"
          @keyup.enter="submitCombinedNlpQuery"
        >
        <button 
          @click="submitCombinedNlpQuery" 
          class="p-2 bg-indigo-500 text-white rounded-md text-sm hover:bg-indigo-600 whitespace-nowrap"
          :disabled="isGlobalLoading || nlpSelectedDataTypes.length === 0"
        >
          {{ isGlobalLoading && nlpQuerySubmitted ? 'Processing...' : 'Submit Query' }}
        </button>
      </div>
      <div class="mb-3">
        <span class="text-sm font-medium mr-2">Query Targets:</span>
        <label v-for="dt in availableDataTypes" :key="`nlp-${dt}`" class="mr-4 text-sm inline-flex items-center">
          <input type="checkbox" :value="dt" v-model="nlpSelectedDataTypes" class="mr-1 form-checkbox h-4 w-4 text-indigo-600">
          <span
            v-if="getIconClassForDataType(dt)"
            :class="[getIconClassForDataType(dt), 'checkbox-icon-display']"
          ></span>
          {{ getModelNameForHumans(dt) }}
        </label>
      </div>
      <div v-if="Object.keys(nlpErrorsByType).length > 0" class="mt-2 text-red-500 text-sm">
        <p v-for="(error, type) in nlpErrorsByType" :key="`nlp-error-${type}`">
          <span v-if="error">
            Error for {{ getModelNameForHumans(type) }}: {{ error }}
          </span>
        </p>
      </div>
    </div>

    <!-- Map Data Type Selection Section -->
    <div class="map-data-selection mb-6 p-4 border rounded-md shadow-sm bg-white">
        <h3 class="text-xl font-semibold mb-3">Display Data Types on Map</h3>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-2">
        <label v-for="dt in availableDataTypes" :key="`map-select-${dt}`" class="text-sm inline-flex items-center p-2 border rounded-md hover:bg-gray-50 cursor-pointer">
            <input type="checkbox" :value="dt" v-model="mapSelectedDataTypes" class="mr-2 form-checkbox h-4 w-4 text-indigo-600" >
            <span
              v-if="getIconClassForDataType(dt)"
              :class="[getIconClassForDataType(dt), 'checkbox-icon-display']"
            ></span>
            {{ getModelNameForHumans(dt) }}
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
    <div v-if="!isReadOnly" class="filters-tab-section mb-6 p-4 border rounded-md shadow-sm bg-white">
        <h3 class="text-xl font-semibold mb-3">Filters</h3>
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-4 overflow-x-auto" aria-label="Tabs">
                <button
                    v-for="dataType in availableDataTypes"
                    :key="`tab-${dataType}`"
                    @click="activeFilterTab = dataType"
                    :class="[
                        'whitespace-nowrap py-3 px-4 border-b-2 font-medium text-sm',
                        activeFilterTab === dataType
                            ? 'border-indigo-500 text-indigo-600'
                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                    ]"
                >
                <span :class="getIconClassForDataType(dataType)" class="toolbar-icon-display"></span>
                    {{ getModelNameForHumans(dataType) }} 
                    <span class="text-xs bg-gray-200 text-gray-700 rounded-full px-1.5 py-0.5 ml-1">
                        {{ (allDataPointsByType[dataType] || []).length }}
                    </span>
                </button>
            </nav>
        </div>
        <div class="mt-4">
            <div v-for="dataType in availableDataTypes" :key="`filter-content-${dataType}`">
                <GenericFiltersControl
                    v-show="activeFilterTab === dataType"
                    :filter-fields-description="allDataTypeDetailsProp[dataType]?.filterFieldsDescription || []"
                    :initial-filters="currentFiltersByType[dataType] || {}"
                    :date-field="allDataTypeDetailsProp[dataType]?.dateField"
                    :data-type="dataType" 
                    @filters-updated="(newFilters) => handleFiltersUpdatedForType(dataType, newFilters)"
                    class="mt-2"
                />
            </div>
        </div>
    </div>
    
    <!-- Selected Item Details -->
    <div class="selected-item-details p-4 border rounded-md shadow-sm bg-white my-6 max-h-[40vh] overflow-y-auto">
      <h3 class="text-lg font-semibold mb-3">Selected Item Details (Type: {{ selectedDataPoint?.alcivartech_type_display || 'N/A' }})</h3>
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
import { usePage, router } from '@inertiajs/vue3'; // Added router

import DataMapDisplay from '@/Components/DataMapDisplay.vue';
import GenericFiltersControl from '@/Components/GenericFiltersControl.vue';
import AiAssistant from '@/Components/AiAssistant.vue';
import GenericDataList from '@/Components/GenericDataList.vue';

const props = defineProps({
  modelMappingProp: Object,
  initialDataTypeProp: String,
  initialDataProp: Array,
  initialFiltersProp: Object,
  allDataTypeDetailsProp: Object, // Keyed by dataType: { dateField, externalIdField, filterFieldsDescription, modelNameForHumans }
  isReadOnly: { // New prop
    type: Boolean,
    default: false,
  },
  initialMapSettings: { // New prop for viewing saved maps
    type: Object,
    default: () => ({ center: [42.3601, -71.0589], zoom: 12, selected_data_types: [] })
  },
  // Prop to pass pre-loaded data for multiple types when in read-only mode
  initialDataSetsProp: {
    type: Object,
    default: () => ({})
  }
});

const page = usePage();
const csrfToken = computed(() => page.props.csrf_token || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'));

const dataMapDisplayRef = ref(null);
const allDataPointsByType = ref({}); // { dataType1: [], dataType2: [] }
const currentFiltersByType = ref({}); // { dataType1: {}, dataType2: {} }
const isLoadingByType = ref({}); // { dataType1: false, dataType2: true }
const nlpErrorsByType = ref({}); // { dataType1: "error message" }

const nlpQueryText = ref('');
const nlpSelectedDataTypes = ref([]);
const nlpQuerySubmitted = ref(false);

const selectedDataPoint = ref(null);
const mapCenter = ref([42.3601, -71.0589]);
const isMapInitialized = ref(false);
const language_codes = ref(['en-US']);

// const filterVisibility = ref({}); // To toggle filter sections - REMOVED for tabs
const activeFilterTab = ref(''); // For tabbed filters
const mapSelectedDataTypes = ref([]); // For selecting which data types appear on map

// Save Map Modal State
const showSaveMapModal = ref(false);
const saveMapForm = ref({
  name: '',
  description: '',
  creator_display_name: '', // Added
  map_type: 'combined',
  data_type: null, // Not used for combined
  filters: {}, // Will be currentFiltersByType
  map_settings: {},
  is_public: false,
});
const isSavingMap = ref(false);
const saveMapError = ref('');


const availableDataTypes = computed(() => Object.keys(props.modelMappingProp || {}));

const isGlobalLoading = computed(() => {
  return Object.values(isLoadingByType.value).some(loading => loading) || nlpQuerySubmitted.value;
});

const getIconClassForDataType = (dataType) => {
  if (!dataType) return '';
  const dtLower = dataType.toLowerCase();
  if (dtLower.includes('crime')) return 'crime-div-icon';
  if (dtLower.includes('case') || dtLower.includes('311')) return 'case-div-icon no-photo'; // Example for 311/cases
  if (dtLower.includes('permit')) return 'permit-div-icon'; // Or 'building-permit-div-icon' if that's the class
  if (dtLower.includes('property_violation') || dtLower.includes('violation')) return 'property-violation-div-icon';
  if (dtLower.includes('construction_off_hour') || dtLower.includes('construction')) return 'construction-off-hour-div-icon';
  if (dtLower.includes('food_inspection') || dtLower.includes('food') || dtLower.includes('inspection')) return 'food-inspection-div-icon';
  // Add more mappings as needed based on your dataTypes and CSS classes
  return ''; // Return empty string or a default icon class if no match
};

const getModelNameForHumans = (dataType) => {
    return props.allDataTypeDetailsProp[dataType]?.modelNameForHumans || dataType.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
};

const currentReportLanguage = computed(() => {
  const locale = language_codes.value[0] || 'en-US';
  const mapping = { 'en-US': 'en', 'es-MX': 'es', /* ... */ };
  return mapping[locale] || 'en';
});

const combinedDataPointsForMap = computed(() => {
  let combined = [];
  for (const dataType of mapSelectedDataTypes.value) { // Filter by mapSelectedDataTypes
    if (availableDataTypes.value.includes(dataType)) { // Ensure it's a valid type
        const points = allDataPointsByType.value[dataType] || [];
        combined.push(...points.map(p => ({ 
            ...p, 
            alcivartech_external_id: `${dataType}-${p[props.allDataTypeDetailsProp[dataType]?.externalIdField || 'id']}`,
            alcivartech_type_display: getModelNameForHumans(dataType) 
        })));
    }
  }
  return combined;
});

const fetchDataForType = async (dataType, filters) => {
  if (props.isReadOnly || !dataType) return; // Don't fetch if read-only
  isLoadingByType.value = { ...isLoadingByType.value, [dataType]: true };
  nlpErrorsByType.value = { ...nlpErrorsByType.value, [dataType]: '' };

  try {
    const response = await axios.post(`/api/data/${dataType}`, {
      filters: filters,
    }, {
      headers: { 'X-CSRF-TOKEN': csrfToken.value },
    });
    allDataPointsByType.value = { ...allDataPointsByType.value, [dataType]: response.data.data || [] };
    currentFiltersByType.value = { ...currentFiltersByType.value, [dataType]: filters }; // Store applied filters
  } catch (error) {
    console.error(`Error fetching ${dataType} data:`, error);
    nlpErrorsByType.value = { ...nlpErrorsByType.value, [dataType]: `Failed to fetch data. ${error.response?.data?.error || error.message}` };
    allDataPointsByType.value = { ...allDataPointsByType.value, [dataType]: [] };
  } finally {
    isLoadingByType.value = { ...isLoadingByType.value, [dataType]: false };
  }
};

const submitCombinedNlpQuery = async () => {
  if (props.isReadOnly || !nlpQueryText.value.trim() || nlpSelectedDataTypes.value.length === 0) return;
  
  nlpQuerySubmitted.value = true;
  nlpErrorsByType.value = {}; // Clear previous errors

  const queryPromises = nlpSelectedDataTypes.value.map(async (dataType) => {
    isLoadingByType.value = { ...isLoadingByType.value, [dataType]: true };
    try {
      const response = await axios.post(`/api/natural-language-query/${dataType}`, {
        query: nlpQueryText.value,
      }, {
        headers: { 'X-CSRF-TOKEN': csrfToken.value },
      });
      allDataPointsByType.value = { ...allDataPointsByType.value, [dataType]: response.data.data || [] };
      if (response.data.filtersApplied) {
        currentFiltersByType.value = { ...currentFiltersByType.value, [dataType]: response.data.filtersApplied };
      }
    } catch (error) {
      console.error(`Error processing NLP query for ${dataType}:`, error);
      nlpErrorsByType.value = { ...nlpErrorsByType.value, [dataType]: `NLP query failed. ${error.response?.data?.error || error.message}` };
      allDataPointsByType.value = { ...allDataPointsByType.value, [dataType]: [] }; // Clear data on error for this type
    } finally {
      isLoadingByType.value = { ...isLoadingByType.value, [dataType]: false };
    }
  });

  await Promise.all(queryPromises);
  nlpQuerySubmitted.value = false;
  
  // Optionally, recenter map based on the first piece of data found
  const firstDataTypeWithData = nlpSelectedDataTypes.value.find(dt => (allDataPointsByType.value[dt] || []).length > 0);
  if (firstDataTypeWithData) {
      const firstPoint = (allDataPointsByType.value[firstDataTypeWithData] || [])[0];
      if (firstPoint && firstPoint.latitude && firstPoint.longitude && dataMapDisplayRef.value?.getMapInstance()) {
          mapCenter.value = [parseFloat(firstPoint.latitude), parseFloat(firstPoint.longitude)];
          dataMapDisplayRef.value.getMapInstance().setView(mapCenter.value, 13);
      }
  }
};

const handleFiltersUpdatedForType = (dataType, newFilters) => {
  if (props.isReadOnly) return;
  fetchDataForType(dataType, newFilters);
};

const handleMarkerClick = (dataPoint) => {
  selectedDataPoint.value = dataPoint; // dataPoint here is already enriched by combinedDataPointsForMap
};

const handleListItemClick = (dataPoint) => {
  selectedDataPoint.value = dataPoint;
  const lat = parseFloat(dataPoint.latitude); // Assuming normalized latitude
  const lon = parseFloat(dataPoint.longitude); // Assuming normalized longitude

  if (dataMapDisplayRef.value && !isNaN(lat) && !isNaN(lon)) {
    // panToAndOpenPopup expects the original externalIdField, which we've mapped to alcivartech_external_id
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

  // Create a superset of all headers
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
  let currentMapCenter = props.initialMapSettings.center;
  let currentMapZoom = props.initialMapSettings.zoom;

  if (mapInstance) {
    currentMapCenter = [mapInstance.getCenter().lat, mapInstance.getCenter().lng];
    currentMapZoom = mapInstance.getZoom();
  }
  
  saveMapForm.value.filters = { ...currentFiltersByType.value }; // Save current filters for all types
  saveMapForm.value.map_settings = {
    center: currentMapCenter,
    zoom: currentMapZoom,
    selected_data_types: [...mapSelectedDataTypes.value], // Save currently selected layers for display
    active_filter_tab: activeFilterTab.value, // Save current active tab
  };

  try {
    router.post(route('saved-maps.store'), saveMapForm.value, {
      onSuccess: () => {
        showSaveMapModal.value = false;
        saveMapForm.value.name = '';
        saveMapForm.value.description = '';
        saveMapForm.value.creator_display_name = ''; // Reset
        saveMapForm.value.is_public = false;
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


// REMOVED: toggleFilterVisibility as accordion is replaced by tabs

onMounted(async () => {
  mapCenter.value = props.initialMapSettings?.center || [42.3601, -71.0589];

  if (props.isReadOnly) {
    // For read-only mode, data is pre-loaded via initialDataSetsProp
    allDataPointsByType.value = { ...(props.initialDataSetsProp || {}) };
    currentFiltersByType.value = { ...(props.initialFiltersProp || {}) }; // These are the saved filters
    mapSelectedDataTypes.value = props.initialMapSettings?.selected_data_types || availableDataTypes.value;
    activeFilterTab.value = props.initialMapSettings?.active_filter_tab || availableDataTypes.value[0] || '';
    
    // Ensure map view is set
     if (isMapInitialized.value && dataMapDisplayRef.value?.getMapInstance()) {
        dataMapDisplayRef.value.getMapInstance().setView(mapCenter.value, props.initialMapSettings?.zoom || 12);
    }
    return; // No fetching needed for read-only
  }

  // Initialize with initial data type if not read-only
  if (props.initialDataTypeProp && props.initialDataProp) {
    allDataPointsByType.value[props.initialDataTypeProp] = props.initialDataProp;
    currentFiltersByType.value[props.initialDataTypeProp] = { ...(props.initialFiltersProp || {}), limit: 100 };
    nlpSelectedDataTypes.value =  availableDataTypes.value;
    activeFilterTab.value = props.initialDataTypeProp; // Set initial active tab
    // enable all data types
    mapSelectedDataTypes.value = availableDataTypes.value;
  } else if (availableDataTypes.value.length > 0) {
    // Fallback if no initialDataTypeProp is specified
    const firstAvailableType = availableDataTypes.value[0];
    activeFilterTab.value = firstAvailableType;
    mapSelectedDataTypes.value = [firstAvailableType];
    nlpSelectedDataTypes.value = [firstAvailableType];
  }


  // Initialize filter visibility and fetch data for other types
  const fetchPromises = [];
  availableDataTypes.value.forEach(dataType => {
    // filterVisibility.value[dataType] = (dataType === props.initialDataTypeProp); // REMOVED

    if (dataType !== props.initialDataTypeProp && !props.isReadOnly) { // Don't fetch if read-only
      const defaultFilters = { limit: 100 }; 
      currentFiltersByType.value[dataType] = defaultFilters;
      fetchPromises.push(fetchDataForType(dataType, defaultFilters));
    } else if (props.initialDataProp && props.initialDataProp.length === 0 && Object.keys(currentFiltersByType.value[props.initialDataTypeProp] || {}).length > 0) {
        fetchPromises.push(fetchDataForType(props.initialDataTypeProp, currentFiltersByType.value[props.initialDataTypeProp]));
    }
  });

  await Promise.all(fetchPromises);

  // Set map center based on initial data if available
  if (props.initialDataProp && props.initialDataProp.length > 0 && !props.isReadOnly) {
    const firstPoint = props.initialDataProp[0];
    if (firstPoint.latitude && firstPoint.longitude) {
      mapCenter.value = [parseFloat(firstPoint.latitude), parseFloat(firstPoint.longitude)];
    }
  } else if (mapSelectedDataTypes.value.length > 0 && !props.isReadOnly) {
    // If no initial data, but some types are selected for map, try to center on first point of first selected type
    const firstMapSelectedType = mapSelectedDataTypes.value[0];
    const dataForFirstType = allDataPointsByType.value[firstMapSelectedType] || [];
    if (dataForFirstType.length > 0) {
        const firstPoint = dataForFirstType[0];
         if (firstPoint.latitude && firstPoint.longitude) {
            mapCenter.value = [parseFloat(firstPoint.latitude), parseFloat(firstPoint.longitude)];
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

/* Basic Tab Styling - customize as needed */
/* Consider moving to a global style or a dedicated Tab component if used elsewhere */
/* For Tailwind, you'd use classes directly in the template */
</style>
