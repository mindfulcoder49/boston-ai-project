<template>
    <div class="boston-map" :class="{ 'map-loading': mapIsLoading }">
      <div id="map" class="h-full"></div>
    </div>
  </template>
  
  <script setup>
  import { ref, onMounted, onBeforeUnmount, watch, nextTick, markRaw, defineProps, defineEmits, defineExpose } from 'vue';
  import 'leaflet/dist/leaflet.css';
  import * as L from 'leaflet';
  import 'leaflet.markercluster/dist/MarkerCluster.css';
  import 'leaflet.markercluster/dist/MarkerCluster.Default.css';
  import 'leaflet.markercluster';
  import { getIconCustomizations } from '@/Utils/iconUtils'; // Added import
  
  const props = defineProps({
    mapCenterCoordinates: Array,
    // dataPointsToDisplay: Array, // This will be effectively replaced by allMapDataPoints + activeFilterTypes for marker rendering
    allMapDataPoints: Array, // New prop for all data
    activeFilterTypes: Object, // New prop for active filter states
    isCenterSelectionModeActive: Boolean,
    tempNewMarkerPlacementCoords: Object, 
    mapIsLoading: Boolean,
    shouldClearTempMarker: Boolean,
    mapConfiguration: Object, // Added new prop
  });
  
  const emit = defineEmits([
    'map-coordinates-selected-for-new-center',
    'marker-data-point-clicked',
    'map-initialized-internal',
  ]);
  
  const initialMap = ref(null);
  const markerCenter = ref(null);
  const newMarker = ref(null); 
  const markerClusterGroups = ref({}); // Changed from markers = ref([])
  
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
    const customizations = getIconCustomizations(dataPoint); // Get customizations
  
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
      case 'Food Inspection': // Add this case
        className = 'food-inspection-div-icon';
        break;
      case 'Car Crash':
        className = 'crash-div-icon';
        break;
      default:
        break;
    }

    if (customizations.className) {
      className += ` ${customizations.className}`; // Append custom class
    }
    // Note: iconUrlOverride from customizations is not directly used here for L.divIcon.
    // The CSS for the custom class should handle background-image changes.
  
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
      popupAnchor: [0, -15], // Adjusted for typical icon bottom center pointing up
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

  const getClusterRadius = (zoom) => {
    if (zoom < 10) {
      return 80; 
    } else if (zoom < 13) {
      return 60;
    } else if (zoom < 16) {
      return 40;
    } else {
      return 10; 
    }
  };

  const createTypedIconCreateFunction = (type) => {
    return function(cluster) {
      const childCount = cluster.getChildCount();
      let classNames = 'marker-cluster';
  
      const typeClass = (type === 'Unknown' || !type) 
                        ? 'mixed' 
                        : type.toLowerCase().replace(/\s+/g, '-').replace(/[()]/g, ''); // Sanitize type for CSS class
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
        iconSize: L.point(40, 40) // Standard size for these cluster icons
      });
    };
  };
  

  // Helper function to generate an HTMLElement for an individual data point's popup
    // onBackToClusterCallback is kept for signature consistency but will be null with leaflet.markercluster
    const createIndividualItemPopupElement = (dataPoint, onBackToClusterCallback = null) => {
      const container = document.createElement('div');
      container.className = 'custom-popup-content individual-item-popup';
      container.style.maxHeight = '350px';
      container.style.overflowY = 'auto';
      container.style.paddingRight = '10px';

      const modelKey = dataPoint.alcivartech_model;
      const config = props.mapConfiguration?.dataPointModelConfig?.[modelKey];
      const sourceData = dataPoint[config?.dataObjectKey || modelKey]; // Fallback to modelKey if dataObjectKey not in config

      // Date and Type are common
      const dateString = new Date(dataPoint.alcivartech_date).toLocaleString();
      let detailsHtml = `<div style="margin-bottom: 5px;"><strong>${dateString}</strong></div>`;
      detailsHtml += `<div>Type: ${dataPoint.alcivartech_type || 'N/A'}</div>`;
      if (modelKey && modelKey.replace(/_/g, ' ').toLowerCase() !== dataPoint.alcivartech_type.toLowerCase()){
         detailsHtml += `<div>Source Model: ${modelKey.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())}</div>`;
      }
      
      // Special handling for Food Inspections with aggregated data (using customPopupHandler flag)
      if ( sourceData?.violation_summary) {
          console.log('Creating custom popup for aggregated food inspections:', dataPoint);
          // This is the existing custom logic for aggregated food inspections
          let initialDetailsHtml = `<div style="margin-bottom: 5px;"><strong>${new Date(sourceData.alcivartech_date || dataPoint.alcivartech_date).toLocaleString()}</strong> (Most Recent Activity)</div>`;
          initialDetailsHtml += `<div>Type: ${dataPoint.alcivartech_type}</div>`;

          if (sourceData.businessname) {
            initialDetailsHtml += `<div style="font-size: 1.1em; margin-bottom: 3px;"><strong>Business: ${sourceData.businessname}</strong></div>`;
          }
          if (sourceData.licenseno) {
            initialDetailsHtml += `<div>License: ${sourceData.licenseno}</div>`;
          }
          if (sourceData.address) {
            initialDetailsHtml += `<div>Address: ${sourceData.address}</div>`;
          }
          container.innerHTML = initialDetailsHtml;

          const historyTitle = document.createElement('div');
          historyTitle.style.marginTop = '10px';
          historyTitle.style.marginBottom = '5px';
          const totalRecords = sourceData.violation_summary.reduce((sum, s) => sum + s.entries.length, 0);
          historyTitle.innerHTML = `<strong>Violation History (${totalRecords} total records):</strong>`;
          container.appendChild(historyTitle);

          const historyContainer = document.createElement('div');
          historyContainer.style.paddingRight = '5px';

          sourceData.violation_summary.forEach((summaryItem) => {
            const violDescEl = document.createElement('div');
            violDescEl.style.marginTop = '8px';
            violDescEl.style.paddingLeft = '5px';
            // Determine status color based on the most recent entry in the summary item
            const mostRecentEntryInSummary = summaryItem.entries[0]; // Entries are pre-sorted by date desc
            const statusColor = mostRecentEntryInSummary.viol_status === 'Fail' || (mostRecentEntryInSummary.result && mostRecentEntryInSummary.result.toLowerCase().includes('fail')) ? '#D32F2F' : '#388E3C';

            violDescEl.innerHTML = `<strong style="color: ${statusColor};">${summaryItem.violdesc}</strong> (${summaryItem.entries.length} record(s))`;
            historyContainer.appendChild(violDescEl);

            const entriesList = document.createElement('ul');
            entriesList.style.listStylePosition = 'inside';
            entriesList.style.paddingLeft = '10px';
            entriesList.style.marginLeft = '0px';

            summaryItem.entries.forEach((entry) => {
              const entryItem = document.createElement('li');
              entryItem.style.fontSize = '0.9em';
              entryItem.style.marginBottom = '6px';
              entryItem.style.borderBottom = '1px dashed #eee';
              entryItem.style.paddingBottom = '4px';
              
              let entryHtml = `<div style="font-weight: bold;"><em>${new Date(entry.alcivartech_date).toLocaleString()}</em></div>`;
              const entryStatusColor = entry.viol_status === 'Fail' || (entry.result && entry.result.toLowerCase().includes('fail')) ? 'red' : 'green';
              entryHtml += `<div>Status: <span style="font-weight:bold; color: ${entryStatusColor};">${entry.viol_status || 'N/A'}</span> | Result: ${entry.result || 'N/A'} | Level: ${entry.viol_level || 'N/A'}</div>`;
              if (entry.comments) {
                if (entry.viol_status === 'Pass' || (entry.result && entry.result.toLowerCase().includes('pass'))) {
                  entryHtml += `<div style="font-style: italic; color: #222; margin-top: 2px;">Comments addressed from previous failed inspection: ${entry.comments}</div>`;
                } else { 
                  entryHtml += `<div style="font-style: italic; color: #222; margin-top: 2px;">Comments: ${entry.comments}</div>`;
                }
              }
              entryItem.innerHTML = entryHtml;
              entriesList.appendChild(entryItem);
            });

            if (entriesList.lastChild) {
                (entriesList.lastChild).style.borderBottom = 'none';
            }
            historyContainer.appendChild(entriesList);
          });
          container.appendChild(historyContainer);
          return container;
      } else if (config && sourceData) { // General case: use mapConfiguration
        if (config.mainIdentifierField && sourceData[config.mainIdentifierField]) {
            detailsHtml += `<div><strong>${config.mainIdentifierLabel || 'ID'}:</strong> ${sourceData[config.mainIdentifierField]}</div>`;
        }
        if (config.descriptionField && sourceData[config.descriptionField]) {
            detailsHtml += `<div><strong>${config.descriptionLabel || 'Description'}:</strong> ${sourceData[config.descriptionField]}</div>`;
        }

        if (config.additionalFields && Array.isArray(config.additionalFields)) {
            config.additionalFields.forEach(fieldConfig => {
                const value = sourceData[fieldConfig.key];
                let showField = true;
                if (fieldConfig.condition) {
                    if (fieldConfig.condition === 'boolean_true_only' && value !== true && String(value).toLowerCase() !== 'true') {
                        showField = false;
                    } else if (fieldConfig.condition === 'not_empty_string' && (value === null || String(value).trim() === '')) {
                        showField = false;
                    }
                    // Add more conditions as needed
                }

                if (showField && value !== undefined && value !== null) {
                    detailsHtml += `<div><strong>${fieldConfig.label || fieldConfig.key}:</strong> ${value}</div>`;
                }
            });
        }
        // Display live_details if present (common for 311 cases)
        if (dataPoint.live_details) {
            detailsHtml += `<div style="margin-top: 5px; padding-top: 5px; border-top: 1px solid #eee;"><strong>Live Details:</strong></div>`;
            Object.entries(dataPoint.live_details).forEach(([key, value]) => {
                const formattedKey = key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                let displayValue = value;
                if (typeof value === 'object' && value !== null) displayValue = JSON.stringify(value);
                else if (value === null || value === undefined) displayValue = 'N/A';
                detailsHtml += `<div>${formattedKey}: ${displayValue}</div>`;
            });
        }
        container.innerHTML = detailsHtml;

      } else if (sourceData) { // Fallback if no config, but sourceData exists (original generic display)
        Object.entries(sourceData).forEach(([key, value]) => {
          const formattedKey = key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
          let displayValue = value;
          if (typeof value === 'object' && value !== null) displayValue = JSON.stringify(value);
          else if (value === null || value === undefined) displayValue = 'N/A';
          detailsHtml += `<div>${formattedKey}: ${displayValue}</div>`;
        });
        container.innerHTML = detailsHtml;
      } else {
        detailsHtml += `<div>No detailed data available for this point. Model: ${modelKey}</div>`;
        container.innerHTML = detailsHtml;
      }
    
      return container;
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
  
      if (props.allMapDataPoints) {
        rebuildAllMarkerClusters(props.allMapDataPoints);
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
      
      // Clear marker cluster groups
      Object.values(markerClusterGroups.value).forEach(group => {
        group.clearLayers(); // Clear markers from the group
        if (initialMap.value && initialMap.value.hasLayer(group)) {
          initialMap.value.removeLayer(group);
        }
      });
      markerClusterGroups.value = {};

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
  
  const rebuildAllMarkerClusters = (currentAllMapDataPoints) => {
    if (!initialMap.value) return;

    // Clear existing groups from map and internal ref
    Object.values(markerClusterGroups.value).forEach(group => {
      if (initialMap.value.hasLayer(group)) {
        initialMap.value.removeLayer(group);
      }
      group.clearLayers();
    });
    markerClusterGroups.value = {};

    if (!currentAllMapDataPoints || currentAllMapDataPoints.length === 0) {
      updateVisibleClusterGroups(); // Ensure map is clear if no data
      return;
    }

    currentAllMapDataPoints.forEach(dataPoint => {
      if (!dataPoint.latitude || !dataPoint.longitude) {
        console.warn('Skipping data point due to invalid coordinates:', dataPoint);
        return;
      }

      const lat = parseFloat(dataPoint.latitude);
      const long = parseFloat(dataPoint.longitude);
      const alcivartechType = dataPoint.alcivartech_type || 'Unknown';

      if (isNaN(lat) || isNaN(long)) {
        console.warn('Skipping data point due to invalid parsed coordinates:', dataPoint);
        return;
      }

      if (!markerClusterGroups.value[alcivartechType]) {
        const newClusterGroup = markRaw(L.markerClusterGroup({
          maxClusterRadius: getClusterRadius,
          iconCreateFunction: createTypedIconCreateFunction(alcivartechType),
        }));
        // Do not add to map here; updateVisibleClusterGroups will handle it
        markerClusterGroups.value[alcivartechType] = newClusterGroup;
      }

      const marker = markRaw(
        L.marker([lat, long], {
          icon: getDivIconInternal(dataPoint),
        })
      );
      
      const individualPopupElement = createIndividualItemPopupElement(dataPoint, null); 
      marker.bindPopup(individualPopupElement, { minWidth: 220 });
  
      marker.on('click', (e) => {
        emit('marker-data-point-clicked', dataPoint);
      });
      
      markerClusterGroups.value[alcivartechType].addLayer(marker);
    });
    
    updateVisibleClusterGroups(); // Add initially active groups to the map
  };

  const updateVisibleClusterGroups = () => {
    if (!initialMap.value) return;

    const currentActiveTypes = props.activeFilterTypes || {};

    Object.entries(markerClusterGroups.value).forEach(([type, group]) => {
      const isActive = currentActiveTypes[type];
      if (isActive) {
        if (!initialMap.value.hasLayer(group)) {
          initialMap.value.addLayer(group);
        }
      } else {
        if (initialMap.value.hasLayer(group)) {
          initialMap.value.removeLayer(group);
        }
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

  // Watch for changes in all data points to rebuild all marker clusters
  watch(() => props.allMapDataPoints, (newDataPoints) => {
    if (initialMap.value && newDataPoints) {
      rebuildAllMarkerClusters(newDataPoints);
    }
  }, { deep: true });

  // Watch for changes in active filter types to toggle cluster group visibility
  watch(() => props.activeFilterTypes, () => {
    if (initialMap.value) {
      updateVisibleClusterGroups();
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
  
  const getAllMarkersFromClusterGroups = () => {
    let allMarkers = [];
    Object.values(markerClusterGroups.value).forEach(group => {
      allMarkers = allMarkers.concat(group.getLayers());
    });
    return allMarkers;
  };

  defineExpose({
    destroyMapAndClear: destroyMapInternal,
    initializeNewMapAtCenter: initializeMapInternal, // Parent can call this with new logical center
    getMapInstance: () => initialMap.value,
    getMarkers: getAllMarkersFromClusterGroups, // Adapted to get markers from cluster groups
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

  /* CSS for custom cluster icons */
  :deep(.custom-cluster-div-icon div) {
    width: var(--icon-size, 30px); /* Use CSS variable from updateZoomVariables, fallback to 30px */
    height: var(--icon-size, 30px);
    background-color: rgba(0, 0, 0, 0.7);
    border-radius: 50%;
    color: white;
    font-weight: bold;
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    font-size: calc(var(--icon-size, 30px) * 0.45); /* Adjust font size relative to icon size */
    border: 2px solid rgba(255, 255, 255, 0.7);
    box-shadow: 0 0 3px rgba(0,0,0,0.6);
    cursor: pointer;
  }

  /* Styles for custom popup content elements */
  :deep(.custom-popup-content) {
    font-family: Arial, sans-serif;
    font-size: 13px;
    line-height: 1.5;
  }

  :deep(.custom-popup-content strong) {
    font-weight: bold;
  }

  :deep(.cluster-list-popup ul) {
    margin-top: 5px;
    padding-left: 0; /* Reset Leaflet's default ul padding if any */
    list-style-type: none;
  }

  :deep(.cluster-list-popup li) {
    padding: 5px 2px;
    cursor: pointer;
    border-bottom: 1px solid #eee;
  }
  :deep(.cluster-list-popup li:last-child) {
    border-bottom: none;
  }
  :deep(.cluster-list-popup li:hover) {
    background-color: #f5f5f5;
  }

  :deep(.individual-item-popup div) {
    margin-bottom: 3px;
  }
  
  :deep(.popup-back-button) {
    margin-top: 12px;
    padding: 6px 10px;
    font-size: 12px;
    color: #333;
    background-color: #f0f0f0;
    border: 1px solid #ccc;
    border-radius: 4px;
    cursor: pointer;
    display: inline-block; /* Or block if you want it full width */
  }
  :deep(.popup-back-button:hover) {
    background-color: #e0e0e0;
    border-color: #bbb;
  }

  /* Styles for leaflet.markercluster icons (adapted from DataMapDisplay.vue) */
  :deep(.marker-cluster) {
    background-clip: padding-box;
    border-radius: 20px;
    display: flex;
    justify-content: center;
    align-items: center;
    font-weight: bold;
  }
  :deep(.marker-cluster div) {
    width: 30px;
    height: 30px;
    margin-left: 0; /* Reset from default leaflet.markercluster if any */
    margin-top: 0;  /* Reset from default leaflet.markercluster if any */
    text-align: center;
    border-radius: 15px;
    font: 12px "Helvetica Neue", Arial, Helvetica, sans-serif;
    display: flex;
    justify-content: center;
    align-items: center;
  }
  :deep(.marker-cluster span) {
    line-height: 30px; /* Or adjust to vertically center based on div height */
  }

  /* Default cluster size-based colors */
  :deep(.marker-cluster-small) { background-color: rgba(181, 226, 140, 0.7); }
  :deep(.marker-cluster-small div) { background-color: rgba(110, 204, 57, 0.8); color: white; }
  :deep(.marker-cluster-medium) { background-color: rgba(241, 211, 87, 0.7); }
  :deep(.marker-cluster-medium div) { background-color: rgba(240, 194, 12, 0.8); color: #333; }
  :deep(.marker-cluster-large) { background-color: rgba(253, 156, 115, 0.7); }
  :deep(.marker-cluster-large div) { background-color: rgba(241, 128, 23, 0.8); color: white; }

  /* Type-specific cluster colors */
  /* Crime: base rgb(252, 127, 127) from original app.css */
  :deep(.cluster-crime) { background-color: rgba(252, 127, 127, 0.5) !important; }
  :deep(.cluster-crime div) { background-color: rgba(222, 97, 97, 0.85) !important; color: white; }

  /* 311 Case: base rgb(59, 130, 246) */
  :deep(.cluster-311-case) { background-color: rgba(59, 130, 246, 0.5) !important; }
  :deep(.cluster-311-case div) { background-color: rgba(29, 100, 216, 0.85) !important; color: white; }

  /* Building Permit: base rgb(138, 231, 138) */
  :deep(.cluster-building-permit) { background-color: rgba(138, 231, 138, 0.5) !important; }
  :deep(.cluster-building-permit div) { background-color: rgba(108, 201, 108, 0.85) !important; color: #333; }

  /* Property Violation: base rgb(255, 255, 0) */
  :deep(.cluster-property-violation) { background-color: rgba(255, 255, 0, 0.5) !important; }
  :deep(.cluster-property-violation div) { background-color: rgba(225, 225, 0, 0.85) !important; color: #333; }

  /* Construction Off Hour: base rgb(114, 203, 209) */
  :deep(.cluster-construction-off-hour) { background-color: rgba(114, 203, 209, 0.5) !important; }
  :deep(.cluster-construction-off-hour div) { background-color: rgba(84, 173, 179, 0.85) !important; color: white; }

  /* Food Inspection: base rgb(255, 165, 0) */
  :deep(.cluster-food-inspection) { background-color: rgba(255, 165, 0, 0.5) !important; }
  :deep(.cluster-food-inspection div) { background-color: rgba(225, 135, 0, 0.85) !important; color: white; }
  
  /* Fallback for 'Unknown' or mixed types */
  :deep(.cluster-mixed) { background-color: rgba(169, 169, 169, 0.5) !important; }
  :deep(.cluster-mixed div) { background-color: rgba(128, 128, 128, 0.85) !important; color: white; }
  
  </style>