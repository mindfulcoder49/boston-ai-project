<template>
    <div class="p-4 bg-gray-100 rounded-lg shadow-md">
      <h3 class="text-lg font-bold text-gray-800">Manage Locations</h3>
  
      <!-- Current Location Details -->
      <div class="mt-4">
        <h4 class="text-md font-semibold text-gray-800">Current Location</h4>
        <div class="flex flex-wrap items-center gap-2">
          <p class="text-gray-700">Lat: {{ location.latitude }}</p>
          <p class="text-gray-700">Lng: {{ location.longitude }}</p>
          <p v-if="location.address" class="text-gray-700">Address: {{ location.address }}</p>
        </div>
  
        <div class="mt-2 flex flex-wrap items-center gap-2">
          <label for="location-name" class="text-sm font-medium text-gray-700">
            Location Name
          </label>
          <select
            id="location-name"
            v-model="selectedName"
            class="px-2 py-1 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
          >
            <option value="home">Home</option>
            <option value="work">Work</option>
            <option value="other">Other</option>
          </select>
          <button
            :disabled="isSaved || saving"
            @click="saveLocation"
            class="px-4 py-2 text-white rounded-lg shadow-lg transition-colors"
            :class="isSaved ? 'bg-gray-400 cursor-not-allowed' : 'bg-green-500 hover:bg-green-600'"
          >
            {{ isSaved ? 'Location Saved' : saving ? 'Saving...' : 'Save Location' }}
          </button>
        </div>
      </div>
  
      <!-- Saved Locations -->
      <div class="mt-4">
        <h4 class="text-md font-semibold text-gray-800">Saved Locations</h4>
        <ul v-if="userLocations.length" class="mt-2 border rounded p-2 bg-white shadow">
          <li
            v-for="(savedLocation, index) in userLocations"
            :key="savedLocation.id"
            class="flex flex-wrap items-center justify-between p-2 border-b last:border-none"
          >
            <div class="flex flex-wrap items-center gap-2 pr-5">
              <p class="font-bold">{{ savedLocation.name }}</p>
              <p class="text-sm text-gray-600">Lat: {{ savedLocation.latitude }}</p>
              <p class="text-sm text-gray-600">Lng: {{ savedLocation.longitude }}</p>
              <p v-if="savedLocation.address" class="text-sm text-gray-600">
                Address: {{ savedLocation.address }}
              </p>
            </div>
            <div class="flex space-x-2 mt-2 md:mt-0">
              <button
                @click="emitLocation(savedLocation)"
                class="px-2 py-1 bg-blue-500 text-white rounded-md shadow hover:bg-blue-600"
              >
                Load
              </button>
              <button
                @click="deleteLocation(savedLocation.id)"
                class="px-2 py-1 bg-red-500 text-white rounded-md shadow hover:bg-red-600"
              >
                Delete
              </button>
            </div>
          </li>
        </ul>
        <p v-else class="text-gray-500 mt-2">No saved locations found</p>
      </div>
    </div>
  </template>
  
  
  <script>
  import { ref, watch, onMounted } from "vue";
  import axios from "axios";
  
  export default {
    name: "SaveLocation",
    props: {
      location: {
        type: Object,
        required: true,
      },
    },
    emits: ["load-location"], // Emit event for loading a location
    setup(props, { emit }) {
      const selectedName = ref("home"); // Default name
      const isSaved = ref(false); // Tracks if the location is already saved
      const saving = ref(false); // Tracks if the save request is in progress
      const userLocations = ref([]); // List of the user's saved locations
  
      // Fetch the user's saved locations on mount
      const fetchUserLocations = async () => {
        try {
          const response = await axios.get("/locations");
          userLocations.value = response.data;
          checkIfSaved();
        } catch (error) {
          console.error("Error fetching locations:", error);
        }
      };
  
      // Check if the current location is already saved
      const checkIfSaved = () => {
        isSaved.value = userLocations.value.some(
          (loc) =>
            loc.latitude === props.location.latitude &&
            loc.longitude === props.location.longitude
        );
      };
  
      // Save the current location
      const saveLocation = async () => {
        if (isSaved.value || saving.value) return;
  
        saving.value = true;
        try {
          const payload = {
            name: selectedName.value,
            latitude: props.location.latitude,
            longitude: props.location.longitude,
            address: props.location.address || null, // Include address if available
          };
          const response = await axios.post("/locations", payload);
          userLocations.value.push(response.data); // Add the new location to the list
          isSaved.value = true;
          saving.value = false;
        } catch (error) {
          console.error("Error saving location:", error);
          saving.value = false;
        }
      };
  
      // Delete a saved location
      const deleteLocation = async (id) => {
        try {
          await axios.delete(`/locations/${id}`);
          userLocations.value = userLocations.value.filter((loc) => loc.id !== id); // Remove from list
          checkIfSaved(); // Recheck saved status for the current location
        } catch (error) {
          console.error("Error deleting location:", error);
        }
      };
  
      // Emit a location to the parent component
      const emitLocation = (location) => {
        emit("load-location", location);
      };
  
      // Watch for changes in the location prop to re-check saved state
      watch(() => props.location, checkIfSaved);
  
      // Fetch the user's locations on mount
      onMounted(fetchUserLocations);
  
      return {
        selectedName,
        isSaved,
        saving,
        saveLocation,
        deleteLocation,
        emitLocation,
        userLocations,
      };
    },
  };
  </script>
  
  <style scoped>
  button[disabled] {
    cursor: not-allowed;
  }
  </style>
  