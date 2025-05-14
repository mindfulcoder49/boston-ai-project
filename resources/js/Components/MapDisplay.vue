<template>
    <div class="boston-map" :class="{ 'map-loading': mapIsLoading }">
      <div id="map" class="h-full"></div>
    </div>
  </template>
  
  <script setup>
  import { ref, onMounted, onBeforeUnmount, watch, nextTick, markRaw, defineProps, defineEmits, defineExpose } from 'vue';
  import 'leaflet/dist/leaflet.css';
  import * as L from 'leaflet';
  
  const props = defineProps({
    mapCenterCoordinates: Array,
    dataPointsToDisplay: Array,
    isCenterSelectionModeActive: Boolean,
    tempNewMarkerPlacementCoords: Object, 
    mapIsLoading: Boolean,
    shouldClearTempMarker: Boolean,
  });
  
  const emit = defineEmits([
    'map-coordinates-selected-for-new-center',
    'marker-data-point-clicked',
    'map-initialized-internal',
  ]);
  
  const initialMap = ref(null);
  const markerCenter = ref(null);
  const newMarker = ref(null); 
  const markers = ref([]);
  
  // currentMapViewport will store the map's view (center/zoom).
  // On first load, it uses props.mapCenterCoordinates or a default.
  // On destroy, it's updated with the map's actual current view.
  // On re-init, this saved view is used to restore the map's position.
  const currentMapViewport = ref({ 
    center: props.mapCenterCoordinates && props.mapCenterCoordinates.length === 2 ? props.mapCenterCoordinates : [42.3601, -71.0589], 
    zoom: 16 
  });
  
  
  const getDivIconInternal = (dataPoint) => {
    let className = 'default-div-icon';
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
      if (dataPoint?.submitted_photo) {
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
    className += ' id'+ dataPoint?.data_point_id;
    return L.divIcon({
      className,
      html: `<div style="${backgroundImage}"></div>`,
      iconSize: null,  // Icon size will be controlled by CSS (--icon-size)
      popupAnchor: [15, 0], // Adjust as needed for your icon style
    });
  };
  
  const getCenterIconInternal = (type) => { // 'type' can differentiate icons if needed, e.g. 'Center', 'NewCenter'
    let className = 'center-div-icon';
    if (type === 'NewCenter') {
      className = 'new-center-div-icon'; // Example if you want a different style for temp new marker
    }
    return L.divIcon({
      className,
      html: `<div></div>`, // Simple div, styling via CSS
      iconSize: null, // Controlled by CSS
      popupAnchor: [0, -15], // Adjust as needed
    });
  };
  
  const initializeMapInternal = (centerArgFromParent = null, viewCenter = false) => {
    nextTick(() => {
      // Guard against re-initializing an already active map.
      // This ensures this function only runs when initialMap.value is null.
      if (initialMap.value) {
        console.warn("MapDisplay: initializeMapInternal called while map instance already exists. Aborting.");
        return;
      }
  
      // Determine the map view settings (center and zoom) for setView.
      // Always use currentMapViewport.value. This holds:
      // 1. On first load: The initial center/zoom (derived from props.mapCenterCoordinates or defaults).
      // 2. On re-initialization (after destroyMapInternal): The map's actual center/zoom from before it was destroyed.
      // This ensures the map view is restored to its previous state if re-initialized.
      if (centerArgFromParent && viewCenter) {
        currentMapViewport.value.center = centerArgFromParent;
      } 
      const viewToSet = currentMapViewport.value.center;
      const zoomToSet = currentMapViewport.value.zoom;
      
      
      // The `centerArgFromParent` (if provided via initializeNewMapAtCenter(coords)) is deliberately NOT used for setView here.
      // This is key to preventing the map from re-centering to a new "logical center"
      // when the map instance is being re-created.
  
      initialMap.value = markRaw(L.map('map').setView(viewToSet, zoomToSet));
      
      L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '© <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>',
      }).addTo(initialMap.value);
  
      if (!initialMap.value) {
        console.error('MapDisplay: Map initialization failed.');
        return;
      }
      
      const updateZoomVariables = () => {
        if (!initialMap.value) return;
        
        initialMap.value.invalidateSize(); // Ensure map size is correct, especially after container resizes
        const zoom = initialMap.value.getZoom();
        
        const minSize = 2; 
        const maxSize = 50; 
        const minZoom = initialMap.value.getMinZoom() || 10; // Use map's minZoom or fallback
        const maxZoom = initialMap.value.getMaxZoom() || 19; // Use map's maxZoom or fallback
        
        let newSize;
        if (zoom <= minZoom) {
          newSize = minSize;
        } else if (zoom >= maxZoom) {
          newSize = maxSize;
        } else {
          // Avoid division by zero if minZoom equals maxZoom (edge case)
          newSize = (maxZoom - minZoom > 0) 
            ? minSize + (maxSize - minSize) * (zoom - minZoom) / (maxZoom - minZoom)
            : minSize;
        }
        document.documentElement.style.setProperty('--icon-size', `${newSize}px`);
      };
  
      initialMap.value.on('zoomend', updateZoomVariables);
      initialMap.value.on('resize', () => { // Handle map container resize
          if(initialMap.value) {
              initialMap.value.invalidateSize(); // Essential after resize
              updateZoomVariables(); // Re-calculate icon sizes if needed
          }
      });
      updateZoomVariables(); // Call once to set initial icon size
  
      initialMap.value.on('click', (e) => {
        if (props.isCenterSelectionModeActive) {
          emit('map-coordinates-selected-for-new-center', e.latlng);
        }
      });
      
      // Place the 'center' marker. This should always reflect the *logical* center,
      // which is props.mapCenterCoordinates.
      if (props.mapCenterCoordinates && props.mapCenterCoordinates.length === 2) {
          markerCenter.value = markRaw(
            L.marker(props.mapCenterCoordinates, {
                icon: getCenterIconInternal('Center'),
            })
          ).addTo(initialMap.value);
      }
  
      emit('map-initialized-internal');
  
      if (props.dataPointsToDisplay) {
        updateMarkersInternal(props.dataPointsToDisplay);
      }
      
      if (props.tempNewMarkerPlacementCoords && newMarker.value == null) {
          newMarker.value = markRaw(
              L.marker([props.tempNewMarkerPlacementCoords.lat, props.tempNewMarkerPlacementCoords.lng], {
                icon: getCenterIconInternal('NewCenter'),
              })
          ).addTo(initialMap.value);
      }
    });
  };
  
  const destroyMapInternal = () => {
    if (initialMap.value) {
      // Save the current map's view before destroying it
      currentMapViewport.value = {
        center: initialMap.value.getCenter(),
        zoom: initialMap.value.getZoom(),
      };
      initialMap.value.off(); // Remove all event listeners
      initialMap.value.remove(); // Remove the map
      initialMap.value = null;
      
      // Clear marker refs, they will be recreated if needed
      markers.value.forEach(marker => marker.remove()); // Ensure Leaflet markers are removed
      markers.value = [];
      if (markerCenter.value) {
        markerCenter.value.remove();
        markerCenter.value = null;
      }
      if (newMarker.value) {
        newMarker.value.remove();
        newMarker.value = null;
      }
    }
  };
  
  const updateMarkersInternal = (dataPoints) => {
    if (!initialMap.value) return;
  
    // Clear existing data point markers
    markers.value.forEach((marker) => initialMap.value.removeLayer(marker));
    markers.value = [];
  
    dataPoints.forEach((dataPoint) => {
      if (dataPoint.latitude && dataPoint.longitude) {
        const popupContentStart = `<div><strong>${new Date(dataPoint.alcivartech_date).toLocaleString()}</strong>`;
        const popupContent = `
            ${popupContentStart}<br>
            ${dataPoint.alcivartech_type === 'Crime' ? dataPoint.offense_description : ''}
            ${dataPoint.alcivartech_type === '311 Case' ? dataPoint.case_title : ''}
            ${dataPoint.alcivartech_type === 'Building Permit' ? dataPoint.description : ''}
            ${dataPoint.alcivartech_type === 'Property Violation' ? dataPoint.description : ''}
            ${dataPoint.alcivartech_type === 'Construction Off Hour' ? dataPoint.address : ''}
            </div>
          `;
        const marker = markRaw(
          L.marker([dataPoint.latitude, dataPoint.longitude], {
            icon: getDivIconInternal(dataPoint),
          })
        );
        marker.on('click', (e) => {
          // Emit data and prevent map click if we are just clicking a marker
          // L.DomEvent.stopPropagation(e); // Good practice if marker clicks shouldn't propagate to map
          emit('marker-data-point-clicked', dataPoint);
        });
        marker.bindPopup(popupContent); // Don't .openPopup() here, open on click or hover as per UX
        marker.addTo(initialMap.value);
        markers.value.push(marker);
      }
    });
  };
  
  
  // This watcher handles changes to the logical center coordinates when the map is *live*.
  // It updates the marker's position without moving the map view.
  watch(() => props.mapCenterCoordinates, (newVal, oldVal) => {
    if (initialMap.value && newVal && newVal.length === 2) {
      // Map is initialized and new coordinates are valid
      if (markerCenter.value) {
        markerCenter.value.setLatLng(newVal);
      } else {
        // Create center marker if it doesn't exist
        markerCenter.value = markRaw(L.marker(newVal, {
          icon: getCenterIconInternal('Center'),
        })).addTo(initialMap.value);
      }
    } else if (initialMap.value && !newVal && markerCenter.value) {
      // If new coordinates are null/invalid, remove the marker
      initialMap.value.removeLayer(markerCenter.value);
      markerCenter.value = null;
    }
  }, { deep: true });

  // Watch for changes in data points to display and update markers
  // This is useful if data points can be updated without a full map re-initialization
  watch(() => props.dataPointsToDisplay, (newDataPoints) => {
    if (initialMap.value && newDataPoints) {
      updateMarkersInternal(newDataPoints);
    }
  }, { deep: true });
  
  
  watch(() => props.tempNewMarkerPlacementCoords, (newCoords) => {
      if (initialMap.value) {
          if (newMarker.value) {
              initialMap.value.removeLayer(newMarker.value);
              newMarker.value = null;
          }
          if (newCoords) {
              newMarker.value = markRaw(
                  L.marker([newCoords.lat, newCoords.lng], {
                  icon: getCenterIconInternal('NewCenter'),
                  })
              ).addTo(initialMap.value);
          }
      }
  });
  
  watch(() => props.shouldClearTempMarker, (clear) => {
      if (clear && newMarker.value && initialMap.value) {
          initialMap.value.removeLayer(newMarker.value);
          newMarker.value = null;
          // Consider emitting an event or resetting the prop in parent if needed:
          // emit('temp-marker-cleared'); 
      }
  });
  
  onMounted(() => {
    // Initialize currentMapViewport based on initial props before map creation
    currentMapViewport.value = {
        center: props.mapCenterCoordinates && props.mapCenterCoordinates.length === 2 ? props.mapCenterCoordinates : [42.3601, -71.0589],
        zoom: 16 // Or derive from a prop if zoom can be set initially
    };
    initializeMapInternal();
  });
  
  onBeforeUnmount(() => {
    destroyMapInternal();
  });
  
  defineExpose({
    destroyMapAndClear: destroyMapInternal,
    initializeNewMapAtCenter: initializeMapInternal, // Parent can call this with new logical center
    getMapInstance: () => initialMap.value,
    getMarkers: () => markers.value,
  });
  
  </script>
  
  <style scoped>
  #map {
    height: 100%;
    width: 100%; /* Ensure map takes full width of its container */
    background-color: #f0f0f0; /* Placeholder background while tiles load */
  }
  .boston-map {
    height: auto; /* Or specific height like 500px, 70vh etc. */
    overflow: hidden; /* Good practice */
    position: relative; /* If you have overlays or absolutely positioned children */
  }
  .map-loading {
     filter: blur(2px); 
     /* transition: filter 0.3s ease-out; Optional: smooth blur transition */
  }
  
  /* Responsive adjustments */
  @media (min-width: 768px) {
    /* .boston-map height might be controlled by parent layout */
  }
  @media (max-width: 768px) {
    .boston-map {
      height: 70vh; /* Example: Taller on mobile */
    }
  }

  </style>