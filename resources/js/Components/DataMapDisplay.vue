<template>
  <div :id="mapId" class="h-full w-full"></div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, watch, markRaw, nextTick} from 'vue';
import 'leaflet/dist/leaflet.css';
import * as L from 'leaflet';
import 'leaflet.markercluster/dist/MarkerCluster.css';
import 'leaflet.markercluster/dist/MarkerCluster.Default.css';
import 'leaflet.markercluster';

// Fix for default icon path issues with Vite/Leaflet
delete L.Icon.Default.prototype._getIconUrl;
// Remove the global override for iconUrl, as we'll set icons individually.
// L.Icon.Default.mergeOptions({
//   iconRetinaUrl: "/images/foodinspectionicon.svg", // No longer needed here
//   iconUrl: "/images/foodinspectionicon.svg",       // No longer needed here
//   shadowUrl: false,
// });


const props = defineProps({
  mapCenterCoordinates: {
    type: Array,
    default: () => [42.3601, -71.0589] // Default Boston center
  },
  dataPointsToDisplay: {
    type: Array,
    default: () => []
  },
  // mapIsLoading: { // This prop might not be directly used by this component for overlay, parent handles loading state
  //   type: Boolean,
  //   default: false
  // },
  externalIdFieldProp: { // To uniquely identify markers if needed, e.g., for panToAndOpenPopup
    type: String,
    default: 'id' // Default to 'id', models should have an 'id' or provide the correct field name
  },
  zoomLevel: {
    type: Number,
    default: 13
  }
});

const emit = defineEmits(['marker-data-point-clicked', 'map-initialized-internal']);

const mapId = `leaflet-map-${Math.random().toString(36).substring(2, 9)}`;
const mapInstance = ref(null);
// const markerClusterGroup = ref(null); // Changed: Will use multiple groups
const markerClusterGroups = ref({}); // Changed: To store type-specific cluster groups
const activeMarkersMap = ref(new Map()); // To store markers by their external ID for quick access

const iconSettings = {
  iconSize: [38, 38],    // Icon size (width, height)
  iconAnchor: [19, 38],  // Point of the icon which will correspond to marker's location (bottom-center)
  popupAnchor: [0, -38]  // Point from which the popup should open relative to the iconAnchor (top-center of anchor)
};

const getMarkerIcon = (alcivartechType) => {
  let iconUrl = '/images/leaflet/marker-icon.png'; // Default Leaflet icon as a fallback
    let iconClassName = 'default-div-icon'; // Default class name for the icon

  switch (alcivartechType) {
    case 'Crime':
      iconUrl = '/images/crimeshieldicon.svg';
      iconClassName = 'crime-div-icon';
      break;
    case '311 Case':
      iconUrl = '/images/boston311icon.svg';
      iconClassName = 'case-div-icon';
      break;
    case 'Building Permit':
      iconUrl = '/images/permiticon.svg';
        iconClassName = 'permit-div-icon';
      break;
    case 'Property Violation':
      iconUrl = '/images/propertyviolationicon.svg';
        iconClassName = 'property-violation-div-icon';
      break;
    case 'Construction Off Hour':
      iconUrl = '/images/constructionoffhouricon.svg';
        iconClassName = 'construction-off-hour-div-icon';
      break;
    case 'Food Inspection':
      iconUrl = '/images/foodinspectionicon.svg';
        iconClassName = 'food-inspection-div-icon';
      break;
    default:
      // console.warn(`No specific icon for type: ${alcivartechType}, using default Leaflet icon.`);
      // For the default Leaflet icon, we might not need to specify all settings if defaults are fine
      // However, to ensure consistency if you have a custom default icon:
      // iconUrl = '/images/custom-default-marker.svg';
      return L.icon({ // Return Leaflet's default icon if type not matched
          iconUrl: '/images/leaflet/marker-icon.png', // Standard Leaflet marker
          iconRetinaUrl: '/images/leaflet/marker-icon-2x.png', // Standard Leaflet marker retina
          shadowUrl: '/images/leaflet/marker-shadow.png', // Standard Leaflet marker shadow
          iconSize: [25, 41],
          iconAnchor: [12, 41],
          popupAnchor: [1, -34],
          shadowSize: [41, 41]
      });
  }

  return L.icon({
    iconUrl: iconUrl,
    iconRetinaUrl: iconUrl, // Assuming SVGs or that retina versions are same as standard for these custom icons
    iconSize: iconSettings.iconSize,
    iconAnchor: iconSettings.iconAnchor,
    popupAnchor: iconSettings.popupAnchor,
    shadowUrl: false, // No shadow for custom icons, consistent with previous setup
    className: iconClassName || 'default-div-icon' // Default class if not specified
  });
};

const getClusterRadius = (zoom) => {
  if (zoom < 10) {
    return 80; // Larger radius when zoomed out
  } else if (zoom < 13) {
    return 60;
  } else if (zoom < 16) {
    return 40;
  } else {
    return 10; // Smaller radius when zoomed in, showing more individual markers or smaller clusters
  }
};

// Helper function to create the iconCreateFunction for a given type
const createTypedIconCreateFunction = (type) => {
  return function(cluster) {
    const childCount = cluster.getChildCount();
    let classNames = 'marker-cluster';

    // Use 'mixed' for 'Unknown' type, otherwise derive from type
    const typeClass = (type === 'Unknown' || !type) 
                      ? 'mixed' 
                      : type.toLowerCase().replace(/\s+/g, '-');
    classNames += ` cluster-${typeClass}`;

    if (childCount < 10) {
      classNames += ' marker-cluster-small';
    } else if (childCount < 100) {
      classNames += ' marker-cluster-medium';
    } else {
      classNames += ' marker-cluster-large';
    }

    return L.divIcon({
      html: `<div><span>${childCount}</span></div>`,
      className: classNames,
      iconSize: L.point(40, 40)
    });
  };
};

const initializeMap = () => {
  if (mapInstance.value) return; // Already initialized

  mapInstance.value = markRaw(L.map(mapId, {
      // scrollWheelZoom: false,
  }).setView(props.mapCenterCoordinates, props.zoomLevel));

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 20,
    attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
  }).addTo(mapInstance.value);

  // Removed single markerClusterGroup initialization here.
  // Groups will be created dynamically in updateMarkers.

  emit('map-initialized-internal', mapInstance.value);
  updateMarkers(props.dataPointsToDisplay); // Initial marker update
};

const createPopupContent = (dataPoint) => {
  const buildHtmlRecursive = (data, isNested = false) => {
    let html = isNested ? '<div style="padding-left: 15px; border-left: 1px solid #eee; margin-top: 5px;">' : '';

    for (const key in data) {
      if (Object.prototype.hasOwnProperty.call(data, key)) {
        const label = key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
        let value = data[key];

        if (typeof value === 'object' && value !== null && !Array.isArray(value)) {
          // For nested objects, print the label, then recursively build its content
          html += `<div><strong>${label}:</strong>`;
          html += buildHtmlRecursive(value, true); // Recursive call
          html += `</div>`;
        } else if (Array.isArray(value)) {
          // For arrays, print the label and each item
          html += `<div><strong>${label}:</strong>`;
          value.forEach(item => {
            html += buildHtmlRecursive(item, true); // Recursive call for each item
          });
          html += `</div>`;
        } else {
          // For simple key-value pairs
          html += `<div><strong>${label}:</strong> ${value}</div>`;
        }
      }
    }
    html += isNested ? '</div>' : '';
    return html;
  };

  let content = '<div style="max-height: 250px; overflow-y: auto; font-size: 0.9em; line-height: 1.4;">';
  content += buildHtmlRecursive(dataPoint);
  content += '</div>';
  return content;
};

const updateMarkers = (newDataPoints) => {
  if (!mapInstance.value) {
    return;
  }

  // Clear layers from all existing cluster groups
  Object.values(markerClusterGroups.value).forEach(group => {
    group.clearLayers();
  });
  activeMarkersMap.value.clear();

  // Optional: Remove cluster groups that will no longer have data.
  // For simplicity, we'll keep them, and they'll just be empty.
  // If removal is desired, track current types and remove unused groups from map and markerClusterGroups.value

  newDataPoints.forEach(dp => {
    const lat = parseFloat(dp.latitude || dp.lat);
    const long = parseFloat(dp.longitude || dp.long || dp.lng);
    const alcivartechType = dp.alcivartech_type || 'Unknown'; // Default to 'Unknown' if type is missing

    if (!isNaN(lat) && !isNaN(long)) {
      // Ensure a cluster group exists for this type
      if (!markerClusterGroups.value[alcivartechType]) {
        const newClusterGroup = markRaw(L.markerClusterGroup({
          maxClusterRadius: getClusterRadius,
          iconCreateFunction: createTypedIconCreateFunction(alcivartechType)
        }));
        mapInstance.value.addLayer(newClusterGroup);
        markerClusterGroups.value[alcivartechType] = newClusterGroup;
      }

      const markerIcon = getMarkerIcon(alcivartechType);
      const marker = markRaw(L.marker([lat, long], { icon: markerIcon, alcivartechType: alcivartechType }));
      marker.bindPopup(createPopupContent(dp));
      marker.on('click', () => {
        emit('marker-data-point-clicked', dp);
      });
      
      markerClusterGroups.value[alcivartechType].addLayer(marker);
      
      const externalId = dp[props.externalIdFieldProp];
      if (externalId !== undefined) {
        activeMarkersMap.value.set(String(externalId), marker);
      }
    } else {
      // console.warn('Skipping data point due to invalid coordinates:', dp);
    }
  });
};

const panToAndOpenPopup = (dataPoint, idField) => {
  const externalId = dataPoint[idField || props.externalIdFieldProp];
  const marker = activeMarkersMap.value.get(String(externalId));

  if (marker && mapInstance.value) {
    const latLng = marker.getLatLng();
    mapInstance.value.setView(latLng, Math.max(mapInstance.value.getZoom(), 15)); // Zoom in if not already
    
    // Open popup after a slight delay to ensure map pan/zoom is complete
    nextTick(() => {
        marker.openPopup();
    });
  } else {
    // console.warn(`Marker with ID ${externalId} not found for panToAndOpenPopup.`);
  }
};


onMounted(() => {
  initializeMap();
});

onBeforeUnmount(() => {
  if (mapInstance.value) {
    // Remove all type-specific cluster groups
    Object.values(markerClusterGroups.value).forEach(group => {
      if (mapInstance.value.hasLayer(group)) {
        mapInstance.value.removeLayer(group);
      }
    });
    markerClusterGroups.value = {}; // Clear the stored groups

    mapInstance.value.remove();
    mapInstance.value = null;
  }
});

watch(() => props.dataPointsToDisplay, (newDataPoints) => {
  updateMarkers(newDataPoints);
}, { deep: true });

watch(() => props.mapCenterCoordinates, (newCenter) => {
  if (mapInstance.value && newCenter && newCenter.length === 2) {
    mapInstance.value.setView(newCenter, props.zoomLevel);
  }
}, { deep: true });


// Expose methods to parent component
defineExpose({
  getMapInstance: () => mapInstance.value,
  panToAndOpenPopup,
});

</script>

<style scoped>
/* Ensure the map container has a defined height in its parent or here */


</style>

<style>
/* Global styles for Leaflet DivIcons if not scoped */
.marker-cluster {
  background-clip: padding-box;
  border-radius: 20px; /* Should match iconSize / 2 if a perfect circle is desired for the 40x40 icon */
  display: flex;
  justify-content: center;
  align-items: center;
  font-weight: bold;
}
.marker-cluster div {
  width: 30px;
  height: 30px;
  margin-left: 0;
  margin-top: 0;
  text-align: center;
  border-radius: 15px; /* Should match width/height / 2 for a perfect circle */
  font: 12px "Helvetica Neue", Arial, Helvetica, sans-serif;
  display: flex;
  justify-content: center;
  align-items: center;
}
.marker-cluster span {
  line-height: 30px; /* Adjust if div height changes */
}

/* Default cluster size-based colors (will be overridden by type-specific if !important is used) */
.marker-cluster-small {
  background-color: rgba(181, 226, 140, 0.6);
}
.marker-cluster-small div {
  background-color: rgba(110, 204, 57, 0.6);
}
.marker-cluster-medium {
  background-color: rgba(241, 211, 87, 0.6);
}
.marker-cluster-medium div {
  background-color: rgba(240, 194, 12, 0.6);
}
.marker-cluster-large {
  background-color: rgba(253, 156, 115, 0.6);
}
.marker-cluster-large div {
  background-color: rgba(241, 128, 23, 0.6);
}

/* Type-specific cluster colors, derived from app.css variables, made darker, with 0.6 alpha */

/* Crime: base rgb(252, 127, 127) */
.cluster-crime {
  background-color: rgba(222, 97, 97, 0.6) !important; 
}
.cluster-crime div {
  background-color: rgba(192, 67, 67, 0.6) !important;
  color: white;
}

/* 311 Case: base rgb(59, 130, 246) */
.cluster-311-case {
  background-color: rgba(29, 100, 216, 0.6) !important; 
}
.cluster-311-case div {
  background-color: rgba(0, 70, 186, 0.6) !important; /* Clamped blue to 0 */
  color: white;
}

/* Building Permit: base rgb(138, 231, 138) */
.cluster-building-permit {
  background-color: rgba(108, 201, 108, 0.6) !important; 
}
.cluster-building-permit div {
  background-color: rgba(78, 171, 78, 0.6) !important;
  color: white;
}

/* Property Violation: base rgb(255, 255, 0) */
.cluster-property-violation {
  background-color: rgba(225, 225, 0, 0.6) !important;
}
.cluster-property-violation div {
  background-color: rgba(195, 195, 0, 0.6) !important;
  color: #333; /* Darker text for yellow background */
}

/* Construction Off Hour: base rgb(114, 203, 209) */
.cluster-construction-off-hour {
  background-color: rgba(84, 173, 179, 0.6) !important;
}
.cluster-construction-off-hour div {
  background-color: rgba(54, 143, 149, 0.6) !important;
  color: white;
}

/* Food Inspection: base rgb(255, 165, 0) */
.cluster-food-inspection {
  background-color: rgba(225, 135, 0, 0.6) !important;
}
.cluster-food-inspection div {
  background-color: rgba(195, 105, 0, 0.6) !important;
  color: white;
}

/* Fallback for clusters with no dominant type or if type is not set */
.cluster-mixed { 
  background-color: rgba(169, 169, 169, 0.6) !important; /* Grey for mixed/unknown */
}
.cluster-mixed div {
  background-color: rgba(128, 128, 128, 0.6) !important;
  color: white;
}

</style>
