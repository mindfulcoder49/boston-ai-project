<template>
  <div class="city-lite-page">
    <Head>
      <title>{{ city.seoTitle }}</title>
    </Head>

    <MapDisplay
      ref="mapDisplayRef"
      :map-center-coordinates="mapCenter"
      :all-map-data-points="filteredDataPoints"
      :active-filter-types="activeFilterTypes"
      :is-center-selection-mode-active="false"
      :temp-new-marker-placement-coords="null"
      :map-is-loading="mapLoading"
      :should-clear-temp-marker="false"
      :map-configuration="mapConfiguration"
      :initial-zoom="initialZoom"
      @marker-data-point-clicked="handleMarkerClick"
    />

    <div class="top-panel" :class="{ collapsed: sidebarCollapsed }">
      <div class="brand-row">
        <div class="brand-copy">
          <a href="/" class="brand-mark">PublicDataWatch</a>
          <h1>{{ city.name }}</h1>
        </div>

        <div class="panel-actions">
          <a
            v-if="!sidebarCollapsed"
            :href="exploreMapUrl"
            class="full-map-link"
            @click="handleExploreMapClick"
          >
            {{ text.openExploreMap }}
          </a>
          <button type="button" class="collapse-toggle" @click="toggleSidebar">
            {{ sidebarCollapsed ? text.expandPanel : text.collapsePanel }}
          </button>
        </div>
      </div>

      <div v-if="!sidebarCollapsed" class="panel-body">
        <p class="tagline">{{ city.tagline }}</p>
        <p class="intro">{{ city.intro }}</p>

        <div v-if="city.focusAreas?.length" class="focus-strip" aria-label="City focus areas">
          <span
            v-for="focusArea in city.focusAreas"
            :key="`${city.key}-${focusArea}`"
            class="focus-chip"
          >
            {{ focusArea }}
          </span>
        </div>

        <div class="language-strip" aria-label="Language options">
          <button
            v-for="option in languageOptions"
            :key="option.code"
            type="button"
            class="language-chip"
            :class="{ active: selectedLanguage === option.code }"
            @click="setLanguage(option.code)"
          >
            {{ option.label }}
          </button>
        </div>

        <div class="action-row">
          <button
            type="button"
            class="primary-action"
            :disabled="geolocationLoading"
            @click="useCurrentLocation"
          >
            {{ geolocationLoading ? text.locating : text.useMyLocation }}
          </button>

          <button type="button" class="secondary-action" @click="searchOpen = !searchOpen">
            {{ searchOpen ? text.hideSearch : text.searchAddress }}
          </button>
        </div>

        <p class="range-summary">
          {{ visibleDateRangeLabel }}
        </p>

        <div class="filter-strip" aria-label="Time filters">
          <button
            v-for="option in recencyOptions"
            :key="option.key"
            type="button"
            class="filter-chip"
            :class="{ active: selectedRecency === option.key }"
            @click="selectedRecency = option.key"
          >
            {{ option.label }}
          </button>
        </div>

        <div v-if="searchOpen" class="search-shell">
          <GoogleAddressSearch
            :language_codes="[selectedLanguage]"
            :placeholder_text="city.searchPlaceholder"
            @address-selected="handleAddressSelected"
            @search-started="handleAddressSearchStarted"
          />
        </div>

        <p v-if="geolocationError" class="feedback error-text">{{ geolocationError }}</p>
        <p class="feedback">
          {{ mapLoading ? text.loadingMap : resultSummary }}
        </p>
      </div>
    </div>

    <section class="bottom-sheet">
      <div v-if="selectedCard" class="sheet-content" :class="{ collapsed: sheetCollapsed }">
        <div class="sheet-header">
          <div>
            <p class="eyebrow">{{ text.selectedRecord }}</p>
            <h2>{{ selectedCard.title }}</h2>
          </div>
          <div class="sheet-actions">
            <a :href="exploreMapUrl" class="sheet-link" @click="handleExploreMapClick">{{ text.openExploreMap }}</a>
            <button type="button" class="collapse-toggle sheet-toggle" @click="sheetCollapsed = !sheetCollapsed">
              {{ sheetCollapsed ? text.expandCard : text.collapseCard }}
            </button>
          </div>
        </div>

        <p v-if="!sheetCollapsed && selectedCard.summary" class="summary">
          {{ selectedCard.summary }}
        </p>

        <div v-if="!sheetCollapsed && selectedLanguage !== 'en-US'" class="detail-group translated-group">
          <p class="detail-heading">{{ translatedHeading }}</p>

          <div v-if="translationLoading" class="translation-loading">
            <span>{{ text.translating }}</span>
            <span class="wiggle-dots" aria-hidden="true">
              <span></span>
              <span></span>
              <span></span>
            </span>
          </div>

          <p v-else-if="translationError" class="error-text">{{ translationError }}</p>

          <template v-else-if="translatedCard">
            <p v-if="translatedCard.summary" class="summary">
              {{ translatedCard.summary }}
            </p>

            <dl class="detail-list">
              <div
                v-for="detail in translatedCard.details"
                :key="`translated-${detail.label}-${detail.value}`"
                class="detail-row"
              >
                <dt>{{ detail.label }}</dt>
                <dd>{{ detail.value }}</dd>
              </div>
            </dl>
          </template>
        </div>

        <div v-if="!sheetCollapsed" class="detail-group">
          <p class="detail-heading">{{ text.english }}</p>
          <dl class="detail-list">
            <div v-for="detail in selectedCard.details" :key="`${detail.label}-${detail.value}`" class="detail-row">
              <dt>{{ detail.label }}</dt>
              <dd>{{ detail.value }}</dd>
            </div>
          </dl>
        </div>
      </div>

      <div v-else class="empty-sheet">
        <p class="eyebrow">{{ city.name }}</p>
        <h2>{{ text.emptyTitle }}</h2>
        <p>{{ text.emptyState }}</p>

        <div class="city-guide">
          <div v-if="city.highlights?.length" class="highlight-grid" aria-label="City landing highlights">
            <article
              v-for="highlight in city.highlights"
              :key="`${city.key}-${highlight.title}`"
              class="highlight-card"
            >
              <p class="detail-heading">{{ highlight.title }}</p>
              <p>{{ highlight.body }}</p>
            </article>
          </div>

          <p class="detail-heading">What's included</p>
          <p>{{ city.overview }}</p>

          <div v-if="city.dataTypes?.length" class="dataset-strip" aria-label="Included datasets">
            <span
              v-for="dataType in city.dataTypes"
              :key="dataType"
              class="dataset-chip"
            >
              {{ dataType }}
            </span>
          </div>

          <p class="detail-heading guide-heading">How to use this page</p>
          <p>{{ city.howToUse }}</p>
          <p class="guide-note">{{ city.dataUpdateNote }}</p>

          <p class="detail-heading guide-heading">Explore more</p>
          <div class="related-links">
            <a
              v-for="link in city.relatedLinks"
              :key="link.url"
              :href="link.url"
              class="related-link"
            >
              {{ link.label }}
            </a>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import axios from 'axios';
import { computed, onMounted, ref, watch } from 'vue';
import { Head } from '@inertiajs/vue3';
import GoogleAddressSearch from '@/Components/GoogleAddressSearch.vue';
import MapDisplay from '@/Components/MapDisplay.vue';
import { trackAnalyticsEvent, trackPageView } from '@/Utils/analytics';

const props = defineProps({
  city: {
    type: Object,
    required: true,
  },
  languageOptions: {
    type: Array,
    required: true,
  },
});

const UI_TEXT = {
  'en-US': {
    useMyLocation: 'Use my location',
    locating: 'Finding your location...',
    searchAddress: 'Search address',
    hideSearch: 'Hide search',
    collapsePanel: 'Hide',
    expandPanel: 'Menu',
    loadingMap: 'Loading nearby data...',
    dataFrom: 'Data from',
    tapMapHint: 'Zoom in to explore nearby activity.',
    selectedRecord: 'Selected record',
    english: 'English',
    translating: 'Translating',
    openExploreMap: 'Open explore map',
    collapseCard: 'Hide card',
    expandCard: 'Show card',
    noReports: 'No nearby records yet.',
    nearbyReports: 'nearby records loaded',
    defaultRange: 'Default (14 days)',
    lastDay: 'Most recent day',
    last3Days: 'Last 3 days',
    last7Days: 'Last 7 days',
    emptyTitle: 'Explore nearby activity in {city}',
    emptyState: 'Use your location, search an address, or zoom into the map to browse nearby records in {city}.',
    geolocationDenied: 'Location access was blocked. You can search your address instead.',
    geolocationUnavailable: 'Your location is unavailable right now.',
    geolocationTimeout: 'Location request timed out. Try again or search your address.',
    geolocationUnsupported: 'This phone does not support location access in the browser.',
    translationFailed: 'Translation did not load. English is still shown below.',
  },
  'es-MX': {
    useMyLocation: 'Usar mi ubicación',
    locating: 'Buscando tu ubicación...',
    searchAddress: 'Buscar dirección',
    hideSearch: 'Ocultar búsqueda',
    collapsePanel: 'Ocultar',
    expandPanel: 'Menú',
    loadingMap: 'Cargando datos cercanos...',
    dataFrom: 'Datos desde',
    tapMapHint: 'Acércate al mapa para explorar actividad cercana.',
    selectedRecord: 'Registro seleccionado',
    english: 'Inglés',
    translating: 'Traduciendo',
    openExploreMap: 'Abrir mapa de explorar',
    collapseCard: 'Ocultar tarjeta',
    expandCard: 'Mostrar tarjeta',
    noReports: 'Todavía no hay registros cercanos.',
    nearbyReports: 'registros cercanos cargados',
    defaultRange: 'Por defecto (14 días)',
    lastDay: 'Día más reciente',
    last3Days: 'Últimos 3 días',
    last7Days: 'Últimos 7 días',
    emptyTitle: 'Explora la actividad cercana en {city}',
    emptyState: 'Usa tu ubicación, busca una dirección o acércate al mapa para ver registros cercanos en {city}.',
    geolocationDenied: 'Se bloqueó el acceso a tu ubicación. Puedes buscar tu dirección.',
    geolocationUnavailable: 'Tu ubicación no está disponible en este momento.',
    geolocationTimeout: 'La solicitud de ubicación tardó demasiado. Inténtalo otra vez o busca tu dirección.',
    geolocationUnsupported: 'Este teléfono no permite ubicación en el navegador.',
    translationFailed: 'No se pudo cargar la traducción. El texto en inglés sigue abajo.',
  },
  'pt-BR': {
    useMyLocation: 'Usar minha localização',
    locating: 'Buscando sua localização...',
    searchAddress: 'Buscar endereço',
    hideSearch: 'Ocultar busca',
    collapsePanel: 'Ocultar',
    expandPanel: 'Menu',
    loadingMap: 'Carregando dados próximos...',
    dataFrom: 'Dados de',
    tapMapHint: 'Aproxime o mapa para explorar a atividade próxima.',
    selectedRecord: 'Registro selecionado',
    english: 'Inglês',
    translating: 'Traduzindo',
    openExploreMap: 'Abrir mapa de exploração',
    collapseCard: 'Ocultar cartão',
    expandCard: 'Mostrar cartão',
    noReports: 'Ainda não há registros próximos.',
    nearbyReports: 'registros próximos carregados',
    defaultRange: 'Padrão (14 dias)',
    lastDay: 'Dia mais recente',
    last3Days: 'Últimos 3 dias',
    last7Days: 'Últimos 7 dias',
    emptyTitle: 'Explore a atividade próxima em {city}',
    emptyState: 'Use sua localização, busque um endereço ou aproxime o mapa para ver registros próximos em {city}.',
    geolocationDenied: 'O acesso à localização foi bloqueado. Você pode buscar seu endereço.',
    geolocationUnavailable: 'Sua localização não está disponível agora.',
    geolocationTimeout: 'A solicitação de localização expirou. Tente novamente ou busque seu endereço.',
    geolocationUnsupported: 'Este telefone não oferece localização no navegador.',
    translationFailed: 'A tradução não carregou. O texto em inglês continua abaixo.',
  },
  'ht-HT': {
    useMyLocation: 'Sèvi ak kote mwen',
    locating: 'M ap chèche kote ou...',
    searchAddress: 'Chèche adrès',
    hideSearch: 'Kache rechèch',
    collapsePanel: 'Kache',
    expandPanel: 'Meni',
    loadingMap: 'Ap chaje done ki pre yo...',
    dataFrom: 'Done soti nan',
    tapMapHint: 'Rale kat la pi pre pou eksplore aktivite ki toupre yo.',
    selectedRecord: 'Dosye ki chwazi a',
    english: 'Anglè',
    translating: 'Tradiksyon ap fèt',
    openExploreMap: 'Louvri kat eksplore a',
    collapseCard: 'Kache kat la',
    expandCard: 'Montre kat la',
    noReports: 'Pa gen dosye ki pre isit la ankò.',
    nearbyReports: 'dosye toupre yo chaje',
    defaultRange: 'Defo (14 jou)',
    lastDay: 'Dènye jou a',
    last3Days: 'Dènye 3 jou yo',
    last7Days: 'Dènye 7 jou yo',
    emptyTitle: 'Eksplore aktivite ki toupre {city}',
    emptyState: 'Sèvi ak kote ou, chèche adrès, oswa rale kat la pi pre pou wè dosye ki toupre yo nan {city}.',
    geolocationDenied: 'Aksè ak kote ou a bloke. Ou ka chèche adrès ou pito.',
    geolocationUnavailable: 'Kote ou a pa disponib kounye a.',
    geolocationTimeout: 'Demann kote a pran twòp tan. Eseye ankò oswa chèche adrès ou.',
    geolocationUnsupported: 'Telefòn sa a pa sipòte lokalizasyon nan navigatè a.',
    translationFailed: 'Tradiksyon an pa t chaje. Tèks angle a toujou anba a.',
  },
  'zh-CN': {
    useMyLocation: '使用我的位置',
    locating: '正在定位...',
    searchAddress: '搜索地址',
    hideSearch: '隐藏搜索',
    collapsePanel: '隐藏',
    expandPanel: '菜单',
    loadingMap: '正在加载附近数据...',
    dataFrom: '数据范围',
    tapMapHint: '放大地图以查看附近活动。',
    selectedRecord: '已选记录',
    english: '英文',
    translating: '翻译中',
    openExploreMap: '打开探索地图',
    collapseCard: '隐藏卡片',
    expandCard: '显示卡片',
    noReports: '附近还没有记录。',
    nearbyReports: '条附近记录已加载',
    defaultRange: '默认（14天）',
    lastDay: '最近一天',
    last3Days: '最近3天',
    last7Days: '最近7天',
    emptyTitle: '查看 {city} 附近活动',
    emptyState: '使用你的位置、搜索地址，或放大地图来查看 {city} 附近记录。',
    geolocationDenied: '定位权限被拒绝。你也可以搜索地址。',
    geolocationUnavailable: '暂时无法获取你的位置。',
    geolocationTimeout: '定位请求超时。请重试或搜索地址。',
    geolocationUnsupported: '此手机浏览器不支持定位。',
    translationFailed: '翻译未加载成功。下方仍显示英文。',
  },
  'vi-VN': {
    useMyLocation: 'Dùng vị trí của tôi',
    locating: 'Đang tìm vị trí của bạn...',
    searchAddress: 'Tìm địa chỉ',
    hideSearch: 'Ẩn tìm kiếm',
    collapsePanel: 'Ẩn',
    expandPanel: 'Menu',
    loadingMap: 'Đang tải dữ liệu gần đây...',
    dataFrom: 'Dữ liệu từ',
    tapMapHint: 'Phóng to bản đồ để xem hoạt động gần đó.',
    selectedRecord: 'Bản ghi đã chọn',
    english: 'Tiếng Anh',
    translating: 'Đang dịch',
    openExploreMap: 'Mở bản đồ khám phá',
    collapseCard: 'Ẩn thẻ',
    expandCard: 'Hiện thẻ',
    noReports: 'Chưa có bản ghi nào ở gần.',
    nearbyReports: 'bản ghi gần đây đã tải',
    defaultRange: 'Mặc định (14 ngày)',
    lastDay: 'Ngày gần nhất',
    last3Days: '3 ngày qua',
    last7Days: '7 ngày qua',
    emptyTitle: 'Khám phá hoạt động gần {city}',
    emptyState: 'Dùng vị trí của bạn, tìm địa chỉ hoặc phóng to bản đồ để xem các bản ghi gần {city}.',
    geolocationDenied: 'Quyền truy cập vị trí đã bị chặn. Bạn có thể tìm địa chỉ thay thế.',
    geolocationUnavailable: 'Hiện chưa lấy được vị trí của bạn.',
    geolocationTimeout: 'Yêu cầu vị trí bị quá thời gian. Hãy thử lại hoặc tìm địa chỉ.',
    geolocationUnsupported: 'Điện thoại này không hỗ trợ định vị trong trình duyệt.',
    translationFailed: 'Không tải được bản dịch. Phần tiếng Anh vẫn ở bên dưới.',
  },
};

const mapDisplayRef = ref(null);
const mapConfiguration = ref({});
const allDataPoints = ref([]);
const selectedPoint = ref(null);
const mapLoading = ref(false);
const searchOpen = ref(false);
const geolocationLoading = ref(false);
const geolocationError = ref('');
const translationLoading = ref(false);
const translationError = ref('');
const translatedCard = ref(null);
const selectedLanguage = ref('en-US');
const sidebarCollapsed = ref(false);
const sheetCollapsed = ref(false);
const selectedRecency = ref('default');
const liveMapCenter = ref([props.city.latitude, props.city.longitude]);
const translationCache = new Map();
const translationRequestId = ref(0);

const centralLocation = ref({
  latitude: props.city.latitude,
  longitude: props.city.longitude,
  address: props.city.name,
});

const mapCenter = ref([props.city.latitude, props.city.longitude]);
const initialZoom = computed(() => (props.city.key === 'everett' ? 14 : 13));

const text = computed(() => {
  const baseText = UI_TEXT[selectedLanguage.value] || UI_TEXT['en-US'];

  return {
    ...baseText,
    emptyTitle: formatText(baseText.emptyTitle, { city: props.city.name }),
    emptyState: formatText(baseText.emptyState, { city: props.city.name }),
  };
});

const recencyOptions = computed(() => ([
  { key: 'default', label: text.value.defaultRange, days: 14 },
  { key: '1', label: text.value.lastDay, days: 1 },
  { key: '3', label: text.value.last3Days, days: 3 },
  { key: '7', label: text.value.last7Days, days: 7 },
]));

const filteredDataPoints = computed(() => {
  const option = recencyOptions.value.find((entry) => entry.key === selectedRecency.value);
  const newestAvailableDate = getLatestDataPointDate(allDataPoints.value);

  if (!option || option.key === 'default' || !newestAvailableDate) {
    return allDataPoints.value;
  }

  const cutoff = new Date(newestAvailableDate);
  cutoff.setHours(0, 0, 0, 0);
  cutoff.setDate(cutoff.getDate() - (option.days - 1));

  return allDataPoints.value.filter((point) => {
    if (!point.alcivartech_date) {
      return false;
    }

    const pointDate = new Date(point.alcivartech_date);
    return !Number.isNaN(pointDate.getTime()) && pointDate >= cutoff;
  });
});

const visibleDateRangeLabel = computed(() => {
  const range = getVisibleDateRange(filteredDataPoints.value);

  if (!range) {
    return `${text.value.dataFrom} -`;
  }

  return `${text.value.dataFrom} ${formatShortDate(range.oldest)} to ${formatShortDate(range.newest)}`;
});

const exploreMapUrl = computed(() => {
  const [lat, lng] = liveMapCenter.value;
  return `/map/${lat.toFixed(6)}/${lng.toFixed(6)}`;
});

const activeFilterTypes = computed(() => {
  const next = {};

  filteredDataPoints.value.forEach((point) => {
    next[point.alcivartech_type] = true;
  });

  return next;
});

const resultSummary = computed(() => {
  if (mapLoading.value) {
    return text.value.loadingMap;
  }

  if (!filteredDataPoints.value.length) {
    return text.value.noReports;
  }

  return `${filteredDataPoints.value.length} ${text.value.nearbyReports}`;
});

const selectedCard = computed(() => buildCard(selectedPoint.value));

const translatedHeading = computed(() => {
  const option = props.languageOptions.find((entry) => entry.code === selectedLanguage.value);
  return option ? option.label : selectedLanguage.value;
});

onMounted(() => {
  const storedLanguage = window.localStorage.getItem(getLanguageStorageKey());
  if (storedLanguage && props.languageOptions.some((option) => option.code === storedLanguage)) {
    selectedLanguage.value = storedLanguage;
  }

  if (window.innerWidth <= 768) {
    sidebarCollapsed.value = true;
  }

  trackPageView({
    city: props.city.key,
    pageType: 'city_landing',
    languageCode: selectedLanguage.value,
    params: {
      page_location: window.location.href,
      page_path: window.location.pathname,
    },
  });

  trackAnalyticsEvent('city_page_view', {
    city: props.city.key,
    pageType: 'city_landing',
    languageCode: selectedLanguage.value,
    params: {
      city_slug: props.city.slug,
    },
  });

  fetchData();
});

watch(
  () => [selectedPoint.value?.data_point_id, selectedLanguage.value, selectedCard.value?.title],
  () => {
    translateSelectedCard();
  }
);

watch(filteredDataPoints, (points) => {
  if (!selectedPoint.value) {
    return;
  }

  if (!points.some((point) => point.data_point_id === selectedPoint.value.data_point_id)) {
    selectedPoint.value = null;
  }
});

function setLanguage(languageCode) {
  selectedLanguage.value = languageCode;
  window.localStorage.setItem(getLanguageStorageKey(), languageCode);
}

function toggleSidebar() {
  sidebarCollapsed.value = !sidebarCollapsed.value;
}

function getLanguageStorageKey() {
  return `city-lite-language:${props.city.key}`;
}

function buildCard(point) {
  if (!point) {
    return null;
  }

  const config = mapConfiguration.value?.dataPointModelConfig?.[point.alcivartech_model] || {};
  const sourceData = config.dataObjectKey ? point[config.dataObjectKey] : null;

  if (!sourceData) {
    return {
      title: point.alcivartech_type || 'Record',
      summary: '',
      details: [
        {
          label: 'When',
          value: formatDate(point.alcivartech_date),
        },
      ],
    };
  }

  const titleValue = config.mainIdentifierField && sourceData[config.mainIdentifierField]
    ? sourceData[config.mainIdentifierField]
    : point.alcivartech_type;

  const details = [
    {
      label: 'When',
      value: formatDate(point.alcivartech_date),
    },
  ];

  if (config.mainIdentifierField && sourceData[config.mainIdentifierField]) {
    details.push({
      label: config.mainIdentifierLabel || 'ID',
      value: stringifyValue(sourceData[config.mainIdentifierField]),
    });
  }

  if (config.additionalFields && Array.isArray(config.additionalFields)) {
    config.additionalFields.forEach((field) => {
      const value = sourceData[field.key];

      if (value !== undefined && value !== null && String(value).trim() !== '') {
        details.push({
          label: field.label || field.key,
          value: stringifyValue(value),
        });
      }
    });
  }

  return {
    title: `${point.alcivartech_type}: ${stringifyValue(titleValue)}`,
    summary: config.descriptionField && sourceData[config.descriptionField]
      ? stringifyValue(sourceData[config.descriptionField])
      : '',
    details,
  };
}

function stringifyValue(value) {
  if (typeof value === 'string') {
    return value;
  }

  if (value === null || value === undefined) {
    return '';
  }

  if (typeof value === 'object') {
    return JSON.stringify(value);
  }

  return String(value);
}

function formatDate(value) {
  if (!value) {
    return 'Unknown';
  }

  const date = new Date(value);

  if (Number.isNaN(date.getTime())) {
    return String(value);
  }

  return date.toLocaleString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
  });
}

function formatShortDate(value) {
  const date = value instanceof Date ? value : new Date(value);

  if (Number.isNaN(date.getTime())) {
    return String(value);
  }

  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });
}

function getLatestDataPointDate(points) {
  let latest = null;

  points.forEach((point) => {
    if (!point.alcivartech_date) {
      return;
    }

    const date = new Date(point.alcivartech_date);

    if (Number.isNaN(date.getTime())) {
      return;
    }

    if (!latest || date > latest) {
      latest = date;
    }
  });

  return latest;
}

function getVisibleDateRange(points) {
  let oldest = null;
  let newest = null;

  points.forEach((point) => {
    if (!point.alcivartech_date) {
      return;
    }

    const date = new Date(point.alcivartech_date);

    if (Number.isNaN(date.getTime())) {
      return;
    }

    if (!oldest || date < oldest) {
      oldest = date;
    }

    if (!newest || date > newest) {
      newest = date;
    }
  });

  if (!oldest || !newest) {
    return null;
  }

  return { oldest, newest };
}

async function fetchData({ reinitializeMap = false } = {}) {
  mapLoading.value = true;

  try {
    const response = await axios.post('/api/map-data', {
      city: props.city.key,
      centralLocation: centralLocation.value,
      radius: props.city.defaultRadius,
      language_codes: ['en-US'],
    });

    mapConfiguration.value = response.data.mapConfiguration || {};
    allDataPoints.value = response.data.dataPoints || [];

    if (selectedPoint.value) {
      const updatedPoint = allDataPoints.value.find((point) => point.data_point_id === selectedPoint.value.data_point_id);
      selectedPoint.value = updatedPoint || null;
    }

    if (reinitializeMap && mapDisplayRef.value) {
      mapDisplayRef.value.destroyMapAndClear();
      mapDisplayRef.value.initializeNewMapAtCenter(mapCenter.value, true);
    }

    setTimeout(bindMapCenterTracking, 120);
  } catch (error) {
    if (error.response?.status === 419) {
      window.location.reload();
      return;
    }

    console.error('Error fetching city landing data:', error);
  } finally {
    mapLoading.value = false;
  }
}

function handleMarkerClick(point) {
  trackAnalyticsEvent('map_marker_selected', {
    city: props.city.key,
    pageType: 'city_landing',
    languageCode: selectedLanguage.value,
    params: {
      city_slug: props.city.slug,
      record_type: point?.alcivartech_type,
      data_point_id: point?.data_point_id,
    },
  });
  selectedPoint.value = point;
  sheetCollapsed.value = false;
}

function handleAddressSearchStarted() {
  trackAnalyticsEvent('address_search_started', {
    city: props.city.key,
    pageType: 'city_landing',
    languageCode: selectedLanguage.value,
    params: {
      city_slug: props.city.slug,
      search_provider: 'google_places',
    },
  });
}

async function useCurrentLocation() {
  trackAnalyticsEvent('use_my_location_clicked', {
    city: props.city.key,
    pageType: 'city_landing',
    languageCode: selectedLanguage.value,
    params: {
      city_slug: props.city.slug,
    },
  });

  if (!navigator.geolocation) {
    geolocationError.value = text.value.geolocationUnsupported;
    return;
  }

  geolocationLoading.value = true;
  geolocationError.value = '';

  navigator.geolocation.getCurrentPosition(
    async (position) => {
      await updateLocation({
        latitude: position.coords.latitude,
        longitude: position.coords.longitude,
        address: 'Current location',
      });
      geolocationLoading.value = false;
    },
    (error) => {
      if (error.code === error.PERMISSION_DENIED) {
        geolocationError.value = text.value.geolocationDenied;
      } else if (error.code === error.POSITION_UNAVAILABLE) {
        geolocationError.value = text.value.geolocationUnavailable;
      } else if (error.code === error.TIMEOUT) {
        geolocationError.value = text.value.geolocationTimeout;
      } else {
        geolocationError.value = text.value.geolocationUnavailable;
      }

      geolocationLoading.value = false;
    },
    {
      enableHighAccuracy: true,
      timeout: 10000,
      maximumAge: 300000,
    }
  );
}

async function handleAddressSelected(location) {
  geolocationError.value = '';

  await updateLocation({
    latitude: location.lat,
    longitude: location.lng,
    address: location.address,
  });

  trackAnalyticsEvent('address_search_completed', {
    city: props.city.key,
    pageType: 'city_landing',
    languageCode: selectedLanguage.value,
    params: {
      city_slug: props.city.slug,
      search_provider: 'google_places',
    },
  });

  searchOpen.value = false;
}

async function updateLocation(location) {
  centralLocation.value = {
    latitude: location.latitude,
    longitude: location.longitude,
    address: location.address,
  };
  mapCenter.value = [location.latitude, location.longitude];

  await fetchData({ reinitializeMap: true });
}

async function translateSelectedCard() {
  const requestId = ++translationRequestId.value;

  translatedCard.value = null;
  translationError.value = '';
  translationLoading.value = false;

  if (!selectedCard.value || selectedLanguage.value === 'en-US') {
    return;
  }

  const cacheKey = `${selectedPoint.value?.data_point_id}:${selectedLanguage.value}`;
  if (translationCache.has(cacheKey)) {
    translatedCard.value = translationCache.get(cacheKey);
    return;
  }

  translationLoading.value = true;
  trackAnalyticsEvent('translation_requested', {
    city: props.city.key,
    pageType: 'city_landing',
    languageCode: selectedLanguage.value,
    params: {
      city_slug: props.city.slug,
      data_point_id: selectedPoint.value?.data_point_id,
      target_language: selectedLanguage.value,
    },
  });

  try {
    const response = await axios.post('/api/city-landing/translate-record', {
      targetLanguage: selectedLanguage.value,
      title: selectedCard.value.title,
      summary: selectedCard.value.summary,
      details: selectedCard.value.details,
    });

    if (requestId !== translationRequestId.value) {
      return;
    }

    translatedCard.value = response.data;
    translationCache.set(cacheKey, response.data);
    trackAnalyticsEvent('translation_completed', {
      city: props.city.key,
      pageType: 'city_landing',
      languageCode: selectedLanguage.value,
      params: {
        city_slug: props.city.slug,
        data_point_id: selectedPoint.value?.data_point_id,
        target_language: selectedLanguage.value,
      },
    });
  } catch (error) {
    if (requestId !== translationRequestId.value) {
      return;
    }

    console.error('Error translating selected record:', error);
    translationError.value = text.value.translationFailed;
    trackAnalyticsEvent('translation_failed', {
      city: props.city.key,
      pageType: 'city_landing',
      languageCode: selectedLanguage.value,
      params: {
        city_slug: props.city.slug,
        data_point_id: selectedPoint.value?.data_point_id,
        target_language: selectedLanguage.value,
      },
    });
  } finally {
    if (requestId === translationRequestId.value) {
      translationLoading.value = false;
    }
  }
}

function handleExploreMapClick() {
  trackAnalyticsEvent('explore_map_clicked', {
    city: props.city.key,
    pageType: 'city_landing',
    languageCode: selectedLanguage.value,
    params: {
      city_slug: props.city.slug,
      destination_path: exploreMapUrl.value,
    },
  });
}

function bindMapCenterTracking() {
  const map = mapDisplayRef.value?.getMapInstance();

  if (!map) {
    return;
  }

  if (!map.__cityLiteCenterTrackingBound) {
    map.on('moveend', syncLiveCenter);
    map.on('zoomend', syncLiveCenter);
    map.__cityLiteCenterTrackingBound = true;
  }

  syncLiveCenter();
}

function syncLiveCenter() {
  const map = mapDisplayRef.value?.getMapInstance();

  if (!map) {
    return;
  }

  const center = map.getCenter();
  liveMapCenter.value = [center.lat, center.lng];
}

function formatText(template, replacements) {
  return Object.entries(replacements).reduce(
    (result, [key, value]) => result.replaceAll(`{${key}}`, value),
    template,
  );
}
</script>

<style scoped>
.city-lite-page {
  position: relative;
  height: 100vh;
  overflow: hidden;
  background: linear-gradient(180deg, #d7e4ec 0%, #eef3ea 100%);
}

.city-lite-page :deep(.boston-map),
.city-lite-page :deep(#map) {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
}

.top-panel {
  position: absolute;
  top: 0.85rem;
  left: 0.85rem;
  right: 0.85rem;
  z-index: 1001;
  max-width: 34rem;
  padding: 1rem;
  border-radius: 1.25rem;
  background: rgba(247, 244, 235, 0.94);
  border: 1px solid rgba(69, 88, 76, 0.14);
  box-shadow: 0 18px 48px rgba(35, 47, 41, 0.16);
  backdrop-filter: blur(10px);
}

.top-panel.collapsed {
  max-width: 16rem;
  padding: 0.7rem 0.8rem;
}

.brand-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  margin-bottom: 0.5rem;
}

.brand-copy {
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
  min-width: 0;
}

.panel-actions {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-shrink: 0;
}

.brand-mark,
.full-map-link,
.sheet-link,
.collapse-toggle {
  color: #22443a;
  text-decoration: none;
  font-weight: 700;
}

.brand-mark {
  font-size: 0.9rem;
  letter-spacing: 0.02em;
}

.full-map-link,
.sheet-link,
.collapse-toggle {
  font-size: 0.9rem;
}

.collapse-toggle {
  border: 0;
  border-radius: 999px;
  padding: 0.45rem 0.8rem;
  background: #dce8dc;
  cursor: pointer;
}

h1 {
  margin: 0;
  font-size: clamp(2rem, 5vw, 2.75rem);
  line-height: 1;
  color: #17362f;
}

.top-panel.collapsed h1 {
  font-size: 1.45rem;
}

.top-panel.collapsed .brand-row {
  margin-bottom: 0;
}

.panel-body {
  display: block;
}

.tagline,
.intro,
.feedback,
.summary,
.empty-sheet p {
  margin: 0;
  color: #355349;
}

.tagline {
  margin-top: 0.35rem;
  font-size: 1rem;
  font-weight: 700;
}

.intro {
  margin-top: 0.25rem;
  font-size: 0.95rem;
}

.focus-strip {
  display: flex;
  flex-wrap: wrap;
  gap: 0.45rem;
  margin-top: 0.75rem;
}

.focus-chip {
  display: inline-flex;
  align-items: center;
  border-radius: 999px;
  padding: 0.42rem 0.72rem;
  background: #e5eee6;
  color: #23443b;
  font-size: 0.8rem;
  font-weight: 800;
}

.language-strip {
  display: flex;
  gap: 0.5rem;
  margin-top: 0.85rem;
  overflow-x: auto;
  padding-bottom: 0.15rem;
}

.language-chip {
  flex: 0 0 auto;
  border: 0;
  border-radius: 999px;
  padding: 0.55rem 0.9rem;
  background: #e7efe6;
  color: #23443b;
  font-weight: 700;
}

.language-chip.active {
  background: #23443b;
  color: #f5f2e8;
}

.action-row {
  display: grid;
  grid-template-columns: 1fr;
  gap: 0.55rem;
  margin-top: 0.9rem;
}

.range-summary {
  margin: 0.7rem 0 0;
  color: #4c685d;
  font-size: 0.82rem;
  font-weight: 700;
}

.filter-strip {
  display: flex;
  gap: 0.45rem;
  margin-top: 0.7rem;
  overflow-x: auto;
  padding-bottom: 0.15rem;
}

.filter-chip {
  flex: 0 0 auto;
  border: 0;
  border-radius: 999px;
  padding: 0.48rem 0.78rem;
  background: #e7efe6;
  color: #24453b;
  font-size: 0.82rem;
  font-weight: 700;
}

.filter-chip.active {
  background: #24453b;
  color: #f6f2e7;
}

.primary-action,
.secondary-action {
  width: 100%;
  border: 0;
  border-radius: 1rem;
  font-weight: 800;
}

.primary-action {
  padding: 0.95rem 1rem;
  background: #1f6a52;
  color: #f7f4eb;
  box-shadow: 0 12px 30px rgba(31, 106, 82, 0.22);
}

.primary-action:disabled {
  opacity: 0.7;
}

.secondary-action {
  padding: 0.8rem 1rem;
  background: #dce8dc;
  color: #1e4237;
}

.search-shell {
  position: relative;
  margin-top: 0.75rem;
}

.search-shell :deep(input) {
  width: 100%;
  min-height: 3rem;
  padding: 0.85rem 1rem;
  border-radius: 0.95rem;
  border: 1px solid rgba(35, 68, 59, 0.16);
  background: rgba(255, 255, 255, 0.96);
  font-size: 1rem;
}

.search-shell :deep(.address-result-list) {
  border-radius: 1rem;
}

.feedback {
  margin-top: 0.65rem;
  font-size: 0.9rem;
}

.bottom-sheet {
  position: absolute;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 1000;
  padding: 0 0.85rem 0.85rem;
  pointer-events: none;
}

.sheet-content,
.empty-sheet {
  pointer-events: auto;
  max-width: 42rem;
  margin: 0 auto;
  max-height: 42vh;
  overflow-y: auto;
  padding: 1rem 1rem 1.15rem;
  border-radius: 1.35rem;
  background: rgba(248, 244, 236, 0.97);
  border: 1px solid rgba(35, 68, 59, 0.15);
  box-shadow: 0 -10px 40px rgba(22, 35, 31, 0.18);
}

.city-guide {
  margin-top: 0.9rem;
  padding-top: 0.9rem;
  border-top: 1px solid rgba(35, 68, 59, 0.08);
}

.highlight-grid {
  display: grid;
  gap: 0.6rem;
  margin-bottom: 0.9rem;
}

.highlight-card {
  padding: 0.75rem 0.8rem;
  border-radius: 1rem;
  background: #edf3ec;
  border: 1px solid rgba(35, 68, 59, 0.08);
}

.highlight-card p:last-child {
  margin-top: 0.35rem;
}

.guide-heading {
  margin-top: 0.9rem;
}

.guide-note {
  margin-top: 0.45rem;
  font-size: 0.88rem;
  color: #5f756b;
}

.dataset-strip,
.related-links {
  display: flex;
  flex-wrap: wrap;
  gap: 0.45rem;
  margin-top: 0.65rem;
}

.dataset-chip,
.related-link {
  display: inline-flex;
  align-items: center;
  border-radius: 999px;
  padding: 0.45rem 0.72rem;
  font-size: 0.82rem;
  font-weight: 700;
  text-decoration: none;
}

.dataset-chip {
  background: #e5eee6;
  color: #23443b;
}

.related-link {
  background: #d9e9de;
  color: #1f4a3e;
}

.sheet-header {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
  align-items: flex-start;
}

.sheet-actions {
  display: flex;
  align-items: center;
  gap: 0.45rem;
  flex-shrink: 0;
}

.sheet-header h2,
.empty-sheet h2 {
  margin: 0.2rem 0 0;
  color: #17362f;
  font-size: 1.2rem;
}

.sheet-content.collapsed {
  max-height: none;
}

.sheet-toggle {
  background: #e7efe6;
}

.eyebrow,
.detail-heading {
  margin: 0;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  font-size: 0.72rem;
  font-weight: 800;
  color: #61756c;
}

.detail-group {
  margin-top: 1rem;
}

.detail-list {
  margin: 0.5rem 0 0;
}

.detail-row {
  display: grid;
  grid-template-columns: 8rem 1fr;
  gap: 0.85rem;
  padding: 0.45rem 0;
  border-bottom: 1px solid rgba(35, 68, 59, 0.08);
}

.detail-row dt {
  color: #587066;
  font-weight: 700;
}

.detail-row dd {
  margin: 0;
  color: #18362f;
  font-weight: 500;
}

.translated-group {
  padding-top: 0.1rem;
  border-top: 1px solid rgba(35, 68, 59, 0.12);
}

.translation-loading {
  display: inline-flex;
  align-items: center;
  gap: 0.55rem;
  margin-top: 0.55rem;
  color: #355349;
  font-weight: 700;
}

.wiggle-dots {
  display: inline-flex;
  gap: 0.2rem;
}

.wiggle-dots span {
  width: 0.38rem;
  height: 0.38rem;
  border-radius: 999px;
  background: #2f5a4d;
  animation: wiggle 0.9s infinite ease-in-out;
}

.wiggle-dots span:nth-child(2) {
  animation-delay: 0.15s;
}

.wiggle-dots span:nth-child(3) {
  animation-delay: 0.3s;
}

.error-text {
  color: #a13333;
}

@keyframes wiggle {
  0%,
  80%,
  100% {
    transform: translateY(0);
    opacity: 0.4;
  }

  40% {
    transform: translateY(-0.22rem);
    opacity: 1;
  }
}

@media (max-width: 768px) {
  .top-panel {
    max-width: none;
    top: 0.5rem;
    left: 0.5rem;
    right: 0.5rem;
    padding: 0.75rem;
    border-radius: 1rem;
  }

  .brand-row {
    margin-bottom: 0.25rem;
  }

  .brand-mark,
  .full-map-link,
  .sheet-link,
  .collapse-toggle {
    font-size: 0.78rem;
  }

  h1 {
    font-size: 2rem;
  }

  .top-panel.collapsed {
    max-width: 11.5rem;
    padding: 0.55rem 0.65rem;
  }

  .top-panel.collapsed h1 {
    font-size: 1.25rem;
  }

  .tagline {
    margin-top: 0.15rem;
    font-size: 0.88rem;
  }

  .intro {
    display: none;
  }

  .language-strip {
    margin-top: 0.55rem;
    gap: 0.35rem;
  }

  .focus-strip {
    margin-top: 0.55rem;
    gap: 0.35rem;
  }

  .focus-chip {
    padding: 0.35rem 0.6rem;
    font-size: 0.72rem;
  }

  .language-chip {
    padding: 0.42rem 0.7rem;
    font-size: 0.84rem;
  }

  .action-row {
    grid-template-columns: 1fr auto;
    align-items: stretch;
    gap: 0.45rem;
    margin-top: 0.65rem;
  }

  .filter-strip {
    margin-top: 0.55rem;
    gap: 0.35rem;
  }

  .range-summary {
    margin-top: 0.5rem;
    font-size: 0.74rem;
  }

  .filter-chip {
    padding: 0.4rem 0.65rem;
    font-size: 0.74rem;
  }

  .primary-action,
  .secondary-action {
    padding: 0.8rem 0.85rem;
    font-size: 0.92rem;
    border-radius: 0.9rem;
  }

  .secondary-action {
    white-space: nowrap;
    width: auto;
    min-width: 6.8rem;
  }

  .search-shell {
    margin-top: 0.55rem;
  }

  .search-shell :deep(input) {
    min-height: 2.6rem;
    padding: 0.7rem 0.85rem;
    font-size: 0.95rem;
  }

  .feedback {
    margin-top: 0.45rem;
    font-size: 0.82rem;
  }

  .collapse-toggle {
    padding: 0.38rem 0.65rem;
  }

  .bottom-sheet {
    padding: 0 0.5rem 0.5rem;
  }

  .sheet-content,
  .empty-sheet {
    max-height: 34vh;
    padding: 0.8rem 0.85rem 0.9rem;
    border-radius: 1rem;
  }

  .empty-sheet {
    max-width: 30rem;
    max-height: none;
    padding: 0.6rem 0.8rem 0.7rem;
  }

  .empty-sheet h2 {
    font-size: 1rem;
    margin-top: 0.1rem;
  }

  .empty-sheet p:last-child {
    font-size: 0.85rem;
  }

  .dataset-chip,
  .related-link {
    font-size: 0.76rem;
    padding: 0.38rem 0.62rem;
  }

  .highlight-grid {
    gap: 0.45rem;
  }

  .highlight-card {
    padding: 0.65rem 0.7rem;
  }

  .detail-row {
    grid-template-columns: 1fr;
    gap: 0.2rem;
  }

  .sheet-actions {
    gap: 0.3rem;
  }
}
</style>
