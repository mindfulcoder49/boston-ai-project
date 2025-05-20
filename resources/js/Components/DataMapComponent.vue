<template>
  <div class="data-map-container p-4">
    <!-- NLP Query Section -->
    <div class="nlp-query-section mb-6 p-4 border rounded-md shadow-sm bg-white">
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
      :filter-fields-description="filterFieldsDescriptionProp"
      :initial-filters="currentFilters"
      :date-field="dateFieldProp"
      :data-type="dataTypeProp"
      @filters-updated="handleFiltersUpdated"
      class="mb-6"
    />
    <!-- AI Assistant -->
    <AiAssistant 
      v-if="dataPoints.length > 0 && !isLoading"
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
            @click="downloadCSV" 
            class="p-2 bg-green-500 text-white rounded-md text-sm hover:bg-green-600"
            :disabled="dataPoints.length === 0"
        >
            Download Displayed Data as CSV
        </button>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch, inject } from 'vue';
import axios from 'axios';
import { usePage } from '@inertiajs/vue3';

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
    if (dataPoints.value.length > 0) {
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
    nlpSubmitted.value = false;
  }
};

const submitNlpQuery = async () => {
  if (!naturalLanguageQuery.value.trim() || !props.dataTypeProp) return;
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

onMounted(() => {
  dataPoints.value = props.initialDataProp || [];
  currentFilters.value = { ...(props.pageFiltersProp || {}), limit: 1000 }; 

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
                 // If map is already initialized by DataMapDisplay, we might want to set view here too
                 // However, DataMapDisplay itself watches mapCenterCoordinates, so this might be redundant
                 // if (isMapInitialized.value && dataMapDisplayRef.value?.getMapInstance()) {
                 //    dataMapDisplayRef.value.getMapInstance().setView(mapCenter.value, 13);
                 // }
            }
        }
  }
});

const handleMapInitialized = (map) => {
    isMapInitialized.value = true;
    // If there was an initial centering based on data before map was ready, apply it now.
    if (mapCenter.value && map) {
        map.setView(mapCenter.value, 13); // Ensure map is centered correctly after init
    }
     // If initial data exists, and map just initialized, ensure markers are shown
    if (props.initialDataProp && props.initialDataProp.length > 0 && dataMapDisplayRef.value) {
       // DataMapDisplay's watch on dataPointsToDisplay should handle this,
       // but an explicit call might be needed if timing is an issue.
       // dataMapDisplayRef.value.updateMarkers(dataPoints.value);
    }
};


// Watch for dataType changes if this component could be reused without full page reload
watch(() => props.dataTypeProp, (newDataType, oldDataType) => {
  if (newDataType !== oldDataType) {
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
