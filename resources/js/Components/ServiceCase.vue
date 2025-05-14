<template>
    <div
      v-if="data"
      class="p-4 bg-gray-100 flex w-full h-full"
      :class="{ 'w-1/2': hasPhoto }"
    >
      <div class="flex-grow mr-4"> <!-- Added mr-4 for spacing if photo exists, wrapper for all text content -->
        
        <!-- Live Data from BOS:311 -->
        <div v-if="liveApiData && Object.keys(liveApiData).length > 0" class="mb-4 p-3 border border-gray-300 bg-white shadow rounded">
          <h3 class="text-md font-semibold text-gray-700">Live Data from BOS:311</h3>
          <ul class="space-y-1 mt-2 text-sm text-gray-600">
            <li v-if="liveApiData.status"><strong>Live Status:</strong> {{ liveApiData.status }}</li>
            <li v-if="liveApiData.status_notes"><strong>Status Notes:</strong> {{ liveApiData.status_notes }}</li>
            <li v-if="liveApiData.service_name"><strong>Live Service Name:</strong> {{ liveApiData.service_name }}</li>
            <li v-if="liveApiData.description"><strong>Live Description:</strong> <span class="whitespace-pre-wrap">{{ liveApiData.description }}</span></li>
            <li v-if="liveApiData.address"><strong>Live Address:</strong> {{ liveApiData.address }}</li>
            <li v-if="liveApiData.agency_responsible"><strong>Live Agency Responsible:</strong> {{ liveApiData.agency_responsible }}</li>
            <li v-if="liveApiData.service_notice"><strong>Service Notice:</strong> {{ liveApiData.service_notice }}</li>
            <li v-if="liveApiData.requested_datetime"><strong>Reported (Live):</strong> {{ formatDate(liveApiData.requested_datetime) }}</li>
            <li v-if="liveApiData.updated_datetime"><strong>Last Updated (Live):</strong> {{ formatDate(liveApiData.updated_datetime) }}</li>
            <li v-if="liveApiData.expected_datetime"><strong>Expected Resolution (Live):</strong> {{ formatDate(liveApiData.expected_datetime) }}</li>
            <li v-if="liveApiData.media_url">
              <strong>Live Media:</strong>
              <img :src="liveApiData.media_url" alt="Live media from BOS:311" class="max-w-full md:max-w-sm h-auto mt-1 border rounded"/>
            </li>
          </ul>
        </div>
        <div v-else-if="liveApiData && Object.keys(liveApiData).length === 0 && !isLoadingLiveData && !liveDataError" class="mb-4 text-sm text-gray-500 p-3 border border-gray-300 bg-gray-50 shadow rounded">
           <p>Checked for live data from BOS:311; no specific details found or applicable for this case ID.</p>
        </div>

        <!-- Live Data Error Display -->
        <div v-if="liveDataError" class="mb-4 text-sm text-red-600 bg-red-100 p-3 rounded border border-red-300">
          <p><strong>Error fetching live data from BOS:311:</strong></p>
          <p class="whitespace-pre-wrap">{{ liveDataError }}</p>
          <p class="mt-2">
            This may mean the case has been deleted, is an internal case, or the ID is incorrect.
            You can try to 
            <a :href="`https://311.boston.gov/tickets/${data.case_enquiry_id}`" target="_blank" rel="noopener noreferrer" class="text-blue-700 hover:underline font-semibold">
              view this case directly on the BOS:311 portal
            </a>.
          </p>
        </div>
        
        <!-- Historical Case Info -->
        <div class="case-info"> <!-- flex-grow removed from here, parent div has it -->
          <h2 class="text-xl font-bold text-gray-800">
            {{ LabelsByLanguageCode[getSingleLanguageCode].caseTitle }} (Historical Record)
          </h2>
          <p class="text-gray-700 mb-4">
            <strong>{{ LabelsByLanguageCode[getSingleLanguageCode].dateLabel }}:</strong> {{ new Date(data.alcivartech_date).toLocaleString() }}
          </p>
          <ul class="space-y-2">
            <li><strong>{{ LabelsByLanguageCode[getSingleLanguageCode].caseId }}:</strong> {{ data.case_enquiry_id }}</li>
            <li><strong>{{ LabelsByLanguageCode[getSingleLanguageCode].status }}:</strong> {{ data.case_status }}</li>
            <li><strong>{{ LabelsByLanguageCode[getSingleLanguageCode].title }}:</strong> {{ data.case_title }}</li>
            <li><strong>{{ LabelsByLanguageCode[getSingleLanguageCode].reason }}:</strong> {{ data.reason }}</li>
            <li><strong>{{ LabelsByLanguageCode[getSingleLanguageCode].subject }}:</strong> {{ data.subject }}</li>
            <li><strong>{{ LabelsByLanguageCode[getSingleLanguageCode].location }}:</strong> {{ data.location }}</li>
            <li><strong>{{ LabelsByLanguageCode[getSingleLanguageCode].neighborhood }}:</strong> {{ data.neighborhood }}</li>
            <li><strong>{{ LabelsByLanguageCode[getSingleLanguageCode].source }}:</strong> {{ data.source }}</li>
            <li><strong>{{ LabelsByLanguageCode[getSingleLanguageCode].department }}:</strong> {{ data.department }}</li>
            <li><strong>{{ LabelsByLanguageCode[getSingleLanguageCode].closureDate }}:</strong> {{ formatDate(data.closed_dt) }}</li>
          </ul>

          <div class="mt-4">
            <button
              @click="fetchLiveDetails"
              :disabled="isLoadingLiveData || !data?.case_enquiry_id"
              class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 disabled:bg-gray-300"
            >
              {{ isLoadingLiveData ? 'Loading Live Data...' : 'Refresh Live Data from BOS:311' }}
            </button>
          </div>
          <!-- Original live data sections removed from here as they are moved above -->
        </div>
      </div>
  
      <OneImageCarousel v-if="hasPhoto" :dataPoints="parsedPhotos" @on-image-click="onImageClick" class="ml-0" /> <!-- ml-4 removed as parent has mr-4 -->
    </div>
</template>
  
<script setup>
  import { computed, defineProps, defineEmits, ref, onMounted, watch } from 'vue';
  import OneImageCarousel from './OneImageCarousel.vue';
  import axios from 'axios';
  
  const props = defineProps({
    data: {
      type: Object,
      required: true,
    },
    language_codes: {
      type: Array,
      default: () => ['en-US'],
    },
  });
  
  const LabelsByLanguageCode = {
    'en-US': {
      caseTitle: '311 Case',
      dateLabel: 'Date',
      caseId: 'Case ID',
      status: 'Status',
      title: 'Title',
      reason: 'Reason',
      subject: 'Subject',
      location: 'Location',
      neighborhood: 'Neighborhood',
      source: 'Source',
      department: 'Department',
      closureDate: 'Closure Date',
    },
    'es-MX': {
      caseTitle: 'Caso 311',
      dateLabel: 'Fecha',
      caseId: 'ID de Caso',
      status: 'Estado',
      title: 'Título',
      reason: 'Razón',
      subject: 'Asunto',
      location: 'Ubicación',
      neighborhood: 'Vecindario',
      source: 'Fuente',
      department: 'Departamento',
      closureDate: 'Fecha de Cierre',
    },
    'zh-CN': {
      caseTitle: '311案例',
      dateLabel: '日期',
      caseId: '案例编号',
      status: '状态',
      title: '标题',
      reason: '原因',
      subject: '主题',
      location: '位置',
      neighborhood: '社区',
      source: '来源',
      department: '部门',
      closureDate: '关闭日期',
    },
    'ht-HT': {
      caseTitle: 'Kaz 311',
      dateLabel: 'Dat',
      caseId: 'ID Kaz',
      status: 'Estati',
      title: 'Tit',
      reason: 'Rezon',
      subject: 'Sijè',
      location: 'Kote',
      neighborhood: 'Katye',
      source: 'Sous',
      department: 'Depatman',
      closureDate: 'Dat Fèmen',
    },
    'vi-VN': {
      caseTitle: 'Trường hợp 311',
      dateLabel: 'Ngày',
      caseId: 'ID Trường hợp',
      status: 'Trạng thái',
      title: 'Tiêu đề',
      reason: 'Lý do',
      subject: 'Chủ đề',
      location: 'Vị trí',
      neighborhood: 'Hàng xóm',
      source: 'Nguồn',
      department: 'Bộ phận',
      closureDate: 'Ngày đóng cửa',
    },
    'pt-BR': { 
      caseTitle: 'Caso 311',
      dateLabel: 'Data',
      caseId: 'ID do Caso',
      status: 'Estado',
      title: 'Título',
      reason: 'Razão',
      subject: 'Assunto',
      location: 'Localização',
      neighborhood: 'Vizinhança',
      source: 'Fonte',
      department: 'Departamento',
      closureDate: 'Data de Encerramento',
    },   
  };
  
  const getSingleLanguageCode = computed(() => props.language_codes[0]);
  
  function formatDate(date) {
    return date ? new Date(date).toLocaleString() : 'N/A';
  }
  
  const parsedPhotos = computed(() => {
    const photos = [];
    if (props.data?.closed_photo) {
      props.data.closed_photo.split('|').forEach(photoUrl => {
        photos.push({ info: { closed_photo: photoUrl, type: '311 Case' } });
      });
    }
    if (props.data?.submitted_photo) {
      props.data.submitted_photo.split('|').forEach(photoUrl => {
        photos.push({ info: { submitted_photo: photoUrl, type: '311 Case' } });
      });
    }
    return photos;
  });
  
  const hasPhoto = computed(() => props.data?.closed_photo || props.data?.submitted_photo);
  const emit = defineEmits(['on-image-click']);
  const onImageClick = (photo) => {
    emit('on-image-click', photo);
  };

  const liveApiData = ref(null);
  const isLoadingLiveData = ref(false);
  const liveDataError = ref(null);



  const fetchLiveDetails = async () => {
    if (!props.data || !props.data.case_enquiry_id) {
      liveDataError.value = 'Case ID is missing from the current record.';
      liveApiData.value = null; // Clear previous data if any
      return;
    }
    isLoadingLiveData.value = true;
    liveDataError.value = null;
    liveApiData.value = null; 

    try {
      const response = await axios.get(`/api/311-case/live/${props.data.case_enquiry_id}`);
      if (response.data && response.data.data) {
        if (response.data.data.length > 0) {
          liveApiData.value = response.data.data[0]; // API returns an array
        } else {
          // API returned success but an empty array for data.
          liveApiData.value = {}; // Indicate that we checked but found no specific item.
        }
      } else {
        liveDataError.value = 'Unexpected API response format when fetching live data.';
        liveApiData.value = {}; 
      }
    } catch (error) {
      console.error('Error fetching live 311 case data:', error);
      if (error.response && error.response.data && error.response.data.error) {
          let details = error.response.data.details;
          if (typeof details === 'object') {
            details = JSON.stringify(details);
          }
          liveDataError.value = `API Error: ${error.response.data.error}${details ? ` (Details: ${details})` : ''}`;
      } else if (error.request) {
          liveDataError.value = 'No response received from server. Please check network or try again.';
      } else {
          liveDataError.value = 'Failed to fetch live data due to a client-side error. Please try again.';
      }
    } finally {
      isLoadingLiveData.value = false;
    }
  };

  onMounted(() => {
    if (props.data && props.data.case_enquiry_id && !liveApiData.value) {
      //fetchLiveDetails();
    }
    if (props.data.live_details) {
      liveApiData.value = props.data.live_details;
    }
  });
  
  watch(() => props.data, (newData, oldData) => {
    // Reset live data if the case_enquiry_id changes or data becomes null
    /*
    if (!newData || (oldData && newData.case_enquiry_id !== oldData.case_enquiry_id)) {
      liveApiData.value = null;
      liveDataError.value = null;
    }
    
    if (newData && newData.case_enquiry_id) {
      // Fetch new data if case_enquiry_id is present and different, or if it's the first time with data
      if (!oldData || newData.case_enquiry_id !== oldData.case_enquiry_id || !liveApiData.value) {
         fetchLiveDetails();
      }
    } */

    if (props.data.live_details) {
      liveApiData.value = props.data.live_details;
    }
  }, { deep: true }); // Use deep watch if internal properties of data might change without data itself being a new object.
                      // If data is always a new object on change, deep might not be necessary.
                      // Given it's a prop, it's safer to assume it might be mutated or replaced.
  
 </script>

<style scoped></style>