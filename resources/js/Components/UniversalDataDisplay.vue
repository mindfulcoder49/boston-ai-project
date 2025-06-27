<template>
  <div v-if="hasData" class="universal-display space-y-6">
    <!-- Optional Image Carousel Section -->
    <OneImageCarousel v-if="hasImages" :dataPoints="parsedPhotos" @on-image-click="handleImageClick" />
    
    <div v-for="section in sections" :key="section.key" class="border rounded p-4 bg-gray-50">
      <h2 class="text-xl font-bold mb-2">{{ section.title || 'Section' }}</h2>
      
      <!-- For 311 Cases: Use EnrichWithApi if source_city is Boston -->
      <EnrichWithApi 
        v-if="section.key==='three_one_one_case_data' && section.content?.source_city!='Cambridge' && section.content?.case_enquiry_id"
        :case-data="section.content" :start-live-data="data.live_details"
      />
      
      <!-- Print each key/value pair -->
      <ul>
        <li v-for="(value, key) in section.content" :key="key" class="mb-1">
          <div v-if="isPhotoField(key, value)">
            <strong>{{ formatLabel(key) }}:</strong>
            <div v-if="Array.isArray(value)">
              <img v-for="(img, i) in value" :src="img" :key="i" class="max-w-xs mb-1" alt="photo" />
            </div>
            <div v-else>
              <img :src="value" alt="photo" class="max-w-xs mb-1" />
            </div>
          </div>
          <div v-else v-if="key !== 'violation_summary'">
            <strong>{{ formatLabel(key) }}:</strong>
            <span>{{ formatValue(key, value) }}</span>
          </div>
        </li>
      </ul>
      
      <!-- For Food Inspections, show nested violation summary if present -->
      <div v-if="section.key==='food_inspection_data' && section.content.violation_summary">
        <h3 class="mt-4 font-semibold">Violation History:</h3>
        <div v-for="(summary, idx) in section.content.violation_summary" :key="idx" class="border-t pt-2">
          <p><strong>{{ formatLabel('violdesc') }}:</strong> {{ summary.violdesc || 'N/A' }}</p>
          <p><strong>Total Records:</strong> {{ summary.entries?.length || 0 }}</p>
          <ul class="pl-4">
            <li v-for="(entry, i) in summary.entries" :key="i">
              <span><strong>Date:</strong> {{ formatValue('alcivartech_date', entry.alcivartech_date) }}</span>
              <span v-if="entry.viol_status"><strong> - Status:</strong> {{ entry.viol_status }}</span>
              <span v-if="entry.comments"><strong> - Comments:</strong> {{ entry.comments }}</span>
            </li>
          </ul>
        </div>
      </div>
    </div>
    
    <!-- Optionally list top-level fields not contained in sub-objects -->
    <div class="border rounded p-4 bg-gray-100">
      <h2 class="text-xl font-bold mb-2">General Info</h2>
      <ul v-if="sections.length">
        <li v-if="data.alcivartech_type"><strong>Type:</strong> {{ data.alcivartech_type }}</li>
        <li v-if="data.alcivartech_date"><strong>Date:</strong> {{ formatValue('alcivartech_date', data.alcivartech_date) }}</li>
        <li v-if="data.latitude"><strong>Latitude:</strong> {{ data.latitude }}</li>
        <li v-if="data.longitude"><strong>Longitude:</strong> {{ data.longitude }}</li>
      </ul>
      <!-- if there is no sections display the rest of the top level fields -->
      <ul v-if="!sections.length">
        <li v-for="(value, key) in data" :key="key" class="mb-1">
          <strong>{{ formatLabel(key) }}: </strong>
          <span>{{ formatValue(key, value) }}</span>
        </li>
      </ul>
    </div>
  </div>
  <div v-else class="p-4 text-gray-600">
    <p>No data available.</p>
  </div>
</template>

<script setup>
import OneImageCarousel from './OneImageCarousel.vue';
import EnrichWithApi from './EnrichWithApi.vue';
import { ref, computed } from 'vue';
import axios from 'axios';

const props = defineProps({
  data: {
    type: Object,
    required: false
  },
  language_codes: {
    type: Array,
    default: () => ['en-US']
  },
  mapConfiguration: { // Received from RadialMap.vue, originally from GenericMapController
    type: Object,
    default: () => ({})
  }
});

const hasData = computed(() => {
  return props.data && typeof props.data === 'object' && Object.keys(props.data).length > 0;
});

// Helper: returns true if object has at least one property with a non-empty value.
function hasNonEmptyData(obj) {
  return Object.values(obj).some(value => {
    if (value === null || value === undefined) return false;
    if (typeof value === 'string' && value.trim() === '') return false;
    if (Array.isArray(value) && value.length === 0) return false;
    // If object, check its own keys recursively (optional)
    if (typeof value === 'object' && !Array.isArray(value)) {
      return Object.keys(value).length > 0;
    }
    return true;
  });
}

// Build an array of sections only if props.data exists and has non-empty fields.
const sections = computed(() => {
  if (!hasData.value || !props.mapConfiguration || !props.mapConfiguration.modelToSubObjectKeyMap || !props.mapConfiguration.dataPointModelConfig) {
    return [];
  }

  const subs = [];
  const modelToSubObjectKeyMap = props.mapConfiguration.modelToSubObjectKeyMap;
  const dataPointModelConfig = props.mapConfiguration.dataPointModelConfig;

  for (const tableName in modelToSubObjectKeyMap) {
    const dataObjectKey = modelToSubObjectKeyMap[tableName];
    if (props.data[dataObjectKey] && typeof props.data[dataObjectKey] === 'object') {
      const content = props.data[dataObjectKey];
      if (hasNonEmptyData(content)) {
        const title = dataPointModelConfig[tableName]?.displayTitle || formatLabel(tableName); // Fallback title
        subs.push({ key: dataObjectKey, title: title, content });
      }
    }
  }
  return subs;
});

const isFetchingLive = ref(false);

// Robust live fetch—even if errors occur, error handling is done.
async function fetchLiveCaseData(caseId) {
  if (!caseId) return;
  isFetchingLive.value = true;
  try {
    const response = await axios.get(`/api/311-case/live/${caseId}`);
    if (response.data && response.data.data && Array.isArray(response.data.data) && response.data.data.length > 0) {
      // Find and update the section for three_one_one_case_data
      const section = sections.value.find(s => s.key === 'three_one_one_case_data');
      if (section) {
        section.content.live_details = response.data.data[0];
      }
    }
  } catch (error) {
    console.error('Live data fetch error for case ID', caseId, error);
  } finally {
    isFetchingLive.value = false;
  }
}

// Helpers to format keys and values.
function formatLabel(key) {
  // Capitalize first letter and replace underscores.
  if (!key) return 'N/A';
  return key.replace(/_/g, ' ').replace(/\b\w/g, char => char.toUpperCase());
}

function formatValue(key, value) {
  if (value === undefined || value === null || value === '') return 'N/A';
  // If key suggests a date, try to format it.
  if (key.toLowerCase().includes('date') || key.toLowerCase().includes('dt')) {
    const d = new Date(value);
    if (!isNaN(d.getTime())) return d.toLocaleString();
  }
  return value;
}

// Check for photo fields or image URLs robustly.
function isPhotoField(key, value) {
  if (!key || !value) return false;
  const lower = key.toLowerCase();
  if (lower.includes('photo') || lower.includes('image')) return true;
  if (typeof value === 'string' && (value.endsWith('.jpg') || value.endsWith('.jpeg') || value.endsWith('.png') || value.endsWith('.gif'))) return true;
  return false;
}

// New: parsedPhotos computed property extracting closed_photo/submitted_photo from top-level data.
const parsedPhotos = computed(() => {
  const photos = [];
  if (props.data?.closed_photo) {
    props.data.closed_photo.split('|').forEach(url => {
      if (url.trim()) {
        photos.push({ info: { photo: url.trim(), type: props.data.alcivartech_type } });
      }
    });
  }
  if (props.data?.submitted_photo) {
    props.data.submitted_photo.split('|').forEach(url => {
      if (url.trim()) {
        photos.push({ info: { photo: url.trim(), type: props.data.alcivartech_type } });
      }
    });
  }
  return photos;
});

const hasImages = computed(() => parsedPhotos.value.length > 0);

function handleImageClick(photo) {
  // Emit or handle the image click event
  console.log('Image clicked in UniversalDataDisplay:', photo);
}

const nestedData = computed(() => {
  if (!props.data || !props.data.alcivartech_model) {
    return null;
  }
  // The key for the nested object is data.alcivartech_model (e.g., 'crime_data', 'everett_crime_data')
  // This key corresponds to dataObjectKey in GenericMapController's config.
  return props.data[props.data.alcivartech_model] || null;
});

const getModelHumanName = (modelKey) => {
  return props.mapConfiguration[modelKey]?.humanName || modelKey.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
};

const formatKey = (key) => {
  return key.replace(/_/g, ' ').replace(/\b\w/g, (l) => l.toUpperCase());
};

const formatDate = (dateString) => {
  if (!dateString) return 'N/A';
  const date = new Date(dateString);
  if (isNaN(date.getTime())) return dateString; // Return original if invalid
  return date.toLocaleString(); // Or any more specific format
};

const isRedundantOrInternal = (key, value) => {
  // Hide top-level fields if they are already displayed or are internal
  const redundantKeys = ['latitude', 'longitude', 'lat', 'lng', 'x_longitude', 'y_latitude', 'location', 'data_point_id', 'alcivartech_type', 'alcivartech_model', 'alcivartech_date'];
  if (redundantKeys.includes(key.toLowerCase())) return true;
  
  // Hide fields from nested object if they are just repeating lat/long already shown from top-level data
  if (props.data && nestedData.value) {
    if ((key.toLowerCase() === 'latitude' || key.toLowerCase() === 'lat' || key.toLowerCase() === 'y_latitude' || key.toLowerCase() === 'gpsy') && parseFloat(value) === parseFloat(props.data.latitude)) return true;
    if ((key.toLowerCase() === 'longitude' || key.toLowerCase() === 'long' || key.toLowerCase() === 'lng' || key.toLowerCase() === 'x_longitude' || key.toLowerCase() === 'gpsx') && parseFloat(value) === parseFloat(props.data.longitude)) return true;
  }

  // Hide fields that are entirely null or empty strings, unless it's a boolean false
  if (value === null || value === '') return true;
  if (typeof value === 'object' && value !== null && Object.keys(value).length === 0 && key !== 'violation_summary') return true;

  return false;
};
</script>

<style scoped>
.universal-display {
  font-size: 0.9rem;
}
.universal-data-display strong {
  color: #374151; /* gray-700 */
}
</style>
