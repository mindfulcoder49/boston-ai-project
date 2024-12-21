<template>
  <div class="w-full my-2 text-center px-3 py-2 border border-gray-300 save-location">
    <!-- Tab Navigation -->
    <div class="flex justify-center border-b border-gray-300">
      <!-- Current Location Tab -->
      <button
        @click="setActiveTab('current')"
        :class="{ 'tab-button': true, 'active': activeTab === 'current' }"
      >
        Current Location
      </button>

      <!-- Saved Locations Tabs -->
      <template v-if="userLocations.length">
        <button
          v-for="(savedLocation, index) in userLocations"
          :key="savedLocation.id"
          @click="setActiveTab(savedLocation.id)"
          :class="{ 'tab-button': true, 'active': activeTab === savedLocation.id }"
        >
          {{ capitalize(savedLocation.name) }}
        </button>
      </template>
    </div>

    <!-- Tab Content: Current Location -->
    <div v-if="activeTab === 'current'" class="mb-6 w-full current-location p-3">
      <h4 class="text-lg font-medium text-gray-700 mb-2">Current Location</h4>
      <div class="flex flex-wrap justify-center items-center gap-2">
        <span class="text-gray-600">Lat: {{ location.latitude }}</span>
        <span class="text-gray-600">Lng: {{ location.longitude }}</span>
        <span v-if="location.address" class="text-gray-600">Address: {{ location.address }}</span>
      </div>

      <div class="mt-4 flex flex-wrap justify-center items-center gap-2">
        <select
          id="location-name"
          v-model="selectedName"
          class="px-3 py-2 shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm pl-2 pr-8"
        >
          <option value="home">Home</option>
          <option value="work">Work</option>
          <option value="other">Other</option>
        </select>
        <button
          :disabled="isSaved || saving"
          @click="saveLocation"
          class="px-4 py-2 text-white shadow-sm transition-colors"
          :class="isSaved ? 'bg-gray-400 cursor-not-allowed' : 'bg-green-500 hover:bg-green-600'"
        >
          {{ isSaved ? 'Location Saved' : saving ? 'Saving...' : 'Save Location' }}
        </button>
      </div>
    </div>

    <!-- Tab Content: Individual Saved Locations -->
    <template v-else-if="userLocations.length">
      <div
        v-for="savedLocation in userLocations"
        :key="savedLocation.id"
        v-show="activeTab === savedLocation.id"
        class="w-full saved-location p-3"
      >
        <h4 class="text-lg font-medium text-gray-700 mb-2">Saved Location</h4>
        <div class="bg-gray-50 p-3 rounded-md shadow-sm flex space-x-4 items-center">
          <p class="font-sm text-gray-800">{{ capitalize(savedLocation.name) }}</p>
          <p class="text-sm text-gray-600">Lat: {{ savedLocation.latitude }}</p>
          <p class="text-sm text-gray-600">Lng: {{ savedLocation.longitude }}</p>
          <p v-if="savedLocation.address" class="text-sm text-gray-600">Address: {{ savedLocation.address }}</p>
        </div>

        <div class="mt-4 flex justify-center gap-2">
          <button
            @click="emitLocation(savedLocation)"
            class="px-4 py-2 bg-blue-500 text-white shadow-sm hover:bg-blue-600 transition-colors"
          >
            Load
          </button>
          <button
            @click="deleteLocation(savedLocation.id)"
            class="px-4 py-2 bg-red-500 text-white shadow-sm hover:bg-red-600 transition-colors"
          >
            Delete
          </button>
        </div>
      </div>
    </template>

    <!-- No Saved Locations Fallback -->
    <div v-else class="w-full saved-location p-3">
      <h4 class="text-lg font-medium text-gray-700 mb-2">No Saved Locations</h4>
      <p class="text-gray-500">You haven’t saved any locations yet. Save your current location to get started.</p>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue';
import axios from 'axios';

// Props
defineProps({
  location: {
    type: Object,
    required: true,
  },
});

// Emit
const emit = defineEmits(['load-location']);

// Reactive States
const selectedName = ref('home');
const isSaved = ref(false);
const saving = ref(false);
const userLocations = ref([]);
const activeTab = ref('current');

// Methods
const setActiveTab = (tab) => {
  activeTab.value = tab;
};

const fetchUserLocations = async () => {
  try {
    const response = await axios.get('/locations');
    userLocations.value = response.data;
    checkIfSaved();
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
  if (isSaved.value || saving.value) return;

  saving.value = true;
  try {
    const payload = {
      name: selectedName.value,
      latitude: location.latitude,
      longitude: location.longitude,
      address: location.address || null,
    };
    const response = await axios.post('/locations', payload);
    userLocations.value.push(response.data);
    isSaved.value = true;
    saving.value = false;
  } catch (error) {
    if (error.response.status === 401) {
      window.location.href = '/login';
    }
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

// Watchers
watch(() => location, checkIfSaved);

// Lifecycle Hooks
onMounted(fetchUserLocations);
</script>

<style scoped>
button[disabled] {
  cursor: not-allowed;
}

.tab-button {
  padding: 10px 15px;
  border: none;
  background-color: #f9f9f9;
  cursor: pointer;
  border-bottom: 2px solid transparent;
  transition: background-color 0.3s, border-bottom-color 0.3s;
}

.tab-button.active {
  border-bottom: 2px solid #3490dc;
  background-color: #ffffff;
}

.tab-button:hover {
  background-color: #f0f0f0;
}
</style>
