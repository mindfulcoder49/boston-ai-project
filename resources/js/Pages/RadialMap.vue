<template>
  <PageTemplate>
    <Head>
      <title>Home</title>
    </Head>

    <SubscriptionBanner />

    <div class="before-map">
      <h1 class="text-2xl font-bold text-gray-800 text-center my-4">{{ translations.LabelsByLanguageCode[getSingleLanguageCode]?.pageTitle }}

      </h1>

      <LanguageSelector
        :languageButtonLabels="languageButtonLabels"
        :currentLanguageCodes="language_codes"
        @language-code-selected="handleLanguageCodeSelected"
      />
      <FoodInspectionTeaser
        :language_codes="language_codes"
        :dataPoints="dataPoints"
        :isAuthenticated="isAuthenticated"
        />

      <CenterManagement
        :centralLocation="centralLocation"
        :tempNewCenterForDisplay="tempNewMapClickCoords"
        :isCenterSelectionActive="centerSelectionActive"
        :language_codes="language_codes"
        :translations="translations"
        :singleLanguageCode="getSingleLanguageCode"
        @toggle-center-selection-mode="handleToggleCenterSelection"
        @address-search-coordinates-selected="handleAddressSearchUpdate"
        @trigger-form-submit="submitNewCenter"
        @load-saved-location="handleLoadLocation"
      />
    </div>

    
    <div class="map-controls-container">
      <MapDisplay
        ref="mapDisplayRef"
        :mapCenterCoordinates="mapCenter"
        :dataPointsToDisplay="dataPoints"
        :isCenterSelectionModeActive="centerSelectionActive"
        :tempNewMarkerPlacementCoords="tempNewMapClickCoords"
        :mapIsLoading="mapLoading"
        :shouldClearTempMarker="cancelNewMarker"
        @map-coordinates-selected-for-new-center="handleMapClickForNewCenter"
        @marker-data-point-clicked="handleMarkerClick"
        @map-initialized-internal="isMapInitialized = true"
      />

        <MapFiltersControl
            :initialFilterTypeState="filters"
            :initialSelectedDates="selectedDates"
            :minDateForFilter="minDate"
            :maxDateForFilter="maxDate"
            :translations="translations"
            :singleLanguageCode="getSingleLanguageCode"
            @filters-updated="handleFiltersUpdated"
        />
  </div>
        
  

    <div class="case-details">
      <h2 class="text-xl font-semibold text-gray-800 text-center my-4">{{ translations.LabelsByLanguageCode[getSingleLanguageCode]?.caseDetailsTitle || 'Selected Case Details' }}</h2>
      <UniversalDataDisplay :data="selectedDataPoint" :language_codes="language_codes" />
    </div>

    <ImageCarousel :dataPoints="dataPoints" @on-image-click="handleImageClick" />

    <AiAssistant 
      :context="filteredDataPoints" 
      :language_codes="language_codes" 
      :centralLocation="centralLocation"
      :radius="reportRadius"
      :currentMapLanguage="currentReportLanguage"
    ></AiAssistant>
    <GenericDataList :totalData="filteredDataPoints" :itemsPerPage="8" @handle-goto-marker="handleListClick" :language_codes="language_codes" />

  </PageTemplate>
</template>

<script setup>
import { ref, computed, onMounted, watch, nextTick, inject } from 'vue';
import axios from 'axios';
import PageTemplate from '@/Components/PageTemplate.vue';
import AiAssistant from '@/Components/AiAssistant.vue';
import GenericDataList from '@/Components/GenericDataList.vue';
import UniversalDataDisplay from '@/Components/UniversalDataDisplay.vue';
import ServiceCase from '@/Components/ServiceCase.vue';
import Crime from '@/Components/Crime.vue';
import BuildingPermit from '@/Components/BuildingPermit.vue';
import PropertyViolation from '@/Components/PropertyViolation.vue';
import OffHours from '@/Components/OffHours.vue';
import { Head } from '@inertiajs/vue3';
import ImageCarousel from '@/Components/ImageCarousel.vue';
import SubscriptionBanner from '@/Components/SubscriptionBanner.vue';

import LanguageSelector from '@/Components/LanguageSelector.vue';
import CenterManagement from '@/Components/CenterManagement.vue';
import MapDisplay from '@/Components/MapDisplay.vue';
import MapFiltersControl from '@/Components/MapFiltersControl.vue';
import FoodInspection from '@/Components/FoodInspection.vue';
import FoodInspectionTeaser from '@/Components/FoodInspectionTeaser.vue';
import { usePage } from '@inertiajs/vue3'; // Import usePage

const page = usePage(); // Get page instance

const mapDisplayRef = ref(null);

const filters = ref({});
const allDataPoints = ref([]);
const dataPoints = ref([]);
const centralLocation = ref({
  latitude: 42.3601,
  longitude: -71.0589,
  address: 'Boston, MA',
});
const reportRadius = ref(0.25); // Default radius for reports, can be made dynamic
const centerSelectionActive = ref(false);
const tempNewMapClickCoords = ref(null);
const mapCenter = ref([centralLocation.value.latitude, centralLocation.value.longitude]);
const cancelNewMarker = ref(false);
const selectedDates = ref([]);
const minDate = ref('');
const maxDate = ref('');
const selectedDataPoint = ref(null);
const isMapInitialized = ref(false);
const mapLoading = ref(false);

const isAuthenticated = computed(() => !!page.props.auth.user); // Compute isAuthenticated

const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
const translations = inject('translations');
const language_codes = ref(['en-US']);

const aggregateFoodViolations = (dataPoints) => {
  const foodViolations = dataPoints.filter(dp => dp.alcivartech_type === 'Food Inspection');
  const otherDataPoints = dataPoints.filter(dp => dp.alcivartech_type !== 'Food Inspection');

  if (foodViolations.length === 0) {
    return otherDataPoints;
  }

  const groupedByLicense = foodViolations.reduce((acc, viol) => {
    const key = viol.licenseno;
    if (!key) { 
        otherDataPoints.push(viol); 
        return acc;
    }
    if (!acc[key]) {
      acc[key] = [];
    }
    acc[key].push(viol);
    return acc;
  }, {});

  const aggregatedFoodViolations = Object.values(groupedByLicense).map(licenseGroup => {
    if (licenseGroup.length === 0) return null;

    // Sort by date to find the most recent record for representative data (address, name, etc.)
    licenseGroup.sort((a, b) => new Date(b.alcivartech_date) - new Date(a.alcivartech_date));
    const mostRecentRecord = licenseGroup[0];

    // Filter for actual violations within the group (those having violdttm)
    // Assumes violdttm indicates an actual violation record, vs. just an inspection.
    const actualViolationEntries = licenseGroup.filter(viol => viol.violdttm);

    let violationSummary = null; 

    if (actualViolationEntries.length > 0) {
      // Create a summary of all actual violations under this license
      const violationSummaryMap = actualViolationEntries.reduce((acc, viol) => {
        // violdesc should ideally exist if violdttm exists for a violation
        const descKey = viol.violdesc || 'Unknown Violation Description'; 
        if (!acc[descKey]) {
          acc[descKey] = {
            violdesc: descKey,
            entries: []
          };
        }
        acc[descKey].entries.push({
          alcivartech_date: viol.alcivartech_date, // Date of the specific violation entry
          viol_status: viol.viol_status,
          comments: viol.comments,
          result: viol.result, 
          viol_level: viol.viol_level,
          // food_violation_id: viol.food_violation_id // original ID if needed
        });
        return acc;
      }, {});
      
      const tempViolationSummary = Object.values(violationSummaryMap).map(summaryItem => {
          summaryItem.entries.sort((a,b) => new Date(b.alcivartech_date) - new Date(a.alcivartech_date));
          return summaryItem;
      });
      tempViolationSummary.sort((a,b) => a.violdesc.localeCompare(b.violdesc));
      violationSummary = tempViolationSummary;
    }

    const aggregatedPoint = {
      ...mostRecentRecord, 
      alcivartech_type: "Food Inspection", 
      alcivartech_date: mostRecentRecord.alcivartech_date, 
      _is_aggregated_food_violation: true 
    };

    if (violationSummary) {
      aggregatedPoint.violation_summary = violationSummary;
    }
    // If violationSummary remains null (because actualViolationEntries was empty),
    // the aggregatedPoint will not have the violation_summary property.
    // The FoodInspection.vue component should then handle it as a record
    // without a violation summary (e.g., an aggregated inspection record).

    return aggregatedPoint;
  }).filter(Boolean); 

  return [...otherDataPoints, ...aggregatedFoodViolations];
};


const languageButtonLabels = {
  'en-US': { select: '✓ English', deselect: '✕ English' },
  'es-MX': { select: '✓ Español', deselect: '✕ Español' },
  'zh-CN': { select: '✓ 中文', deselect: '✕ 中文' },
  'ht-HT': { select: '✓ Kreyòl Ayisyen', deselect: '✕ Kreyòl Ayisyen' },
  'vi-VN': { select: '✓ Tiếng Việt', deselect: '✕ Tiếng Việt' },
  'pt-BR': { select: '✓ Português', deselect: '✕ Português' },
};

const getSingleLanguageCode = computed(() => {
  return language_codes.value[0];
});

const currentReportLanguage = computed(() => {
  const locale = language_codes.value[0] || 'en-US';
  // Map to backend-compatible language codes
  // The backend validation is: 'en,es,fr,pt,zh-CN,ht,vi,km,ar,el,it,ru,ko,ja,pl'
  const mapping = {
    'en-US': 'en',
    'es-MX': 'es',
    'zh-CN': 'zh-CN', // Already compatible
    'ht-HT': 'ht',
    'vi-VN': 'vi',
    'pt-BR': 'pt',
    // Add other mappings as needed based on your UI language_codes and backend support
  };
  return mapping[locale] || 'en'; // Default to 'en' if no specific mapping
});

const handleLanguageCodeSelected = (code) => {
  if (language_codes.value.includes(code)) {
    // Original logic: commented out removeLanguageCode(code);
  } else {
    language_codes.value = [code];
    // Original logic: commented out fetchData if code === 'en-US'
  }
  // if language change should trigger data refetch, call fetchData() here
};

const handleToggleCenterSelection = () => {
  centerSelectionActive.value = !centerSelectionActive.value;
  if (!centerSelectionActive.value) { // Selection cancelled or completed
    tempNewMapClickCoords.value = null; 
    cancelNewMarker.value = true; // Signal MapDisplay to clear its temp marker
    nextTick(() => { cancelNewMarker.value = false; }); // Reset flag
  } else {
    cancelNewMarker.value = false;
  }
};

const handleMapClickForNewCenter = (latlng) => {
    if (centerSelectionActive.value) {
        tempNewMapClickCoords.value = { lat: latlng.lat, lng: latlng.lng }; // For display and temp marker
        
        centralLocation.value.latitude = latlng.lat;
        centralLocation.value.longitude = latlng.lng;
        centralLocation.value.address = `${latlng.lat.toFixed(3)}, ${latlng.lng.toFixed(3)}`;
        mapCenter.value = [latlng.lat, latlng.lng];
        
        if (mapDisplayRef.value) mapDisplayRef.value.destroyMapAndClear();
        fetchData().then(() => {
            if (mapDisplayRef.value) mapDisplayRef.value.initializeNewMapAtCenter(mapCenter.value);
        });
        // centerSelectionActive.value = false; // Optionally auto-disable selection mode
    }
};

const handleAddressSearchUpdate = (coordinates) => {
    centralLocation.value.latitude = coordinates.lat;
    centralLocation.value.longitude = coordinates.lng;
    if (coordinates.address) {
        centralLocation.value.address = coordinates.address;
    } else {
        centralLocation.value.address = coordinates.lat + ', ' + coordinates.lng;
    }
    mapCenter.value = [coordinates.lat, coordinates.lng];
    if (mapDisplayRef.value) mapDisplayRef.value.destroyMapAndClear();
    fetchData().then(() => {
        if (mapDisplayRef.value) mapDisplayRef.value.initializeNewMapAtCenter([coordinates.lat, coordinates.lng], true);
    });
};



const handleLoadLocation = (location) => {
    centralLocation.value.latitude = location.latitude;
    centralLocation.value.longitude = location.longitude;
    if (location.address) {
      centralLocation.value.address = location.address;
    } else {
      centralLocation.value.address = location.latitude + ', ' + location.longitude;
    }
    mapCenter.value = [location.latitude, location.longitude];
    if (mapDisplayRef.value) mapDisplayRef.value.destroyMapAndClear();
    fetchData().then(() => {
        if (mapDisplayRef.value) mapDisplayRef.value.initializeNewMapAtCenter([location.latitude, location.longitude], true);
    });
    // Potentially update reportRadius if location object contains a preferred radius
    // For example: if (location.preferred_radius) reportRadius.value = location.preferred_radius;
};

const submitNewCenter = () => { // This was tied to an empty form originally
  fetchData();
};

const fetchData = async () => {
  try {
    mapLoading.value = true;
    const requestLanguageCodes = ['en-US']; // Original behavior forced en-US for API
    const response = await axios.post('/api/map-data', {
      centralLocation: centralLocation.value,
      language_codes: requestLanguageCodes,
    }, {
      headers: { 'X-CSRF-TOKEN': csrfToken },
    });

    allDataPoints.value = response.data.dataPoints;

    // keep top level fields and merge in the correct data type subobject and delete all subobjects
    allDataPoints.value = allDataPoints.value.map((dataPoint) => {
      const dataType = dataPoint.alcivartech_type;
      console.log('Data type:', dataType.toLowerCase() + '_data');
      // Log the subobjects for debugging
      console.log('Data point:', dataPoint);
      
      const subObject = dataPoint[dataType.toLowerCase().replace(/ /g, '_').replace('311','three_one_one') + '_data'];
      console.log('Sub-object:', subObject);
      if (subObject) {
        // Remove the sub-object from the dataPoint
        //delete dataPoint[dataType.toLowerCase() + '_data'];
        // Merge top-level fields with the sub-object
        console.log('Sub-object:', subObject);
        return { ...dataPoint, ...subObject };
      }
      
      return dataPoint; // Return as is if no sub-object
    })
    // Aggregate food violations after fetching
    allDataPoints.value = aggregateFoodViolations(allDataPoints.value);

    updateDateRange();
    populateFilters(); // Initialize filters based on new data
    applyFiltersAndData(); 
    if (allDataPoints.value.length > 0) {
      selectedDataPoint.value = allDataPoints.value[0];
    }
    
    // Ensure map resizes if its container changed, etc.
    setTimeout(() => {
       if (mapDisplayRef.value && mapDisplayRef.value.getMapInstance()) {
            mapDisplayRef.value.getMapInstance().invalidateSize();
        }
    }, 100);
    mapLoading.value = false;

    // Async fetch live 311 details


  } catch (error) {
    console.error('Error fetching data:', error);
    mapLoading.value = false;
    if (error.response && error.response.status === 419) window.location.reload();
  } finally {
    mapLoading.value = false;
    setTimeout(() => {
      fetchLiveData(); // Fetch live data after initial data load
    }, 1000); // Delay to ensure initial data is set
  }
};

const fetchLiveData = async () => {
  if (allDataPoints.value && allDataPoints.value.length > 0) {
      const threeOneOneCases = allDataPoints.value.filter(
        dp => dp.alcivartech_type === '311 Case' && dp.case_enquiry_id
      );
      if (threeOneOneCases.length > 0) {
        //caseEnquiryIds is an array of case_enquiry_ids as strings
        const caseEnquiryIds = threeOneOneCases.map(dp => dp.case_enquiry_id.toString());
        try {
          /*
            const liveDetailsResponse = await axios.post('/api/311-case/live-multiple', {
            case_enquiry_ids: caseEnquiryIds,
            }, { headers: { 'X-CSRF-TOKEN': csrfToken } });
            */
           //fetch the data, but if the response is a 429 error, wait 5 seconds and try again
          const liveDetailsResponse = await axios.post('/api/311-case/live-multiple', {
            case_enquiry_ids: caseEnquiryIds,
            }, { headers: { 'X-CSRF-TOKEN': csrfToken } });
            if (liveDetailsResponse.status === 429) {
                console.error('Rate limit exceeded, retrying in 5 seconds...');
                setTimeout(() => {
                    fetchLiveData();
                }, 5000);
                return;
            }

            const liveDataArray = liveDetailsResponse.data.data;
            if (liveDataArray && Array.isArray(liveDataArray)) {
            const liveDataMap = new Map();
            liveDataArray.forEach(liveCase => {
                if (liveCase.service_request_id) {
                liveDataMap.set(liveCase.service_request_id.toString(), liveCase);
                }
            });
            allDataPoints.value = allDataPoints.value.map(dp => {
                if (dp.alcivartech_type === '311 Case' && dp.case_enquiry_id) {
                const liveDetail = liveDataMap.get(dp.case_enquiry_id.toString());
                if (liveDetail) return { ...dp, live_details: liveDetail };
                }
                return dp;
            });
            applyFiltersAndData(); // Re-apply filters with new live data
            if (selectedDataPoint.value && selectedDataPoint.value.alcivartech_type === '311 Case' && selectedDataPoint.value.case_enquiry_id) {
                const liveDetailForSelected = liveDataMap.get(selectedDataPoint.value.case_enquiry_id.toString());
                if (liveDetailForSelected) {
                selectedDataPoint.value = { ...selectedDataPoint.value, live_details: liveDetailForSelected };
                }
            }
            }
        } catch (liveError) {
            console.error('Error fetching live 311 case details:', liveError);
            if (liveError.response && liveError.response.status === 419) window.location.reload();
        }
      }
    }
  }

const updateDateRange = () => {
  if (allDataPoints.value.length === 0) {
    minDate.value = '';
    maxDate.value = '';
    return;
  }
  const dates = allDataPoints.value.map((point) => new Date(point.alcivartech_date));
  minDate.value = new Date(Math.min(...dates)).toISOString().split('T')[0];
  maxDate.value = new Date(Math.max(...dates)).toISOString().split('T')[0];
  console.log('Date range updated:', minDate.value, maxDate.value);
};

const populateFilters = () => {
  const newFilters = {};
  allDataPoints.value.forEach((dataPoint) => {
    if (newFilters[dataPoint.alcivartech_type] === undefined) {
      // If a filter type already exists in filters.value, preserve its state
      newFilters[dataPoint.alcivartech_type] = filters.value[dataPoint.alcivartech_type] !== undefined 
                                                ? filters.value[dataPoint.alcivartech_type] 
                                                : true;
    }
  });
  filters.value = newFilters; // This will be passed to MapFiltersControl
};

const handleFiltersUpdated = (newFilterState) => {
  filters.value = { ...newFilterState.activeTypes };
  selectedDates.value = [...newFilterState.selectedDates];
  applyFiltersAndData();
};

const applyFiltersAndData = () => {
  if (allDataPoints.value.length === 0) {
    dataPoints.value = [];
    return;
  }
  let filtered = allDataPoints.value;
  if (selectedDates.value.length > 0) {
    filtered = filtered.filter(point => {
      const pointDate = new Date(point.alcivartech_date).toISOString().split('T')[0];
      return selectedDates.value.includes(pointDate);
    });
  }
  dataPoints.value = filtered.filter(point => filters.value[point.alcivartech_type]);
};


const filteredDataPoints = computed(() => {
  return dataPoints.value;
});

const handleMarkerClick = (dataPoint) => {
  selectedDataPoint.value = dataPoint;
};

const handleImageClick = (data) => {
  selectedDataPoint.value = data; // Assuming data is {info: dataPoint}
  const mapInstance = mapDisplayRef.value?.getMapInstance();
  const currentMarkers = mapDisplayRef.value?.getMarkers();
  if (mapInstance && currentMarkers) {
    currentMarkers.forEach((marker) => {
      if (marker.options.icon.options.className.includes('id'+data.data_point_id)) {
        marker.openPopup();
      } 
    });
  }
};

const handleListClick = (data) => {
  selectedDataPoint.value = data;
  const mapInstance = mapDisplayRef.value?.getMapInstance();
  const currentMarkers = mapDisplayRef.value?.getMarkers();
  if (mapInstance && currentMarkers) {
    currentMarkers.forEach((marker) => {
        if (marker.options.icon.options.className.includes('id'+data.data_point_id)) {
            marker.openPopup();
            // scroll to popup
            const popupElement = marker.getPopup().getElement();
            if (popupElement) {
                 popupElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        } 
    });
  }
};

watch(centralLocation, (newLoc) => {
    mapCenter.value = [newLoc.latitude, newLoc.longitude];
}, { deep: true });




onMounted(() => {
  fetchData(); 
});

</script>

<style scoped>
/* Styles from original Home.vue that are page-specific */
.page-div {
  display: flex;
  flex-wrap: wrap;
  justify-content: space-between;
}
  .map-controls-container { /* New container for MapFiltersControl and ImageCarousel */
    width: 100%;
    display: flex;
    flex-direction:row;
  }
  .case-details {
    width: 100%;  /* Assuming this was the intended layout alongside map controls */
  }
  .boston-map { /* This class is now inside MapDisplay.vue, ensure it is styled from there or globally if needed */
    width: 80%; 
    height: 70vh
  }

/* Ensure MapDisplay's root element takes up appropriate space if these styles were critical */
/* .boston-map in MapDisplay.vue has its own media queries for height */
</style>