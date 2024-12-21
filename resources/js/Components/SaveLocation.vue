<template>
  <div class="w-full my-2 text-center flex justify-center px-3 py-2 border border-gray-300">
      <!-- Current Location Details -->
      <div class="mb-6 w-1/2">
          <h4 class="text-lg font-medium text-gray-700 mb-2">Current Location</h4>
          <div class="flex flex-wrap justify-center items-center gap-2">
              <span class="text-gray-600">Lat: {{ location.latitude }}</span>
              <span class="text-gray-600">Lng: {{ location.longitude }}</span>
              <span v-if="location.address" class="text-gray-600">Address: {{ location.address }}</span>
          </div>

          <div class="mt-4 flex flex-wrap justify-center items-center gap-2">
              <label for="location-name" class="text-sm font-medium text-gray-700">
                  Location Name
              </label>
               <select
                  id="location-name"
                  v-model="selectedName"
                  class="px-3 py-2 shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500  sm:text-sm pl-2 pr-8 "
               >
               <option value="home">Home</option>
               <option value="work">Work</option>
               <option value="other">Other</option>
               </select>
              <button
                  :disabled="isSaved || saving"
                  @click="saveLocation"
                  class="px-4 py-2 text-white   shadow-sm transition-colors"
                  :class="isSaved ? 'bg-gray-400 cursor-not-allowed' : 'bg-green-500 hover:bg-green-600'"
              >
                  {{ isSaved ? 'Location Saved' : saving ? 'Saving...' : 'Save Location' }}
              </button>
          </div>
      </div>

      <!-- Saved Locations -->
      <div class="w-1/2">
          <h4 class="text-lg font-medium text-gray-700 mb-3">Saved Locations</h4>
          <ul v-if="userLocations.length" class="bg-gray-50  shadow-sm  divide-y">
              <li v-for="(savedLocation, index) in userLocations" :key="savedLocation.id"
                  class="flex items-center justify-between py-3 px-4 flex-wrap">
                  <div class="flex flex-col items-start  space-y-1">
                          <div class="flex flex-wrap items-center">
                            <p class="font-medium text-gray-800 mr-2">{{ savedLocation.name[0].toUpperCase() + savedLocation.name.slice(1) }}</p>
                              <span class="text-sm text-gray-600 mr-2">Lat: {{ savedLocation.latitude }}</span>
                              <span class="text-sm text-gray-600 mr-2">Lng: {{ savedLocation.longitude }}</span>
                              <span v-if="savedLocation.address" class="text-sm text-gray-600 mr-2">
                                  Address: {{ savedLocation.address }}
                              </span>
                          </div>
                  </div>
                  <div class="flex space-x-2">
                      <button @click="emitLocation(savedLocation)"
                          class="px-3 py-1 bg-blue-500 text-white   shadow-sm hover:bg-blue-600 transition-colors">
                          Load
                      </button>
                      <button @click="deleteLocation(savedLocation.id)"
                          class="px-3 py-1 bg-red-500 text-white  shadow-sm hover:bg-red-600 transition-colors">
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

              //if the error is a 401, redirect to the login page
              if (error.response.status === 401) {
                  window.location.href = '/login';
              }
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