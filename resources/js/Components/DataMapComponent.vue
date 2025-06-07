<template>
  <div class="data-map-container p-4">
    <!-- Save Map Button -->
    <div v-if="!isReadOnly && page.props.auth.user" class="mb-4 text-right">
      <button @click="showSaveMapModal = true" class="p-2 bg-blue-500 text-white rounded-md text-sm hover:bg-blue-600">
        Save Current Map View
      </button>
    </div>

    <!-- NLP Query Section -->
    <div v-if="!isReadOnly" class="nlp-query-section mb-6 p-4 border rounded-md shadow-sm bg-white">
      <h3 class="text-xl font-semibold mb-3">Natural Language Query</h3>
      <p class="text-sm text-gray-600 mb-2">
        Ask a question about the data (e.g., "Show all incidents last week", "Find entries related to 'vandalism' in January").
      </p>
      <div class="flex items-center space-x-2">
        <input 
          v-model="naturalLanguageQuery" 
          type="text" 
          :placeholder="`Query ${dataTypeForHumans} data...`"
          class="p-2 border rounded-md w-full text-sm"
          @keyup.enter="submitNlpQuery"
        >
        <button 
          @click="submitNlpQuery" 
          class="p-2 bg-indigo-500 text-white rounded-md text-sm hover:bg-indigo-600 whitespace-nowrap"
          :disabled="isLoading"
        >
          {{ isLoading && nlpSubmitted ? 'Processing...' : 'Submit Query' }}
        </button>
      </div>
       <div v-if="nlpError" class="mt-2 text-red-500 text-sm">{{ nlpError }}</div>
       <div v-if="isLoading && nlpSubmitted" class="mt-2 text-sm text-indigo-500">Fetching data based on your query...</div>
    </div>


    
    <!-- Map Display Section -->
    <div class="mb-6"> <!-- Wrapper for the map, similar to CrimeMapComponent's map div -->
        <DataMapDisplay
            ref="dataMapDisplayRef"
            :mapCenterCoordinates="mapCenter"
            :dataPointsToDisplay="dataPoints"
            :externalIdFieldProp="externalIdFieldProp"
            @marker-data-point-clicked="handleMarkerClick"
            @map-initialized-internal="handleMapInitialized"
            class="h-[70vh] w-full rounded-md shadow-md generic-map" 
        />
    </div>
    
    <!-- Selected Item Details -->
    <div class="selected-item-details p-4 border rounded-md shadow-sm bg-white mb-6 max-h-[40vh] overflow-y-auto"> <!-- Placed below map -->
        <h3 class="text-lg font-semibold mb-3">Selected Item Details</h3>
        <div v-if="selectedDataPoint" class="text-sm">
            <!-- Basic generic display of selectedDataPoint -->
            <div v-for="(value, key) in selectedDataPoint" :key="key" class="mb-1">
                <strong class="capitalize">{{ key.replace(/_/g, ' ') }}:</strong> 
                <span v-if="typeof value === 'object'">{{ JSON.stringify(value) }}</span>
                <span v-else>{{ value }}</span>
            </div>
            <!-- TODO: Implement dynamic component loading for specific data types if needed -->
            <!-- Example: <component :is="detailComponentForDataType" :data="selectedDataPoint" /> -->
        </div>
        <div v-else class="text-gray-500">Click a marker or list item to see details.</div>
    </div>
    <!-- Manual Filters Section -->
    <GenericFiltersControl
      v-if="!isReadOnly"
      :filter-fields-description="filterFieldsDescriptionProp"
      :initial-filters="currentFilters"
      :date-field="dateFieldProp"
      :data-type="dataTypeProp"
      @filters-updated="handleFiltersUpdated"
      class="mb-6"
    />
    <!-- AI Assistant -->
    <AiAssistant 
      v-if="dataPoints.length > 0 && !isLoading && !isReadOnly"
      :context="dataPoints" 
      :language_codes="language_codes" 
      :currentMapLanguage="currentReportLanguage"
      class="my-6"
    />

    <!-- Data List Section -->
    <GenericDataList
      :totalData="dataPoints"
      :itemsPerPage="10"
      @handle-goto-marker="handleListItemClick"
      :language_codes="language_codes"
      class="mt-6"
    />

    <!-- Download CSV Button -->
     <div class="mt-6 text-right">
        <button 
            v-if="!isReadOnly"
            @click="downloadCSV" 
            class="p-2 bg-green-500 text-white rounded-md text-sm hover:bg-green-600"
            :disabled="dataPoints.length === 0"
        >
            Download Displayed Data as CSV
        </button>
    </div>

    <!-- Save Map Modal -->
    <div v-if="showSaveMapModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full flex justify-center items-center z-50">
      <div class="bg-white p-6 rounded-lg shadow-xl w-full max-w-md">
        <h3 class="text-lg font-semibold mb-4">Save Map</h3>
        <form @submit.prevent="handleSaveMap">
          <div class="mb-4">
            <label for="mapName" class="block text-sm font-medium text-gray-700">Map Name*</label>
            <input type="text" v-model="saveMapForm.name" id="mapName" required class="mt-1 p-2 border rounded-md w-full">
          </div>
          <div class="mb-4">
            <label for="mapDescription" class="block text-sm font-medium text-gray-700">Description</label>
            <textarea v-model="saveMapForm.description" id="mapDescription" rows="3" class="mt-1 p-2 border rounded-md w-full"></textarea>
          </div>
          <div class="mb-4">
            <label for="creatorDisplayName" class="block text-sm font-medium text-gray-700">Creator Display Name (Optional)</label>
            <input type="text" v-model="saveMapForm.creator_display_name" id="creatorDisplayName" placeholder="Leave blank to use your account name" class="mt-1 p-2 border rounded-md w-full">
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

// import MapDisplay from '@/Components/MapDisplay.vue'; // Replaced
import DataMapDisplay from '@/Components/DataMapDisplay.vue'; // Added
import GenericFiltersControl from '@/Components/GenericFiltersControl.vue';
import AiAssistant from '@/Components/AiAssistant.vue';
import GenericDataList from '@/Components/GenericDataList.vue';

const props = defineProps({
  initialDataProp: Array,
  pageFiltersProp: Object,
  dataTypeProp: String,
  dateFieldProp: String,
  externalIdFieldProp: String,
  filterFieldsDescriptionProp: [String, Array, Object],
  isReadOnly: { // New prop
    type: Boolean,
    default: false,
  },
  initialMapSettings: { // New prop for viewing saved maps
    type: Object,
    default: () => ({ center: [42.3601, -71.0589], zoom: 12 }) // Default Boston center & zoom
  }
});

const page = usePage();
const csrfToken = computed(() => page.props.csrf_token || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'));
const translations = inject('translations'); // Assuming translations are provided globally

const dataMapDisplayRef = ref(null); // Renamed from mapDisplayRef
const dataPoints = ref([]);
const currentFilters = ref({});
const naturalLanguageQuery = ref('');
const nlpError = ref('');
const nlpSubmitted = ref(false); // To distinguish general loading from NLP loading

const selectedDataPoint = ref(null);
const mapCenter = ref([42.3601, -71.0589]); // Default Boston center, adjust as needed
const isLoading = ref(false);
const isMapInitialized = ref(false);

// Save Map Modal State
const showSaveMapModal = ref(false);
const saveMapForm = ref({
  name: '',
  description: '',
  creator_display_name: '', // Added
  map_type: 'single',
  data_type: '', // Will be set from props
  filters: {},
  map_settings: {},
  is_public: false,
});
const isSavingMap = ref(false);
const saveMapError = ref('');


const language_codes = ref(['en-US']); // Default, make dynamic if needed

const dataTypeForHumans = computed(() => {
    if (!props.dataTypeProp) return 'Data';
    return props.dataTypeProp.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
});

const currentReportLanguage = computed(() => {
  const locale = language_codes.value[0] || 'en-US';
  const mapping = { 'en-US': 'en', 'es-MX': 'es', /* ... more mappings */ };
  return mapping[locale] || 'en';
});

const fetchData = async (filtersToApply) => {
  if (!props.dataTypeProp) return;
  isLoading.value = true;
  nlpError.value = '';
  try {
    const response = await axios.post(`/api/data/${props.dataTypeProp}`, {
      filters: filtersToApply,
    }, {
      headers: { 'X-CSRF-TOKEN': csrfToken.value },
    });
    dataPoints.value = response.data.data || [];
    if (dataPoints.value.length > 0 && !props.isReadOnly) { // Only auto-center if not read-only (saved map has its own center)
        // Recenter map if data points exist and map is initialized
        if (isMapInitialized.value && dataMapDisplayRef.value?.getMapInstance()) {
            const firstPointWithCoords = dataPoints.value.find(dp => (dp.latitude || dp.lat) && (dp.longitude || dp.long || dp.lng));
            if (firstPointWithCoords) {
                const lat = parseFloat(firstPointWithCoords.latitude || firstPointWithCoords.lat);
                const lon = parseFloat(firstPointWithCoords.longitude || firstPointWithCoords.long || firstPointWithCoords.lng);
                if (!isNaN(lat) && !isNaN(lon)) {
                    mapCenter.value = [lat, lon];
                    dataMapDisplayRef.value.getMapInstance().setView(mapCenter.value, 13); // Adjust zoom as needed
                }
            }
        }
    } else {
        // console.log("No data points returned for the current filters.");
    }
  } catch (error) {
    console.error(`Error fetching ${props.dataTypeProp} data:`, error);
    nlpError.value = `Failed to fetch data. ${error.response?.data?.error || error.message}`;
    dataPoints.value = [];
  } finally {
    isLoading.value = false;
    nlpSubmitted.value = false; // Reset NLP submission flag here too
  }
};

const submitNlpQuery = async () => {
  if (props.isReadOnly || !naturalLanguageQuery.value.trim() || !props.dataTypeProp) return;
  isLoading.value = true;
  nlpSubmitted.value = true; // Mark that NLP query initiated loading
  nlpError.value = '';
  try {
    const response = await axios.post(`/api/natural-language-query/${props.dataTypeProp}`, {
      query: naturalLanguageQuery.value,
    }, {
      headers: { 'X-CSRF-TOKEN': csrfToken.value },
    });
    dataPoints.value = response.data.data || [];
    // Update currentFilters based on what the NLP query resolved to, if provided
    if (response.data.filtersApplied) {
      currentFilters.value = { ...response.data.filtersApplied };
    }
     if (dataPoints.value.length > 0 && isMapInitialized.value && dataMapDisplayRef.value?.getMapInstance()) {
        const firstPointWithCoords = dataPoints.value.find(dp => (dp.latitude || dp.lat) && (dp.longitude || dp.long || dp.lng));
        if (firstPointWithCoords) {
            const lat = parseFloat(firstPointWithCoords.latitude || firstPointWithCoords.lat);
            const lon = parseFloat(firstPointWithCoords.longitude || firstPointWithCoords.long || firstPointWithCoords.lng);
            if (!isNaN(lat) && !isNaN(lon)) {
                mapCenter.value = [lat, lon];
                dataMapDisplayRef.value.getMapInstance().setView(mapCenter.value, 13);
            }
        }
    }
  } catch (error) {
    console.error(`Error processing NLP query for ${props.dataTypeProp}:`, error);
    nlpError.value = `NLP query failed. ${error.response?.data?.error || error.message}`;
    dataPoints.value = []; // Clear data on NLP error
  } finally {
    isLoading.value = false;
    nlpSubmitted.value = false;
  }
};

const handleFiltersUpdated = (newFilters) => {
  if (props.isReadOnly) return;
  currentFilters.value = { ...newFilters };
  fetchData(currentFilters.value);
};

const handleMarkerClick = (dataPoint) => {
  selectedDataPoint.value = dataPoint;
};

const handleListItemClick = (dataPoint) => {
  selectedDataPoint.value = dataPoint;
  const lat = parseFloat(dataPoint.latitude || dataPoint.lat);
  const lon = parseFloat(dataPoint.longitude || dataPoint.long || dataPoint.lng);

  if (dataMapDisplayRef.value && !isNaN(lat) && !isNaN(lon)) {
    dataMapDisplayRef.value.panToAndOpenPopup(dataPoint, props.externalIdFieldProp);
  }
};

const escapeCSVField = (field) => {
  if (field === null || typeof field === 'undefined') return '';
  let stringField = String(field);
  if (stringField.includes(',') || stringField.includes('"') || stringField.includes('\n')) {
    stringField = stringField.replace(/"/g, '""'); // Escape double quotes
    return `"${stringField}"`; // Enclose in double quotes
  }
  return stringField;
};

const downloadCSV = () => {
  if (dataPoints.value.length === 0) return;

  const headers = Object.keys(dataPoints.value[0] || {});
  const csvRows = [
    headers.map(escapeCSVField).join(','), // Header row
    ...dataPoints.value.map(row => 
      headers.map(header => escapeCSVField(row[header])).join(',')
    )
  ];
  
  const csvString = csvRows.join('\n');
  const blob = new Blob([csvString], { type: 'text/csv;charset=utf-8;' });
  const link = document.createElement('a');
  const url = URL.createObjectURL(blob);
  link.setAttribute('href', url);
  link.setAttribute('download', `${props.dataTypeProp}_data.csv`);
  link.style.visibility = 'hidden';
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
  
  saveMapForm.value.data_type = props.dataTypeProp;
  saveMapForm.value.filters = { ...currentFilters.value }; // Save current filters
  saveMapForm.value.map_settings = {
    center: currentMapCenter,
    zoom: currentMapZoom,
  };

  try {
    // Using Inertia post for form handling (redirects, errors)
    router.post(route('saved-maps.store'), saveMapForm.value, {
      onSuccess: () => {
        showSaveMapModal.value = false;
        // Reset form if needed, or rely on page reload/redirect
        saveMapForm.value.name = '';
        saveMapForm.value.description = '';
        saveMapForm.value.creator_display_name = ''; // Reset
        saveMapForm.value.is_public = false;
      },
      onError: (errors) => {
        const errorKeys = Object.keys(errors);
        if (errorKeys.length > 0) {
            saveMapError.value = errors[errorKeys[0]]; // Display first error
        } else {
            saveMapError.value = 'An unknown error occurred while saving the map.';
        }
        console.error('Error saving map:', errors);
      },
      onFinish: () => {
        isSavingMap.value = false;
      }
    });
  } catch (error) { // Fallback for non-Inertia errors, though router.post should handle most
    console.error('Failed to save map:', error);
    saveMapError.value = error.response?.data?.message || 'Failed to save map.';
    isSavingMap.value = false;
  }
};


onMounted(() => {
  mapCenter.value = props.initialMapSettings?.center || [42.3601, -71.0589];

  dataPoints.value = props.initialDataProp || [];
  currentFilters.value = { ...(props.pageFiltersProp || {}), limit: 1000 }; 

  // If it's read-only, data is assumed to be pre-loaded via props by ViewSavedMapPage.
  // No initial fetch unless specifically told to (e.g. if initialDataProp is empty but filters exist)
  if (!props.isReadOnly) {
    const initialFetchRequired = Object.keys(currentFilters.value).filter(k => k !== 'limit').length > 0 || dataPoints.value.length === 0;
    if (initialFetchRequired) {
        fetchData(currentFilters.value);
    } else if (dataPoints.value.length > 0) {
          const firstPointWithCoords = dataPoints.value.find(dp => (dp.latitude || dp.lat) && (dp.longitude || dp.long || dp.lng));
          if (firstPointWithCoords) {
              const lat = parseFloat(firstPointWithCoords.latitude || firstPointWithCoords.lat);
              const lon = parseFloat(firstPointWithCoords.longitude || firstPointWithCoords.long || firstPointWithCoords.lng);
              if (!isNaN(lat) && !isNaN(lon)) {
                   mapCenter.value = [lat, lon];
              }
          }
    }
  } else {
    // For read-only, mapCenter is already set by initialMapSettings.
    // Data is passed via initialDataProp.
    // Ensure map view is set correctly if map is already initialized.
    if (isMapInitialized.value && dataMapDisplayRef.value?.getMapInstance()) {
        dataMapDisplayRef.value.getMapInstance().setView(mapCenter.value, props.initialMapSettings?.zoom || 12);
    }
  }
});

const handleMapInitialized = (map) => {
    isMapInitialized.value = true;
    const zoomToUse = props.initialMapSettings?.zoom || 13;
    if (mapCenter.value && map) {
        map.setView(mapCenter.value, zoomToUse); 
    }
    if (props.isReadOnly && dataPoints.value.length > 0 && dataMapDisplayRef.value) {
       // DataMapDisplay's watch on dataPointsToDisplay should handle this.
    }
};


// Watch for dataType changes if this component could be reused without full page reload
watch(() => props.dataTypeProp, (newDataType, oldDataType) => {
  if (newDataType !== oldDataType && !props.isReadOnly) { // Don't refetch if read-only
    naturalLanguageQuery.value = '';
    selectedDataPoint.value = null;
    // Reset filters or fetch new filter descriptions if they change with dataType
    currentFilters.value = { limit: 1000 }; // Reset to default
    fetchData(currentFilters.value);
  }
});

</script>

<style scoped>
.data-map-container {
  display: flex;
  flex-direction: column;
  gap: 1.5rem; /* Equivalent to mb-6 for spacing between major sections */
}

.generic-map {
  height: 70vh; /* Adjust as needed */
  width: 100%;
  border-radius: 0.5rem; /* Equivalent to rounded-md */
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1); /* Equivalent to shadow-md */
}
/* Add any additional specific styles here */
</style>
