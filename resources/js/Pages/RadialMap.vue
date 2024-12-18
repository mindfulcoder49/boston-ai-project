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
    
    <!-- Save New Center button -->
    <button 
      type="submit" 
      :disabled="!centerSelected" 
      class="px-4 py-2 text-white bg-green-500 disabled:bg-gray-400 hover:bg-green-600 transition-colors w-1/2"
    >
      Select New Center
    </button>
  </div>
  <div class="flex space-x-4" v-if="isAuthenticated">
    <!-- SaveLocation Component -->
    <SaveLocation
      :location="form.centralLocation"
      @load-location="handleLoadLocation"
    />

  </div>
  <div class="flex space-x-4" v-else>
    <p class="text-gray-700 mt-4 mt-8 text-lg leading-relaxed text-center">
      Log in to save locations
    </p>
  </div>
</form>



        


    <!-- Boston Map Component -->
    <BostonMap 
      v-if="showMap"
      :dataPoints="filteredDataPoints" 
      :center="mapCenter" 
      :centralLocation="centralLocation"
      :centerSelectionActive="centerSelectionActive" 
      :cancelNewMarker="cancelNewMarker"
      @map-click="setNewCenter" 
      @marker-click="displayDataPoint" 
    />



    <div>

       <!-- Date Slider and Manual Input -->
<div class="date-filter-container mt-4 mb-4 flex flex-col w-full">
  <div class="flex items-center w-full space-x-4">
    <label for="date-range" class="text-m font-bold w-1/5">Filter by Date:</label>
    <div class=" w-1/5"    >
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
    <div class="flex items-center space-x-1 min-[400px]:w-1/3 w-full min-[400px]:justify-end  pr-2">
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

<script>
import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3'; // Import Inertia form helper
import BostonMap from '../Components/BostonMap.vue';
import PageTemplate from '@/Components/PageTemplate.vue';
import AiAssistant from '@/Components/AiAssistant.vue';
import GenericDataList from '@/Components/GenericDataList.vue';
import JsonTree from '@/Components/JsonTree.vue';
import { map } from 'leaflet';
import AddressSearch from '@/Components/AddressSearch.vue'; // Import the new component
import { Head, Link } from '@inertiajs/vue3';
import DataPointDetails from '@/Components/DataPointDetails.vue';
import SaveLocation from '@/Components/SaveLocation.vue';
import { usePage } from "@inertiajs/vue3"; 
import ServiceCase from '@/Components/ServiceCase.vue';
import Crime from '@/Components/Crime.vue';
import BuildingPermit from '@/Components/BuildingPermit.vue';

export default {
  name: 'RadialMap',
  components: { 
    BostonMap, 
    PageTemplate, 
    AiAssistant, 
    GenericDataList, 
    JsonTree, 
    AddressSearch, 
    Head, 
    Link, 
    DataPointDetails, 
    SaveLocation,
    ServiceCase,
    Crime,
    BuildingPermit,
   },
  props: ['dataPoints', 'centralLocation', 'auth', 'codes'],
  
  setup(props) {
    const filters = ref({});
    const centerSelectionActive = ref(false);
    const centerSelected = ref(false);
    const newCenter = ref(null);
    const newMarker = ref(null); // Track the new center marker
    const mapCenter = ref([props.centralLocation.latitude, props.centralLocation.longitude]);
    const cancelNewMarker = ref(false);
    const showMap = ref(true);

    const selectedDate = ref('');
    const minDate = ref('');
    const maxDate = ref('');
    const dayOffset = ref(0);
    const showAllDates = ref(true);
    const selectedDataPoint = ref(null);

    const isAuthenticated = computed(() => !!props.auth?.user);

    const displayDataPoint = (dataPoint) => {
      selectedDataPoint.value = dataPoint;
    };

    // Calculate the number of days between the minDate and maxDate
    const daysBetweenMinAndMax = ref(0);
    
    // Initialize date range based on the dataPoints
    if (props.dataPoints.length > 0) {
      const dates = props.dataPoints.map(dataPoint => new Date(dataPoint.date));
      minDate.value = dates.reduce((a, b) => a < b ? a : b).toISOString().split('T')[0];
      maxDate.value = dates.reduce((a, b) => a > b ? a : b).toISOString().split('T')[0];
      selectedDate.value = minDate.value;

      // Calculate the number of days between minDate and maxDate
      const minDateObj = new Date(minDate.value);
      const maxDateObj = new Date(maxDate.value);
      daysBetweenMinAndMax.value = Math.ceil((maxDateObj - minDateObj) / (1000 * 60 * 60 * 24));
    }

    // Update the selectedDate based on the slider's day offset
    const updateDateFromSlider = () => {
      const minDateObj = new Date(minDate.value);
      const newDate = new Date(minDateObj.getTime() + dayOffset.value * (1000 * 60 * 60 * 24));
      selectedDate.value = newDate.toISOString().split('T')[0];
    };

    // Update the slider's offset based on the manually inputted date
    const updateSliderFromInput = () => {
      const minDateObj = new Date(minDate.value);
      const selectedDateObj = new Date(selectedDate.value);
      dayOffset.value = Math.floor((selectedDateObj - minDateObj) / (1000 * 60 * 60 * 24));
    };

    // Filter dataPoints based on selected date
    const filteredDataPoints = computed(() => {
      if (showAllDates.value) {
        return props.dataPoints.filter(dataPoint => filters.value[dataPoint.type]);
      }
      const myDataPoints = props.dataPoints.filter(dataPoint => {
        return new Date(dataPoint.date).toISOString().split('T')[0] === selectedDate.value;
      });
      return myDataPoints.filter(dataPoint => filters.value[dataPoint.type]);
    });

    // Use Inertia form for submitting centralLocation
    const form = useForm({
      centralLocation: {
        latitude: props.centralLocation.latitude,
        longitude: props.centralLocation.longitude,
        address: props.centralLocation.address || 'Address not found',
      },
    });

    // Function to reload the map by toggling visibility
    const reloadMap = () => {
      showMap.value = false;    // Remove the map from the DOM
      setTimeout(() => {
        showMap.value = true;   // Re-add the map after a small delay
      }, 0);  // Use a 0 ms delay to force the rerender
    };

    // Populate filters with unique types from dataPoints
    props.dataPoints.forEach((dataPoint) => {
      if (!filters.value[dataPoint.type]) {
        filters.value[dataPoint.type] = true;
      }
    });


    // Toggle filter state
    const toggleFilter = (type) => {
      filters.value[type] = !filters.value[type];
    };

    

    const toggleCenterSelection = () => {
      if (centerSelectionActive.value) {
        // Cancel behavior: deactivate center selection and remove the marker
        centerSelectionActive.value = false;
        centerSelected.value = false;
        newCenter.value = null;

        // Emit the cancel event by setting `cancelNewMarker` to true
        cancelNewMarker.value = true;

        // If a marker is present, remove it from the map
        if (newMarker.value) {
          newMarker.value = null;
        }
      } else {
        // Enable center selection
        centerSelectionActive.value = true;
        centerSelected.value = false;
        newCenter.value = null;
        cancelNewMarker.value = false; // Reset the cancel state
      }
    };


    // Set new center on map click and place a marker
    const setNewCenter = (latlng) => {
      if (centerSelectionActive.value) {
        newCenter.value = latlng;
        centerSelected.value = true;
        form.centralLocation.latitude = latlng.lat;
        form.centralLocation.longitude = latlng.lng;

        // Emit the new marker position to BostonMap component
        // This ensures the new marker is updated correctly
        newMarker.value = latlng;
        mapCenter.value = [latlng.lat, latlng.lng];
      }
    };

    // Submit the new center to the backend
    const submitNewCenter = () => {
      form.post('/', {
        preserveScroll: true,
        onSuccess: () => {
          centerSelectionActive.value = false;
          centerSelected.value = false;
          console.log('props.centralLocation:', props.centralLocation);
          mapCenter.value = [props.centralLocation.latitude, props.centralLocation.longitude];
          // Optionally clear the marker here if necessary
        }
      });
    };

    const updateCenterCoordinates = ({ lat, lng, address }) => {
      console.log('Updating center coordinates:', { lat, lng, address });

      // Update the form's central location
      form.centralLocation.latitude = lat;
      form.centralLocation.longitude = lng;
      form.centralLocation.address = address || 'Address not found';

      // Call setNewCenter to update the map's center and state
      setNewCenter({ lat, lng });

      // Call submitNewCenter to persist the changes
      submitNewCenter();
    };

    const handleLoadLocation = (location) => {
      console.log("Loaded location:", location);
      // Update the center or perform any other action with the loaded location

      // call updateCenterCoordinates to update the map's center and state
      updateCenterCoordinates({ lat: location.latitude, lng: location.longitude, address: location.address });
    };



    return {
      filters,
      filteredDataPoints,
      toggleFilter,
      toggleCenterSelection,
      setNewCenter,
      submitNewCenter,
      centerSelectionActive,
      centerSelected,
      newCenter,
      form,
      mapCenter,
      cancelNewMarker,
      newMarker,
      showMap,
      reloadMap,
      selectedDate,
      minDate,
      maxDate,
      daysBetweenMinAndMax,
      updateDateFromSlider,
      updateSliderFromInput,
      dayOffset,
      showAllDates,
      updateCenterCoordinates,
      displayDataPoint,
      selectedDataPoint,
      handleLoadLocation,
      isAuthenticated,
    };
  },
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
  display:none;
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
