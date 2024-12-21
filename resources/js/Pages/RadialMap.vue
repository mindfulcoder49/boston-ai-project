<template>
  <PageTemplate>
    <Head>
      <title>Home</title>
    </Head>

    <div class="before-map">
    <!-- Page Title -->
    <h1 class="text-2xl font-bold text-gray-800 text-center my-4">Boston City Govt Activity</h1>

    <AddressSearch @address-selected="updateCenterCoordinates" />

    <form @submit.prevent="submitNewCenter" class="">
      <!-- Selected Center Coordinates display -->
      <div v-if="newCenter" class="p-4 bg-gray-100  shadow text-center">
        <p class="font-bold text-gray-800">Selected Center Coordinates:</p>
        <p class="text-gray-700">{{ newCenter.lat }}, {{ newCenter.lng }}</p>
      </div>
      <!--
      <div v-else class="p-4 bg-gray-100  shadow text-center">
        <p class="font-bold text-gray-800">Current Center Coordinates:</p>
        <p class="text-gray-700">{{ centralLocation.latitude }}, {{ centralLocation.longitude }}</p>
      </div>

      -->


    </form>

    <!-- Button container -->
    <div class="flex space-x-4">
    <!-- Choose New Center button -->
    <button
        type="button"
        @click="toggleCenterSelection"
        class="px-4 py-2 text-white bg-blue-500  shadow-lg disabled:bg-gray-400 hover:bg-blue-600 transition-colors w-1/2 m-auto"
      >
        {{ centerSelectionActive ? 'Cancel' : 'Choose New Center' }}
      </button>
    </div>
  </div>

    <div class="m-5 page-div">

      

    <div class="boston-map">
      <div id="map" class="h-[70vh]"></div>
    </div>

          <!-- Filter Buttons -->
  <div class="map-controls">
    <div class="filter-container flex space-x-0 justify-center">
        <button
          v-for="(isActive, type) in filters"
          :key="type"
          @click="toggleFilter(type)"
          :class="{'active': isActive, 'inactive': !isActive, [`${type.toLowerCase().replace(' ', '-').replace(/\d/g, 'a')}-filter-button`]: true,
          //set the width based on the number of filters
          'w-1/12': Object.keys(filters).length > 6,
          'w-1/6': Object.keys(filters).length === 6,
          'w-1/5': Object.keys(filters).length === 5,
          'w-1/4': Object.keys(filters).length === 4,
          'w-1/3': Object.keys(filters).length === 3,
          'w-1/2': Object.keys(filters).length === 2,
          'w-full': Object.keys(filters).length === 1}"

          class="filter-button px-2 py-2 shadow-lg disabled:bg-gray-400 transition-colors text-base"

        >
          <span class="invisible md:visible">{{ type }} </span>
        </button>
        <!-- Reload Button 
        <button
          @click="reloadMap"
          class="px-4 py-2 text-white bg-red-500  shadow-lg hover:bg-red-600 transition-colors w-1/4"
        >
          Reload Map
        </button> -->
    </div>
 
    <div class="date-filter-container flex flex-col w-full">
      <div class="flex flex-wrap justify-between">
        <button
          v-for="(date, index) in getDates()"
          :key="index"
          @click="toggleDateSelection(date)"
          :class="{
            'bg-blue-500 text-white': selectedDates.includes(date),
            'bg-gray-200 hover:bg-gray-300': !selectedDates.includes(date),
          }"
          class="px-4 py-2 shadow transition-colors w-1/5"
        >
          {{ new Date(date).toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' }) }}
        </button>
        <button
          @click="clearDateSelections"
          class="px-4 py-2 bg-blue-500 text-white hover:bg-blue-400 transition-colors w-1/2 show-all-dates"
        >
          All Dates
        </button>
      </div>
          <!-- check the selectedDataPoint type and display the appropriate component -->

    <SaveLocation
          :location="centralLocation"
          @load-location="handleLoadLocation"
        />

        <ImageCarousel :dataPoints="dataPoints" @on-image-click="handleImageClick"  />

    </div>


  </div>
    


     <div class="case-details">

    <ServiceCase v-if="selectedDataPoint && selectedDataPoint.type === '311 Case'" :data="selectedDataPoint" />
    <Crime v-if="selectedDataPoint && selectedDataPoint.type === 'Crime'" :data="selectedDataPoint" />
    <BuildingPermit v-if="selectedDataPoint && selectedDataPoint.type === 'Building Permit'" :data="selectedDataPoint" />
    <PropertyViolation v-if="selectedDataPoint && selectedDataPoint.type === 'Property Violation'" :data="selectedDataPoint" />
    </div>

      <!-- AiAssistant Component -->
      <AiAssistant :context="filteredDataPoints" />
      <GenericDataList :totalData="filteredDataPoints" :itemsPerPage="8" @handle-goto-marker="handleListClick" />

    <!-- Pass filteredDataPoints as context to AiAssistant -->
    </div>
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
import ImageCarousel from '@/Components/ImageCarousel.vue';
import { data } from 'autoprefixer';
import PropertyViolation from '@/Components/PropertyViolation.vue';

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
const selectedDates = ref([]); // Stores selected dates
const minDate = ref('');
const maxDate = ref('');
const dayOffset = ref(0);
const showAllDates = ref(true);
const selectedDataPoint = ref(null);
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
const isAuthenticated = page.props.auth.user;

// Define the icons for different types of markers
const getDivIcon = (dataPoint) => {
  let className = 'default-div-icon'; // Fallback class
  let type = dataPoint.type;
  let backgroundImage = '';

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
    case 'Property Violation':
      className = 'property-violation-div-icon';
      break;
    default:
      break;
  }

  if (type === "311 Case") {
    // Add classes and set the background image if photos are present
    if (dataPoint.info?.submitted_photo) {
      //get the first valid URL, there may be multiple separated by " | "
      const photoURL = dataPoint.info.submitted_photo.split(' | ')[0];

      className += ' submitted-photo';
      backgroundImage = `background-image: url(${photoURL});`;
    }
    if (dataPoint.info?.closed_photo) {
      const photoURL = dataPoint.info.closed_photo.split(' | ')[0];
      className += ' closed-photo';
      backgroundImage = `background-image: url(${photoURL});`;
    }
    if (!dataPoint.info?.submitted_photo && !dataPoint.info?.closed_photo) {
      className += ' no-photo';
    }
  }

  className += ' id'+ dataPoint.info?.id; // Add the base class

  return L.divIcon({
    className,
    html: `<div style="${backgroundImage}"></div>`, // Apply inline background-image
    iconSize: null,
    popupAnchor: [15, 0],
  });
};



const getCenterIcon = (type) => {
  let className = 'center-div-icon'; // Fallback class

  return L.divIcon({
    className,
    html: `<div></div>`, // You can customize this to show more data
    iconSize: null,
    popupAnchor: [0, -15],
  });
};


const clearDateSelections = () => {
  selectedDates.value = [];
  applyFilters();
};


const handleImageClick = (data) => {
  selectedDataPoint.value = data;
  // find the marker with the classname that matches id + data.info.id and open the popup
  markers.value.forEach((marker) => {
    console.log('marker', marker);
    if (marker.options.icon.options.className.includes('id'+data.info.id)) {
      marker.openPopup();
    } 
  });

  console.log('Selected Data Point:', data);
};

const handleListClick = (data) => {
  selectedDataPoint.value = data;
  // find the marker with the classname that matches id + data.info.id and open the popup
  markers.value.forEach((marker) => {
    console.log('marker', marker);
    if (marker.options.icon.options.className.includes('id'+data.info.id)) {
      marker.openPopup();
      //scroll to marker using class name
      document.querySelector('.leaflet-popup-content-wrapper').scrollIntoView({ behavior: 'smooth', block: 'center' });
    } 
  });

  console.log('Selected Data Point:', data);
};

//function to get the dates included in the dataPoints to create a button for each day that can be used to filter the dataPoints
const getDates = () => {
  //use minDate and maxDate to create an array of dates
  const dates = [];
  const currentDate = new Date(minDate.value);
  const endDate = new Date(maxDate.value);
  while (currentDate <= endDate) {
    dates.push(new Date(currentDate).toISOString().split('T')[0]);
    currentDate.setDate(currentDate.getDate() + 1);
  }
  return dates;
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
    // pick the most recent data point to assign to selectedDataPoint 
    if (allDataPoints.value.length > 0) {
      selectedDataPoint.value = allDataPoints.value[0];
    }


  } catch (error) {
    console.error('Error fetching data:', error);
  }
};

const updateDateRange = () => {
  const dates = allDataPoints.value.map((point) => new Date(point.date));
  minDate.value = new Date(Math.min(...dates)).toISOString().split('T')[0];
  maxDate.value = new Date(Math.max(...dates)).toISOString().split('T')[0];

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

const toggleDateSelection = (date) => {
  const index = selectedDates.value.indexOf(date);
  if (index > -1) {
    // If the date is already selected, remove it
    selectedDates.value.splice(index, 1);
  } else {
    // Otherwise, add it
    selectedDates.value.push(date);
  }
  applyFilters(); // Update the filtered data
};


const applyFilters = () => {
  // Check if allDataPoints is loaded
  if (allDataPoints.value.length === 0) {
    return;
  }

  // If no dates are selected, show all data points based on type filter
  if (selectedDates.value.length === 0) {
      dataPoints.value = allDataPoints.value.filter(point => filters.value[point.type]);
  } else {
    const filteredByDate = allDataPoints.value.filter(point => {
      // Convert point.date to YYYY-MM-DD format
       const pointDate = new Date(point.date).toISOString().split('T')[0]

        //check if the current dataPoint is included in the list of selectedDates
      return selectedDates.value.includes(pointDate);
    });
    // Filter by type in addition to the dates selected
    dataPoints.value = filteredByDate.filter(point => filters.value[point.type]);
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
            icon: getCenterIcon('Center'),
          })
        ).addTo(initialMap.value);
      }
    });

    markerCenter.value = markRaw(
      L.marker(mapCenter.value, {
        icon: getCenterIcon('Center'),
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
          ${dataPoint.type === 'Property Violation' ? dataPoint.info.description : ''}
          </div>
        `;

      const marker = markRaw(
        L.marker([dataPoint.latitude, dataPoint.longitude], {
          icon: getDivIcon(dataPoint),
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
        icon: getCenterIcon('Center'),
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

.boston-map {
  height: auto; /* Let the container grow with its content */
  max-height: 70vh;
  overflow: hidden;
}

/* on screens bigger than 768 px, make the map 600px wide, and flow everything else to the right */
@media (min-width: 768px) {
  #map {
  height: 100vh;
}

.boston-map {
  height: auto; /* Let the container grow with its content */
  max-height: 100vh;
  overflow: hidden;
}

  .boston-map {
    width: 50%;
  }
  .page-div {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
  }

  .filter-container button {
    /* put text to right of icon */
    background-position: 10px center;
    padding-left: 60px;
  }
  
  .date-filter-container {
    width:auto;
  }

  .date-filter-container button {
    width: 33%;
    font-size: 0.8rem;
  }

  .map-controls {
    width: 50%;
  }

  .show-all-dates {
    width:auto;
  }

  .case-details {
    width: 50%;
  }
}
</style>