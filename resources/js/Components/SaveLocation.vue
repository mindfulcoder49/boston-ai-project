<template>
  <div class="w-full my-2 text-center px-3 py-2 border border-gray-300 save-location">
    <!-- Tab Navigation -->
    <div class="flex justify-center border-b border-gray-300">
      <!-- Current Location Tab -->
      <button
        @click="setActiveTab('current')"
        :class="{ 'tab-button': true, 'active': activeTab === 'current' }"
      >
        {{ LabelsByLanguageCode[getSingleLanguageCode].currentLocation }}
      </button>

      <!-- Saved Locations Tabs -->
      <template v-if="userLocations.length">
        <button
          v-for="(savedLocation, index) in userLocations"
          :key="savedLocation.id"
          @click="setActiveTab(savedLocation.id)"
          :class="{ 'tab-button': true, 'active': activeTab === savedLocation.id }"
        >
          {{ capitalize(savedLocation.name) }}
        </button>
      </template>
    </div>

    <!-- Tab Content: Current Location -->
    <div v-if="activeTab === 'current'" class="mb-6 w-full current-location p-3">
      <h4 class="text-lg font-medium text-gray-700 mb-2">
        {{ LabelsByLanguageCode[getSingleLanguageCode].currentLocation }}
      </h4>

      <div class="flex flex-wrap justify-center items-center gap-2">
        <span class="text-gray-600">Lat: {{ location.latitude }}</span>
        <span class="text-gray-600">Lng: {{ location.longitude }}</span>
        <span v-if="location.address" class="text-gray-600">
          {{ LabelsByLanguageCode[getSingleLanguageCode].address }}: {{ location.address }}
        </span>
      </div>


      <div class="mt-4 flex flex-wrap justify-center items-center gap-2">
        <select
          id="location-report"
          v-model="location.report"
          class="px-3 py-2 shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm pl-2 pr-8"
        >
          <option value="off">{{ LabelsByLanguageCode[getSingleLanguageCode].off }}</option>
          <option value="daily">{{ LabelsByLanguageCode[getSingleLanguageCode].daily }}</option>
          <option value="weekly">{{ LabelsByLanguageCode[getSingleLanguageCode].weekly }}</option>
        </select>
        <select
          id="location-name"
          v-model="selectedName"
          class="px-3 py-2 shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm pl-2 pr-8"
        >
          <option value="home">{{ LabelsByLanguageCode[getSingleLanguageCode].home }}</option>
          <option value="work">{{ LabelsByLanguageCode[getSingleLanguageCode].work }}</option>
          <option value="other">{{ LabelsByLanguageCode[getSingleLanguageCode].other }}</option>
        </select>
        <!-- language input, a free text field -->
        <input
          type="text"
          v-model="location.language"
          class="px-3 py-2 shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm
          pl-2 pr-8"
          placeholder="Language"
        />
        <button
          @click="saveLocation"
          class="px-4 py-2 text-white shadow-sm transition-colors bg-green-500 hover:bg-green-600"
        >
          {{
             LabelsByLanguageCode[getSingleLanguageCode].saveLocation
          }}
        </button>
        <span v-if="maxLocationsReached" class="text-red-500">
          {{ LabelsByLanguageCode[getSingleLanguageCode].maxLocationsReached }}
        </span>
      </div>
    </div>

    <!-- Tab Content: Individual Saved Locations -->
    <template v-else-if="userLocations.length">
      <div
        v-for="savedLocation in userLocations"
        :key="savedLocation.id"
        v-show="activeTab === savedLocation.id"
        class="w-full saved-location p-3"
      >
        <h4 class="text-lg font-medium text-gray-700 mb-2">
          {{ LabelsByLanguageCode[getSingleLanguageCode].savedLocation }}
        </h4>
        <div class="bg-gray-50 p-3 rounded-md shadow-sm flex space-x-4 items-center">
          <p class="font-sm text-gray-800">{{ capitalize(savedLocation.name) }}</p>
          <p class="text-sm text-gray-600">
            Lat: {{ savedLocation.latitude }}
          </p>
          <p class="text-sm text-gray-600">
            Lng: {{ savedLocation.longitude }}
          </p>
          <p v-if="savedLocation.address" class="text-sm text-gray-600">
            {{ LabelsByLanguageCode[getSingleLanguageCode].address }}: {{ savedLocation.address }}
          </p>
        </div>

        <!--Change report dropdown-->
        <div class="mt-4 flex flex-wrap justify-center items-center gap-2">
          <select
            id="location-report"
            v-model="savedLocation.report"
            class="px-3 py-2 shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm pl-2 pr-8"
          >
            <option value="off">{{ LabelsByLanguageCode[getSingleLanguageCode].off }}</option>
            <option value="daily">{{ LabelsByLanguageCode[getSingleLanguageCode].daily }}</option>
            <option value="weekly">{{ LabelsByLanguageCode[getSingleLanguageCode].weekly }}</option>
          </select>
          <select
            id="location-name"
            v-model="savedLocation.name"
            class="px-3 py-2 shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm pl-2 pr-8"
          >
            <option value="home">{{ LabelsByLanguageCode[getSingleLanguageCode].home }}</option>
            <option value="work">{{ LabelsByLanguageCode[getSingleLanguageCode].work }}</option>
            <option value="other">{{ LabelsByLanguageCode[getSingleLanguageCode].other }}</option>
          </select>
          <!-- language input, a free text field -->
          <input
            type="text"
            v-model="savedLocation.language"
            class="px-3 py-2 shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm
            pl-2 pr-8"
            placeholder="Language"
          />
          <button
            @click="updateLocation(savedLocation.id, savedLocation)"
            class="px-4 py-2 bg-blue-500 text-white shadow-sm hover:bg-blue-600 transition-colors"
          >
            {{ LabelsByLanguageCode[getSingleLanguageCode].update }}
          </button>

        </div>

        <div class="mt-4 flex justify-center gap-2">
          <button
            @click="emitLocation(savedLocation)"
            class="px-4 py-2 bg-blue-500 text-white shadow-sm hover:bg-blue-600 transition-colors"
          >
            {{ LabelsByLanguageCode[getSingleLanguageCode].load }}
          </button>
          <button
            @click="deleteLocation(savedLocation.id)"
            class="px-4 py-2 bg-red-500 text-white shadow-sm hover:bg-red-600 transition-colors"
          >
            {{ LabelsByLanguageCode[getSingleLanguageCode].delete }}
          </button>
          <button
            @click="dispatchReport(savedLocation)"
            class="px-4 py-2 bg-green-500 text-white shadow-sm hover:bg-green-600 transition-colors"
          >
            {{ LabelsByLanguageCode[getSingleLanguageCode].sendReport }}
          </button>
          <span v-if="reportDispatched" class="text-green-500 content-center">
            {{ LabelsByLanguageCode[getSingleLanguageCode].reportSent }}
          </span>
        </div>
      </div>
    </template>

    <!-- No Saved Locations Fallback -->
    <div v-else class="w-full saved-location p-3">
      <h4 class="text-lg font-medium text-gray-700 mb-2">
        {{ LabelsByLanguageCode[getSingleLanguageCode].noSavedLocations }}
      </h4>
    </div>
  </div>
</template>


<script setup>
import { ref, watch, onMounted, computed } from 'vue';
import axios from 'axios';

// Props
const props = defineProps({
  location: {
    type: Object,
    required: true,
  },
  language_codes: {
    type: Array,
    required: true,
  },
});

// Emit
const emit = defineEmits(['load-location']);

// Reactive States
const selectedName = ref('home');
const isSaved = ref(false);
const saving = ref(false);
const userLocations = ref([]);
const activeTab = ref('current');
const reportDispatched = ref(false);
const maxLocationsReached = ref(false);

// Methods
/*
Route::post('/locations/{location}/dispatch-report', [LocationController::class, 'dispatchLocationReportEmail'])->name('locations.dispatch-report');
*/

const dispatchReport = async (location) => {
  try {
    await axios.post(`/locations/${location.id}/dispatch-report`);
    reportDispatched.value = true;
    //set the reportDispatched value to false after 5 seconds
    setTimeout(() => {
      reportDispatched.value = false;
    }, 3000);
  } catch (error) {
    console.error('Error dispatching report:', error);
  }
};


const setActiveTab = (tab) => {
  activeTab.value = tab;
};

const fetchUserLocations = async (mode) => {
  try {
    const response = await axios.get('/locations');
    userLocations.value = response.data;
    checkIfSaved();
    if (mode === 'set' && userLocations.value.length) {
      emitLocation(userLocations.value[0]);
      //set the active tab to the first location
      setActiveTab(userLocations.value[0].id);
    }
  } catch (error) {
    console.error('Error fetching locations:', error);
  }
};

const checkIfSaved = () => {
  isSaved.value = userLocations.value.some(
    (loc) =>
      loc.latitude === location.latitude && loc.longitude === location.longitude
  );
};

const saveLocation = async () => {
  

  //if (isSaved.value || saving.value) return;

  saving.value = true;
  try {
    const payload = {
      name: selectedName.value,
      latitude: props.location.latitude,
      longitude: props.location.longitude,
      address: props.location.address || null,
      report: props.location.report || 'off',
      language: props.location.language || 'English',
    };
    const response = await axios.post('/locations', payload);
    userLocations.value.push(response.data);
    isSaved.value = true;
    saving.value = false;
  } catch (error) {
    if (error.response.status === 401) {
      window.location.href = '/login';
    } else if (error.response.status === 403) {
      maxLocationsReached.value = true;
    } else {
      console.error('Error saving location:', error);
    }
    saving.value = false;
  }
};

const updateLocation = async (id, payload) => {
  try {
    await axios.put(`/locations/${id}`, payload);
  } catch (error) {
    console.error('Error updating location:', error);
  }
};

const deleteLocation = async (id) => {
  try {
    await axios.delete(`/locations/${id}`);
    userLocations.value = userLocations.value.filter((loc) => loc.id !== id);
    checkIfSaved();
    //select the current location tab after deleting a location
    setActiveTab('current');
  } catch (error) {
    console.error('Error deleting location:', error);
  }
};

const emitLocation = (location) => {
  emit('load-location', location);
};

// Utilities
const capitalize = (str) => str[0].toUpperCase() + str.slice(1);

const LabelsByLanguageCode = {
  'en-US': {
    currentLocation: 'Current Location',
    saveLocation: 'Save Location',
    locationSaved: 'Location Saved',
    saving: 'Saving...',
    delete: 'Delete',
    load: 'Load',
    noSavedLocations: "You haven’t saved any locations yet. Save your current location to get started.",
    savedLocation: 'Saved Location',
    selectName: 'Select Name',
    home: 'Home',
    work: 'Work',
    other: 'Other',
    off: 'Off',
    daily: 'Daily',
    weekly: 'Weekly',
    address: 'Address',
    update: 'Update',
    sendReport: 'Send Report',
    reportSent: 'Report Sent',
    maxLocationsReached: 'You have reached the maximum number of saved locations.',
  },
  'es-MX': {
    currentLocation: 'Ubicación Actual',
    saveLocation: 'Guardar Ubicación',
    locationSaved: 'Ubicación Guardada',
    saving: 'Guardando...',
    delete: 'Eliminar',
    load: 'Cargar',
    noSavedLocations: 'Aún no has guardado ubicaciones. Guarda tu ubicación actual para comenzar.',
    savedLocation: 'Ubicación Guardada',
    selectName: 'Seleccionar Nombre',
    home: 'Casa',
    work: 'Trabajo',
    other: 'Otro',
    off: 'Apagado',
    daily: 'Diario',
    weekly: 'Semanal',
    address: 'Dirección',
    update: 'Actualizar',
    sendReport: 'Enviar Reporte',
    reportSent: 'Reporte Enviado',
    maxLocationsReached: 'Has alcanzado el número máximo de ubicaciones guardadas.',
  },
  'zh-CN': {
    currentLocation: '当前位置',
    saveLocation: '保存位置',
    locationSaved: '位置已保存',
    saving: '保存中...',
    delete: '删除',
    load: '加载',
    noSavedLocations: '您还没有保存任何位置。保存您当前的位置以开始。',
    savedLocation: '已保存的位置',
    selectName: '选择名称',
    home: '家',
    work: '工作',
    other: '其他',
    off: '关闭',
    daily: '每日',
    weekly: '每周',
    address: '地址',
    update: '更新',
    sendReport: '发送报告',
    reportSent: '报告已发送',
    maxLocationsReached: '您已达到保存位置的最大数量。',
  },
  'ht-HT': {
    currentLocation: 'Kote Kounye a',
    saveLocation: 'Sove Kote a',
    locationSaved: 'Kote Sove',
    saving: 'Ap sove...',
    delete: 'Efase',
    load: 'Chaje',
    noSavedLocations: 'Ou poko sove okenn kote. Sove kote w ye kounye a pou kòmanse.',
    savedLocation: 'Kote Sove',
    selectName: 'Chwazi Non',
    home: 'Kay',
    work: 'Travay',
    other: 'Lòt',
    off: 'Fèmen',
    daily: 'Chak jou',
    weekly: 'Chak semèn',
    address: 'Adrès',
    update: 'Mizajou',
    sendReport: 'Voye Rapò',
    reportSent: 'Rapò voye',
    maxLocationsReached: 'Ou rive nan kantite maksimòm kote sove yo.',
  },
  'vi-VN': {
    currentLocation: 'Vị Trí Hiện Tại',
    saveLocation: 'Lưu Vị Trí',
    locationSaved: 'Đã Lưu Vị Trí',
    saving: 'Đang lưu...',
    delete: 'Xóa',
    load: 'Tải',
    noSavedLocations: 'Bạn chưa lưu bất kỳ vị trí nào. Lưu vị trí hiện tại của bạn để bắt đầu.',
    savedLocation: 'Vị Trí Đã Lưu',
    selectName: 'Chọn Tên',
    home: 'Nhà',
    work: 'Công việc',
    other: 'Khác',
    off: 'Tắt',
    daily: 'Hàng ngày',
    weekly: 'Hàng tuần',
    address: 'Địa chỉ',
    update: 'Cập nhật',
    sendReport: 'Gửi Báo Cáo',
    reportSent: 'Báo cáo đã gửi',
    maxLocationsReached: 'Bạn đã đạt số lượng tối đa của vị trí đã lưu.',  
  },
  'pt-BR': {
    currentLocation: 'Localização Atual',
    saveLocation: 'Salvar Localização',
    locationSaved: 'Localização Salva',
    saving: 'Salvando...',
    delete: 'Excluir',
    load: 'Carregar',
    noSavedLocations: 'Você ainda não salvou nenhuma localização. Salve sua localização atual para começar.',
    savedLocation: 'Localização Salva',
    selectName: 'Selecionar Nome',
    home: 'Casa',
    work: 'Trabalho',
    other: 'Outro',
    off: 'Desligado',
    daily: 'Diário',
    weekly: 'Semanal',
    address: 'Endereço',
    update: 'Atualizar',
    sendReport: 'Enviar Relatório',
    reportSent: 'Relatório Enviado',
    maxLocationsReached: 'Você atingiu o número máximo de localizações salvas.',
  },
};


const getSingleLanguageCode = computed(() => props.language_codes[0]);

// Watchers
watch(() => location, checkIfSaved);

// Lifecycle Hooks, onmounted fetchUserLocation and emit the first location
onMounted(() => {
  fetchUserLocations('set');
  //if props.location.report is not set, set it to 'off'
  if (!props.location.report) {
    props.location.report = 'off';
    props.location.language = 'English';
  }
});
</script>

<style scoped>
button[disabled] {
  cursor: not-allowed;
}

.tab-button {
  padding: 10px 15px;
  border: none;
  background-color: #f9f9f9;
  cursor: pointer;
  border-bottom: 2px solid transparent;
  transition: background-color 0.3s, border-bottom-color 0.3s;
}

.tab-button.active {
  border-bottom: 2px solid #3490dc;
  background-color: #ffffff;
}

.tab-button:hover {
  background-color: #f0f0f0;
}
</style>
