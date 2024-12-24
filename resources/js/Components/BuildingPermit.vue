<template>
  <div v-if="data" class="p-4 bg-gray-100 rounded-lg">
    <h2 class="text-xl font-bold text-gray-800">
      {{ LabelsByLanguageCode[getSingleLanguageCode].buildingPermitTitle }}
    </h2>
    <p class="text-gray-700 mb-4">
      <strong>{{ LabelsByLanguageCode[getSingleLanguageCode].dateLabel }}:</strong> {{ new Date(data.date).toLocaleString() }}
    </p>
    <ul class="space-y-2">
      <li><strong>{{ LabelsByLanguageCode[getSingleLanguageCode].permitNumber }}:</strong> {{ data.info.permitnumber }}</li>
      <li><strong>{{ LabelsByLanguageCode[getSingleLanguageCode].workType }}:</strong> {{ data.info.worktype }}</li>
      <li><strong>{{ LabelsByLanguageCode[getSingleLanguageCode].description }}:</strong> {{ data.info.description }}</li>
      <li><strong>{{ LabelsByLanguageCode[getSingleLanguageCode].status }}:</strong> {{ data.info.status }}</li>
      <li><strong>{{ LabelsByLanguageCode[getSingleLanguageCode].declaredValue }}:</strong> {{ data.info.declared_valuation }}</li>
      <li><strong>{{ LabelsByLanguageCode[getSingleLanguageCode].totalFees }}:</strong> {{ data.info.total_fees }}</li>
      <li><strong>{{ LabelsByLanguageCode[getSingleLanguageCode].expirationDate }}:</strong> {{ formatDate(data.info.expiration_date) }}</li>
      <li><strong>{{ LabelsByLanguageCode[getSingleLanguageCode].address }}:</strong> {{ data.info.address }}, {{ data.info.city }}, {{ data.info.state }} {{ data.info.zip }}</li>
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
    buildingPermitTitle: 'Building Permit',
    dateLabel: 'Date',
    permitNumber: 'Permit Number',
    workType: 'Work Type',
    description: 'Description',
    status: 'Status',
    declaredValue: 'Declared Value',
    totalFees: 'Total Fees',
    expirationDate: 'Expiration Date',
    address: 'Address',
  },
  'es-MX': {
    buildingPermitTitle: 'Permiso de Construcción',
    dateLabel: 'Fecha',
    permitNumber: 'Número de Permiso',
    workType: 'Tipo de Trabajo',
    description: 'Descripción',
    status: 'Estado',
    declaredValue: 'Valor Declarado',
    totalFees: 'Tarifas Totales',
    expirationDate: 'Fecha de Expiración',
    address: 'Dirección',
  },
  'zh-CN': {
    buildingPermitTitle: '建筑许可',
    dateLabel: '日期',
    permitNumber: '许可证号',
    workType: '工作类型',
    description: '描述',
    status: '状态',
    declaredValue: '声明价值',
    totalFees: '总费用',
    expirationDate: '到期日',
    address: '地址',
  },
  'ht-HT': {
    buildingPermitTitle: 'Pèmi Bati',
    dateLabel: 'Dat',
    permitNumber: 'Nimewo Pèmi',
    workType: 'Kalite Travay',
    description: 'Deskripsyon',
    status: 'Estati',
    declaredValue: 'Valè Deklare',
    totalFees: 'Frais Total',
    expirationDate: 'Dat Eksperyasyon',
    address: 'Adrès',
  },
  'pt-BR': {
    buildingPermitTitle: 'Licença de Construção',
    dateLabel: 'Encontro',
    permitNumber: 'Número do Permissão',
    workType: 'Tipo de Trabalho',
    description: 'Descrição',
    status: 'Estado',
    declaredValue: 'Valor Declarado',
    totalFees: 'Taxas Totais',
    expirationDate: 'Data de Expiração',
    address: 'Endereço',
  },
};

const getSingleLanguageCode = computed(() => props.language_codes[0]);

function formatDate(date) {
  return date ? new Date(date).toLocaleString() : 'N/A';
}
</script>