<template>
  <div class="">
    <input
      type="text"
      v-model="searchQuery"
      @input="searchAddresses"
      :placeholder="getAddressPlaceholderbyLanguageCode()"
      class="border w-full"
      @focus="showResults = true"
    />

    <ul v-if="results.length && showResults" class="border p-2 bg-white shadow address-result-list">
      <!-- CLose results button-->
       <li class="cursor-pointer p-2 rounded" @click="results = []">Clear Results</li>
      <li 
        v-for="(result, index) in results" 
        :key="index" 
        @click="selectAddress(result)"
        class="cursor-pointer p-2 rounded"
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
  props: ["initialSearchQuery", "language_codes"],
  data() {
    return {
      searchQuery: this.initialSearchQuery || "",
      results: [],
      showResults: true,
      addressPlaceholder: "Enter address to change location or just click Choose New Center",
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
      this.showResults = false;
    },
    getAddressPlaceholderbyLanguageCode() {
      const languageCode = this.language_codes[0];
      const placeholderTranslations = {
        'en-US': 'Enter address to change location or just click Choose New Center',
        'es-MX': 'Ingrese la dirección para cambiar la ubicación o simplemente haga clic en Elegir nuevo centro',
        'zh-CN': '输入地址以更改位置或只需单击选择新中心',
        'ht-HT': 'Antre adrès la pou chanje kote ou ye oswa jis klike Chwazi Nouvo Sant',
        'vi-VN': 'Nhập địa chỉ để thay đổi vị trí hoặc chỉ cần nhấp vào Chọn Trung tâm Mới',
        'pt-BR': 'Digite o endereço para alterar a localização ou apenas clique em Escolher Novo Centro',
      };

      return placeholderTranslations[languageCode] || 'Enter address to change location or just click Choose New Center';
    
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
