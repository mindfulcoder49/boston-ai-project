<template>
  <div v-if="data" class="p-4 bg-gray-100 rounded-lg">
    <h2 class="text-xl font-bold text-gray-800">
      {{ LabelsByLanguageCode[getSingleLanguageCode].buildingPermitTitle }}
    </h2>
    <p class="text-gray-700 mb-4">
      <strong>{{ LabelsByLanguageCode[getSingleLanguageCode].dateLabel }}:</strong> {{ new Date(data.alcivartech_date).toLocaleString() }}
    </p>
    <ul class="space-y-2">
      <li><strong>{{ LabelsByLanguageCode[getSingleLanguageCode].permitNumber }}:</strong> {{ data.permitnumber }}</li>
      <li><strong>{{ LabelsByLanguageCode[getSingleLanguageCode].workType }}:</strong> {{ data.worktype }}</li>
      <li v-if="data.permittypedescr"><strong>{{ LabelsByLanguageCode[getSingleLanguageCode].permitTypeDescription }}:</strong> {{ data.permittypedescr }}</li>
      <li><strong>{{ LabelsByLanguageCode[getSingleLanguageCode].description }}:</strong> {{ data.description }}</li>
      <li v-if="data.applicant"><strong>{{ LabelsByLanguageCode[getSingleLanguageCode].applicant }}:</strong> {{ data.applicant }}</li>
      <li><strong>{{ LabelsByLanguageCode[getSingleLanguageCode].status }}:</strong> {{ data.status }}</li>
      <li v-if="data.occupancytype"><strong>{{ LabelsByLanguageCode[getSingleLanguageCode].occupancyType }}:</strong> {{ data.occupancytype }}</li>
      <li v-if="data.sq_feet"><strong>{{ LabelsByLanguageCode[getSingleLanguageCode].sqFeet }}:</strong> {{ data.sq_feet }}</li>
      <li><strong>{{ LabelsByLanguageCode[getSingleLanguageCode].declaredValue }}:</strong> {{ data.declared_valuation }}</li>
      <li><strong>{{ LabelsByLanguageCode[getSingleLanguageCode].totalFees }}:</strong> {{ data.total_fees }}</li>
      <li><strong>{{ LabelsByLanguageCode[getSingleLanguageCode].expirationDate }}:</strong> {{ formatDate(data.expiration_date) }}</li>
      <li><strong>{{ LabelsByLanguageCode[getSingleLanguageCode].address }}:</strong> {{ data.address }}, {{ data.city }}, {{ data.state }} {{ data.zip }}</li>
      <li v-if="data.property_id"><strong>{{ LabelsByLanguageCode[getSingleLanguageCode].propertyId }}:</strong> {{ data.property_id }}</li>
      <li v-if="data.parcel_id"><strong>{{ LabelsByLanguageCode[getSingleLanguageCode].parcelId }}:</strong> {{ data.parcel_id }}</li>
      <li v-if="data.gpsy"><strong>{{ LabelsByLanguageCode[getSingleLanguageCode].gpsY }}:</strong> {{ data.gpsy }}</li>
      <li v-if="data.gpsx"><strong>{{ LabelsByLanguageCode[getSingleLanguageCode].gpsX }}:</strong> {{ data.gpsx }}</li>
      <li v-if="data.y_latitude"><strong>{{ LabelsByLanguageCode[getSingleLanguageCode].yLatitude }}:</strong> {{ data.y_latitude }}</li>
      <li v-if="data.x_longitude"><strong>{{ LabelsByLanguageCode[getSingleLanguageCode].xLongitude }}:</strong> {{ data.x_longitude }}</li>
      <!-- add comments if they exist -->
      <li v-if="data.comments" >
        <strong>{{ LabelsByLanguageCode[getSingleLanguageCode].comments }}:</strong>
        <pre class="whitespace-pre-wrap text-sm text-gray-600">{{ data.comments }}</pre>
      </li>
    </ul>
  </div>
</template>

<script setup>
import { computed, defineProps } from 'vue';

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
    comments: 'Comments',
    permitTypeDescription: 'Permit Type Description',
    applicant: 'Applicant',
    occupancyType: 'Occupancy Type',
    sqFeet: 'Square Feet',
    propertyId: 'Property ID',
    parcelId: 'Parcel ID',
    gpsY: 'GPS Y',
    gpsX: 'GPS X',
    yLatitude: 'Latitude (Y)',
    xLongitude: 'Longitude (X)',
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
    comments: 'Comentarios',
    permitTypeDescription: 'Descripción del Tipo de Permiso',
    applicant: 'Solicitante',
    occupancyType: 'Tipo de Ocupación',
    sqFeet: 'Pies Cuadrados',
    propertyId: 'ID de Propiedad',
    parcelId: 'ID de Parcela',
    gpsY: 'GPS Y',
    gpsX: 'GPS X',
    yLatitude: 'Latitud (Y)',
    xLongitude: 'Longitud (X)',
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
    comments: '评论',
    permitTypeDescription: '许可证类型说明',
    applicant: '申请人',
    occupancyType: '占用类型',
    sqFeet: '平方英尺',
    propertyId: '物业编号',
    parcelId: '地块编号',
    gpsY: 'GPS Y坐标',
    gpsX: 'GPS X坐标',
    yLatitude: '纬度 (Y)',
    xLongitude: '经度 (X)',
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
    comments: 'Kòmantè',
    permitTypeDescription: 'Deskripsyon Kalite Pèmi',
    applicant: 'Aplikan',
    occupancyType: 'Kalite Okipasyon',
    sqFeet: 'Pye Kare',
    propertyId: 'ID Pwopriyete',
    parcelId: 'ID Pasèl',
    gpsY: 'GPS Y',
    gpsX: 'GPS X',
    yLatitude: 'Latitid (Y)',
    xLongitude: 'Lonjitid (X)',
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
    comments: 'Comentários',
    permitTypeDescription: 'Descrição do Tipo de Permissão',
    applicant: 'Requerente',
    occupancyType: 'Tipo de Ocupação',
    sqFeet: 'Pés Quadrados',
    propertyId: 'ID da Propriedade',
    parcelId: 'ID do Terreno',
    gpsY: 'GPS Y',
    gpsX: 'GPS X',
    yLatitude: 'Latitude (Y)',
    xLongitude: 'Longitude (X)',
  },
};

const getSingleLanguageCode = computed(() => props.language_codes[0]);

function formatDate(date) {
  return date ? new Date(date).toLocaleString() : 'N/A';
}
</script>