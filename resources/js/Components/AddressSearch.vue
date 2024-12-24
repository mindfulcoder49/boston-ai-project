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

    <ul v-if="results.length && showResults" class="border p-2 bg-white shadow address-result-list">
      <!-- Clear results button -->
      <li class="cursor-pointer p-2 rounded" @click="results = []">
        {{ localizedLabels.clearResults }}
      </li>
      <li 
        v-for="(result, index) in results" 
        :key="index" 
        @click="selectAddress(result)"
        class="cursor-pointer p-2 rounded"
      >
        {{ result.full_address }} - ({{ result.x_coord }}, {{ result.y_coord }})
      </li>
    </ul>

    <p v-if="!results.length && searchQuery" class="text-gray-500 mt-2">
      {{ localizedLabels.noResultsFound }}
    </p>
  </div>
</template>

<script>
import axios from "axios";

const localizationLabelsByLanguageCode = {
  'en-US': {
    addressPlaceholder: 'Enter address to change location or just click Choose New Center',
    clearResults: 'Clear Results',
    noResultsFound: 'No results found.',
  },
  'es-MX': {
    addressPlaceholder: 'Ingrese la dirección para cambiar la ubicación o simplemente haga clic en Elegir nuevo centro',
    clearResults: 'Borrar resultados',
    noResultsFound: 'No se encontraron resultados.',
  },
  'zh-CN': {
    addressPlaceholder: '输入地址以更改位置或只需单击选择新中心',
    clearResults: '清除结果',
    noResultsFound: '未找到结果。',
  },
  'ht-HT': {
    addressPlaceholder: 'Antre adrès la pou chanje kote ou ye oswa jis klike Chwazi Nouvo Sant',
    clearResults: 'Efase Rezilta yo',
    noResultsFound: 'Pa gen rezilta jwenn.',
  },
  'vi-VN': {
    addressPlaceholder: 'Nhập địa chỉ để thay đổi vị trí hoặc chỉ cần nhấp vào Chọn Trung tâm Mới',
    clearResults: 'Xóa kết quả',
    noResultsFound: 'Không tìm thấy kết quả.',
  },
  'pt-BR': {
    addressPlaceholder: 'Digite o endereço para alterar a localização ou apenas clique em Escolher Novo Centro',
    clearResults: 'Limpar resultados',
    noResultsFound: 'Nenhum resultado encontrado.',
  },
};


export default {
  props: ["initialSearchQuery", "language_codes"],
  data() {
    return {
      searchQuery: this.initialSearchQuery || "",
      results: [],
      showResults: true,
    };
  },
  computed: {
    localizedLabels() {
      const languageCode = this.language_codes[0] || "en-US";
      return localizationLabelsByLanguageCode[languageCode] || localizationLabelsByLanguageCode["en-US"];
    },
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
        address: address.full_address,
      };
      this.$emit("address-selected", location);
      this.showResults = false;
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

li:hover {
  background-color: rgba(240, 240, 240, 0.7);
}

.address-result-list {
  position: absolute;
  z-index: 1000;
  width: 100%;
  background-color: rgba(255, 255, 255, 0.9);
  border: 1px solid #e0e0e0;
}
</style>
