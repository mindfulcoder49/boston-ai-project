<template>
    <div v-if="data" class="p-4 bg-gray-100 rounded-lg">
      <h2 class="text-xl font-bold text-gray-800">
        {{ LabelsByLanguageCode[getSingleLanguageCode].propertyViolationTitle }}
      </h2>
      <p class="text-gray-700 mb-4">
        <strong>{{ LabelsByLanguageCode[getSingleLanguageCode].dateLabel }}:</strong> {{ new Date(data.date).toLocaleString() }}
      </p>
      <ul class="space-y-2">
        <li><strong>{{ LabelsByLanguageCode[getSingleLanguageCode].caseNumber }}:</strong> {{ data.info.case_no }}</li>
        <li><strong>{{ LabelsByLanguageCode[getSingleLanguageCode].caseDefinitionKey }}:</strong> {{ data.info.ap_case_defn_key }}</li>
        <li><strong>{{ LabelsByLanguageCode[getSingleLanguageCode].status }}:</strong> {{ data.info.status }}</li>
        <li><strong>{{ LabelsByLanguageCode[getSingleLanguageCode].code }}:</strong> {{ data.info.code }}</li>
        <li><strong>{{ LabelsByLanguageCode[getSingleLanguageCode].value }}:</strong> {{ data.info.value }}</li>
        <li><strong>{{ LabelsByLanguageCode[getSingleLanguageCode].description }}:</strong> {{ data.info.description }}</li>
        <li><strong>{{ LabelsByLanguageCode[getSingleLanguageCode].violationStreetNumber }}:</strong> {{ data.info.violation_stno }}</li>
        <li><strong>{{ LabelsByLanguageCode[getSingleLanguageCode].violationStreetHigh }}:</strong> {{ data.info.violation_sthigh }}</li>
        <li><strong>{{ LabelsByLanguageCode[getSingleLanguageCode].violationStreet }}:</strong> {{ data.info.violation_street }}</li>
        <li><strong>{{ LabelsByLanguageCode[getSingleLanguageCode].violationSuffix }}:</strong> {{ data.info.violation_suffix }}</li>
        <li><strong>{{ LabelsByLanguageCode[getSingleLanguageCode].violationCity }}:</strong> {{ data.info.violation_city }}</li>
        <li><strong>{{ LabelsByLanguageCode[getSingleLanguageCode].violationState }}:</strong> {{ data.info.violation_state }}</li>
        <li><strong>{{ LabelsByLanguageCode[getSingleLanguageCode].violationZip }}:</strong> {{ data.info.violation_zip }}</li>
        <li><strong>{{ LabelsByLanguageCode[getSingleLanguageCode].ward }}:</strong> {{ data.info.ward }}</li>
        <li><strong>{{ LabelsByLanguageCode[getSingleLanguageCode].contactAddress1 }}:</strong> {{ data.info.contact_addr1 }}</li>
        <li><strong>{{ LabelsByLanguageCode[getSingleLanguageCode].contactAddress2 }}:</strong> {{ data.info.contact_addr2 }}</li>
        <li><strong>{{ LabelsByLanguageCode[getSingleLanguageCode].contactCity }}:</strong> {{ data.info.contact_city }}</li>
        <li><strong>{{ LabelsByLanguageCode[getSingleLanguageCode].contactState }}:</strong> {{ data.info.contact_state }}</li>
        <li><strong>{{ LabelsByLanguageCode[getSingleLanguageCode].contactZip }}:</strong> {{ data.info.contact_zip }}</li>
        <li><strong>{{ LabelsByLanguageCode[getSingleLanguageCode].samId }}:</strong> {{ data.info.sam_id }}</li>
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
      propertyViolationTitle: 'Property Violation',
      dateLabel: 'Date',
      caseNumber: 'Case Number',
      caseDefinitionKey: 'Case Definition Key',
      status: 'Status',
      code: 'Code',
      value: 'Value',
      description: 'Description',
      violationStreetNumber: 'Violation Street Number',
      violationStreetHigh: 'Violation Street High',
      violationStreet: 'Violation Street',
      violationSuffix: 'Violation Suffix',
      violationCity: 'Violation City',
      violationState: 'Violation State',
      violationZip: 'Violation Zip',
      ward: 'Ward',
      contactAddress1: 'Contact Address 1',
      contactAddress2: 'Contact Address 2',
      contactCity: 'Contact City',
      contactState: 'Contact State',
      contactZip: 'Contact Zip',
      samId: 'Sam ID',
    },
    'es-MX': {
      propertyViolationTitle: 'Violación de Propiedad',
      dateLabel: 'Fecha',
      caseNumber: 'Número de Caso',
      caseDefinitionKey: 'Clave de Definición de Caso',
      status: 'Estado',
      code: 'Código',
      value: 'Valor',
      description: 'Descripción',
      violationStreetNumber: 'Número de Calle de Violación',
      violationStreetHigh: 'Calle Alta de Violación',
      violationStreet: 'Calle de Violación',
      violationSuffix: 'Sufijo de Violación',
      violationCity: 'Ciudad de Violación',
      violationState: 'Estado de Violación',
      violationZip: 'Código Postal de Violación',
      ward: 'Distrito',
      contactAddress1: 'Dirección de Contacto 1',
      contactAddress2: 'Dirección de Contacto 2',
      contactCity: 'Ciudad de Contacto',
      contactState: 'Estado de Contacto',
      contactZip: 'Código Postal de Contacto',
      samId: 'ID Sam',
    },
    'zh-CN': {
      propertyViolationTitle: '物业违规',
      dateLabel: '日期',
      caseNumber: '案件编号',
      caseDefinitionKey: '案件定义键',
      status: '状态',
      code: '代码',
      value: '价值',
      description: '描述',
      violationStreetNumber: '违规街道编号',
      violationStreetHigh: '违规街道高',
      violationStreet: '违规街道',
      violationSuffix: '违规后缀',
      violationCity: '违规城市',
      violationState: '违规状态',
      violationZip: '违规邮政编码',
      ward: '区',
      contactAddress1: '联系人地址1',
      contactAddress2: '联系人地址2',
      contactCity: '联系人城市',
      contactState: '联系人状态',
      contactZip: '联系人邮政编码',
      samId: 'Sam ID',
    },
    'ht-HT': {
      propertyViolationTitle: 'Vyolasyon Pwopriyete',
      dateLabel: 'Dat',
      caseNumber: 'Nimewo Ka',
      caseDefinitionKey: 'Kle Definisyon Ka',
      status: 'Estati',
      code: 'Kòd',
      value: 'Valè',
      description: 'Deskripsyon',
      violationStreetNumber: 'Nimewo Lari Vyolasyon',
      violationStreetHigh: 'Lari Wo Vyolasyon',
      violationStreet: 'Lari Vyolasyon',
      violationSuffix: 'Sifiz Vyolasyon',
      violationCity: 'Vil Vyolasyon',
      violationState: 'Eta Vyolasyon',
      violationZip: 'Kòd Postal Vyolasyon',
      ward: 'Seksyon',
      contactAddress1: 'Adrès Kontak 1',
      contactAddress2: 'Adrès Kontak 2',
      contactCity: 'Vil Kontak',
      contactState: 'Eta Kontak',
      contactZip: 'Kòd Postal Kontak',
      samId: 'ID Sam',
    },
    'vi-VN': {
      propertyViolationTitle: 'Vi Phạm Tài Sản',
      dateLabel: 'Ngày',
      caseNumber: 'Số Vụ',
      caseDefinitionKey: 'Khóa Định Nghĩa Vụ',
      status: 'Trạng Thái',
      code: 'Mã',
      value: 'Giá Trị',
      description: 'Mô Tả',
      violationStreetNumber: 'Số Đường Vi Phạm',
      violationStreetHigh: 'Đường Cao Vi Phạm',
      violationStreet: 'Đường Vi Phạm',
      violationSuffix: 'Hậu Tố Vi Phạm',
      violationCity: 'Thành Phố Vi Phạm',
      violationState: 'Bang Vi Phạm',
      violationZip: 'Mã Bưu Chính Vi Phạm',
      ward: 'Phường',
      contactAddress1: 'Địa Chỉ Liên Hệ 1',
      contactAddress2: 'Địa Chỉ Liên Hệ 2',
      contactCity: 'Thành Phố Liên Hệ',
      contactState: 'Bang Liên Hệ',
      contactZip: 'Mã Bưu Chính Liên Hệ',
      samId: 'ID Sam',
    },
    'pt-BR': {
      propertyViolationTitle: 'Violação de Propriedade',
      dateLabel: 'Data',
      caseNumber: 'Número do Caso',
      caseDefinitionKey: 'Chave de Definição do Caso',
      status: 'Status',
      code: 'Código',
      value: 'Valor',
      description: 'Descrição',
      violationStreetNumber: 'Número da Rua da Violação',
      violationStreetHigh: 'Rua Alta da Violação',
      violationStreet: 'Rua da Violação',
      violationSuffix: 'Sufixo da Violação',
      violationCity: 'Cidade da Violação',
      violationState: 'Estado da Violação',
      violationZip: 'CEP da Violação',
      ward: 'Distrito',
      contactAddress1: 'Endereço de Contato 1',
      contactAddress2: 'Endereço de Contato 2',
      contactCity: 'Cidade de Contato',
      contactState: 'Estado de Contato',
      contactZip: 'CEP de Contato',
      samId: 'ID Sam',
    },
  };
  
  const getSingleLanguageCode = computed(() => language_codes[0]);
  </script>