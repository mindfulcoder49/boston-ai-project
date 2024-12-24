<template>
    <div
      v-if="data"
      class="p-4 bg-gray-100 rounded-lg shadow flex w-full"
      :class="{ 'w-1/2': hasPhoto }"
    >
      <div class="case-info">
        <h2 class="text-xl font-bold text-gray-800">
          {{ LabelsByLanguageCode[getSingleLanguageCode].caseTitle }}
        </h2>
        <p class="text-gray-700 mb-4">
          <strong>{{ LabelsByLanguageCode[getSingleLanguageCode].dateLabel }}:</strong> {{ new Date(data.date).toLocaleString() }}
        </p>
        <ul class="space-y-2">
          <li><strong>{{ LabelsByLanguageCode[getSingleLanguageCode].caseId }}:</strong> {{ data.info.case_enquiry_id }}</li>
          <li><strong>{{ LabelsByLanguageCode[getSingleLanguageCode].status }}:</strong> {{ data.info.case_status }}</li>
          <li><strong>{{ LabelsByLanguageCode[getSingleLanguageCode].title }}:</strong> {{ data.info.case_title }}</li>
          <li><strong>{{ LabelsByLanguageCode[getSingleLanguageCode].reason }}:</strong> {{ data.info.reason }}</li>
          <li><strong>{{ LabelsByLanguageCode[getSingleLanguageCode].subject }}:</strong> {{ data.info.subject }}</li>
          <li><strong>{{ LabelsByLanguageCode[getSingleLanguageCode].location }}:</strong> {{ data.info.location }}</li>
          <li><strong>{{ LabelsByLanguageCode[getSingleLanguageCode].neighborhood }}:</strong> {{ data.info.neighborhood }}</li>
          <li><strong>{{ LabelsByLanguageCode[getSingleLanguageCode].source }}:</strong> {{ data.info.source }}</li>
          <li><strong>{{ LabelsByLanguageCode[getSingleLanguageCode].department }}:</strong> {{ data.info.department }}</li>
          <li><strong>{{ LabelsByLanguageCode[getSingleLanguageCode].closureDate }}:</strong> {{ formatDate(data.info.closed_dt) }}</li>
        </ul>
      </div>
  
      <OneImageCarousel v-if="hasPhoto" :dataPoints="parsedPhotos" @on-image-click="onImageClick" />
    </div>
  </template>
  
  <script setup>
  import { computed, defineProps, defineEmits } from 'vue';
  import OneImageCarousel from './OneImageCarousel.vue';
  
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
    if (props.data.info?.closed_photo) {
      props.data.info.closed_photo.split('|').forEach(photoUrl => {
        photos.push({ info: { closed_photo: photoUrl, type: '311 Case' } });
      });
    }
    if (props.data.info?.submitted_photo) {
      props.data.info.submitted_photo.split('|').forEach(photoUrl => {
        photos.push({ info: { submitted_photo: photoUrl, type: '311 Case' } });
      });
    }
    return photos;
  });
  
  const hasPhoto = computed(() => props.data.info?.closed_photo || props.data.info?.submitted_photo);
  const emit = defineEmits(['on-image-click']);
  const onImageClick = (photo) => {
    emit('on-image-click', photo);
  };
  </script>

<style scoped></style>