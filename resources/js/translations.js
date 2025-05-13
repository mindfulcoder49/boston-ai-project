

const dataTypeMapByLanguageCode = {
    'en-US': {
      'Crime': 'Crime',
      '311 Case': '311 Case',
      'Building Permit': 'Building Permit',
      'Property Violation': 'Property Violation',
      'Construction Off Hour': 'Constr Off Hour',
    },
    'es-MX': {
      'Crime': 'Crimen',
      '311 Case': 'Caso 311',
      'Building Permit': 'Permiso de Constr',
      'Property Violation': 'Violación de Prop',
      'Construction Off Hour': 'Constr Fuera'
    },
    'zh-CN': {
      'Crime': '犯罪',
      '311 Case': '311案例',
      'Building Permit': '建筑许可',
      'Property Violation': '财产违规',
      'Construction Off Hour': '非工作时间施工',
    },
    'ht-HT': {
      'Crime': 'Krim',
      '311 Case': 'Ka 311',
      'Building Permit': 'Pèmi Bati',
      'Property Violation': 'Vyolasyon Pwopriyete',
      'Construction Off Hour': 'Konstr Moun Ki Pa Travay',
    },
    'vi-VN': {
      'Crime': 'Tội phạm',
      '311 Case': 'Trường hợp 311',
      'Building Permit': 'Giấy phép Xây dựng',
      'Property Violation': 'Vi phạm Tài sản',
      'Construction Off Hour': 'Xây dựng Ngoài giờ',
    },
    'pt-BR': {
      'Crime': 'Crime',
      '311 Case': 'Caso 311',
      'Building Permit': 'Licença de Constr',
      'Property Violation': 'Violação de Prop',
      'Construction Off Hour': 'Constr Fora'
    },
  };

  const localizationLabelsByLanguageCode = {
    'en-US': {
      allDatesButton: 'All Dates',
      chooseNewCenter: 'Choose New Center',
      cancelText: 'Cancel',
    },
    'es-MX': {
      allDatesButton: 'Todas las fechas',
      chooseNewCenter: 'Elegir nuevo centro',
      cancelText: 'Cancelar',
    },
    'zh-CN': {
      allDatesButton: '所有日期',
      chooseNewCenter: '选择新中心',
      cancelText: '取消',
    },
    'ht-HT': {
      allDatesButton: 'Tout dat',
      chooseNewCenter: 'Chwazi Nouvo Sant',
      cancelText: 'Anile',
    },
    'vi-VN': {
      allDatesButton: 'Tất cả các ngày',
      chooseNewCenter: 'Chọn Trung tâm Mới',
      cancelText: 'Hủy',
    },
    'pt-BR': {
      allDatesButton: 'Todas as datas',
      chooseNewCenter: 'Escolher Novo Centro',
      cancelText: 'Cancelar',
    },
  };

  const LabelsByLanguageCode = {
    'en-US': {
      pageTitle: 'BostonScope',
    },
    'es-MX': {
      pageTitle: 'BostonScope',
    },
    'zh-CN': {
      pageTitle: '波士顿市政府活动地图', //translation literal: 'Boston City Government Activity Map',
    },
    'ht-HT': {
      pageTitle: 'BostonScope',
    },
    'vi-VN': {
      pageTitle: 'Bản đồ hoạt động của Chính phủ Thành phố Boston',
    },
    'pt-BR': {
      pageTitle: 'BostonScope',
    },
  };


  const CrimeLabelsByLanguageCode = {
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

export const translations = {
    dataTypeMapByLanguageCode,
    localizationLabelsByLanguageCode,
    LabelsByLanguageCode,
  };