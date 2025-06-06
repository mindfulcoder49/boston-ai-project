<template>
  <div>
    <input
      type="text"
      v-model="searchQuery"
      @input="searchAddresses"
      :placeholder="localizedLabels.addressPlaceholder"
      class="border w-full"
      @focus="showResults = true"
    />

    <ul v-if="results.length && showResults && !showGoogleSuggestions" class="border p-2 bg-white shadow address-result-list">
      <li class="cursor-pointer p-2 rounded" @click="clearLocalResults">
        {{ localizedLabels.clearResults }}
      </li>
      <li 
        v-for="(result, index) in results" 
        :key="`local-${index}`" 
        @click="selectAddress({ lat: result.y_coord, lng: result.x_coord, address: result.full_address })"
        class="cursor-pointer p-2 rounded"
      >
        {{ result.full_address }}
        <span v-if="result.city || result.zip" class="text-xs text-gray-600 ml-1">
          ({{ result.city }}{{ result.city && result.zip ? ', ' : '' }}{{ result.zip }})
        </span>
      </li>
    </ul>

    <p v-if="!results.length && searchQuery && !isLoadingLocal && !showGoogleSuggestions" class="text-gray-500 mt-2">
      {{ localizedLabels.noResultsFound }}
    </p>

    <div v-if="searchQuery.length >= 3 && !isLoadingGoogle" class="mt-2">
      <button @click="triggerGoogleSearch" class="text-sm text-blue-600 hover:underline">
        {{ localizedLabels.searchWithGoogle }}
      </button>
    </div>
    
    <p v-if="isLoadingGoogle" class="text-gray-500 mt-2">{{ localizedLabels.loadingGoogleResults }}</p>

    <ul v-if="googleSuggestions.length && showGoogleSuggestions" class="border p-2 bg-white shadow address-result-list mt-1">
      <li class="p-2 text-gray-700 font-semibold">{{ localizedLabels.googleSuggestionsTitle }}</li>
      <li class="cursor-pointer p-2 rounded" @click="clearGoogleSuggestions">
        {{ localizedLabels.clearGoogleResults }}
      </li>
      <li 
        v-for="(suggestion, index) in googleSuggestions" 
        :key="`google-${index}`" 
        @click="selectGoogleSuggestion(suggestion)"
        class="cursor-pointer p-2 rounded"
      >
        {{ suggestion }}
      </li>
    </ul>
     <p v-if="googleSearchAttempted && !googleSuggestions.length && showGoogleSuggestions && !isLoadingGoogle" class="text-gray-500 mt-2">
      {{ localizedLabels.noGoogleResultsFound }}
    </p>

  </div>
</template>

<script>
import axios from "axios";

const localizationLabelsByLanguageCode = {
  'en-US': {
    addressPlaceholder: 'Enter address...',
    clearResults: 'Clear Local Results',
    noResultsFound: 'No local results found.',
    searchWithGoogle: "Can't find it? Search with Google",
    googleSuggestionsTitle: "Google Suggestions (Did you mean?):",
    clearGoogleResults: "Clear Google Results",
    noGoogleResultsFound: "No results found via Google.",
    loadingGoogleResults: "Searching with Google...",
  },
  'es-MX': {
    addressPlaceholder: 'Ingrese la dirección...',
    clearResults: 'Borrar Resultados Locales',
    noResultsFound: 'No se encontraron resultados locales.',
    searchWithGoogle: "¿No lo encuentras? Buscar con Google",
    googleSuggestionsTitle: "Sugerencias de Google (¿Quizás quisiste decir?):",
    clearGoogleResults: "Borrar Resultados de Google",
    noGoogleResultsFound: "No se encontraron resultados a través de Google.",
    loadingGoogleResults: "Buscando con Google...",
  },
  'zh-CN': {
    addressPlaceholder: '输入地址...',
    clearResults: '清除本地结果',
    noResultsFound: '未找到本地结果。',
    searchWithGoogle: "找不到？用谷歌搜索",
    googleSuggestionsTitle: "谷歌建议 (您是指?):",
    clearGoogleResults: "清除谷歌结果",
    noGoogleResultsFound: "通过谷歌未找到结果。",
    loadingGoogleResults: "正在使用谷歌搜索...",
  },
  'ht-HT': {
    addressPlaceholder: 'Antre adrès...',
    clearResults: 'Efase Rezilta Lokal yo',
    noResultsFound: 'Pa gen rezilta lokal jwenn.',
    searchWithGoogle: "Ou pa jwenn li? Chèche ak Google",
    googleSuggestionsTitle: "Sijesyon Google (Èske ou te vle di?):",
    clearGoogleResults: "Efase Rezilta Google yo",
    noGoogleResultsFound: "Pa gen rezilta jwenn via Google.",
    loadingGoogleResults: "Chèche ak Google...",
  },
  'vi-VN': {
    addressPlaceholder: 'Nhập địa chỉ...',
    clearResults: 'Xóa kết quả cục bộ',
    noResultsFound: 'Không tìm thấy kết quả cục bộ.',
    searchWithGoogle: "Không tìm thấy? Tìm kiếm với Google",
    googleSuggestionsTitle: "Gợi ý của Google (Ý bạn là?):",
    clearGoogleResults: "Xóa kết quả của Google",
    noGoogleResultsFound: "Không tìm thấy kết quả nào qua Google.",
    loadingGoogleResults: "Đang tìm kiếm với Google...",
  },
  'pt-BR': {
    addressPlaceholder: 'Digite o endereço...',
    clearResults: 'Limpar Resultados Locais',
    noResultsFound: 'Nenhum resultado local encontrado.',
    searchWithGoogle: "Não encontrou? Pesquisar com o Google",
    googleSuggestionsTitle: "Sugestões do Google (Você quis dizer?):",
    clearGoogleResults: "Limpar Resultados do Google",
    noGoogleResultsFound: "Nenhum resultado encontrado via Google.",
    loadingGoogleResults: "Pesquisando com o Google...",
  },
};


export default {
  props: ["initialSearchQuery", "language_codes"],
  data() {
    return {
      searchQuery: this.initialSearchQuery || "",
      results: [],
      showResults: true,
      isLoadingLocal: false,
      
      googleSuggestions: [],
      showGoogleSuggestions: false,
      isLoadingGoogle: false,
      googleSearchAttempted: false,
    };
  },
  computed: {
    localizedLabels() {
      const languageCode = this.language_codes && this.language_codes.length > 0 ? this.language_codes[0] : "en-US";
      return localizationLabelsByLanguageCode[languageCode] || localizationLabelsByLanguageCode["en-US"];
    },
  },
  methods: {
    async searchAddresses() {
      this.clearGoogleSuggestions(); // Clear Google results when local search is re-triggered
      if (this.searchQuery.length >= 1) {
        this.isLoadingLocal = true;
        try {
          const response = await axios.get("/search-address", {
            params: { address: this.searchQuery },
          });
          this.results = response.data.data; // data now includes city and zip
          this.showResults = true; 
        } catch (error) {
          console.error("Error fetching local address data:", error);
          this.results = [];
        } finally {
          this.isLoadingLocal = false;
        }
      } else {
        this.results = [];
      }
    },
    selectAddress(location) { // location is { lat, lng, address }
      this.$emit("address-selected", location);
      this.searchQuery = location.address; // Optionally update input with selected address
      this.clearAllResults();
    },
    clearLocalResults() {
      this.results = [];
      // this.showResults = false; // Keep input focused if desired
    },
    clearGoogleSuggestions() {
      this.googleSuggestions = [];
      this.showGoogleSuggestions = false;
      this.googleSearchAttempted = false;
    },
    clearAllResults() {
      this.results = [];
      this.googleSuggestions = [];
      this.showResults = false;
      this.showGoogleSuggestions = false;
      this.googleSearchAttempted = false;
    },
    async triggerGoogleSearch() {
      if (this.searchQuery.length < 1) return;
      this.isLoadingGoogle = true;
      this.googleSearchAttempted = true;
      this.results = []; // Clear local results
      this.showResults = false;

      try {
        const response = await axios.post("/api/google-places-autocomplete", {
          input: this.searchQuery,
        });
        this.googleSuggestions = response.data.suggestions || [];
        this.showGoogleSuggestions = true;
      } catch (error) {
        console.error("Error fetching Google suggestions:", error);
        this.googleSuggestions = [];
      } finally {
        this.isLoadingGoogle = false;
      }
    },
    async selectGoogleSuggestion(suggestionText) {
      this.isLoadingGoogle = true; // Show loading indicator for geocoding
      try {
        const response = await axios.post("/api/geocode-google-place", {
          address: suggestionText,
        });
        const { lat, lng, address } = response.data;
        this.selectAddress({ lat, lng, address });
      } catch (error) {
        console.error("Error geocoding Google suggestion:", error);
        // Optionally show an error message to the user
      } finally {
        this.isLoadingGoogle = false;
        this.clearGoogleSuggestions();
      }
    },
  },
  watch: {
    searchQuery(newQuery, oldQuery) {
      if (newQuery === "") {
        this.clearAllResults();
      } else if (newQuery !== oldQuery && this.showGoogleSuggestions) {
        // If user types more while Google suggestions are shown, clear them to avoid confusion
        // and let local search take over or allow re-triggering Google search.
        this.clearGoogleSuggestions();
      }
    }
  }
};
</script>

<style scoped>
input {
  margin-bottom: 10px;
}
ul {
  max-height: 250px; /* Increased max-height */
  overflow-y: auto;
}
li {
  padding: 8px;
  border-bottom: 1px solid #e0e0e0;
}
li:last-child {
  border-bottom: none;
}

li:hover {
  background-color: rgba(240, 240, 240, 0.7);
}

.address-result-list {
  position: absolute;
  z-index: 1000;
  width: calc(100% - 2px); /* Adjust width to align with input considering border */
  max-width: inherit; /* Ensure it doesn't overflow parent if parent has max-width */
  background-color: rgba(255, 255, 255, 0.95); /* Slightly less transparent */
  border: 1px solid #e0e0e0;
  box-shadow: 0 4px 6px rgba(0,0,0,0.1); /* Added shadow for better visibility */
  border-radius: 4px; /* Rounded corners */
}
</style>
