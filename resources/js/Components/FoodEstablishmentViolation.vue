<template>
  <div v-if="data" class="p-4 border border-black-200 rounded-lg h-full overflow-y-auto">
    <h2 class="text-xl font-bold  mb-1">
      {{ data.violation_summary ? LabelsByLanguageCode[getSingleLanguageCode].foodEstablishmentRecordTitle : LabelsByLanguageCode[getSingleLanguageCode].foodEstablishmentViolationTitle }}
    </h2>
    <p class=" text-gray-600 mb-3">
      <strong>{{ data.violation_summary ? LabelsByLanguageCode[getSingleLanguageCode].mostRecentActivityDate : LabelsByLanguageCode[getSingleLanguageCode].dateLabel }}:</strong>
      {{ formatDate(data.alcivartech_date) }}
    </p>

    <!-- Common Establishment Details -->
    <div class="mb-4 text-sm border-t border-b border-black-100 py-3">
      <h3 class=" font-semibold mb-1.5">{{ LabelsByLanguageCode[getSingleLanguageCode].establishmentDetails }}</h3>
      <p><strong>{{ LabelsByLanguageCode[getSingleLanguageCode].businessName }}:</strong> {{ data.businessname || 'N/A' }}</p>
      <p v-if="data.dbaname"><strong>{{ LabelsByLanguageCode[getSingleLanguageCode].dbaName }}:</strong> {{ data.dbaname }}</p>
      <p><strong>{{ LabelsByLanguageCode[getSingleLanguageCode].licenseNumber }}:</strong> {{ data.licenseno || 'N/A' }}</p>
      <p><strong>{{ LabelsByLanguageCode[getSingleLanguageCode].licenseStatus }}:</strong> {{ data.licstatus || 'N/A' }}</p>
      <p><strong>{{ LabelsByLanguageCode[getSingleLanguageCode].licenseCategory }}:</strong> {{ data.licensecat || 'N/A' }}</p>
      <p><strong>{{ LabelsByLanguageCode[getSingleLanguageCode].address }}:</strong> {{ data.address || 'N/A' }}, {{ data.city || 'N/A' }}, {{ data.state || 'N/A' }} {{ data.zip || 'N/A' }}</p>
    </div>

    <!-- Violation History (if aggregated) -->
    <div v-if="data.violation_summary" class="text-sm">
      <h3 class=" font-semibold  mb-2">
        {{ LabelsByLanguageCode[getSingleLanguageCode].violationHistory }}
        <span class="font-normal text-gray-600">({{ data.violation_summary.reduce((sum, s) => sum + s.entries.length, 0) }} {{ LabelsByLanguageCode[getSingleLanguageCode].totalRecords }})</span>
      </h3>
      <div v-for="summaryItem in data.violation_summary" :key="summaryItem.violdesc" class="mb-3 p-2.5 bg-white rounded shadow-sm border border-black-100">
        <p class="font-semibold  text-sm">{{ summaryItem.violdesc }}</p>
        <p class=" text-gray-500 mb-1.5"> ({{ summaryItem.entries.length }} {{ summaryItem.entries.length === 1 ? LabelsByLanguageCode[getSingleLanguageCode].recordSingular : LabelsByLanguageCode[getSingleLanguageCode].recordPlural }})</p>
        <ul class="space-y-2">
          <li v-for="(entry, index) in summaryItem.entries" :key="index" class="p-2  rounded  border-l-2 border-black-300">
            <p><strong>{{ LabelsByLanguageCode[getSingleLanguageCode].dateLabel }}:</strong> {{ formatDate(entry.alcivartech_date) }}</p>
            <p v-if="entry.viol_status"><strong>{{ LabelsByLanguageCode[getSingleLanguageCode].violationStatus }}:</strong> <span :class="entry.viol_status === 'Fail' || (entry.result && entry.result.toLowerCase().includes('fail')) ? ' font-bold' : 'text-green-700 font-bold'">{{ entry.viol_status }}</span></p>
            <p v-if="entry.result"><strong>{{ LabelsByLanguageCode[getSingleLanguageCode].inspectionResult }}:</strong> {{ entry.result }}</p>
            <p v-if="entry.viol_level"><strong>{{ LabelsByLanguageCode[getSingleLanguageCode].violationLevel }}:</strong> {{ entry.viol_level }}</p>
            <p v-if="entry.comments" class="mt-1"><strong>{{ LabelsByLanguageCode[getSingleLanguageCode].comments }}:</strong> <span class="italic">{{ entry.comments }}</span></p>
          </li>
        </ul>
      </div>
    </div>

    <!-- Single Violation Details (if not aggregated) -->
    <div v-else class="text-sm">
        <h3 class=" font-semibold  mb-1.5">{{ LabelsByLanguageCode[getSingleLanguageCode].violationDetails }}</h3>
        <ul class="space-y-1">
            <li v-if="data.result"><strong>{{ LabelsByLanguageCode[getSingleLanguageCode].inspectionResult }}:</strong> {{ data.result }}</li>
            <li v-if="data.resultdttm"><strong>{{ LabelsByLanguageCode[getSingleLanguageCode].resultDate }}:</strong> {{ formatDate(data.resultdttm) }}</li>
            <li v-if="data.violation"><strong>{{ LabelsByLanguageCode[getSingleLanguageCode].violationCode }}:</strong> {{ data.violation }} <span v-if="data.viol_level"> ({{ data.viol_level }})</span></li>
            <li v-if="data.violdesc"><strong>{{ LabelsByLanguageCode[getSingleLanguageCode].violationDescription }}:</strong> {{ data.violdesc }}</li>
            <li v-if="data.viol_status"><strong>{{ LabelsByLanguageCode[getSingleLanguageCode].violationStatus }}:</strong> {{ data.viol_status }}</li>
            <li v-if="data.comments"><strong>{{ LabelsByLanguageCode[getSingleLanguageCode].comments }}:</strong> {{ data.comments }}</li>
        </ul>
    </div>
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
    foodEstablishmentViolationTitle: 'Food Establishment Violation',
    foodEstablishmentRecordTitle: 'Food Establishment Record',
    dateLabel: 'Date',
    mostRecentActivityDate: 'Most Recent Activity',
    businessName: 'Business Name',
    dbaName: 'DBA Name',
    licenseNumber: 'License No.',
    licenseStatus: 'License Status',
    licenseCategory: 'License Category',
    inspectionResult: 'Inspection Result',
    resultDate: 'Result Date',
    violationCode: 'Violation Code',
    violationDescription: 'Violation Description',
    violationStatus: 'Status',
    comments: 'Comments',
    address: 'Address',
    establishmentDetails: 'Establishment Details',
    violationHistory: 'Violation History',
    violationDetails: 'Violation Details',
    totalRecords: 'total records',
    recordSingular: 'record',
    recordPlural: 'records',
    violationLevel: 'Level',
  },
  'es-MX': {
    foodEstablishmentViolationTitle: 'Violación de Establecimiento de Comida',
    foodEstablishmentRecordTitle: 'Registro de Establecimiento de Comida',
    dateLabel: 'Fecha',
    mostRecentActivityDate: 'Actividad Más Reciente',
    businessName: 'Nombre del Negocio',
    dbaName: 'Nombre DBA',
    licenseNumber: 'No. de Licencia',
    licenseStatus: 'Estado de Licencia',
    licenseCategory: 'Categoría de Licencia',
    inspectionResult: 'Resultado de Inspección',
    resultDate: 'Fecha de Resultado',
    violationCode: 'Código de Violación',
    violationDescription: 'Descripción de Violación',
    violationStatus: 'Estado',
    comments: 'Comentarios',
    address: 'Dirección',
    establishmentDetails: 'Detalles del Establecimiento',
    violationHistory: 'Historial de Violaciones',
    violationDetails: 'Detalles de la Violación',
    totalRecords: 'registros totales',
    recordSingular: 'registro',
    recordPlural: 'registros',
    violationLevel: 'Nivel',
  },
  'zh-CN': {
    foodEstablishmentViolationTitle: '食品机构违规',
    foodEstablishmentRecordTitle: '食品机构记录',
    dateLabel: '日期',
    mostRecentActivityDate: '最近活动',
    businessName: '企业名称',
    dbaName: 'DBA名称',
    licenseNumber: '许可证号',
    licenseStatus: '许可证状态',
    licenseCategory: '许可证类别',
    inspectionResult: '检查结果',
    resultDate: '结果日期',
    violationCode: '违规代码',
    violationDescription: '违规描述',
    violationStatus: '状态',
    comments: '评论',
    address: '地址',
    establishmentDetails: '机构详情',
    violationHistory: '违规历史',
    violationDetails: '违规详情',
    totalRecords: '总记录',
    recordSingular: '条记录',
    recordPlural: '条记录',
    violationLevel: '级别',
  },
  'ht-HT': {
    foodEstablishmentViolationTitle: 'Vyolasyon Etablisman Manje',
    foodEstablishmentRecordTitle: 'Dosye Etablisman Manje',
    dateLabel: 'Dat',
    mostRecentActivityDate: 'Aktivite Pi Resan',
    businessName: 'Non Biznis',
    dbaName: 'Non DBA',
    licenseNumber: 'Nimewo Lisans',
    licenseStatus: 'Estati Lisans',
    licenseCategory: 'Kategori Lisans',
    inspectionResult: 'Rezilta Enspeksyon',
    resultDate: 'Dat Rezilta',
    violationCode: 'Kòd Vyolasyon',
    violationDescription: 'Deskripsyon Vyolasyon',
    violationStatus: 'Estati',
    comments: 'Kòmantè',
    address: 'Adrès',
    establishmentDetails: 'Detay Etablisman',
    violationHistory: 'Istwa Vyolasyon',
    violationDetails: 'Detay Vyolasyon',
    totalRecords: 'dosye total',
    recordSingular: 'dosye',
    recordPlural: 'dosye yo',
    violationLevel: 'Nivo',
  },
  'pt-BR': {
    foodEstablishmentViolationTitle: 'Violação de Estabelecimento Alimentar',
    foodEstablishmentRecordTitle: 'Registro de Estabelecimento Alimentar',
    dateLabel: 'Data',
    mostRecentActivityDate: 'Atividade Mais Recente',
    businessName: 'Nome da Empresa',
    dbaName: 'Nome DBA',
    licenseNumber: 'Nº da Licença',
    licenseStatus: 'Status da Licença',
    licenseCategory: 'Categoria da Licença',
    inspectionResult: 'Resultado da Inspeção',
    resultDate: 'Data do Resultado',
    violationCode: 'Código da Violação',
    violationDescription: 'Descrição da Violação',
    violationStatus: 'Status',
    comments: 'Comentários',
    address: 'Endereço',
    establishmentDetails: 'Detalhes do Estabelecimento',
    violationHistory: 'Histórico de Violações',
    violationDetails: 'Detalhes da Violação',
    totalRecords: 'registros totais',
    recordSingular: 'registro',
    recordPlural: 'registros',
    violationLevel: 'Nível',
  },
  'vi-VN': {
    foodEstablishmentViolationTitle: 'Vi phạm Cơ sở Thực phẩm',
    foodEstablishmentRecordTitle: 'Hồ sơ Cơ sở Thực phẩm',
    dateLabel: 'Ngày',
    mostRecentActivityDate: 'Hoạt động gần nhất',
    businessName: 'Tên doanh nghiệp',
    dbaName: 'Tên DBA',
    licenseNumber: 'Số giấy phép',
    licenseStatus: 'Tình trạng giấy phép',
    licenseCategory: 'Loại giấy phép',
    inspectionResult: 'Kết quả kiểm tra',
    resultDate: 'Ngày có kết quả',
    violationCode: 'Mã vi phạm',
    violationDescription: 'Mô tả vi phạm',
    violationStatus: 'Trạng thái',
    comments: 'Bình luận',
    address: 'Địa chỉ',
    establishmentDetails: 'Chi tiết Cơ sở',
    violationHistory: 'Lịch sử Vi phạm',
    violationDetails: 'Chi tiết Vi phạm',
    totalRecords: 'tổng số hồ sơ',
    recordSingular: 'hồ sơ',
    recordPlural: 'hồ sơ',
    violationLevel: 'Cấp độ',
  }
};

const getSingleLanguageCode = computed(() => {
  // Fallback to 'en-US' if the provided language code is not in LabelsByLanguageCode
  if (props.language_codes && props.language_codes.length > 0 && LabelsByLanguageCode[props.language_codes[0]]) {
    return props.language_codes[0];
  }
  return 'en-US';
});

function formatDate(dateString) {
  if (!dateString) return 'N/A';
  // Check if dateString is already a Date object or a valid date string
  const date = new Date(dateString);
  if (isNaN(date.getTime())) { // Invalid date
    return dateString; // Return original string if it's not a valid date
  }
  return date.toLocaleString(getSingleLanguageCode.value, { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
}
</script>
