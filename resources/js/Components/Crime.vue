<template>
  <div v-if="data" class="p-4 bg-gray-100 rounded-lg shadow">
    <h2 class="text-xl font-bold text-gray-800">
      {{ LabelsByLanguageCode[getSingleLanguageCode].crimeReportTitle }}
    </h2>
    <p class="text-gray-700 mb-4">
      <strong>{{ LabelsByLanguageCode[getSingleLanguageCode].dateLabel }}:</strong> {{ new Date(data.date).toLocaleString() }}
    </p>
    <ul class="space-y-2">
      <li><strong>{{ LabelsByLanguageCode[getSingleLanguageCode].incidentNumber }}:</strong> {{ data.info.incident_number }}</li>
      <li><strong>{{ LabelsByLanguageCode[getSingleLanguageCode].offense }}:</strong> {{ data.info.offense_description }}</li>
      <li><strong>{{ LabelsByLanguageCode[getSingleLanguageCode].district }}:</strong> {{ data.info.district }}</li>
      <li><strong>{{ LabelsByLanguageCode[getSingleLanguageCode].street }}:</strong> {{ data.info.street }}</li>
      <li><strong>{{ LabelsByLanguageCode[getSingleLanguageCode].day }}:</strong> {{ data.info.day_of_week }}</li>
      <li><strong>{{ LabelsByLanguageCode[getSingleLanguageCode].time }}:</strong> {{ formatTime(data.info.hour) }}</li>
    </ul>
  </div>
</template>

<script setup>
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

import { computed } from 'vue';

const LabelsByLanguageCode = {
  'en-US': {
    crimeReportTitle: 'Crime Report',
    dateLabel: 'Date',
    incidentNumber: 'Incident Number',
    offense: 'Offense',
    district: 'District',
    street: 'Street',
    day: 'Day',
    time: 'Time',
  },
  'es-MX': {
    crimeReportTitle: 'Reporte de Delito',
    dateLabel: 'Fecha',
    incidentNumber: 'Número de Incidente',
    offense: 'Delito',
    district: 'Distrito',
    street: 'Calle',
    day: 'Día',
    time: 'Hora',
  },
  'zh-CN': {
    crimeReportTitle: '犯罪报告',
    dateLabel: '日期',
    incidentNumber: '事件编号',
    offense: '犯罪',
    district: '地区',
    street: '街道',
    day: '天',
    time: '时间',
  },
  'ht-HT': {
    crimeReportTitle: 'Rapò Krim',
    dateLabel: 'Dat',
    incidentNumber: 'Nimewo Ensidan',
    offense: 'Delit',
    district: 'Distrik',
    street: 'Lari',
    day: 'Jou',
    time: 'Lè',
  },
  'vi-VN': {
    crimeReportTitle: 'Báo Cáo Tội Phạm',
    dateLabel: 'Ngày',
    incidentNumber: 'Số Vụ',
    offense: 'Tội',
    district: 'Khu Vực',
    street: 'Đường',
    day: 'Ngày',
    time: 'Thời Gian',
  },
  'pt-BR': {
    crimeReportTitle: 'Relatório de Crime',
    dateLabel: 'Data',
    incidentNumber: 'Número do Incidente',
    offense: 'Crime',
    district: 'Distrito',
    street: 'Rua',
    day: 'Dia',
    time: 'Hora',
  },
};

const getSingleLanguageCode = computed(() => props.language_codes[0]);

function formatTime(hour) {
  const date = new Date();
  date.setHours(hour, 0, 0);
  return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}
</script>