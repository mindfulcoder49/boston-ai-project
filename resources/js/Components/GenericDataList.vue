<template>
  <div class="w-full">
    <!-- Filter and Sort Controls -->
    <div class="mb-4 p-4 border rounded-md bg-gray-50">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Keyword Search -->
        <div>
          <label for="keywordSearch" class="block text-sm font-medium text-gray-700 mb-1">{{ localizedLabels.searchPlaceholder }}</label>
          <input
            type="text"
            id="keywordSearch"
            v-model="searchKeyword"
            :placeholder="localizedLabels.searchPlaceholder"
            class="p-2 border rounded-md w-full text-sm"
          />
        </div>

        <!-- Sort Control -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">{{ localizedLabels.sortButtonLabel }}</label>
          <button
            @click="toggleSort"
            class="p-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 w-full text-sm"
          >
            {{ sortButtonText }}
          </button>
        </div>
      </div>

      <!-- Data Source (Model) Filters -->
      <div class="mt-4">
        <h4 class="text-sm font-medium text-gray-700 mb-1">{{ localizedLabels.dataSourcesHeader }}</h4>
        <div class="mb-2">
          <button @click="selectAllModels" class="text-xs p-1 bg-green-100 text-green-700 rounded mr-2 hover:bg-green-200">{{ localizedLabels.selectAllDataSourcesLabel }}</button>
          <button @click="deselectAllModels" class="text-xs p-1 bg-green-100 text-green-700 rounded hover:bg-green-200">{{ localizedLabels.deselectAllDataSourcesLabel }}</button>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-2">
          <div v-for="model in availableModels" :key="model" class="flex items-center">
            <input
              type="checkbox"
              :id="`filter_model_${model}`"
              :value="model"
              v-model="selectedModels"
              class="form-checkbox h-4 w-4 text-green-600 transition duration-150 ease-in-out mr-2"
            />
            <label
              :for="`filter_model_${model}`"
              class="text-sm text-gray-700">{{ model }}</label>
          </div>
        </div>
      </div>

      <!-- Data Type Filters -->
      <div class="mt-4">
        <h4 class="text-sm font-medium text-gray-700 mb-1">{{ localizedLabels.dataTypesHeader }}</h4>
        <div class="mb-2">
          <button @click="selectAllTypes" class="text-xs p-1 bg-blue-100 text-blue-700 rounded mr-2 hover:bg-blue-200">{{ localizedLabels.selectAllDataTypesLabel }}</button>
          <button @click="deselectAllTypes" class="text-xs p-1 bg-blue-100 text-blue-700 rounded hover:bg-blue-200">{{ localizedLabels.deselectAllDataTypesLabel }}</button>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-2">
          <div v-for="type in availableDataTypes" :key="type" class="flex items-center">
            <input
              type="checkbox"
              :id= "`filter_${type}`" 
              :value="type"
              v-model="selectedDataTypes"
              class="form-checkbox h-4 w-4 text-indigo-600 transition duration-150 ease-in-out mr-2"
            />
            <label 
            :for= "`filter_${type}`"
            class="text-sm text-gray-700">{{ type }}</label>
          </div>
        </div>
      </div>
    </div>

    <!-- Pagination Controls -->
    <div class="flex justify-between items-center mt-4 mb-4">
      <button
        @click="prevPage"
        :disabled="currentPage === 1 || totalPages === 0"
        class="p-2 bg-blue-500 text-white rounded-lg shadow-lg hover:bg-blue-600 w-1/4 sm:w-1/6 disabled:bg-gray-300"
      >
        {{ localizedLabels.previousButton }}
      </button>
      <div class="flex items-center">
        <span class="mr-2">{{ localizedLabels.pageLabel }}</span>
        <input
          v-model.number="inputPage"
          @keyup.enter="goToPage"
          type="number"
          min="1"
          :max="totalPages"
          class="w-16 p-1 border rounded-md text-center"
          :disabled="totalPages === 0"
        />
        <span class="ml-2">{{ localizedLabels.ofLabel }} {{ totalPages }}</span>
      </div>
      <button
        @click="nextPage"
        :disabled="currentPage === totalPages || totalPages === 0"
        class="p-2 bg-blue-500 text-white rounded-lg shadow-lg hover:bg-blue-600 w-1/4 sm:w-1/6 disabled:bg-gray-300"
      >
        {{ localizedLabels.nextButton }}
      </button>
    </div>

    <!-- No Results Message -->
    <div v-if="paginatedData.length === 0" class="text-center text-gray-500 py-8">
      {{ localizedLabels.noResultsMessage }}
    </div>

    <!-- Data List -->
    <div v-else class="flex flex-wrap -mx-2">
      <div v-for="(item, index) in paginatedData" :key="item.alcivartech_external_id || item.id || index" class="p-2 w-full sm:w-1/2 md:w-1/3 lg:w-1/4">
        <div class="bg-gray-100 p-2 rounded-md mb-2 data-container">
          <UniversalDataDisplay :data="item" :language_codes="language_codes" :map-configuration="mapConfiguration" />
          <!-- Button to emit datapoint for goto marker on map function -->
          <button
            @click="$emit('handle-goto-marker', item)"
            class="p-2 bg-blue-500 text-white hover:bg-blue-600 find-button mb-4 ml-4 mt-2"
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
import FoodInspection from "./FoodInspection.vue";
import UniversalDataDisplay from "@/Components/UniversalDataDisplay.vue";

const localizationLabelsByLanguageCode = {
  'en-US': {
    previousButton: 'Previous',
    nextButton: 'Next',
    noResultsMessage: 'No results found for the current filters.',
    pageLabel: 'Page',
    ofLabel: 'of',
    searchPlaceholder: 'Search...',
    sortButtonLabel: 'Sort by Date',
    dataTypesHeader: 'Filter by Data Type',
    newestFirst: 'Newest First',
    oldestFirst: 'Oldest First',
    unsorted: 'Unsorted',
    selectAllDataTypesLabel: 'Select All',
    deselectAllDataTypesLabel: 'Deselect All',
    dataSourcesHeader: 'Filter by Data Source',
    selectAllDataSourcesLabel: 'Select All Sources',
    deselectAllDataSourcesLabel: 'Deselect All Sources',
  },
  'es-MX': {
    previousButton: 'Anterior',
    nextButton: 'Siguiente',
    noResultsMessage: 'No se encontraron resultados para los filtros actuales.',
    pageLabel: 'Página',
    ofLabel: 'de',
    searchPlaceholder: 'Buscar...',
    sortButtonLabel: 'Ordenar por Fecha',
    dataTypesHeader: 'Filtrar por Tipo de Dato',
    newestFirst: 'Más Recientes Primero',
    oldestFirst: 'Más Antiguos Primero',
    unsorted: 'Sin Ordenar',
    selectAllDataTypesLabel: 'Seleccionar Todos',
    deselectAllDataTypesLabel: 'Deseleccionar Todos',
    dataSourcesHeader: 'Filtrar por Fuente de Datos',
    selectAllDataSourcesLabel: 'Seleccionar Todas las Fuentes',
    deselectAllDataSourcesLabel: 'Deseleccionar Todas las Fuentes',
  },
  // Add other languages similarly
  'zh-CN': {
    previousButton: '上一页',
    nextButton: '下一页',
    noResultsMessage: '根据当前筛选条件未找到结果。',
    pageLabel: '页',
    ofLabel: '的',
    searchPlaceholder: '搜索...',
    sortButtonLabel: '按日期排序',
    dataTypesHeader: '按数据类型筛选',
    newestFirst: '最新优先',
    oldestFirst: '最早优先',
    unsorted: '未排序',
    selectAllDataTypesLabel: '全选',
    deselectAllDataTypesLabel: '取消全选',
    dataSourcesHeader: '按数据源筛选',
    selectAllDataSourcesLabel: '选择所有数据源',
    deselectAllDataSourcesLabel: '取消全选数据源',
  },
  'ht-HT': {
    previousButton: 'Anvan',
    nextButton: 'Pwochen',
    noResultsMessage: 'Pa gen rezilta pou filtè aktyèl yo.',
    pageLabel: 'Paj',
    ofLabel: 'nan',
    searchPlaceholder: 'Chèche...',
    sortButtonLabel: 'Triye pa Dat',
    dataTypesHeader: 'Filtre pa Kalite Done',
    newestFirst: 'Pi Nouvo Anvan',
    oldestFirst: 'Pi Ansyen Anvan',
    unsorted: 'Pa Triye',
    selectAllDataTypesLabel: 'Chwazi Tout',
    deselectAllDataTypesLabel: 'Dechwazi Tout',
    dataSourcesHeader: 'Filtre pa Sous Done',
    selectAllDataSourcesLabel: 'Chwazi Tout Sous',
    deselectAllDataSourcesLabel: 'Dechwazi Tout Sous',
  },
  'vi-VN': {
    previousButton: 'Trước',
    nextButton: 'Kế tiếp',
    noResultsMessage: 'Không tìm thấy kết quả cho bộ lọc hiện tại.',
    pageLabel: 'Trang',
    ofLabel: 'của',
    searchPlaceholder: 'Tìm kiếm...',
    sortButtonLabel: 'Sắp xếp theo Ngày',
    dataTypesHeader: 'Lọc theo Loại Dữ liệu',
    newestFirst: 'Mới nhất Trước',
    oldestFirst: 'Cũ nhất Trước',
    unsorted: 'Chưa sắp xếp',
    selectAllDataTypesLabel: 'Chọn Tất cả',
    deselectAllDataTypesLabel: 'Bỏ chọn Tất cả',
    dataSourcesHeader: 'Lọc theo Nguồn Dữ liệu',
    selectAllDataSourcesLabel: 'Chọn Tất cả Nguồn',
    deselectAllDataSourcesLabel: 'Bỏ chọn Tất cả Nguồn',
  },
  'pt-BR': {
    previousButton: 'Anterior',
    nextButton: 'Próximo',
    noResultsMessage: 'Nenhum resultado encontrado para os filtros atuais.',
    pageLabel: 'Página',
    ofLabel: 'de',
    searchPlaceholder: 'Pesquisar...',
    sortButtonLabel: 'Ordenar por Data',
    dataTypesHeader: 'Filtrar por Tipo de Dado',
    newestFirst: 'Mais Recentes Primeiro',
    oldestFirst: 'Mais Antigos Primeiro',
    unsorted: 'Não Ordenado',
    selectAllDataTypesLabel: 'Selecionar Todos',
    deselectAllDataTypesLabel: 'Desmarcar Todos',
    dataSourcesHeader: 'Filtrar por Fonte de Dados',
    selectAllDataSourcesLabel: 'Selecionar Todas as Fontes',
    deselectAllDataSourcesLabel: 'Desmarcar Todas as Fontes',
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
    FoodInspection,
    UniversalDataDisplay,
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
    mapConfiguration: { // Added mapConfiguration prop
      type: Object,
      default: () => ({}),
    }
  },
  data() {
    return {
      currentPage: 1,
      inputPage: 1,
      searchKeyword: '',
      selectedDataTypes: [],
      selectedModels: [], // Added for model filtering
      sortDirection: 'none', // 'none', 'asc', 'desc'
    };
  },
  computed: {
    localizedLabels() {
      const languageCode = this.language_codes[0] || "en-US";
      return localizationLabelsByLanguageCode[languageCode] || localizationLabelsByLanguageCode["en-US"];
    },
    availableDataTypes() {
      if (!this.totalData) return [];
      const types = new Set(this.totalData.map(item => this.getDisplayType(item)).filter(Boolean));
      return Array.from(types).sort();
    },
    availableModels() {
      if (!this.totalData) return [];
      const models = new Set(this.totalData.map(item => item.alcivartech_model).filter(Boolean));
      return Array.from(models).sort();
    },
    processedData() {
      let data = [...this.totalData];

      // 0. Filter by selectedModels
      if (this.selectedModels.length > 0 && this.selectedModels.length < this.availableModels.length) {
        const selectedModelSet = new Set(this.selectedModels);
        data = data.filter(item => item.alcivartech_model && selectedModelSet.has(item.alcivartech_model));
      } else if (this.selectedModels.length === 0 && this.availableModels.length > 0) {
        // If no models are selected, show no data
        return [];
      }

      // 1. Filter by selectedDataTypes (display names)
      if (this.selectedDataTypes.length > 0 && this.selectedDataTypes.length < this.availableDataTypes.length) {
        const selectedSet = new Set(this.selectedDataTypes);
        data = data.filter(item => {
          const displayType = this.getDisplayType(item);
          return displayType && selectedSet.has(displayType);
        });
      } else if (this.selectedDataTypes.length === 0 && this.availableDataTypes.length > 0) {
        // If no types are selected, show no data (unless there are no types available at all)
        return [];
      }


      // 2. Filter by searchKeyword
      if (this.searchKeyword && this.searchKeyword.trim() !== '') {
        const lowerKeyword = this.searchKeyword.trim().toLowerCase();
        data = data.filter(item => {
          return Object.values(item).some(val => {
            if (val === null || typeof val === 'undefined') return false;
            // For objects (like violation_summary), stringify and search
            if (typeof val === 'object') {
                 try {
                    return JSON.stringify(val).toLowerCase().includes(lowerKeyword);
                 } catch (e) {
                    return false; // Cannot stringify, so cannot search
                 }
            }
            return String(val).toLowerCase().includes(lowerKeyword);
          });
        });
      }

      // 3. Sort by alcivartech_date
      if (this.sortDirection !== 'none') {
        data.sort((a, b) => {
          const dateA = a.alcivartech_date ? new Date(a.alcivartech_date) : null;
          const dateB = b.alcivartech_date ? new Date(b.alcivartech_date) : null;

          if (!dateA && !dateB) return 0;
          // Consistent handling for null dates: items without a date go to the end when ascending, start when descending.
          if (!dateA) return this.sortDirection === 'asc' ? 1 : -1; 
          if (!dateB) return this.sortDirection === 'asc' ? -1 : 1;

          if (this.sortDirection === 'asc') {
            return dateA - dateB;
          } else { // desc
            return dateB - dateA;
          }
        });
      }
      return data;
    },
    totalPages() {
      if (!this.processedData || this.processedData.length === 0) return 0;
      return Math.ceil(this.processedData.length / this.itemsPerPage);
    },
    paginatedData() {
      if (!this.processedData || this.processedData.length === 0) return [];
      const start = (this.currentPage - 1) * this.itemsPerPage;
      const end = start + this.itemsPerPage;
      return this.processedData.slice(start, end);
    },
    sortButtonText() {
      const base = this.localizedLabels.sortButtonLabel;
      if (this.sortDirection === 'desc') return `${base} (${this.localizedLabels.newestFirst})`;
      if (this.sortDirection === 'asc') return `${base} (${this.localizedLabels.oldestFirst})`;
      return `${base} (${this.localizedLabels.unsorted})`;
    }
  },
  methods: {
    getDisplayType(item) {
      if (
        item &&
        this.mapConfiguration &&
        this.mapConfiguration.dataPointModelConfig &&
        item.alcivartech_model &&
        this.mapConfiguration.dataPointModelConfig[item.alcivartech_model]
      ) {
        return this.mapConfiguration.dataPointModelConfig[item.alcivartech_model].displayTitle || item.alcivartech_type;
      }
      return item.alcivartech_type;
    },
    resetPageAndInput() {
      this.currentPage = 1;
      this.inputPage = 1;
    },
    nextPage() {
      if (this.currentPage < this.totalPages) {
        this.currentPage++;
        this.inputPage = this.currentPage;
      }
    },
    prevPage() {
      if (this.currentPage > 1) {
        this.currentPage--;
        this.inputPage = this.currentPage;
      }
    },
    goToPage() {
      const pageNum = parseInt(this.inputPage, 10);
      if (pageNum >= 1 && pageNum <= this.totalPages) {
        this.currentPage = pageNum;
      }
      // Always reset inputPage to reflect current valid page, even if input was invalid
      this.inputPage = this.currentPage; 
    },
    toggleSort() {
      if (this.sortDirection === 'none') {
        this.sortDirection = 'desc'; // Default to newest first
      } else if (this.sortDirection === 'desc') {
        this.sortDirection = 'asc';
      } else {
        this.sortDirection = 'none';
      }
    },
    selectAllTypes() {
      this.selectedDataTypes = [...this.availableDataTypes];
    },
    deselectAllTypes() {
      this.selectedDataTypes = [];
    },
    initializeSelectedTypes() {
      this.selectedDataTypes = [...this.availableDataTypes];
    },
    selectAllModels() {
      this.selectedModels = [...this.availableModels];
    },
    deselectAllModels() {
      this.selectedModels = [];
    },
    initializeSelectedModels() {
      this.selectedModels = [...this.availableModels];
    }
  },
  watch: {
    totalData: {
      handler() {
        this.initializeSelectedTypes();
        this.initializeSelectedModels(); // Initialize models as well
        this.resetPageAndInput();
      },
      deep: true,
    },
    searchKeyword() {
      this.resetPageAndInput();
    },
    selectedDataTypes: {
        handler() {
            this.resetPageAndInput();
        },
        deep: true // Necessary because it's an array
    },
    selectedModels: { // Watcher for model changes
        handler() {
            this.resetPageAndInput();
        },
        deep: true
    },
    sortDirection() {
      this.resetPageAndInput();
    },
    // When totalPages changes (due to filtering), ensure currentPage is not out of bounds.
    totalPages(newTotalPages) {
        if (this.currentPage > newTotalPages && newTotalPages > 0) {
            this.currentPage = newTotalPages;
            this.inputPage = newTotalPages;
        } else if (newTotalPages === 0) {
            this.currentPage = 1; // Or 0 if preferred, but 1 is common for "page 1 of 0"
            this.inputPage = 1;
        }
    }
  },
  mounted() {
    this.initializeSelectedTypes();
    this.initializeSelectedModels(); // Initialize models on mount
    // Ensure inputPage reflects initial currentPage
    this.inputPage = this.currentPage;
  }
};
</script>

<style scoped>
/* Add any additional styling */
.data-container {
  transition: transform 0.3s;
  height: 500px; /* Or min-height if content varies a lot */
  overflow-y: auto; /* Changed to y for vertical scroll only */
  display: flex;
  flex-direction: column; /* Ensure button is at bottom */
  justify-content: space-between; /* Pushes button to bottom if content is short */
}
.data-container:hover {
  transform: scale(1.02);
}
.find-button {
  align-self: flex-end; /* Aligns button to the right if container is flex */
  margin-top: auto; /* Pushes button to the bottom of the flex container */
}
</style>
