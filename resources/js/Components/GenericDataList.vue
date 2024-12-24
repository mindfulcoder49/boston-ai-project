<template>
  <div className="w-full">
    <!-- Pagination Controls -->
    <div class="flex justify-between items-center mt-4 mb-4 ">
      <button
        @click="prevPage"
        :disabled="currentPage === 1"
        class="p-2 bg-blue-500 text-white rounded-lg shadow-lg hover:bg-blue-600 w-1/4 sm:w-1/6 disabled:bg-gray-300"
      >
        Previous
      </button>
      <div class="flex items-center">
        <span class="mr-2">Page</span>
        <input
          v-model.number="inputPage"
          @change="goToPage"
          type="number"
          min="1"
          :max="totalPages"
          class="w-16 p-1 border rounded-md text-center"
        />
        <span class="ml-2">of {{ totalPages }}</span>
      </div>
      <button
        @click="nextPage"
        :disabled="currentPage === totalPages"
        class="p-2 bg-blue-500 text-white rounded-lg shadow-lg hover:bg-blue-600 w-1/4 sm:w-1/6 disabled:bg-gray-300"
      >
        Next
      </button>
    </div>

    <!-- No Results Message -->
    <div v-if="paginatedData.length === 0" class="text-center text-gray-500">
      No results found
    </div>

    <!-- Data List -->
    <div v-else class="flex flex-wrap">
      <div v-for="(item, index) in paginatedData" :key="index" class="p-4 bg-white w-full sm:w-1/2 md:w-1/3 lg:w-1/4">
        <div class="bg-gray-100 p-2 rounded-md mb-2">
          <ServiceCase v-if="item.type === '311 Case'" :data="item" :language_codes="language_codes" />
          <Crime v-if="item.type === 'Crime'" :data="item" :language_codes="language_codes" />
          <BuildingPermit v-if="item.type === 'Building Permit'" :data="item" :language_codes="language_codes" />
          <PropertyViolation v-if="item.type === 'Property Violation'" :data="item" :language_codes="language_codes" />
          <OffHours v-if="item.type === 'Construction Off Hour'" :data="item" :language_codes="language_codes" />

          <!-- Button to emit datapoint for goto marker on map function -->
          <button
            @click="$emit('handle-goto-marker', item)"
            class="p-2 bg-blue-500 text-white hover:bg-blue-600 find-button mb-4 ml-4"
          >
            <!-- add svg image from imagees/find_on_map.svg -->
            <img src="/images/find_on_map.svg" alt="Find on Map" class="w-10 h-10" />
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import ServiceCase from "@/Components/ServiceCase.vue";
import Crime from "@/Components/Crime.vue";
import BuildingPermit from "@/Components/BuildingPermit.vue";
import PropertyViolation from "@/Components/PropertyViolation.vue";
import OffHours from "@/Components/OffHours.vue";

export default {
  name: "GenericDataList",
  components: {
    ServiceCase,
    Crime,
    BuildingPermit,
    PropertyViolation,
    OffHours,
  },
  props: {
    totalData: {
      type: Array,
      required: true,
    },
    itemsPerPage: {
      type: Number,
      default: 10,
    },
    language_codes: {
      type: Array,
      default: () => ["en-US"],
    },
  },
  data() {
    return {
      currentPage: 1,
      inputPage: 1,
      sortKey: "date", // Default sort by date
      sortOrder: "desc", // Default to descending order
    };
  },
  computed: {
    totalPages() {
      return Math.ceil(this.totalData.length / this.itemsPerPage);
    },
    sortedData() {
      return [...this.totalData].sort((a, b) => {
        let result = 0;
        if (a[this.sortKey] < b[this.sortKey]) {
          result = -1;
        } else if (a[this.sortKey] > b[this.sortKey]) {
          result = 1;
        }
        return this.sortOrder === "asc" ? result : -result;
      });
    },
    paginatedData() {
      const start = (this.currentPage - 1) * this.itemsPerPage;
      const end = start + this.itemsPerPage;
      return this.sortedData.slice(start, end);
    },
    getSingleLanguageCode() {
      return this.language_codes[0];
    },
  },
  watch: {
    currentPage(newPage) {
      this.inputPage = newPage;
    },
  },
  methods: {
    nextPage() {
      if (this.currentPage < this.totalPages) {
        this.currentPage++;
      }
    },
    prevPage() {
      if (this.currentPage > 1) {
        this.currentPage--;
      }
    },
    goToPage() {
      if (this.inputPage >= 1 && this.inputPage <= this.totalPages) {
        this.currentPage = this.inputPage;
      } else {
        this.inputPage = this.currentPage;
      }
    },
    sortBy(key) {
      if (this.sortKey === key) {
        // If the same column is clicked, toggle the sort order
        this.sortOrder = this.sortOrder === "asc" ? "desc" : "asc";
      } else {
        // If a new column is clicked, set it as the sort key and default to descending order
        this.sortKey = key;
        this.sortOrder = "desc";
      }
    },
  },
};
</script>

<style scoped>
/* Add any additional styling */
</style>
