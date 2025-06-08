<template>
  <div v-if="hasFilters" class="p-4 border rounded-md shadow-sm bg-gray-50 mb-4">
    <h3 class="text-lg font-semibold mb-2">Map Filters Applied at Save Time</h3>
    <div v-if="isCombinedMap" class="space-y-3">
      <div v-for="(typeFilters, dataType) in filters" :key="dataType">
        <h4 class="text-md font-medium text-gray-700">{{ getDataTypeName(dataType) }} Filters:</h4>
        <ul v-if="Object.keys(typeFilters).length > 0" class="list-disc list-inside pl-4 text-sm text-gray-600">
          <li v-for="(value, key) in typeFilters" :key="key">
            <span class="font-semibold">{{ formatFilterKey(key) }}:</span> {{ formatFilterValue(value) }}
          </li>
        </ul>
        <p v-else class="text-sm text-gray-500 pl-4">No specific filters saved for this data type.</p>
      </div>
    </div>
    <div v-else>
      <ul v-if="Object.keys(filters).length > 0" class="list-disc list-inside pl-4 text-sm text-gray-600">
        <li v-for="(value, key) in filters" :key="key">
          <span class="font-semibold">{{ formatFilterKey(key) }}:</span> {{ formatFilterValue(value) }}
        </li>
      </ul>
      <p v-else class="text-sm text-gray-500">No specific filters saved for this map.</p>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  filters: {
    type: Object,
    required: true,
  },
  mapType: {
    type: String,
    required: true, // 'single' or 'combined'
  },
  allDataTypeDetails: { // Used for getting human-readable names for combined maps
    type: Object,
    default: () => ({}),
  }
});

const hasFilters = computed(() => {
  if (!props.filters) return false;
  return Object.keys(props.filters).length > 0;
});

const isCombinedMap = computed(() => props.mapType === 'combined');

const getDataTypeName = (dataType) => {
  return props.allDataTypeDetails[dataType]?.modelNameForHumans || dataType.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
};

const formatFilterKey = (key) => {
  return key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
};

const formatFilterValue = (value) => {
  if (Array.isArray(value)) {
    return value.join(', ');
  }
  if (typeof value === 'object' && value !== null) {
    // Handle date ranges or other complex objects if necessary
    if (value.start_date && value.end_date) {
      return `From ${new Date(value.start_date).toLocaleDateString()} to ${new Date(value.end_date).toLocaleDateString()}`;
    }
    return JSON.stringify(value);
  }
  return value;
};
</script>

<style scoped>
/* Add any specific styles if needed */
</style>
