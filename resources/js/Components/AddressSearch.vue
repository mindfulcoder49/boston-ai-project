<template>
  <div class="">
    <input
      type="text"
      v-model="searchQuery"
      @input="searchAddresses"
      placeholder="Enter address to change location or just click Choose New Center"
      class="border w-full"
    />

    <ul v-if="results.length" class="border p-2 bg-white shadow">
      <li 
        v-for="(result, index) in results" 
        :key="index" 
        @click="selectAddress(result)"
        class="cursor-pointer hover:bg-gray-100 p-2 rounded"
      >
        {{ result.full_address }} - ({{ result.x_coord }}, {{ result.y_coord }})
      </li>
    </ul>

    <p v-if="!results.length && searchQuery" class="text-gray-500 mt-2">No results found.</p>
  </div>
</template>

<script>
import axios from "axios";

export default {
  props: ["initialSearchQuery"],
  data() {
    return {
      searchQuery: this.initialSearchQuery || "",
      results: [],
    };
  },
  methods: {
    async searchAddresses() {
      if (this.searchQuery.length >= 1) {
        try {
          const response = await axios.get("/search-address", {
            params: { address: this.searchQuery },
          });
          this.results = response.data.data;
        } catch (error) {
          console.error("Error fetching address data:", error);
        }
      } else {
        this.results = [];
      }
    },
    selectAddress(address) {
      const location = {
        lat: address.y_coord,
        lng: address.x_coord,
        address: address.full_address, // Include the full address
      };
      this.$emit("address-selected", location); // Emit the coordinates and address
    },
  },
};
</script>

<style scoped>
input {
  margin-bottom: 10px;
}
ul {
  max-height: 200px;
  overflow-y: auto;
}
li {
  padding: 8px;
  border-bottom: 1px solid #e0e0e0;
}
li:last-child {
  border-bottom: none;
}
</style>
