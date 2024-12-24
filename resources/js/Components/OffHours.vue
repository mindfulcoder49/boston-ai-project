<template>
  <div v-if="data" class="p-4 bg-gray-100 rounded-lg shadow">
    <h2 class="text-xl font-bold text-gray-800">
      {{ LabelsByLanguageCode[getSingleLanguageCode].constructionOffHourTitle }}
    </h2>
    <p class="text-gray-700 mb-4">
      <strong>{{ LabelsByLanguageCode[getSingleLanguageCode].startDateLabel }}:</strong> {{ new Date(data.info.start_datetime).toLocaleString() }}
    </p>
    <p class="text-gray-700 mb-4">
      <strong>{{ LabelsByLanguageCode[getSingleLanguageCode].endDateLabel }}:</strong> {{ formatDate(data.info.stop_datetime) }}
    </p>
    <ul class="space-y-2">
      <li><strong>{{ LabelsByLanguageCode[getSingleLanguageCode].applicationNumber }}:</strong> {{ data.info.app_no }}</li>
      <li><strong>{{ LabelsByLanguageCode[getSingleLanguageCode].address }}:</strong> {{ data.info.address }}</li>
      <li><strong>{{ LabelsByLanguageCode[getSingleLanguageCode].ward }}:</strong> {{ data.info.ward }}</li>
      <li><strong>{{ LabelsByLanguageCode[getSingleLanguageCode].latitude }}:</strong> {{ data.latitude }}</li>
      <li><strong>{{ LabelsByLanguageCode[getSingleLanguageCode].longitude }}:</strong> {{ data.longitude }}</li>
    </ul>
  </div>
</template>

<script setup>
defineProps({
  data: {
    type: Object,
    required: true,
  },
  language_codes: {
    type: Array,
    default: () => ['en-US'],
  },
});

import { computed } from 'vue';

const LabelsByLanguageCode = {
  'en-US': {
    constructionOffHourTitle: 'Construction Off Hour',
    startDateLabel: 'Start Date',
    endDateLabel: 'End Date',
    applicationNumber: 'Application Number',
    address: 'Address',
    ward: 'Ward',
    latitude: 'Latitude',
    longitude: 'Longitude',
  },
  'es-MX': {
    constructionOffHourTitle: 'Construcción Fuera de Horario',
    startDateLabel: 'Fecha de Inicio',
    endDateLabel: 'Fecha de Fin',
    applicationNumber: 'Número de Aplicación',
    address: 'Dirección',
    ward: 'Distrito',
    latitude: 'Latitud',
    longitude: 'Longitud',
  },
  'zh-CN': {
    constructionOffHourTitle: '非工作时间施工',
    startDateLabel: '开始日期',
    endDateLabel: '结束日期',
    applicationNumber: '申请编号',
    address: '地址',
    ward: '区',
    latitude: '纬度',
    longitude: '经度',
  },
  'ht-HT': {
    constructionOffHourTitle: 'Konstriksyon Òf Orè',
    startDateLabel: 'Dat Kòmanse',
    endDateLabel: 'Dat Fen',
    applicationNumber: 'Nimewo Aplikasyon',
    address: 'Adrès',
    ward: 'Zòn',
    latitude: 'Latitid',
    longitude: 'Lonjitid',
  },
  'vi-VN': {
    constructionOffHourTitle: 'Xây Dựng Ngoài Giờ',
    startDateLabel: 'Ngày Bắt Đầu',
    endDateLabel: 'Ngày Kết Thúc',
    applicationNumber: 'Số Hồ Sơ',
    address: 'Địa Chỉ',
    ward: 'Phường',
    latitude: 'Vĩ Độ',
    longitude: 'Kinh Độ',
  },
  'pt-BR': {
    constructionOffHourTitle: 'Construção Fora do Horário',
    startDateLabel: 'Data de Início',
    endDateLabel: 'Data de Término',
    applicationNumber: 'Número de Aplicação',
    address: 'Endereço',
    ward: 'Distrito',
    latitude: 'Latitude',
    longitude: 'Longitude',
  },
};

const getSingleLanguageCode = computed(() => language_codes[0]);

function formatDate(date) {
  return date ? new Date(date).toLocaleString() : 'N/A';
}
</script>