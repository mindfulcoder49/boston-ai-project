<template>
  <div class="generic-data-display">
    <div v-for="(value, key) in filteredData" :key="key" class="mb-2">
      <div v-if="isPhotoField(key, value)">
        <div><strong>{{ formatLabel(key) }}:</strong></div>
        <div v-if="Array.isArray(value)">
          <img v-for="(img, idx) in value" :src="img" :key="idx" class="max-w-xs mb-1" alt="photo" />
        </div>
        <div v-else>
          <img :src="value" class="max-w-xs mb-1" alt="photo" />
        </div>
      </div>
      <div v-else>
        <strong>{{ formatLabel(key) }}:</strong>
        <span>{{ value }}</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  data: {
    type: Object,
    required: true
  },
  language_codes: {
    type: Array,
    default: () => ['en-US']
  }
});

// Exclude any generic internal fields if needed.
const filteredData = computed(() => {
  const excludeKeys = ['id', 'alcivartech_type', 'alcivartech_date'];
  const entries = {};
  for (const [key, value] of Object.entries(props.data)) {
    if (!excludeKeys.includes(key)) {
      entries[key] = value;
    }
  }
  return entries;
});

function formatLabel(key) {
  // Capitalize first letter and replace underscores with spaces.
  return key.replace(/_/g, ' ').replace(/\b\w/g, char => char.toUpperCase());
}

function isPhotoField(key, value) {
  const keyLower = key.toLowerCase();
  if (keyLower.includes('photo') || keyLower.includes('image')) {
    return true;
  }
  if (typeof value === 'string' && (value.endsWith('.jpg') || value.endsWith('.jpeg') || value.endsWith('.png') || value.endsWith('.gif'))) {
    return true;
  }
  return false;
}
</script>

<style scoped>
.generic-data-display {
  font-size: 0.9rem;
}
</style>
