

const dataTypeMapByLanguageCode = {
    'en-US': {
      'Crime': 'Crime',
      '311 Case': '311 Case',
      'Building Permit': 'Building Permit',
      'Property Violation': 'Property Violation',
      'Construction Off Hour': 'Constr Off Hour',
      'Food Establishment Violation': 'Food Inspection',
    },
    'es-MX': {
      'Crime': 'Crimen',
      '311 Case': 'Caso 311',
      'Building Permit': 'Permiso de Constr',
      'Property Violation': 'Violación de Prop',
      'Construction Off Hour': 'Constr Fuera',
      'Food Establishment Violation': 'Inspección de Alimentos',
    },
    'zh-CN': {
      'Crime': '犯罪',
      '311 Case': '311案例',
      'Building Permit': '建筑许可',
      'Property Violation': '财产违规',
      'Construction Off Hour': '非工作时间施工',
      'Food Establishment Violation': '食品检查',
    },
    'ht-HT': {
      'Crime': 'Krim',
      '311 Case': 'Ka 311',
      'Building Permit': 'Pèmi Bati',
      'Property Violation': 'Vyolasyon Pwopriyete',
      'Construction Off Hour': 'Konstr Moun Ki Pa Travay',
      'Food Establishment Violation': 'Enspeksyon Manje',
    },
    'vi-VN': {
      'Crime': 'Tội phạm',
      '311 Case': 'Trường hợp 311',
      'Building Permit': 'Giấy phép Xây dựng',
      'Property Violation': 'Vi phạm Tài sản',
      'Construction Off Hour': 'Xây dựng Ngoài giờ',
      'Food Establishment Violation': 'Kiểm tra Thực phẩm',
    },
    'pt-BR': {
      'Crime': 'Crime',
      '311 Case': 'Caso 311',
      'Building Permit': 'Licença de Constr',
      'Property Violation': 'Violação de Prop',
      'Construction Off Hour': 'Constr Fora',
      'Food Establishment Violation': 'Inspeção de Alimentos',
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

  const LocationLabelsByLanguageCode = {
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
      report: 'Report Frequency',
      language: 'Language',
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
      report: 'Frecuencia de Reporte',
      language: 'Idioma',
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
      report: '报告频率',
      language: '语言',
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
      report: 'Frekans Rapò',
      language: 'Lang',
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
      report: 'Tần Suất Báo Cáo',
      language: 'Ngôn Ngữ',
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
      report: 'Frequência do Relatório',
      language: 'Idioma',
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

  const CaseLabelsByLanguageCode = {
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
    LocationLabelsByLanguageCode,
    CrimeLabelsByLanguageCode,
    CaseLabelsByLanguageCode,
  };