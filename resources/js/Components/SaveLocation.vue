<template>
<div class="p-4 border rounded-lg shadow hover:shadow-lg transition-shadow bg-white">
<button
    class="text-lg font-medium text-white bg-slate-700 hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 py-2 px-4 rounded-md shadow-md flex items-center justify-center cursor-pointer transition-colors duration-150 ease-in-out w-full md:m-auto md:w-1/3"
    @click="toggleExpanded"
  >
  <!-- add icon depending on if expanded-->
    <span :class="isExpanded ? 'rotate-0' : '-rotate-90'" class="inline-block transition-transform duration-300">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
      </svg>
    </span>

    <span class="ml-2">
      {{ translations.localizationLabelsByLanguageCode[getSingleLanguageCode].savedLocationsTitle || 'Manage Saved Locations' }}
    </span>
  </button>
</div>
    <!-- Toggle Button, arrow points right when collapsed and down when expanded -->


  <div class="w-full my-2 p-4 bg-white shadow-lg rounded-lg save-location"
        :class="{
          'hidden': !isExpanded,
          'block': isExpanded,
        }"
        >


    <!-- Tab Navigation -->
    <div class="flex border-b border-gray-200">
      <!-- Current Location Tab -->
      <button
        @click="setActiveTab('current')"
        :class="['px-4 py-3 text-sm font-medium leading-5 focus:outline-none transition-colors duration-150 ease-in-out', activeTab === 'current' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-gray-700 hover:border-gray-300 hover:border-b-2']"
      >
        {{ translations.LocationLabelsByLanguageCode[getSingleLanguageCode].currentLocation }}
      </button>

      <!-- Saved Locations Tabs -->
      <template v-if="userLocations.length">
        <button
          v-for="(savedLocation) in userLocations"
          :key="savedLocation.id"
          @click="setActiveTab(savedLocation.id)"
          :class="['px-4 py-3 text-sm font-medium leading-5 focus:outline-none transition-colors duration-150 ease-in-out', activeTab === savedLocation.id ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-gray-700 hover:border-gray-300 hover:border-b-2']"
        >
          {{ capitalize(savedLocation.name) }}
        </button>
      </template>
    </div>

    <!-- Tab Content -->
    <div class="mt-6">
      <!-- Tab Content: Current Location -->
      <div v-if="activeTab === 'current'" class="current-location">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">
          {{ translations.LocationLabelsByLanguageCode[getSingleLanguageCode].currentLocation }}
        </h3>

        <div class="text-sm text-gray-700 mb-4 space-y-1">
          <div><strong>Lat:</strong> {{ location.latitude }}</div>
          <div><strong>Lng:</strong> {{ location.longitude }}</div>
          <div v-if="location.address">
            <strong>{{ translations.LocationLabelsByLanguageCode[getSingleLanguageCode].address }}:</strong> {{ location.address }}
          </div>
        </div>

        <div class="space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label for="current-location-report" class="block text-sm font-medium text-gray-700">{{ translations.LocationLabelsByLanguageCode[getSingleLanguageCode].report }}</label>
              <select
                id="current-location-report"
                v-model="location.report"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
              >
                <option value="off">{{ translations.LocationLabelsByLanguageCode[getSingleLanguageCode].off }}</option>
                <option value="daily">{{ translations.LocationLabelsByLanguageCode[getSingleLanguageCode].daily }}</option>
                <option value="weekly">{{ translations.LocationLabelsByLanguageCode[getSingleLanguageCode].weekly }}</option>
              </select>
            </div>
            <div>
              <label for="current-location-name" class="block text-sm font-medium text-gray-700">{{ translations.LocationLabelsByLanguageCode[getSingleLanguageCode].selectName }}</label>
              <select
                id="current-location-name"
                v-model="selectedName"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
              >
                <option value="home">{{ translations.LocationLabelsByLanguageCode[getSingleLanguageCode].home }}</option>
                <option value="work">{{ translations.LocationLabelsByLanguageCode[getSingleLanguageCode].work }}</option>
                <option value="other">{{ translations.LocationLabelsByLanguageCode[getSingleLanguageCode].other }}</option>
              </select>
            </div>
          </div>
          <div>
            <label for="current-location-language" class="block text-sm font-medium text-gray-700">{{ translations.LocationLabelsByLanguageCode[getSingleLanguageCode].language }}</label>
            <input
              type="text"
              id="current-location-language"
              v-model="location.language"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
              :placeholder="translations.LocationLabelsByLanguageCode[getSingleLanguageCode].language"
            />
          </div>
          <div class="mt-6">
            <button
              v-if="isAuthenticated"
              @click="saveLocation"
              class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
              :disabled="saving"
            >
              {{ saving ? translations.LocationLabelsByLanguageCode[getSingleLanguageCode].saving : translations.LocationLabelsByLanguageCode[getSingleLanguageCode].saveLocation }}
            </button>
            <div v-else class="flex flex-col space-y-2 items-center">
              <a :href="route('socialite.redirect', 'google') + '?redirect_to=' + currentPath"
                 class="flex items-center justify-center w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                <img class="h-5 w-5 mr-2" src="https://upload.wikimedia.org/wikipedia/commons/c/c1/Google_%22G%22_logo.svg" alt="Google logo">
                {{ translations.LocationLabelsByLanguageCode[getSingleLanguageCode].loginWithGoogleToSave || 'Login with Google to Save' }}
              </a>
              <Link :href="route('login') + '?redirect_to=' + currentPath" class="text-sm text-blue-600 hover:underline">
                {{ translations.LocationLabelsByLanguageCode[getSingleLanguageCode].loginManuallyToSave || 'Or login manually to save' }}
              </Link>
            </div>
            <span v-if="maxLocationsReached && isAuthenticated" class="mt-2 block text-sm text-red-500 text-center">
              {{ translations.LocationLabelsByLanguageCode[getSingleLanguageCode].maxLocationsReached }}
            </span>
          </div>
        </div>
      </div>

      <!-- Tab Content: Individual Saved Locations -->
      <template v-else-if="userLocations.length">
        <div
          v-for="savedLocation in userLocations"
          :key="savedLocation.id"
          v-show="activeTab === savedLocation.id"
          class="saved-location"
        >
          <h3 class="text-lg font-semibold text-gray-800 mb-4">
            {{ capitalize(savedLocation.name) }} - {{ translations.LocationLabelsByLanguageCode[getSingleLanguageCode].savedLocation }}
          </h3>
          <div class="p-3 bg-gray-50 rounded-md shadow-sm text-sm text-gray-700 mb-4 space-y-1">
            <div><strong>Lat:</strong> {{ savedLocation.latitude }}</div>
            <div><strong>Lng:</strong> {{ savedLocation.longitude }}</div>
            <div v-if="savedLocation.address">
              <strong>{{ translations.LocationLabelsByLanguageCode[getSingleLanguageCode].address }}:</strong> {{ savedLocation.address }}
            </div>
          </div>

          <div class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label :for="'saved-location-report-' + savedLocation.id" class="block text-sm font-medium text-gray-700">{{ translations.LocationLabelsByLanguageCode[getSingleLanguageCode].report }}</label>
                <select
                  :id="'saved-location-report-' + savedLocation.id"
                  v-model="savedLocation.report"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                >
                  <option value="off">{{ translations.LocationLabelsByLanguageCode[getSingleLanguageCode].off }}</option>
                  <option value="daily">{{ translations.LocationLabelsByLanguageCode[getSingleLanguageCode].daily }}</option>
                  <option value="weekly">{{ translations.LocationLabelsByLanguageCode[getSingleLanguageCode].weekly }}</option>
                </select>
              </div>
              <div>
                <label :for="'saved-location-name-' + savedLocation.id" class="block text-sm font-medium text-gray-700">{{ translations.LocationLabelsByLanguageCode[getSingleLanguageCode].selectName }}</label>
                <select
                  :id="'saved-location-name-' + savedLocation.id"
                  v-model="savedLocation.name"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                >
                  <option value="home">{{ translations.LocationLabelsByLanguageCode[getSingleLanguageCode].home }}</option>
                  <option value="work">{{ translations.LocationLabelsByLanguageCode[getSingleLanguageCode].work }}</option>
                  <option value="other">{{ translations.LocationLabelsByLanguageCode[getSingleLanguageCode].other }}</option>
                </select>
              </div>
            </div>
            <div>
              <label :for="'saved-location-language-' + savedLocation.id" class="block text-sm font-medium text-gray-700">{{ translations.LocationLabelsByLanguageCode[getSingleLanguageCode].language }}</label>
              <input
                type="text"
                :id="'saved-location-language-' + savedLocation.id"
                v-model="savedLocation.language"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                :placeholder="translations.LocationLabelsByLanguageCode[getSingleLanguageCode].language"
              />
            </div>
            <div class="mt-6">
              <button
                @click="updateLocation(savedLocation.id, savedLocation)"
                class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
              >
                {{ translations.LocationLabelsByLanguageCode[getSingleLanguageCode].update }}
              </button>
            </div>
          </div>

          <div class="mt-6 grid grid-cols-1 sm:grid-cols-3 gap-3">
            <button
              @click="emitLocation(savedLocation)"
              class="w-full flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            >
              {{ translations.LocationLabelsByLanguageCode[getSingleLanguageCode].load }}
            </button>
            <button
              @click="deleteLocation(savedLocation.id)"
              class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
            >
              {{ translations.LocationLabelsByLanguageCode[getSingleLanguageCode].delete }}
            </button>
            <button
              @click="dispatchReport(savedLocation)"
              class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
            >
              {{ translations.LocationLabelsByLanguageCode[getSingleLanguageCode].sendReport }}
            </button>
          </div>
          <div v-if="reportDispatched && activeTab === savedLocation.id" class="mt-3 text-sm text-green-600 text-center">
            {{ translations.LocationLabelsByLanguageCode[getSingleLanguageCode].reportSent }}
          </div>
        </div>
      </template>

      <!-- No Saved Locations Fallback -->
      <div v-else-if="activeTab !== 'current' && !userLocations.find(loc => loc.id === activeTab)" class="py-6 text-center">
        <p class="text-md text-gray-600">
          {{ translations.LocationLabelsByLanguageCode[getSingleLanguageCode].noSavedLocations }}
        </p>
      </div>
    </div>
  </div>
</template>


<script setup>
import { ref, watch, onMounted, computed } from 'vue';
import axios from 'axios';
import { usePage, Link } from '@inertiajs/vue3';

// Props
const props = defineProps({
  location: {
    type: Object,
    required: true,
  },
  language_codes: {
    type: Array,
    required: true,
  },
  translations: {
    type: Object,
    required: true,
  },
});

// Emit
const emit = defineEmits(['load-location']);

// Reactive States
const page = usePage();
const isAuthenticated = computed(() => !!page.props.auth.user);
const currentPath = computed(() => window.location.pathname + window.location.search);

const selectedName = ref('home');
const isSaved = ref(false);
const saving = ref(false);
const userLocations = ref([]);
const activeTab = ref('current');
const reportDispatched = ref(false);
const maxLocationsReached = ref(false);
const isExpanded = ref(false);

const toggleExpanded = () => {
  isExpanded.value = !isExpanded.value;
};

// Methods
/*
Route::post('/locations/{location}/dispatch-report', [LocationController::class, 'dispatchLocationReportEmail'])->name('locations.dispatch-report');
*/

const dispatchReport = async (location) => {
  try {
    await axios.post(`/locations/${location.id}/dispatch-report`);
    reportDispatched.value = true;
    //set the reportDispatched value to false after 5 seconds
    setTimeout(() => {
      reportDispatched.value = false;
    }, 3000);
  } catch (error) {
    console.error('Error dispatching report:', error);
  }
};


const setActiveTab = (tab) => {
  activeTab.value = tab;
};

const fetchUserLocations = async (mode) => {
  try {
    const response = await axios.get('/locations');
    userLocations.value = response.data;
    checkIfSaved();
    if (mode === 'set' && userLocations.value.length) {
      emitLocation(userLocations.value[0]);
      //set the active tab to the first location
      setActiveTab(userLocations.value[0].id);
    }
  } catch (error) {
    console.error('Error fetching locations:', error);
  }
};

const checkIfSaved = () => {
  isSaved.value = userLocations.value.some(
    (loc) =>
      loc.latitude === location.latitude && loc.longitude === location.longitude
  );
};

const saveLocation = async () => {
  
  if (!isAuthenticated.value) {
    // This case should ideally be handled by the UI showing login buttons,
    // but as a fallback, redirect.
    window.location.href = route('login') + '?redirect_to=' + currentPath.value;
    return;
  }

  //if (isSaved.value || saving.value) return;

  saving.value = true;
  try {
    const payload = {
      name: selectedName.value,
      latitude: props.location.latitude,
      longitude: props.location.longitude,
      address: props.location.address || null,
      report: props.location.report || 'off',
      language: props.location.language || 'English',
    };
    const response = await axios.post('/locations', payload);
    userLocations.value.push(response.data);
    isSaved.value = true;
    saving.value = false;
    maxLocationsReached.value = false; // Reset on successful save
  } catch (error) {
    if (error.response && error.response.status === 401) {
      window.location.href = route('login') + '?redirect_to=' + currentPath.value;
    } else if (error.response && error.response.status === 403) {
      maxLocationsReached.value = true;
    } else {
      console.error('Error saving location:', error);
    }
    saving.value = false;
  }
};

const updateLocation = async (id, payload) => {
  try {
    await axios.put(`/locations/${id}`, payload);
  } catch (error) {
    console.error('Error updating location:', error);
  }
};

const deleteLocation = async (id) => {
  try {
    await axios.delete(`/locations/${id}`);
    userLocations.value = userLocations.value.filter((loc) => loc.id !== id);
    checkIfSaved();
    //select the current location tab after deleting a location
    setActiveTab('current');
  } catch (error) {
    console.error('Error deleting location:', error);
  }
};

const emitLocation = (location) => {
  emit('load-location', location);
};

// Utilities
const capitalize = (str) => str[0].toUpperCase() + str.slice(1);



const getSingleLanguageCode = computed(() => props.language_codes[0]);

// Watchers
watch(() => location, checkIfSaved);

// Lifecycle Hooks, onmounted fetchUserLocation and emit the first location
onMounted(() => {
  fetchUserLocations('set');
  //if props.location.report is not set, set it to 'off'
  if (!props.location.report) {
    props.location.report = 'off';
    props.location.language = 'English';
  }
});
</script>

<style scoped>
button[disabled] {
  cursor: not-allowed;
  opacity: 0.7;
}

/* Removed old .tab-button styles as Tailwind classes are now used directly */
</style>
