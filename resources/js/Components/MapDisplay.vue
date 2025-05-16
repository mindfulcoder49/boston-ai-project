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
  
  const CLUSTERING_DISTANCE_METERS = 1; // Distance in meters to consider points for clustering
  
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

  const getClusterIconInternal = (count) => {
    return L.divIcon({
      className: 'custom-cluster-div-icon',
      html: `<div>${count}</div>`,
      iconSize: null, // Size controlled by CSS using --icon-size
      popupAnchor: [0, -15], // Adjust if necessary, similar to other icons
    });
  };

  // Helper function to generate an HTMLElement for an individual data point's popup
  const createIndividualItemPopupElement = (dataPoint, onBackToClusterCallback = null) => {
    const container = document.createElement('div');
    container.className = 'custom-popup-content individual-item-popup';
    // Basic styling for the content wrapper, can be enhanced with CSS classes
    container.style.maxHeight = '250px'; 
    container.style.overflowY = 'auto';
    container.style.paddingRight = '10px'; // Space for scrollbar if needed

    const dateString = new Date(dataPoint.alcivartech_date).toLocaleString();
    let detailsHtml = `<div style="margin-bottom: 5px;"><strong>${dateString}</strong></div>`;
  
    switch (dataPoint.alcivartech_type) {
      case 'Crime':
        detailsHtml += `<div>Type: Crime</div><div>Description: ${dataPoint.offense_description || 'N/A'}</div>`;
        if (dataPoint.incident_number) {
          detailsHtml += `<div>Incident Number: ${dataPoint.incident_number}</div>`;
        }
        break;
      case '311 Case':
        detailsHtml += `<div>Type: 311 Case</div><div>Title: ${dataPoint.case_title || 'N/A'}</div>`;
        if (dataPoint.case_enquiry_id) {
          detailsHtml += `<div>Case ID: ${dataPoint.case_enquiry_id}</div>`;
        }
        break;
      case 'Building Permit':
        detailsHtml += `<div>Type: Building Permit</div><div>Description: ${dataPoint.description || 'N/A'}</div>`;
        if (dataPoint.permitnumber) {
          detailsHtml += `<div>Permit Number: ${dataPoint.permitnumber}</div>`;
        }
        if (dataPoint.permit_type) {
          detailsHtml += `<div>Permit Type: ${dataPoint.permit_type}</div>`;
        }
        break;
      case 'Property Violation':
        detailsHtml += `<div>Type: Property Violation</div><div>Description: ${dataPoint.description || 'N/A'}</div>`;
        if (dataPoint.ticket_number) {
          detailsHtml += `<div>Ticket Number: ${dataPoint.ticket_number}</div>`;
        }
        if (dataPoint.violation_type) {
          detailsHtml += `<div>Violation Type: ${dataPoint.violation_type}</div>`;
        }
        break;
      case 'Construction Off Hour':
        detailsHtml += `<div>Type: Construction (Off Hour)</div><div>Address: ${dataPoint.address || 'N/A'}</div>`;
        if (dataPoint.app_no) {
          detailsHtml += `<div>Application Number: ${dataPoint.app_no}</div>`;
        }
        break;
      default:
        detailsHtml += 'No details available.';
    }
    container.innerHTML = detailsHtml;
  
    if (onBackToClusterCallback) {
      const backButton = document.createElement('button');
      backButton.textContent = '‹ Back to Cluster List';
      backButton.className = 'popup-back-button'; // For styling
      // Basic styling for the back button
      backButton.style.marginTop = '10px';
      backButton.style.padding = '5px 10px';
      backButton.style.cursor = 'pointer';
      backButton.style.border = '1px solid #ccc';
      backButton.style.backgroundColor = '#f9f9f9';
      backButton.style.borderRadius = '3px';
      
      backButton.onclick = (e) => {
          e.stopPropagation(); // Prevent map click or other underlying events
          onBackToClusterCallback();
      };
      container.appendChild(backButton);
    }
    return container;
  };
  
  // Helper function to generate an HTMLElement for a cluster's list of items
  // ...existing code...
  const createClusterListPopupElement = (cluster, onItemClickCallback) => {
    const container = document.createElement('div');
    container.className = 'custom-popup-content cluster-list-popup';
    // Styling for the main container of the popup
    container.style.maxHeight = '250px'; // Max height before vertical scroll
    container.style.overflowY = 'auto';  // Enable vertical scroll for the whole list
    container.style.paddingRight = '5px'; // A little padding so scrollbar doesn't overlap content
    // container.style.width = 'auto'; // Let content determine width, Leaflet will constrain overall popup

    const title = document.createElement('strong');
    title.textContent = `Cluster (${cluster.count} items):`;
    title.style.display = 'block';
    title.style.marginBottom = '8px'; // Increased margin
    title.style.fontSize = '1.1em'; // Slightly larger title
    container.appendChild(title);
  
    const list = document.createElement('div');
    // list.style.listStyle = 'none'; // Not needed for divs
    list.style.paddingLeft = '0';
    list.style.marginTop = '0';
  
    cluster.points.forEach((dataPoint) => {
      const listItem = document.createElement('div');
      // listItem styling for flex layout
      listItem.style.display = 'flex';        // Use flexbox for alignment
      listItem.style.alignItems = 'center';   // Vertically align items in the center
      listItem.style.cursor = 'pointer';
      listItem.style.padding = '6px 2px'; // Adjusted padding
      listItem.style.borderBottom = '1px solid #eee';
      listItem.style.minWidth = '200px'; // Ensure a minimum width for better layout

      // Icon div
      const iconDiv = document.createElement('div');
      let iconClassName = '';
      if (dataPoint.alcivartech_type === 'Crime') {
        iconClassName = 'crime-div-icon';
      } else if (dataPoint.alcivartech_type === '311 Case') {
        iconClassName = 'case-div-icon';
        if (dataPoint.submitted_photo) {
          iconClassName += ' submitted-photo';
        } else if (dataPoint.closed_photo) {
          iconClassName += ' closed-photo';
        } else {
          iconClassName += ' no-photo';
        }
      } else if (dataPoint.alcivartech_type === 'Building Permit') {
        iconClassName = 'permit-div-icon';
      } else if (dataPoint.alcivartech_type === 'Property Violation') {
        iconClassName = 'property-violation-div-icon';
      } else if (dataPoint.alcivartech_type === 'Construction Off Hour') {
        iconClassName = 'construction-off-hour-div-icon';
      }
      iconDiv.className = iconClassName; // Apply base class
      iconDiv.style.width = 'var(--icon-size)';
      iconDiv.style.height = 'var(--icon-size)';
      iconDiv.style.flexShrink = '0'; // Prevent icon from shrinking

      const insideIconDiv = document.createElement('div');
      // insideIconDiv styling (already has background-size, position from CSS)
      // Ensure it fills the iconDiv
      insideIconDiv.style.width = '100%';
      insideIconDiv.style.height = '100%';

      if (dataPoint.alcivartech_type === '311 Case') {
        if (dataPoint.submitted_photo) {
          const photoURL = dataPoint.submitted_photo.split(' | ')[0];
          insideIconDiv.style.backgroundImage = `url(${photoURL})`;
        } else if (dataPoint.closed_photo) { // Use else if to prioritize submitted_photo
          const photoURL = dataPoint.closed_photo.split(' | ')[0];
          insideIconDiv.style.backgroundImage = `url(${photoURL})`;
        }
      }
      iconDiv.appendChild(insideIconDiv);
      listItem.appendChild(iconDiv);

      // Item preview text generation (no more substring)
      let itemPreviewText = '';
      if (dataPoint.alcivartech_type === 'Crime' && dataPoint.offense_description) {
        const idPart = dataPoint.incident_number ? `ID: ${dataPoint.incident_number} - ` : '';
        itemPreviewText = `${idPart}${dataPoint.offense_description}`;
      } else if (dataPoint.alcivartech_type === '311 Case' && dataPoint.case_title) {
        const idPart = dataPoint.case_enquiry_id ? `ID: ${dataPoint.case_enquiry_id} - ` : '';
        itemPreviewText = `${idPart}${dataPoint.case_title}`;
      } else if (dataPoint.alcivartech_type === 'Building Permit' && dataPoint.description) {
        const idPart = dataPoint.permitnumber ? `Permit: ${dataPoint.permitnumber} - ` : '';
        itemPreviewText = `${idPart}${dataPoint.description}`;
      } else if (dataPoint.alcivartech_type === 'Property Violation' && dataPoint.description) {
        const idPart = dataPoint.ticket_number ? `Ticket: ${dataPoint.ticket_number} - ` : '';
        itemPreviewText = `${idPart}${dataPoint.description}`;
      } else if (dataPoint.alcivartech_type === 'Construction Off Hour' && dataPoint.address) {
        const idPart = dataPoint.app_no ? `App: ${dataPoint.app_no} - ` : '';
        itemPreviewText = `${idPart}${dataPoint.address}`;
      }
      
      // Item preview div
      const itemPreviewDiv = document.createElement('div');
      itemPreviewDiv.textContent = itemPreviewText;
      itemPreviewDiv.style.marginLeft = '10px';    // Space between icon and text
      itemPreviewDiv.style.overflowX = 'auto';   // Enable horizontal scroll for this text
      itemPreviewDiv.style.whiteSpace = 'nowrap'; // Prevent text from wrapping
      itemPreviewDiv.style.flexGrow = '1';        // Allow text to take available space
      itemPreviewDiv.style.fontSize = '0.9rem';
      itemPreviewDiv.style.fontWeight = 'bold';
      itemPreviewDiv.style.color = '#333';
      // itemPreviewDiv.style.lineHeight = 'var(--icon-size)'; // Not needed with flex align-items: center

      // Add a scrollbar style for webkit browsers if desired
      // itemPreviewDiv.style.setProperty('-webkit-overflow-scrolling', 'touch'); // For smoother scrolling on iOS
      // You might need to add custom scrollbar CSS if you want to style it beyond default

      listItem.appendChild(itemPreviewDiv);
      listItem.title = `Click for details: ${itemPreviewText}`; // Show full text on hover

      listItem.onmouseover = () => { listItem.style.backgroundColor = '#f0f0f0'; };
      listItem.onmouseout = () => { listItem.style.backgroundColor = 'transparent'; };
  
      listItem.onclick = (e) => {
        e.stopPropagation(); 
        onItemClickCallback(dataPoint);
      };
      list.appendChild(listItem);
    });
    
    if(list.lastChild) {
      (list.lastChild).style.borderBottom = 'none';
    }

    container.appendChild(list);
    return container;
  };
// ...existing code...
  
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
  
    // Clear existing data point markers (includes individual and cluster markers)
    markers.value.forEach((marker) => initialMap.value.removeLayer(marker));
    markers.value = [];
  
    if (!dataPoints || dataPoints.length === 0) {
      return;
    }
  
    let clusters = []; // Stores { points: [], representativeLatLng: L.latLng, count: 0 }
  
    dataPoints.forEach(dataPoint => {
      if (!dataPoint.latitude || !dataPoint.longitude) return;
  
      const pointLatLng = L.latLng(dataPoint.latitude, dataPoint.longitude);
      let foundCluster = null;
  
      for (const cluster of clusters) {
        if (pointLatLng.distanceTo(cluster.representativeLatLng) < CLUSTERING_DISTANCE_METERS) {
          foundCluster = cluster;
          break;
        }
      }
  
      if (foundCluster) {
        foundCluster.points.push(dataPoint);
        foundCluster.count++;
        // Optional: Recalculate representativeLatLng (e.g., centroid).
        // For simplicity, keeping the first point's location as representative.
      } else {
        clusters.push({
          points: [dataPoint],
          representativeLatLng: pointLatLng, // Use this point's location as representative for the new cluster
          count: 1,
        });
      }
    });
  
    // Now, render clusters and single points
    clusters.forEach(cluster => {
      if (cluster.count > 1) {
        const clusterMarker = markRaw(
          L.marker(cluster.representativeLatLng, {
            icon: getClusterIconInternal(cluster.count),
          })
        );
        
        // Bind a popup that will be managed dynamically on 'popupopen'
        clusterMarker.bindPopup(null, { minWidth: 240 }); // Initial empty popup, options for size

        clusterMarker.on('popupopen', (popupEvent) => {
          const popup = popupEvent.popup;
          
          const showItemDetails = (dataPoint) => {
            const itemElement = createIndividualItemPopupElement(dataPoint, () => {
              // This is the "Back to Cluster" action
              showClusterList(); 
            });
            popup.setContent(itemElement);
            // Move popup to the individual item's location
            popup.setLatLng([dataPoint.latitude, dataPoint.longitude]);
            popup.update(); // Ensure Leaflet redraws the popup
            emit('marker-data-point-clicked', dataPoint);
          };

          const showClusterList = () => {
            const listElement = createClusterListPopupElement(cluster, (dataPoint) => {
              // This is the action when an item in the list is clicked
              showItemDetails(dataPoint);
            });
            popup.setContent(listElement);
            // Ensure popup is at the cluster's representative location
            popup.setLatLng(cluster.representativeLatLng);
            popup.update();
          };
          
          showClusterList(); // Initial view when popup opens
        });
  
        clusterMarker.addTo(initialMap.value);
        markers.value.push(clusterMarker);
      } else {
        // Single point, render as before but use the new popup helper
        const dataPoint = cluster.points[0];
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
        
        const individualPopupElement = createIndividualItemPopupElement(dataPoint, null); // No "back" button
        marker.bindPopup(individualPopupElement, { minWidth: 220 });

        marker.on('click', (e) => {
          // Popup opens automatically on click due to bindPopup.
          // We still emit for consistency or if parent needs to react to any marker click.
          emit('marker-data-point-clicked', dataPoint);
        });
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
  </style>