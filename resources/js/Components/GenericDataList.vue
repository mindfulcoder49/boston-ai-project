<template>
  <div className="w-full">
    <!-- Pagination Controls -->
    <div class="flex justify-between items-center mt-4 mb-4">
      <button
        @click="prevPage"
        :disabled="currentPage === 1"
        class="p-2 bg-blue-500 text-white rounded-lg shadow-lg hover:bg-blue-600 w-1/4 sm:w-1/6 disabled:bg-gray-300"
      >
        {{ localizedLabels.previousButton }}
      </button>
      <div class="flex items-center">
        <span class="mr-2">{{ localizedLabels.pageLabel }}</span>
        <input
          v-model.number="inputPage"
          @change="goToPage"
          type="number"
          min="1"
          :max="totalPages"
          class="w-16 p-1 border rounded-md text-center"
        />
        <span class="ml-2">{{ localizedLabels.ofLabel }} {{ totalPages }}</span>
      </div>
      <button
        @click="nextPage"
        :disabled="currentPage === totalPages"
        class="p-2 bg-blue-500 text-white rounded-lg shadow-lg hover:bg-blue-600 w-1/4 sm:w-1/6 disabled:bg-gray-300"
      >
        {{ localizedLabels.nextButton }}
      </button>
    </div>

    <!-- No Results Message -->
    <div v-if="paginatedData.length === 0" class="text-center text-gray-500">
      {{ localizedLabels.noResultsMessage }}
    </div>

    <!-- Data List -->
    <div v-else class="flex flex-wrap">
      <div v-for="(item, index) in paginatedData" :key="index" class="p-4 bg-white w-full sm:w-1/2 md:w-1/3 lg:w-1/4">
        <div class="bg-gray-100 p-2 rounded-md mb-2 data-container">
          <ServiceCase v-if="item.alcivartech_type === '311 Case'" :data="item" :language_codes="language_codes" />
          <Crime v-if="item.alcivartech_type === 'Crime'" :data="item" :language_codes="language_codes" />
          <BuildingPermit v-if="item.alcivartech_type === 'Building Permit'" :data="item" :language_codes="language_codes" />
          <PropertyViolation v-if="item.alcivartech_type === 'Property Violation'" :data="item" :language_codes="language_codes" />
          <OffHours v-if="item.alcivartech_type === 'Construction Off Hour'" :data="item" :language_codes="language_codes" />
          <FoodEstablishmentViolation v-if="item.alcivartech_type === 'Food Establishment Violation'" :data="item" :language_codes="language_codes" />
          <!-- Button to emit datapoint for goto marker on map function -->
          <button
            @click="$emit('handle-goto-marker', item)"
            class="p-2 bg-blue-500 text-white hover:bg-blue-600 find-button mb-4 ml-4"
          >
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
import FoodEstablishmentViolation from "./FoodInspection.vue";

const localizationLabelsByLanguageCode = {
  'en-US': {
    previousButton: 'Previous',
    nextButton: 'Next',
    noResultsMessage: 'No results found',
    pageLabel: 'Page',
    ofLabel: 'of',
  },
  'es-MX': {
    previousButton: 'Anterior',
    nextButton: 'Siguiente',
    noResultsMessage: 'No se encontraron resultados',
    pageLabel: 'Página',
    ofLabel: 'de',
  },
  'zh-CN': {
    previousButton: '上一页',
    nextButton: '下一页',
    noResultsMessage: '未找到结果',
    pageLabel: '页',
    ofLabel: '的',
  },
  'ht-HT': {
    previousButton: 'Anvan',
    nextButton: 'Pwochen',
    noResultsMessage: 'Pa gen rezilta jwenn',
    pageLabel: 'Paj',
    ofLabel: 'nan',
  },
  'vi-VN': {
    previousButton: 'Trước',
    nextButton: 'Kế tiếp',
    noResultsMessage: 'Không tìm thấy kết quả',
    pageLabel: 'Trang',
    ofLabel: 'của',
  },
  'pt-BR': {
    previousButton: 'Anterior',
    nextButton: 'Próximo',
    noResultsMessage: 'Nenhum resultado encontrado',
    pageLabel: 'Página',
    ofLabel: 'de',
  },
};


export default {
  name: "GenericDataList",
  components: {
    ServiceCase,
    Crime,
    BuildingPermit,
    PropertyViolation,
    OffHours,
    FoodEstablishmentViolation,
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
    };
  },
  computed: {
    localizedLabels() {
      const languageCode = this.language_codes[0] || "en-US";
      return localizationLabelsByLanguageCode[languageCode] || localizationLabelsByLanguageCode["en-US"];
    },
    totalPages() {
      return Math.ceil(this.totalData.length / this.itemsPerPage);
    },
    paginatedData() {
      const start = (this.currentPage - 1) * this.itemsPerPage;
      const end = start + this.itemsPerPage;
      return this.totalData.slice(start, end);
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
  },
};
</script>

<style scoped>
/* Add any additional styling */
.data-container {
  transition: transform 0.3s;
  height: 500px;
  overflow: auto;
}
.data-container:hover {
  transform: scale(1.02);
}
</style>
