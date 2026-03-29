<template>
  <div class="google-address-search">
    <div class="google-address-search-row" :class="{ 'has-submit-button': show_submit_button }">
      <input
        type="text"
        v-model="searchQuery"
        @input="handleInput"
        @keydown.enter.prevent="submitCurrentQuery"
        :placeholder="localizedLabels.addressPlaceholder"
        class="google-address-search-input border w-full"
        @focus="showGoogleSuggestions = googleSuggestions.length > 0"
      />
      <button
        v-if="show_submit_button"
        type="button"
        :class="submit_button_class || 'google-address-search-submit'"
        :disabled="isLoadingGoogle || searchQuery.trim().length < 3"
        @click="submitCurrentQuery"
      >
        {{ submit_button_label || 'Search address' }}
      </button>
    </div>

    <p v-if="isLoadingGoogle" class="text-gray-500 mt-2">{{ localizedLabels.loadingGoogleResults }}</p>

    <ul v-if="googleSuggestions.length && showGoogleSuggestions" class="border p-2 bg-white shadow address-result-list mt-1">
      <li class="p-2 text-gray-700 font-semibold">{{ localizedLabels.googleSuggestionsTitle }}</li>
      <li class="cursor-pointer p-2 rounded" @click="clearGoogleSuggestionsAndQuery">
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
import { debounce } from 'lodash-es'; // Using lodash-es for debounce

const localizationLabelsByLanguageCode = {
  'en-US': {
    addressPlaceholder: 'Enter address (Google Search)...',
    googleSuggestionsTitle: "Google Suggestions:",
    clearGoogleResults: "Clear Google Results",
    noGoogleResultsFound: "No results found via Google.",
    loadingGoogleResults: "Searching with Google...",
  },
  'es-MX': {
    addressPlaceholder: 'Ingrese la dirección (Búsqueda en Google)...',
    googleSuggestionsTitle: "Sugerencias de Google:",
    clearGoogleResults: "Borrar Resultados de Google",
    noGoogleResultsFound: "No se encontraron resultados a través de Google.",
    loadingGoogleResults: "Buscando con Google...",
  },
  'zh-CN': {
    addressPlaceholder: '输入地址 (谷歌搜索)...',
    googleSuggestionsTitle: "谷歌建议:",
    clearGoogleResults: "清除谷歌结果",
    noGoogleResultsFound: "通过谷歌未找到结果。",
    loadingGoogleResults: "正在使用谷歌搜索...",
  },
  'ht-HT': {
    addressPlaceholder: 'Antre adrès (Rechèch Google)...',
    googleSuggestionsTitle: "Sijesyon Google:",
    clearGoogleResults: "Efase Rezilta Google yo",
    noGoogleResultsFound: "Pa gen rezilta jwenn via Google.",
    loadingGoogleResults: "Chèche ak Google...",
  },
  'vi-VN': {
    addressPlaceholder: 'Nhập địa chỉ (Tìm kiếm Google)...',
    googleSuggestionsTitle: "Gợi ý của Google:",
    clearGoogleResults: "Xóa kết quả của Google",
    noGoogleResultsFound: "Không tìm thấy kết quả nào qua Google.",
    loadingGoogleResults: "Đang tìm kiếm với Google...",
  },
  'pt-BR': {
    addressPlaceholder: 'Digite o endereço (Pesquisa Google)...',
    googleSuggestionsTitle: "Sugestões do Google:",
    clearGoogleResults: "Limpar Resultados do Google",
    noGoogleResultsFound: "Nenhum resultado encontrado via Google.",
    loadingGoogleResults: "Pesquisando com o Google...",
  },
};


export default {
  props: {
    initialSearchQuery: {
      type: String,
      default: "",
    },
    language_codes: {
      type: Array,
      default: () => [],
    },
    placeholder_text: {
      type: String,
      default: null,
    },
    show_submit_button: {
      type: Boolean,
      default: false,
    },
    submit_button_label: {
      type: String,
      default: "Search address",
    },
    submit_button_class: {
      type: String,
      default: "",
    },
  },
  emits: ["address-selected", "search-started"],
  data() {
    return {
      searchQuery: this.initialSearchQuery || "",
      googleSuggestions: [],
      showGoogleSuggestions: false,
      isLoadingGoogle: false,
      googleSearchAttempted: false,
      debouncedGoogleSearch: null,
      hasTrackedSearchStart: false,
    };
  },
  computed: {
    localizedLabels() {
      const languageCode = this.language_codes && this.language_codes.length > 0 ? this.language_codes[0] : "en-US";
      const baseLabels = localizationLabelsByLanguageCode[languageCode] || localizationLabelsByLanguageCode["en-US"];
      return {
        ...baseLabels,
        addressPlaceholder: this.placeholder_text || baseLabels.addressPlaceholder,
      };
    },
  },
  created() {
    this.debouncedGoogleSearch = debounce(this.fetchGoogleSuggestions, 600);
  },
  methods: {
    handleInput() {
      if (this.searchQuery.length >= 3) {
        this.isLoadingGoogle = true;
        this.googleSearchAttempted = true;
        if (!this.hasTrackedSearchStart) {
          this.$emit("search-started", {
            queryLength: this.searchQuery.length,
          });
          this.hasTrackedSearchStart = true;
        }
        this.debouncedGoogleSearch();
      } else {
        this.clearGoogleSuggestions();
        this.isLoadingGoogle = false;
      }
    },
    async fetchGoogleSuggestions() {
      if (this.searchQuery.length < 3) { // Double check, though handleInput should prevent this
        this.isLoadingGoogle = false;
        return;
      }
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
    async submitCurrentQuery() {
      const query = this.searchQuery.trim();
      if (query.length < 3) {
        return;
      }

      this.isLoadingGoogle = true;
      this.googleSearchAttempted = true;
      if (!this.hasTrackedSearchStart) {
        this.$emit("search-started", {
          queryLength: query.length,
          manualSubmit: true,
        });
        this.hasTrackedSearchStart = true;
      }

      try {
        const response = await axios.post("/api/geocode-google-place", {
          address: query,
        });
        const { lat, lng, address } = response.data;
        this.selectAddress({ lat, lng, address });
      } catch (error) {
        console.error("Error geocoding manual address search:", error);
        await this.fetchGoogleSuggestions();
      } finally {
        this.isLoadingGoogle = false;
      }
    },
    selectAddress(location) { // location is { lat, lng, address }
      this.$emit("address-selected", location);
      this.searchQuery = location.address; 
      this.clearGoogleSuggestions();
    },
    clearGoogleSuggestions() {
      this.googleSuggestions = [];
      this.showGoogleSuggestions = false;
      this.googleSearchAttempted = false;
      this.hasTrackedSearchStart = false;
    },
    clearGoogleSuggestionsAndQuery() {
      this.searchQuery = "";
      this.clearGoogleSuggestions();
    },
    async selectGoogleSuggestion(suggestionText) {
      this.isLoadingGoogle = true; 
      try {
        const response = await axios.post("/api/geocode-google-place", {
          address: suggestionText,
        });
        const { lat, lng, address } = response.data;
        this.selectAddress({ lat, lng, address }); // This will also update searchQuery and clear suggestions
      } catch (error) {
        console.error("Error geocoding Google suggestion:", error);
        // Optionally show an error message to the user
      } finally {
        this.isLoadingGoogle = false;
        // selectAddress already clears suggestions, but ensure it's cleared if an error occurs before selectAddress is called
        if (!this.searchQuery || this.searchQuery !== suggestionText) { 
            this.clearGoogleSuggestions();
        }
      }
    },
  },
  watch: {
    searchQuery(newQuery) {
      if (newQuery === "") {
        this.clearGoogleSuggestions();
        this.isLoadingGoogle = false; // Ensure loading is stopped
      }
    }
  }
};
</script>

<style scoped>
.google-address-search {
  position: relative;
}

.google-address-search-row {
  display: grid;
  gap: 0.75rem;
}

.google-address-search-row.has-submit-button {
  grid-template-columns: minmax(0, 1fr);
}

.google-address-search-input {
  margin-bottom: 0;
}

.google-address-search-submit {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 0.85rem;
  border: 1px solid #e2e8f0;
  background: #0f172a;
  color: white;
  padding: 0.8rem 1rem;
  font-size: 0.95rem;
  font-weight: 600;
  transition: background-color 0.15s ease, opacity 0.15s ease;
}

.google-address-search-submit:hover:enabled {
  background: #1e293b;
}

.google-address-search-submit:disabled {
  opacity: 0.55;
  cursor: not-allowed;
}

@media (min-width: 768px) {
  .google-address-search-row.has-submit-button {
    grid-template-columns: minmax(0, 1fr) auto;
  }
}

ul {
  max-height: 250px; 
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
  width: calc(100% - 2px); 
  max-width: inherit; 
  background-color: rgba(255, 255, 255, 0.95); 
  border: 1px solid #e0e0e0;
  box-shadow: 0 4px 6px rgba(0,0,0,0.1); 
  border-radius: 4px; 
}
</style>
