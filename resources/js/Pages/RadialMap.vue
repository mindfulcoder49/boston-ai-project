<template>
  <PageTemplate>
    <Head>
      <title>Home</title>
    </Head>

    <div>
    <h2>Basic Plan - $5/month</h2>
    <p>Get access to all basic features.</p>
    <!-- Use Inertia Link to trigger the server-side redirect to Stripe 
    <Link :href="route('subscribe.checkout')" class="btn btn-primary" method="get">
      Subscribe
    </Link> -->

    <button @click="goToRoute(route('subscribe.checkout'))" class="px-4 py-2 text-white bg-blue-500  shadow-lg hover:bg-blue-600 transition-colors m-auto">
      Subscribe
    </button>

    <p>Already a subscriber? 
      <button @click="goToRoute(route('billing'))" class="px-4 py-2 text-white bg-blue-500  shadow-lg hover:bg-blue-600 transition-colors m-auto">
        Go to Billing Portal
      </button>
    </p>

  </div>

    <div class="before-map">
    <!-- Page Title -->
    <h1 class="text-2xl font-bold text-gray-800 text-center my-4">{{ translations.LabelsByLanguageCode[getSingleLanguageCode]?.pageTitle }}</h1>

    <!--Language buttons navbar to include or remove lanagueg codes from the array          $languageCodes = [
            'es-MX', 'zh-CN', 'ht-HT', 'vi-VN', 'pt-BR',
        ];
        -->
    <div class="flex flex-wrap justify-center">
      <button
        v-for="code in Object.keys(languageButtonLabels)"
        :key="code"
        @click="toggleLanguageCode(code)"
        class="px-4 py-2 border-white border"
        :class="{
          'bg-blue-500 text-white': language_codes.includes(code),
          'bg-gray-200 hover:bg-gray-300': !language_codes.includes(code),
        }"
      >
        {{ language_codes.includes(code) ? languageButtonLabels[code].deselect : languageButtonLabels[code].select }}
      </button>
    </div>


    <AddressSearch @address-selected="updateCenterCoordinates" :language_codes="language_codes" />

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
        {{ centerSelectionActive ? translations.localizationLabelsByLanguageCode[getSingleLanguageCode].cancelText : translations.localizationLabelsByLanguageCode[getSingleLanguageCode].chooseNewCenter }}
      </button>
    </div>
  </div>

    <div class="m-5 page-div">

      

    <div class="boston-map" :class="{ 'map-loading': mapLoading }">
      <div id="map" class="h-full"></div>
    </div>

          <!-- Filter Buttons -->
  <div class="map-controls">
    <div class="filter-container flex justify-center">
        <div
          v-for="(isActive, type) in filters"
          :key="type"
          @click="toggleFilter(type)"
          :class="{'active': isActive, 'inactive': !isActive, [`${type.toLowerCase().replace(/\s/g, '-').replace(/\d/g, 'a')}-filter-button`]: true,
          //set the width based on the number of filters
          'w-1/12': Object.keys(filters).length > 6,
          'w-1/6': Object.keys(filters).length === 6,
          'w-1/5': Object.keys(filters).length === 5,
          'w-1/4': Object.keys(filters).length === 4,
          'w-1/3': Object.keys(filters).length === 3,
          'w-1/2': Object.keys(filters).length === 2,
          'w-full': Object.keys(filters).length === 1}"

          class="filter-button shadow-lg disabled:bg-gray-400 transition-colors text-base"

        >
          <div class="invisible filter-button-text lg:visible">{{ getDataTypeTranslation(type) }}</div>
    </div>
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
          {{ new Date(date).toLocaleDateString( getSingleLanguageCode
            , { weekday: 'short', month: 'short', day: 'numeric' }) }}
        </button>
        <button
          @click="clearDateSelections"
          class="px-4 py-2 bg-blue-500 text-white hover:bg-blue-400 transition-colors w-1/2 show-all-dates"
        >
          {{ translations.localizationLabelsByLanguageCode[getSingleLanguageCode].allDatesButton }}
        </button>
      </div>
          <!-- check the selectedDataPoint type and display the appropriate component -->

    <SaveLocation :location="centralLocation" :language_codes="language_codes"  @load-location="handleLoadLocation"  />

        <ImageCarousel :dataPoints="dataPoints" @on-image-click="handleImageClick"  />

    </div>


  </div>
    


     <div class="case-details">

    <ServiceCase v-if="selectedDataPoint && selectedDataPoint.alcivartech_type === '311 Case'" :data="selectedDataPoint" :language_codes="language_codes" />
    <Crime v-if="selectedDataPoint && selectedDataPoint.alcivartech_type === 'Crime'" :data="selectedDataPoint" :language_codes="language_codes" />
    <BuildingPermit v-if="selectedDataPoint && selectedDataPoint.alcivartech_type === 'Building Permit'" :data="selectedDataPoint" :language_codes="language_codes" />
    <PropertyViolation v-if="selectedDataPoint && selectedDataPoint.alcivartech_type === 'Property Violation'" :data="selectedDataPoint" :language_codes="language_codes" />
    <OffHours v-if="selectedDataPoint && selectedDataPoint.alcivartech_type === 'Construction Off Hour'" :data="selectedDataPoint" :language_codes="language_codes" />
    </div>

      <!-- AiAssistant Component -->
      <AiAssistant :context="filteredDataPoints" :language_codes="language_codes"></AiAssistant>
      <GenericDataList :totalData="filteredDataPoints" :itemsPerPage="8" @handle-goto-marker="handleListClick" :language_codes="language_codes" />

    <!-- Pass filteredDataPoints as context to AiAssistant -->
    </div>
  </PageTemplate>
</template>

<script setup>
import { ref, computed, onMounted, watch, nextTick, markRaw, inject } from 'vue';
import axios from 'axios';
import PageTemplate from '@/Components/PageTemplate.vue';
import AiAssistant from '@/Components/AiAssistant.vue';
import GenericDataList from '@/Components/GenericDataList.vue';
import AddressSearch from '@/Components/AddressSearch.vue';
import ServiceCase from '@/Components/ServiceCase.vue';
import Crime from '@/Components/Crime.vue';
import BuildingPermit from '@/Components/BuildingPermit.vue';
import SaveLocation from '@/Components/SaveLocation.vue';
import { Head, Link } from '@inertiajs/vue3';
import { usePage } from '@inertiajs/vue3';
import 'leaflet/dist/leaflet.css';
import * as L from 'leaflet';
import ImageCarousel from '@/Components/ImageCarousel.vue';
import { data } from 'autoprefixer';
import PropertyViolation from '@/Components/PropertyViolation.vue';
import OffHours from '@/Components/OffHours.vue';
import { map } from 'leaflet';

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
const mapLoading = ref(false);

const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

// get auth prop
const page = usePage();
const isAuthenticated = page.props.auth.user;

const language_codes = ref(['en-US']);

const translations = inject('translations');

const goToRoute = ( route ) => {
  window.location.href = route;
}

const LabelsByLanguageCode = {
  'en-US': {
    pageTitle: 'Boston City Govt Activity Map',
  },
  'es-MX': {
    pageTitle: 'Mapa de Actividades del Gobierno de la Ciudad de Boston',
  },
  'zh-CN': {
    pageTitle: '波士顿市政府活动地图',
  },
  'ht-HT': {
    pageTitle: 'Kat Aktivite Gouvènman Vil Boston',
  },
  'vi-VN': {
    pageTitle: 'Bản đồ Hoạt động Chính phủ Thành phố Boston',
  },
  'pt-BR': {
    pageTitle: 'Mapa de Atividades do Governo da Cidade de Boston',
  },
};

const getSingleLanguageCode = computed(() => {
  return language_codes.value[0];
});


const addLanguageCode = (code) => {
  language_codes.value.push(code);
  fetchData();
};

const removeLanguageCode = (code) => {
  const index = language_codes.value.indexOf(code);
  if (index > -1) {
    language_codes.value.splice(index, 1);
  }
  fetchData();
};

const toggleLanguageCode = (code) => {
  if (language_codes.value.includes(code)) {
    //removeLanguageCode(code);
  } else {
    //make it the only language code
    language_codes.value = [code];
    //only fetch data if the code is english or spanish
    if (code === 'en-US') {
      //fetchData();
    }
  }
};

//make an array with lanague codes and their corresponding labels which shoudl be int he language of the user targeted, and have an add and remove label for each language code
const languageButtonLabels = {
  'en-US': {
    select: '✓ English',
    deselect: '✕ English',
  },
  'es-MX': {
    select: '✓ Español',
    deselect: '✕ Español',
  },
  'zh-CN': {
    select: '✓ 中文',
    deselect: '✕ 中文',
  },
  'ht-HT': {
    select: '✓ Kreyòl Ayisyen',
    deselect: '✕ Kreyòl Ayisyen',
  },
  'vi-VN': {
    select: '✓ Tiếng Việt',
    deselect: '✕ Tiếng Việt',
  },
  'pt-BR': {
    select: '✓ Português',
    deselect: '✕ Português',
  },
};

// Define the icons for different types of markers
const getDivIcon = (dataPoint) => {
  let className = 'default-div-icon'; // Fallback class
  let type = dataPoint.alcivartech_type;
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
    case 'Construction Off Hour':
      className = 'construction-off-hour-div-icon';
      break;
    default:
      break;
  }

  if (type === "311 Case") {
    // Add classes and set the background image if photos are present
    if (dataPoint?.submitted_photo) {
      //get the first valid URL, there may be multiple separated by " | "
      const photoURL = dataPoint.submitted_photo.split(' | ')[0];

      className += ' submitted-photo';
      backgroundImage = `background-image: url(${photoURL});`;
    }
    if (dataPoint?.closed_photo) {
      const photoURL = dataPoint.closed_photo.split(' | ')[0];
      className += ' closed-photo';
      backgroundImage = `background-image: url(${photoURL});`;
    }
    if (!dataPoint?.submitted_photo && !dataPoint?.closed_photo) {
      className += ' no-photo';
    }
  }

  className += ' id'+ dataPoint?.id; // Add the base class

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
    if (marker.options.icon.options.className.includes('id'+data.id)) {
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
    mapLoading.value = true;
    // set language_codes here to en-US for all languages except for es-MX
    // actually we are hardcoding the language codes to en-US for now
    //const requestLanguageCodes = language_codes.value.map((code) => (code === 'es-MX' ? code : 'en-US'));

    const requestLanguageCodes = ['en-US'];
    const response = await axios.post('/api/map-data', {
      centralLocation: centralLocation.value,
      language_codes: requestLanguageCodes,
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
    setTimeout(() => {
      initialMap.value.invalidateSize();
    }, 100);
    mapLoading.value = false;


  } catch (error) {
    console.error('Error fetching data:', error);
    mapLoading.value = false;
    //if it's a 419 error reload the page
    if (error.response.status === 419) {
      window.location.reload();
    }
  }
};

const updateDateRange = () => {
  const dates = allDataPoints.value.map((point) => new Date(point.alcivartech_date));
  minDate.value = new Date(Math.min(...dates)).toISOString().split('T')[0];
  maxDate.value = new Date(Math.max(...dates)).toISOString().split('T')[0];

};

const populateFilters = () => {
  //filters.value = {};
  allDataPoints.value.forEach((dataPoint) => {
    if (filters.value[dataPoint.alcivartech_type] === undefined) {
      filters.value[dataPoint.alcivartech_type] = true;
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
      dataPoints.value = allDataPoints.value.filter(point => filters.value[point.alcivartech_type]);
  } else {
    const filteredByDate = allDataPoints.value.filter(point => {
      // Convert point.date to YYYY-MM-DD format
       const pointDate = new Date(point.alcivartech_date).toISOString().split('T')[0]

        //check if the current dataPoint is included in the list of selectedDates
      return selectedDates.value.includes(pointDate);
    });
    // Filter by type in addition to the dates selected
    dataPoints.value = filteredByDate.filter(point => filters.value[point.alcivartech_type]);
  }
  
    if (initialMap.value) {
      updateMarkers(dataPoints.value);
    }
};

const dataTypeMapByLanguageCode = {
  'en-US': {
    'Crime': 'Crime',
    '311 Case': '311 Case',
    'Building Permit': 'Building Permit',
    'Property Violation': 'Property Violation',
    'Construction Off Hour': 'Constr Off Hour',
  },
  'es-MX': {
    'Crime': 'Crimen',
    '311 Case': 'Caso 311',
    'Building Permit': 'Permiso de Constr',
    'Property Violation': 'Violación de Prop',
    'Construction Off Hour': 'Constr Fuera'
  },
  'zh-CN': {
    'Crime': '犯罪',
    '311 Case': '311案例',
    'Building Permit': '建筑许可',
    'Property Violation': '财产违规',
    'Construction Off Hour': '非工作时间施工',
  },
  'ht-HT': {
    'Crime': 'Krim',
    '311 Case': 'Ka 311',
    'Building Permit': 'Pèmi Bati',
    'Property Violation': 'Vyolasyon Pwopriyete',
    'Construction Off Hour': 'Konstr Moun Ki Pa Travay',
  },
  'vi-VN': {
    'Crime': 'Tội phạm',
    '311 Case': 'Trường hợp 311',
    'Building Permit': 'Giấy phép Xây dựng',
    'Property Violation': 'Vi phạm Tài sản',
    'Construction Off Hour': 'Xây dựng Ngoài giờ',
  },
  'pt-BR': {
    'Crime': 'Crime',
    '311 Case': 'Caso 311',
    'Building Permit': 'Licença de Constr',
    'Property Violation': 'Violação de Prop',
    'Construction Off Hour': 'Constr Fora'
  },
}; 

const getDataTypeTranslation = (type) => {
  return dataTypeMapByLanguageCode[language_codes.value[0]][type];
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

const initializeMap = ( center = null) => {
  nextTick(() => {
    if (initialMap.value) return;

    // Initialize the map
    if ( center == null ) {
    initialMap.value = markRaw(L.map('map').setView(currentMapViewport.value.center || mapCenter.value || [42.3601, -71.0589], currentMapViewport.value.zoom || 16));
    } else {
      initialMap.value = markRaw(L.map('map').setView(center, 16));
    }

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
      initialMap.value.invalidateSize();

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
    console.log('dataPoint', dataPoint);
    if (dataPoint.latitude && dataPoint.longitude) {
      //display date in popup like Nov 1, 2021 12:00:00 AM, and then display more details below
      //get date from dataPoint.alcivartech_date and convert to string
      const popupContentStart = `
            <div><strong>${new Date(dataPoint.alcivartech_date).toLocaleString()}</strong>
        `;

      // Add more details to the popup depending on dataPoint.alcivartech_type
      // for Crime, 311 Case, and Building Permit
      // crime - info.offense_description
      // case - info.case_title
      // permit - info.worktype

      const popupContent = `
          ${popupContentStart}
          ${dataPoint.alcivartech_type === 'Crime' ? dataPoint.offense_description : ''}
          ${dataPoint.alcivartech_type === '311 Case' ? dataPoint.case_title : ''}
          ${dataPoint.alcivartech_type === 'Building Permit' ? dataPoint.description : ''}
          ${dataPoint.alcivartech_type === 'Property Violation' ? dataPoint.description : ''}
          ${dataPoint.alcivartech_type === 'Construction Off Hour' ? dataPoint.address : ''}
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
    initializeMap( [coordinates.lat, coordinates.lng] );
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
    initializeMap( [location.latitude, location.longitude] );
  console.log('location loaded: ', location);
};

const localizationLabelsByLanguageCode = {
  'en-US': {
    allDatesButton: 'All Dates',
    chooseNewCenter: 'Choose New Center',
    cancelText: 'Cancel',
  },
  'es-MX': {
    allDatesButton: 'Todas las fechas',
    chooseNewCenter: 'Elegir nuevo centro',
    cancelText: 'Cancelar',
  },
  'zh-CN': {
    allDatesButton: '所有日期',
    chooseNewCenter: '选择新中心',
    cancelText: '取消',
  },
  'ht-HT': {
    allDatesButton: 'Tout dat',
    chooseNewCenter: 'Chwazi Nouvo Sant',
    cancelText: 'Anile',
  },
  'vi-VN': {
    allDatesButton: 'Tất cả các ngày',
    chooseNewCenter: 'Chọn Trung tâm Mới',
    cancelText: 'Hủy',
  },
  'pt-BR': {
    allDatesButton: 'Todas as datas',
    chooseNewCenter: 'Escolher Novo Centro',
    cancelText: 'Cancelar',
  },
};

</script>

<style scoped>
#map {
  height: 100%;
}

.boston-map {
  height: auto; /* Let the container grow with its content */
  overflow: hidden;
}

.map-loading {
  filter: blur(2px);
}

.filter-button-text {
  width:100%;
  height: 100%;
  font-weight: 800;
  font-size: 1.5rem;
  align-content: center;
  border-radius: 50%;
}

/* on screens bigger than 768 px, make the map 600px wide, and flow everything else to the right */
@media (min-width: 768px) {
  #map {
  height: 100%;
}

.boston-map {
  height: auto; /* Let the container grow with its content */
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

  .filter-container div {
    /* put text to right of icon */
    background-position: center;
    text-align: center;
    padding: 0.5rem;
    
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

@media (max-width: 768px) {
  #map {
  height: 100%;
  }

  .boston-map {
    height: 70vh;
  }
}
</style>