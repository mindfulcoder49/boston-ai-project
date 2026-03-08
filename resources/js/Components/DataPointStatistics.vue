<template>
  <div class="p-4 border rounded-md shadow-sm bg-white mt-4">
    <div class="flex items-center justify-between mb-3">
      <h4 class="text-lg font-semibold">Data Statistics</h4>
      <button
        v-if="hasActiveFilters"
        @click="clearAllFilters"
        class="text-xs text-indigo-600 hover:underline border border-indigo-300 rounded px-2 py-0.5"
      >Clear all filters</button>
    </div>

    <!-- Filter active indicator -->
    <div v-if="hasActiveFilters" class="mb-3 p-2 bg-indigo-50 border border-indigo-200 rounded text-xs text-indigo-700">
      Field filters active — map and AI context show filtered results only.
    </div>

    <!-- Field Selection -->
    <div class="mb-4 p-2 border-b">
      <h5 class="font-semibold mb-2">Select fields to analyze:</h5>
      <div v-for="(modelInfo, modelName) in availableModelsAndFields" :key="modelName" class="mb-3">
        <label class="font-medium text-md">{{ modelInfo.displayTitle }}</label>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-x-4 gap-y-1 mt-1">
          <div v-for="field in modelInfo.fields" :key="field.name" class="flex items-center">
            <input
              type="checkbox"
              :id="`stat-field-${modelName}-${field.name}`"
              :value="field.name"
              v-model="selectedFields[modelName]"
              class="mr-2 h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
            >
            <label :for="`stat-field-${modelName}-${field.name}`" class="text-sm select-none">{{ field.label }}</label>
          </div>
        </div>
      </div>
    </div>

    <!-- Statistics Display with per-value checkboxes -->
    <div v-if="hasStatistics">
      <div v-for="(modelStats, modelName) in statistics" :key="modelName" class="mb-4">
        <div v-for="(fieldStats, fieldName) in modelStats" :key="fieldName" class="mb-3">
          <div class="flex items-center justify-between mb-1">
            <h6 class="font-medium text-sm">{{ getFieldLabel(modelName, fieldName) }}</h6>
            <button
              v-if="hasFieldFilter(modelName, fieldName)"
              @click="clearFieldFilter(modelName, fieldName)"
              class="text-xs text-indigo-600 hover:underline"
            >clear</button>
          </div>
          <div class="space-y-0.5 pl-1">
            <label
              v-for="(count, valueKey) in fieldStats"
              :key="valueKey"
              class="flex items-center gap-2 text-sm cursor-pointer select-none"
              :class="{ 'opacity-40': !isValueChecked(modelName, fieldName, valueKey) }"
            >
              <input
                type="checkbox"
                :checked="isValueChecked(modelName, fieldName, valueKey)"
                @change="toggleValue(modelName, fieldName, valueKey)"
                class="h-3.5 w-3.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 flex-shrink-0"
              >
              <span
                class="flex-1 truncate"
                :class="{ 'line-through': !isValueChecked(modelName, fieldName, valueKey) }"
              >{{ valueKey === 'null' || valueKey === '' ? 'N/A' : valueKey }}</span>
              <span class="text-gray-500 text-xs tabular-nums">{{ count }}</span>
            </label>
          </div>
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
  resetKey: {
    type: Number,
    default: 0,
  },
});

const emit = defineEmits(['value-filters-changed']);

// Field selection state: { [modelName]: string[] }
const selectedFields = ref({});

// Per-value checkbox state: { [modelName]: { [fieldName]: { [valueKey]: boolean } } }
// Absent entry defaults to true (checked).
const valueCheckboxState = ref({});

// Only reset field-value filters after a real data refetch.
// Client-side type/date changes should preserve the user's selections.
watch(() => props.resetKey, () => {
  valueCheckboxState.value = {};
});

const availableModelsAndFields = computed(() => {
  const models = {};
  if (!props.mapConfiguration?.dataPointModelConfig || !props.dataPoints) return models;

  const presentModelNames = [...new Set(props.dataPoints.map(p => p.alcivartech_model))];

  presentModelNames.forEach(modelName => {
    const modelConfig = props.mapConfiguration.dataPointModelConfig[modelName];
    if (modelConfig && modelConfig.filterFieldsDescription) {
      models[modelName] = {
        displayTitle: modelConfig.displayTitle,
        fields: modelConfig.filterFieldsDescription.filter(
          f => f.name !== 'search_term'
            && !f.name.endsWith('_min')
            && !f.name.endsWith('_max')
            && !f.name.endsWith('_start')
            && !f.name.endsWith('_end')
        )
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
        const values = relevantPoints
          .map(p => p[dataObjectKey][fieldName])
          .filter(v => v !== null && v !== undefined)
          .map(Number);
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
            let idx = Math.floor((val - min) / bucketSize);
            if (val === max) idx = bucketCount - 1;
            if (buckets[idx]) buckets[idx].count++;
          });
          buckets.forEach(b => { if (b.count > 0) fieldStats[b.name] = b.count; });
        }
      } else {
        relevantPoints.forEach(point => {
          const value = point[dataObjectKey][fieldName];
          if (Array.isArray(value)) {
            value.forEach(v => {
              const k = String(v);
              fieldStats[k] = (fieldStats[k] || 0) + 1;
            });
          } else {
            const k = String(value);
            fieldStats[k] = (fieldStats[k] || 0) + 1;
          }
        });
      }

      if (Object.keys(fieldStats).length > 0) {
        stats[modelName][fieldName] = Object.fromEntries(
          Object.entries(fieldStats).sort(([, a], [, b]) => b - a)
        );
      }
    });
  }

  return stats;
});

const hasStatistics = computed(() =>
  Object.values(statistics.value).some(m => Object.keys(m).length > 0)
);

// Whenever statistics change, ensure every visible value key is explicitly present in
// valueCheckboxState (default true). Without this, absent keys look "checked" via the
// !== false guard but are missing from the emitted filter, producing an empty allowedValues
// array that hides all points on first click.
watch(statistics, (newStats) => {
  for (const [modelName, fields] of Object.entries(newStats)) {
    if (!valueCheckboxState.value[modelName]) valueCheckboxState.value[modelName] = {};
    for (const [fieldName, valueCounts] of Object.entries(fields)) {
      if (!valueCheckboxState.value[modelName][fieldName]) {
        valueCheckboxState.value[modelName][fieldName] = {};
      }
      const currentState = valueCheckboxState.value[modelName][fieldName];
      // If the user has already restricted this field (some values unchecked), treat any
      // newly appearing value as excluded — otherwise it would silently expand the filter
      // (e.g. "Drug Offenses only" would gain "Robbery" when dates change).
      const hasActiveFilter = Object.values(currentState).some(v => !v);
      for (const valueKey of Object.keys(valueCounts)) {
        if (!(valueKey in currentState)) {
          currentState[valueKey] = !hasActiveFilter;
        }
      }
    }
  }
}, { deep: true, immediate: true });

const hasActiveFilters = computed(() =>
  Object.values(valueCheckboxState.value).some(fields =>
    Object.values(fields).some(valueBooleans =>
      Object.values(valueBooleans).some(v => !v)
    )
  )
);

// Default: absent entry = checked (true)
const isValueChecked = (modelName, fieldName, valueKey) =>
  valueCheckboxState.value[modelName]?.[fieldName]?.[valueKey] !== false;

const hasFieldFilter = (modelName, fieldName) => {
  const state = valueCheckboxState.value[modelName]?.[fieldName];
  return state ? Object.values(state).some(v => !v) : false;
};

const toggleValue = (modelName, fieldName, valueKey) => {
  if (!valueCheckboxState.value[modelName]) valueCheckboxState.value[modelName] = {};
  if (!valueCheckboxState.value[modelName][fieldName]) valueCheckboxState.value[modelName][fieldName] = {};
  valueCheckboxState.value[modelName][fieldName][valueKey] = !isValueChecked(modelName, fieldName, valueKey);
};

const clearFieldFilter = (modelName, fieldName) => {
  const state = valueCheckboxState.value[modelName]?.[fieldName];
  if (state) Object.keys(state).forEach(k => { state[k] = true; });
};

const clearAllFilters = () => {
  for (const modelName in valueCheckboxState.value) {
    for (const fieldName in valueCheckboxState.value[modelName]) {
      const state = valueCheckboxState.value[modelName][fieldName];
      Object.keys(state).forEach(k => { state[k] = true; });
    }
  }
};

// Emit active filters to parent whenever checkbox state changes.
// A field filter is only emitted when at least one value is checked AND at least one is
// unchecked. All-unchecked is treated as "no restriction" to avoid hiding all data.
watch(valueCheckboxState, () => {
  const activeFilters = {};
  for (const [modelName, fields] of Object.entries(valueCheckboxState.value)) {
    for (const [fieldName, valueBooleans] of Object.entries(fields)) {
      const allValues   = Object.keys(valueBooleans);
      const allowed     = allValues.filter(k => valueBooleans[k]);
      const hasFilter   = allowed.length > 0 && allowed.length < allValues.length;
      if (hasFilter) {
        if (!activeFilters[modelName]) activeFilters[modelName] = {};
        activeFilters[modelName][fieldName] = allowed;
      }
    }
  }
  emit('value-filters-changed', activeFilters);
}, { deep: true });

function getFieldLabel(modelName, fieldName) {
  return availableModelsAndFields.value[modelName]?.fields.find(f => f.name === fieldName)?.label || fieldName;
}
</script>
