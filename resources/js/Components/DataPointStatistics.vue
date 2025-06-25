<template>
  <div class="p-4 border rounded-md shadow-sm bg-white mt-4">
    <h4 class="text-lg font-semibold mb-3">Data Statistics</h4>

    <!-- Field Selection -->
    <div class="mb-4 p-2 border-b">
      <h5 class="font-semibold mb-2">Select fields to analyze:</h5>
      <div v-for="(modelInfo, modelName) in availableModelsAndFields" :key="modelName" class="mb-3">
        <label class="font-medium text-md">{{ modelInfo.displayTitle }}</label>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-x-4 gap-y-1 mt-1">
          <div v-for="field in modelInfo.fields" :key="field.name" class="flex items-center">
            <input type="checkbox" :id="`stat-field-${modelName}-${field.name}`" :value="field.name" v-model="selectedFields[modelName]" class="mr-2 h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
            <label :for="`stat-field-${modelName}-${field.name}`" class="text-sm select-none">{{ field.label }}</label>
          </div>
        </div>
      </div>
    </div>

    <!-- Statistics Display -->
    <div v-if="hasStatistics">
      <div v-for="(modelStats, modelName) in statistics" :key="modelName" class="mb-4">
        <div v-for="(fieldStats, fieldName) in modelStats" :key="fieldName" class="mb-3">
          <h6 class="font-medium text-sm">{{ getFieldLabel(modelName, fieldName) }}</h6>
          <ul class="list-disc list-inside text-sm pl-4 mt-1">
            <li v-for="(count, value) in fieldStats" :key="value">
              <span class="font-semibold">{{ value === null || value === '' ? 'N/A' : value }}</span>: {{ count }}
            </li>
          </ul>
        </div>
      </div>
    </div>
    <div v-else class="text-gray-500 text-sm">Select one or more fields above to see statistics.</div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';

const props = defineProps({
  dataPoints: {
    type: Array,
    default: () => []
  },
  mapConfiguration: {
    type: Object,
    default: () => ({})
  },
});

// Stores user's selections, e.g., { crime_data: ['district', 'day_of_week'], ... }
const selectedFields = ref({});

const availableModelsAndFields = computed(() => {
  const models = {};
  if (!props.mapConfiguration?.dataPointModelConfig || !props.dataPoints) return models;

  const presentModelNames = [...new Set(props.dataPoints.map(p => p.alcivartech_model))];

  presentModelNames.forEach(modelName => {
    const modelConfig = props.mapConfiguration.dataPointModelConfig[modelName];
    if (modelConfig && modelConfig.filterFieldsDescription) {
      models[modelName] = {
        displayTitle: modelConfig.displayTitle,
        fields: modelConfig.filterFieldsDescription.filter(f => f.name !== 'search_term' && !f.name.endsWith('_min') && !f.name.endsWith('_max') && !f.name.endsWith('_start') && !f.name.endsWith('_end'))
      };
      if (!selectedFields.value[modelName]) {
        selectedFields.value[modelName] = [];
      }
    }
  });
  return models;
});

const statistics = computed(() => {
  const stats = {};
  const config = props.mapConfiguration?.dataPointModelConfig;
  if (!config) return {};

  for (const modelName in selectedFields.value) {
    const fieldsToAnalyze = selectedFields.value[modelName];
    if (fieldsToAnalyze.length === 0) continue;

    stats[modelName] = {};
    const modelConfig = config[modelName];
    const dataObjectKey = modelConfig.dataObjectKey;

    fieldsToAnalyze.forEach(fieldName => {
      const fieldDef = modelConfig.filterFieldsDescription.find(f => f.name === fieldName);
      if (!fieldDef) return;

      const fieldStats = {};
      const relevantPoints = props.dataPoints.filter(p => p.alcivartech_model === modelName && p[dataObjectKey]);

      if (fieldDef.type === 'number') {
        // Handle numeric fields by creating buckets
        const values = relevantPoints.map(p => p[dataObjectKey][fieldName]).filter(v => v !== null && v !== undefined).map(Number);
        if (values.length === 0) return;

        const min = Math.min(...values);
        const max = Math.max(...values);
        
        if (min === max) {
            fieldStats[min] = values.length;
        } else {
            const bucketCount = 5;
            const bucketSize = (max - min) / bucketCount;
            const buckets = Array.from({ length: bucketCount }, (_, i) => {
                const lower = min + i * bucketSize;
                const upper = lower + bucketSize;
                return { name: `${lower.toFixed(1)} - ${upper.toFixed(1)}`, count: 0 };
            });

            values.forEach(val => {
                let bucketIndex = Math.floor((val - min) / bucketSize);
                if (val === max) bucketIndex = bucketCount - 1; // Edge case for max value
                if (buckets[bucketIndex]) {
                    buckets[bucketIndex].count++;
                }
            });
            buckets.forEach(b => { if(b.count > 0) fieldStats[b.name] = b.count; });
        }
      } else {
        // Handle text, select, boolean, etc. by counting unique values
        relevantPoints.forEach(point => {
          const value = point[dataObjectKey][fieldName];
          if (Array.isArray(value)) { // for multiselect
            value.forEach(v => {
              const valStr = String(v);
              fieldStats[valStr] = (fieldStats[valStr] || 0) + 1;
            });
          } else { // for select, boolean, text
            const valStr = String(value);
            fieldStats[valStr] = (fieldStats[valStr] || 0) + 1;
          }
        });
      }
      
      if (Object.keys(fieldStats).length > 0) {
        // Sort stats by count descending
        stats[modelName][fieldName] = Object.fromEntries(
          Object.entries(fieldStats).sort(([, a], [, b]) => b - a)
        );
      }
    });
  }

  return stats;
});

const hasStatistics = computed(() => {
  return Object.values(statistics.value).some(modelStats => Object.keys(modelStats).length > 0);
});

function getModelDisplayName(modelName) {
  return props.mapConfiguration?.dataPointModelConfig?.[modelName]?.displayTitle || modelName;
}

function getFieldLabel(modelName, fieldName) {
  const field = availableModelsAndFields.value[modelName]?.fields.find(f => f.name === fieldName);
  return field?.label || fieldName;
}
</script>
