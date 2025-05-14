<template>
  <div>
    <!-- Engaging Title -->
    <h2 class="text-2xl font-bold text-center text-gray-800 mb-4">
      {{ translations.localizationLabelsByLanguageCode[singleLanguageCodeToUse].setYourHubTitle || 'Pinpoint Your Hub!' }}
    </h2>
    <p class="text-center text-gray-600 mb-6">
      {{ translations.localizationLabelsByLanguageCode[singleLanguageCodeToUse].setYourHubDescription || 'Set your central location to personalize your map experience. Choose your preferred method below:' }}
    </p>

    <!-- Coordinates Display -->
    <div v-if="tempNewCenterForDisplay" class="p-4 bg-green-50 border border-green-300 rounded-lg shadow text-center mb-6">
      <p class="font-bold text-green-800">{{ translations.localizationLabelsByLanguageCode[singleLanguageCodeToUse].newCenterPreviewTitle || 'New Center Preview:' }}</p>
      <p class="text-green-700">{{ tempNewCenterForDisplay.lat }}, {{ tempNewCenterForDisplay.lng }}</p>
      <!-- Removed confirm prompt as confirmation is now implicit -->
    </div>
    <div v-else-if="centralLocation && centralLocation.latitude && centralLocation.longitude" class="p-4 bg-blue-50 border border-blue-300 rounded-lg shadow text-center mb-6">
      <p class="font-bold text-blue-800">{{ translations.localizationLabelsByLanguageCode[singleLanguageCodeToUse].currentSavedCenterTitle || 'Current Saved Center:' }}</p>
      <p class="text-blue-700">{{ centralLocation.latitude }}, {{ centralLocation.longitude }}</p>
    </div>
    <div v-else class="p-4 bg-gray-100 rounded-lg shadow text-center mb-6">
      <p class="font-bold text-gray-800">{{ translations.localizationLabelsByLanguageCode[singleLanguageCodeToUse].noCenterSetTitle || 'No central location set yet.' }}</p>
      <p class="text-gray-600">{{ translations.localizationLabelsByLanguageCode[singleLanguageCodeToUse].setupPrompt || "Let's get one set up!" }}</p>
    </div>

    <!-- Action Methods - Revamped Layout -->
    <div class="mb-8">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <!-- Method 1: Use Current Location -->
        <div class="p-4 border rounded-lg shadow hover:shadow-lg transition-shadow bg-white">
          <button
            type="button"
            @click="requestCurrentLocation"
            :disabled="geolocationLoading"
            class="w-full px-4 py-3 text-white bg-indigo-600 rounded-lg shadow-md hover:bg-indigo-700 transition-colors flex items-center justify-center space-x-2 disabled:bg-gray-400"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" /></svg>
            <span>{{ geolocationLoading ? (translations.localizationLabelsByLanguageCode[singleLanguageCodeToUse].locatingButton || 'Locating...') : (translations.localizationLabelsByLanguageCode[singleLanguageCodeToUse].useCurrentLocationButton || 'Use My Current Location') }}</span>
          </button>
          <p v-if="geolocationError" class="text-red-500 text-sm text-center mt-2">{{ geolocationError }}</p>
        </div>

        <!-- Method 2: Search Address -->
        <div class="p-4 border rounded-lg shadow hover:shadow-lg transition-shadow bg-white">
          <AddressSearch @address-selected="handleAddressSelected" :language_codes="language_codes" :placeholder_text="translations.localizationLabelsByLanguageCode[singleLanguageCodeToUse].searchAddressPlaceholder || 'Search for an address'" />
        </div>

        <!-- Method 3: Click on Map -->
        <div class="p-4 border rounded-lg shadow hover:shadow-lg transition-shadow bg-white">
          <button
            type="button"
            @click="handleToggleCenterSelection"
            class="w-full px-4 py-3 text-white rounded-lg shadow-md transition-colors flex items-center justify-center space-x-2"
            :class="isCenterSelectionActive ? 'bg-red-500 hover:bg-red-600' : 'bg-teal-500 hover:bg-teal-600'"
          >
            <svg v-if="!isCenterSelectionActive" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M17.293 3.293A1 1 0 0118 4v12a1 1 0 01-1.707.707L10 10.414l-6.293 6.293A1 1 0 012 16V4a1 1 0 011.707-.707L10 9.586l6.293-6.293a1 1 0 011.000 0z" clip-rule="evenodd" /></svg>
            <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L10 8.586 7.707 6.293a1 1 0 00-1.414 1.414L8.586 10l-2.293 2.293a1 1 0 001.414 1.414L10 11.414l2.293 2.293a1 1 0 001.414-1.414L11.414 10l2.293-2.293z" clip-rule="evenodd" /></svg>
            <span>{{ isCenterSelectionActive ? (translations.localizationLabelsByLanguageCode[singleLanguageCodeToUse].cancelMapSelectionButton || 'Cancel Map Selection') : (translations.localizationLabelsByLanguageCode[singleLanguageCodeToUse].selectByMapClickButton || 'Select by Clicking on Map') }}</span>
          </button>
          <p v-if="isCenterSelectionActive" class="text-center text-blue-600 mt-2 animate-pulse">
            {{ translations.localizationLabelsByLanguageCode[singleLanguageCodeToUse].clickOnMapInstruction || 'Now, click on the map to choose your center!' }}
          </p>
        </div>
      </div>
    </div>
    
    <!-- Removed Form to Confirm/Submit -->
    
    <!-- The SaveLocation component remains -->
    <div class="mt-10 border-t pt-6">
      <h3 class="text-lg font-semibold text-gray-700 mb-3 text-center">{{ translations.localizationLabelsByLanguageCode[singleLanguageCodeToUse].savedLocationsTitle || 'Manage Saved Locations' }}</h3>
      <SaveLocation :location="centralLocation" :language_codes="language_codes" @load-location="handleLoadSavedLocation" />
    </div>
  </div>
</template>

<script setup>
import { defineProps, defineEmits, computed, ref } from 'vue';
import AddressSearch from '@/Components/AddressSearch.vue';
import SaveLocation from '@/Components/SaveLocation.vue';

const props = defineProps({
  centralLocation: Object,
  tempNewCenterForDisplay: Object,
  isCenterSelectionActive: Boolean,
  language_codes: Array,
  translations: Object,
  singleLanguageCode: String,
});

const emit = defineEmits([
  'toggle-center-selection-mode',
  'address-search-coordinates-selected',
  'load-saved-location',
]);

const singleLanguageCodeToUse = computed(() => props.singleLanguageCode || 'en-US');

const geolocationLoading = ref(false);
const geolocationError = ref(null);

const requestCurrentLocation = () => {
  if (navigator.geolocation) {
    geolocationLoading.value = true;
    geolocationError.value = null;
    navigator.geolocation.getCurrentPosition(
      (position) => {
        const coordinates = {
          lat: position.coords.latitude,
          lng: position.coords.longitude,
        };
        emit('address-search-coordinates-selected', coordinates);
        geolocationLoading.value = false;
      },
      (error) => {
        console.error("Geolocation error: ", error);
        let message = translations.localizationLabelsByLanguageCode[singleLanguageCodeToUse.value]?.geolocationGenericError || 'Could not retrieve your location.';
        if (error.code === error.PERMISSION_DENIED) {
          message = translations.localizationLabelsByLanguageCode[singleLanguageCodeToUse.value]?.geolocationPermissionDeniedError || 'Permission denied. Please enable location services in your browser settings.';
        } else if (error.code === error.POSITION_UNAVAILABLE) {
          message = translations.localizationLabelsByLanguageCode[singleLanguageCodeToUse.value]?.geolocationPositionUnavailableError || 'Location information is unavailable.';
        } else if (error.code === error.TIMEOUT) {
          message = translations.localizationLabelsByLanguageCode[singleLanguageCodeToUse.value]?.geolocationTimeoutError || 'The request to get user location timed out.';
        }
        geolocationError.value = message;
        geolocationLoading.value = false;
      }
    );
  } else {
    geolocationError.value = translations.localizationLabelsByLanguageCode[singleLanguageCodeToUse.value]?.geolocationNotSupportedError || 'Geolocation is not supported by this browser.';
  }
};

const handleAddressSelected = (coordinates) => {
  geolocationError.value = null; // Clear geolocation error if address is selected
  emit('address-search-coordinates-selected', coordinates);
};

const handleToggleCenterSelection = () => {
  emit('toggle-center-selection-mode');
};

const handleLoadSavedLocation = (location) => {
  emit('load-saved-location', location);
};
</script>