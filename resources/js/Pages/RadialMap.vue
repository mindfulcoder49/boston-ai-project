<template>
  <PageTemplate>
    <Head>
      <title>Home</title>
    </Head>

    <!-- Page Title -->
    <h1 class="text-2xl font-bold text-gray-800 text-center">The Boston App</h1>

    <!-- Form to submit new center coordinates -->
    <p class="text-gray-700 mt-4 mt-8 text-lg leading-relaxed text-center">
      This map displays crime, 311 cases, and building permits located within a half mile from the center point. You can choose a new center point by clicking the "Choose New Center" button and then clicking on the map. Click "Save New Center" to update the map.
    </p>

    <AddressSearch @address-selected="updateCenterCoordinates" />

    <form @submit.prevent="submitNewCenter" class="space-y-4 mb-4">
      <!-- Selected Center Coordinates display -->
      <div v-if="newCenter" class="p-4 bg-gray-100  shadow text-center">
        <p class="font-bold text-gray-800">Selected Center Coordinates:</p>
        <p class="text-gray-700">{{ newCenter.lat }}, {{ newCenter.lng }}</p>
      </div>
      <div v-else class="p-4 bg-gray-100  shadow text-center">
        <p class="font-bold text-gray-800">Current Center Coordinates:</p>
        <p class="text-gray-700">{{ centralLocation.latitude }}, {{ centralLocation.longitude }}</p>
      </div>
      <!-- Button container -->
      <div class="flex space-x-4">
        <!-- Choose New Center button -->
        <button
          type="button"
          @click="toggleCenterSelection"
          class="px-4 py-2 text-white bg-blue-500  shadow-lg disabled:bg-gray-400 hover:bg-blue-600 transition-colors w-1/2"
        >
          {{ centerSelectionActive ? 'Cancel' : 'Choose New Center' }}
        </button>
      </div>
      <div class="flex space-x-4" v-if="isAuthenticated">
        <!-- SaveLocation Component -->
        <SaveLocation
          :location="centralLocation"
          @load-location="handleLoadLocation"
        />
      </div>
      <div class="flex space-x-4" v-else>
        <p class="text-gray-700 mt-4 mt-8 text-lg leading-relaxed text-center">
          Log in to save locations
        </p>
      </div>
    </form>

    <div class="boston-map">
      <div id="map" class="h-[70vh] mb-6 rounded-lg shadow-lg"></div>
    </div>

    <div>
      <!-- Date Slider and Manual Input -->
      <div class="date-filter-container mt-4 mb-4 flex flex-col w-full">
        <div class="flex items-center w-full space-x-4">
          <label for="date-range" class="text-m font-bold w-1/5">Filter by Date:</label>
          <div class=" w-1/5">
            <p class="text-sm p-2 text-left">{{ minDate }}</p>
          </div>

          <!-- Date Slider -->
          <input
            id="date-range"
            type="range"
            :min="0"
            :max="daysBetweenMinAndMax"
            v-model="dayOffset"
            :disabled="showAllDates"
            @input="updateDateFromSlider"
            class="w-4/5"
          />

          <div class="w-1/5">
            <p class="text-sm p-2 text-right ">{{ maxDate }}</p>
          </div>
        </div>

        <div class="flex justify-between items-center w-full mt-4 min-[400px]:flex-row flex-col">
          <!-- Display Selected Date -->
          <div class="flex items-center space-x-1 min-[400px]:w-1/3 w-full min-[400px]:justify-end pr-2">
            <p class="text-sm text-center w-full min-[400px]:text-right">Selected:</p>
          </div>

          <!-- Manual Date Input -->
          <div class="min-[400px]:w-1/3 w-full">
            <input
              type="date"
              v-model="selectedDate"
              :disabled="showAllDates"
              @change="updateSliderFromInput"
              class="border  w-full min-[400px]:max-w-[200px] p-2"
              placeholder="YYYY-MM-DD"
            />
          </div>

          <!-- Show All Dates button-->
          <div class="min-[400px]:w-1/3 w-full">
            <button
              @click="showAllDates = !showAllDates"
              class="px-4 py-2 text-white bg-blue-500  shadow-lg hover:bg-blue-600 transition-colors w-full"
            >
              {{ showAllDates ? 'Filter by Date' : 'Show All Dates' }}
            </button>
          </div>
        </div>
      </div>

      <!-- Filter Buttons -->
      <div class="filter-container flex sm:space-x-4 space-x-0">
        <button
          v-for="(isActive, type) in filters"
          :key="type"
          @click="toggleFilter(type)"
          :class="{'active': isActive, 'inactive': !isActive, [`${type.toLowerCase().replace(' ', '-').replace(/\d/g, 'a')}-filter-button`]: true}"
          class="filter-button px-2 py-2  shadow-lg disabled:bg-gray-400 transition-colors w-1/4 text-base"
        >
          <span class="invisible md:visible">{{ type }} </span>
        </button>
        <!-- Reload Button -->
        <button
          @click="reloadMap"
          class="px-4 py-2 text-white bg-red-500  shadow-lg hover:bg-red-600 transition-colors w-1/4"
        >
          Reload Map
        </button>
      </div>
      <p class="text-gray-700 mt-4 mb-4 text-lg leading-relaxed">
        Filter by data type by clicking the filter buttons above
      </p>
    </div>
    <!-- check the selectedDataPoint type and display the appropriate component -->
    <ServiceCase v-if="selectedDataPoint && selectedDataPoint.type === '311 Case'" :data="selectedDataPoint" />
    <Crime v-if="selectedDataPoint && selectedDataPoint.type === 'Crime'" :data="selectedDataPoint" />
    <BuildingPermit v-if="selectedDataPoint && selectedDataPoint.type === 'Building Permit'" :data="selectedDataPoint" />
    <div>
      <!-- AiAssistant Component -->
      <AiAssistant :context="filteredDataPoints" />
      <GenericDataList :totalData="filteredDataPoints" :itemsPerPage="5" />
    </div>
    <!-- Pass filteredDataPoints as context to AiAssistant -->
  </PageTemplate>
</template>

<script setup>
import { ref, computed, onMounted, watch, nextTick, markRaw } from 'vue';
import axios from 'axios';
import PageTemplate from '@/Components/PageTemplate.vue';
import AiAssistant from '@/Components/AiAssistant.vue';
import GenericDataList from '@/Components/GenericDataList.vue';
import AddressSearch from '@/Components/AddressSearch.vue';
import ServiceCase from '@/Components/ServiceCase.vue';
import Crime from '@/Components/Crime.vue';
import BuildingPermit from '@/Components/BuildingPermit.vue';
import SaveLocation from '@/Components/SaveLocation.vue';
import { Head } from '@inertiajs/vue3';
import { usePage } from '@inertiajs/vue3';
import 'leaflet/dist/leaflet.css';
import * as L from 'leaflet';

const filters = ref({});
const allDataPoints = ref([]); // Store all fetched data points here
const dataPoints = ref([]); // Store filtered data points here
const centralLocation = ref({
  latitude: 42.3601,
  longitude: -71.0589,
  address: 'Boston, MA',
});
const centerSelectionActive = ref(false);
const centerSelected = ref(false);
const newCenter = ref(null);
const mapCenter = ref([centralLocation.value.latitude, centralLocation.value.longitude]);
const cancelNewMarker = ref(false);
const selectedDate = ref('');
const minDate = ref('');
const maxDate = ref('');
const dayOffset = ref(0);
const showAllDates = ref(true);
const selectedDataPoint = ref(null);
const isAuthenticated = ref(false);
const isMapInitialized = ref(false);
// default is Boston, MA
const currentMapViewport = ref({ center: [42.3601, -71.0589], zoom: 16 });

// Leaflet map refs and setup
const initialMap = ref(null);
const markerCenter = ref(null);
const newMarker = ref(null);
const markers = ref([]);

const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

// get auth prop
const page = usePage();
isAuthenticated.value = page.props.auth;

// Define the icons for different types of markers
const getDivIcon = (type) => {
  let className = 'default-div-icon'; // Fallback class

  switch (type) {
    case 'Crime':
      className = 'crime-div-icon';
      break;
    case '311 Case':
      className = 'case-div-icon';
      break;
    case 'Building Permit':
      className = 'permit-div-icon';
      break;
    case 'Center':
      className = 'center-div-icon';
      break;
    default:
      break;
  }

  return L.divIcon({
    className,
    html: `<div></div>`, // You can customize this to show more data
    iconSize: null,
    popupAnchor: [0, -15],
  });
};

const fetchData = async () => {
  try {
    const response = await axios.post('/api/map-data', {
      centralLocation: centralLocation.value,
      days: 14, // Default days
    }, {
      headers: {
        'X-CSRF-TOKEN': csrfToken,
      },
    });

    allDataPoints.value = response.data.dataPoints;
    updateDateRange();
    populateFilters();
    applyFilters();
  } catch (error) {
    console.error('Error fetching data:', error);
  }
};

const updateDateRange = () => {
  if (allDataPoints.value.length > 0) {
    const dates = allDataPoints.value.map((point) => new Date(point.date));
    minDate.value = dates.reduce((a, b) => (a < b ? a : b)).toISOString().split('T')[0];
    maxDate.value = dates.reduce((a, b) => (a > b ? a : b)).toISOString().split('T')[0];
    selectedDate.value = minDate.value;

    const minDateObj = new Date(minDate.value);
    const maxDateObj = new Date(maxDate.value);
    dayOffset.value = Math.ceil((maxDateObj - minDateObj) / (1000 * 60 * 60 * 24));
  }
};

const populateFilters = () => {
  filters.value = {};
  allDataPoints.value.forEach((dataPoint) => {
    if (!filters.value[dataPoint.type]) {
      filters.value[dataPoint.type] = true;
    }
  });
};

const toggleFilter = (type) => {
  filters.value[type] = !filters.value[type];
  applyFilters();
};

const toggleCenterSelection = () => {
  centerSelectionActive.value = !centerSelectionActive.value;
  centerSelected.value = false;
  newCenter.value = null;
  cancelNewMarker.value = !centerSelectionActive.value;
};

const setNewCenter = (latlng) => {
  if (centerSelectionActive.value) {
    newCenter.value = latlng;
    centerSelected.value = true;
    centralLocation.value.latitude = latlng.lat;
    centralLocation.value.longitude = latlng.lng;
    mapCenter.value = [latlng.lat, latlng.lng];
    destroyMap();
    fetchData();
    initializeMap();
  }
};

// Apply the filters and update the dataPoints ref
const applyFilters = () => {
  if (showAllDates.value) {
    dataPoints.value = allDataPoints.value.filter((point) => filters.value[point.type]);
  } else {
    const filteredByDate = allDataPoints.value.filter((point) => {
      return new Date(point.date).toISOString().split('T')[0] === selectedDate.value;
    });
    dataPoints.value = filteredByDate.filter((point) => filters.value[point.type]);
  }
  if (initialMap.value) {
    updateMarkers(dataPoints.value);
  }
};

const filteredDataPoints = computed(() => {
  return dataPoints.value;
});

const reloadMap = () => {
  destroyMap();
  initializeMap();
};

const destroyMap = () => {
  if (initialMap.value) {
    //Store the current viewport before destroying the map
    currentMapViewport.value = {
        center: initialMap.value.getCenter(),
        zoom: initialMap.value.getZoom(),
    };
      initialMap.value.off();
    initialMap.value.remove();
    initialMap.value = null;
    isMapInitialized.value = false;
  }
};

const initializeMap = () => {
  nextTick(() => {
    if (initialMap.value) return;

    // Initialize the map
    initialMap.value = markRaw(L.map('map').setView(currentMapViewport.value.center || mapCenter.value || [42.3601, -71.0589], currentMapViewport.value.zoom || 16));

    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '© <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    }).addTo(initialMap.value);

    // Ensure the map has been initialized correctly
    if (!initialMap.value) {
      console.error('Map initialization failed');
      return;
    }

    // Debug: Check if the map has zoom capabilities
    console.log('Map initialized with center:', mapCenter.value);
    console.log('Initial zoom level:', initialMap.value.getZoom());

    // Update CSS variable on zoomend
    const updateZoomVariables = () => {
      if (!initialMap.value) {
        console.error('Map not defined during zoomend');
        return;
      }

      const zoom = initialMap.value.getZoom();

      // Icon size calculation
      const minSize = 2; // Icon size at minZoom
      const maxSize = 50; // Icon size at maxZoom
      const minZoom = 10;
      const maxZoom = 19;

      // Linear interpolation for icon size
      const newSize = minSize + (maxSize - minSize) * (zoom - minZoom) / (maxZoom - minZoom);

      // Debugging outputs
      console.log('Zoom level:', zoom);
      console.log('Calculated icon size:', newSize);

      // Update CSS variable
      document.documentElement.style.setProperty('--icon-size', `${newSize}px`);
    };

    // Attach zoomend event listener
    initialMap.value.on('zoomend', updateZoomVariables);

    // Debug: Ensure the listener is attached
    console.log('Zoomend listener attached');

    // Initialize CSS variable with the current zoom level
    updateZoomVariables();

    // Add other map-related listeners and markers
    initialMap.value.on('click', (e) => {
      if (centerSelectionActive.value) {
        setNewCenter(e.latlng);

        if (newMarker.value) {
          initialMap.value.removeLayer(newMarker.value); // Remove old marker
        }

        newMarker.value = markRaw(
          L.marker([e.latlng.lat, e.latlng.lng], {
            icon: getDivIcon('Center'),
          })
        ).addTo(initialMap.value);
      }
    });

    markerCenter.value = markRaw(
      L.marker(mapCenter.value, {
        icon: getDivIcon('Center'),
      })
    ).addTo(initialMap.value);

    isMapInitialized.value = true;
    if (dataPoints.value) {
      updateMarkers(dataPoints.value);
    }
  });
};

// Leaflet Map Functionality
onMounted(() => {
  initializeMap();
  fetchData();
});

// Update the markers for dataPoints and add the new center
const updateMarkers = (dataPoints) => {
  if (!initialMap.value) return;

  // Clear existing markers
  markers.value.forEach((marker) => initialMap.value.removeLayer(marker));
  markers.value = []; // Reset the markers array

  // Add new markers with DivIcons
  dataPoints.forEach((dataPoint) => {
    if (dataPoint.latitude && dataPoint.longitude) {
      //display date in popup like Nov 1, 2021 12:00:00 AM, and then display more details below
      //get date from dataPoint.date and convert to string
      const popupContentStart = `
            <div><strong>${new Date(dataPoint.date).toLocaleString()}</strong>
        `;

      // Add more details to the popup depending on dataPoint.type
      // for Crime, 311 Case, and Building Permit
      // crime - info.offense_description
      // case - info.case_title
      // permit - info.worktype

      const popupContent = `
          ${popupContentStart}
          ${dataPoint.type === 'Crime' ? dataPoint.info.offense_description : ''}
          ${dataPoint.type === '311 Case' ? dataPoint.info.case_title : ''}
          ${dataPoint.type === 'Building Permit' ? dataPoint.info.description : ''}
          </div>
        `;

      const marker = markRaw(
        L.marker([dataPoint.latitude, dataPoint.longitude], {
          icon: getDivIcon(dataPoint.type),
        })
      );

      marker.on('click', () => {
        displayDataPoint(dataPoint);
      });

      marker.bindPopup(popupContent).openPopup();

      marker.addTo(initialMap.value);

      // Store marker reference in array
      markers.value.push(marker);
    }
  });
};

// Watch for changes in the center and update the map center and center marker
watch(
  () => mapCenter.value,
  (newCenter) => {
    if (initialMap.value) {
      initialMap.value.setView(newCenter);
    }
  }
);

watch(
  () => centralLocation.value,
  (centralLocation) => {
    // Remove old center marker
    if (markerCenter.value && initialMap.value) {
      initialMap.value.removeLayer(markerCenter.value);
    }

    // Add new center marker
    if (initialMap.value) {
        markerCenter.value = L.marker([centralLocation.latitude, centralLocation.longitude], {
        icon: getDivIcon('Center'),
      }).addTo(initialMap.value);
    }
  },
  { deep: true }
);

watch(() => cancelNewMarker.value, (cancel) => {
  console.log('cancel', cancel);
  if (cancel && newMarker.value && initialMap.value) {
    initialMap.value.removeLayer(newMarker.value);
  }
});

// Date Handling

const daysBetweenMinAndMax = computed(() => {
  if (!minDate.value || !maxDate.value) {
    return 0;
  }
  const minDateObj = new Date(minDate.value);
  const maxDateObj = new Date(maxDate.value);
  return Math.ceil((maxDateObj - minDateObj) / (1000 * 60 * 60 * 24));
});

const updateDateFromSlider = () => {
  if (!minDate.value) return;
  const minDateObj = new Date(minDate.value);
  const newDate = new Date(minDateObj.getTime() + dayOffset.value * (1000 * 60 * 60 * 24));
  selectedDate.value = newDate.toISOString().split('T')[0];
  applyFilters();
};

const updateSliderFromInput = () => {
  if (!minDate.value || !selectedDate.value) return;
  const minDateObj = new Date(minDate.value);
  const selectedDateObj = new Date(selectedDate.value);
  dayOffset.value = Math.round((selectedDateObj - minDateObj) / (1000 * 60 * 60 * 24));
  applyFilters();
};

const updateCenterCoordinates = (coordinates) => {
    centralLocation.value.latitude = coordinates.lat;
    centralLocation.value.longitude = coordinates.lng;
    centralLocation.value.address = coordinates.address;
    mapCenter.value = [coordinates.lat, coordinates.lng];
    destroyMap();
    fetchData();
    initializeMap();
};

const submitNewCenter = () => {
  fetchData();
};

const displayDataPoint = (dataPoint) => {
  selectedDataPoint.value = dataPoint;
};

const handleLoadLocation = (location) => {
    centralLocation.value.latitude = location.latitude;
    centralLocation.value.longitude = location.longitude;
    centralLocation.value.address = location.address;
    mapCenter.value = [location.latitude, location.longitude];
    destroyMap();
    fetchData();
    initializeMap();
  console.log('location loaded: ', location);
};
</script>

<style scoped>
#map {
  height: 70vh;
}

.filter-container {
  display: flex;
  margin-bottom: 15px;
}

.filter-button {
  border: 1px solid transparent;
}

.center-filter-button {
  display: none;
}

.filter-button.inactive {
  background-color: #f0f0f0;
  color: #333;
}

.filter-button:hover {
  border: 1px solid black;
}

.center-control {
  margin-bottom: 15px;
  display: flex;
  gap: 10px;
}

.center-form {
  margin-bottom: 15px;
}
</style>