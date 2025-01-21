<template>
  <div class="boston-map">
    <div id="map" class="h-full mb-6 rounded-lg shadow-lg"></div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch, nextTick, markRaw } from 'vue';
import 'leaflet/dist/leaflet.css';
import * as L from 'leaflet';

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

const props = defineProps({
  dataPoints: {
    type: Array,
    required: true,
    default: () => [],
  },
  center: {  
    type: Array, //used for the centerview of the map
    required: true,
  },
  centralLocation: { 
    type: Object, //used for the central point so it can be set independent of the centerview
    required: true,
  },
  centerSelectionActive: {
    type: Boolean, // A prop to determine if "Choose New Center" is active
    required: true,
  },
  cancelNewMarker: {
    type: Boolean, // A prop to signal the cancellation of the new marker
    required: true,
    default: false,
  },
});

const emit = defineEmits(['map-click', 'marker-click']);
const initialMap = ref(null);
const markerCenter = ref(null); // Store the center marker
const newMarker = ref(null); // Ref for dynamically added marker

onMounted(() => {
  nextTick(() => {
    // Initialize the map
    initialMap.value = markRaw(L.map('map').setView(props.center || [42.3601, -71.0589], 16));

    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    }).addTo(initialMap.value);

    // Ensure the map has been initialized correctly
    if (!initialMap.value) {
      console.error('Map initialization failed');
      return;
    }

    // Debug: Check if the map has zoom capabilities
    console.log('Map initialized with center:', props.center);
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
      if (props.centerSelectionActive) {
        emit('map-click', e.latlng);

        if (newMarker.value) {
          initialMap.value.removeLayer(newMarker.value); // Remove old marker
        }

        newMarker.value = markRaw(L.marker([e.latlng.lat, e.latlng.lng], {
          icon: getDivIcon('Center'),
        })).addTo(initialMap.value);
      }
    });

    markerCenter.value = markRaw(L.marker(props.center, {
      icon: getDivIcon('Center'),
    })).addTo(initialMap.value);

    updateMarkers(props.dataPoints);
  });
});


// Update the markers for dataPoints and add the new center
const markers = ref([]);

const updateMarkers = (dataPoints) => {
  if (!initialMap.value) return;

  // Clear existing markers
  markers.value.forEach(marker => initialMap.value.removeLayer(marker));
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

      const marker = markRaw(L.marker([dataPoint.latitude, dataPoint.longitude], {
        icon: getDivIcon(dataPoint.type),
      }));


      

      marker.on('click', () => {
        emit('marker-click', dataPoint);
      });

      marker.bindPopup(popupContent).openPopup();

      marker.addTo(initialMap.value);

      // Store marker reference in array
      markers.value.push(marker);
    }
  });
};

// Watch for changes in the dataPoints and update markers accordingly
watch(() => props.dataPoints, updateMarkers, { deep: true });

// Watch for changes in the center and update the map center and center marker
watch(() => props.center, (newCenter) => {
  if (initialMap.value) {
    initialMap.value.setView(newCenter);


  }
});

watch(() => props.centralLocation, (centralLocation) => {
    // Remove old center marker
    if (markerCenter.value) {
      initialMap.value.removeLayer(markerCenter.value);
    }

    // Add new center marker
    markerCenter.value = L.marker([centralLocation.latitude, centralLocation.longitude], {
      icon: getDivIcon('Center'),
    }).addTo(initialMap.value);
});


watch(() => props.cancelNewMarker, (cancel) => {
  console.log('cancel', cancel);
  if (cancel && newMarker.value) {
    initialMap.value.removeLayer(newMarker.value);
  }
});
</script>

<style scoped>
#map {
  height: 70vh;
}
</style>
