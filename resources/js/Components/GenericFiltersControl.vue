<template>
  <div class="p-4 border rounded-md shadow-sm bg-white">
    <h4 class="text-lg font-semibold mb-3">Filters</h4>
    <div v-if="parsedFields.length === 0 && !dateField" class="text-gray-500">No filters available for this data type.</div>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <!-- General Search Term Filter - Placed first if present -->
      <template v-for="field in parsedFields" :key="field.name">
        <div v-if="field.name === 'search_term'" class="flex flex-col md:col-span-2 lg:col-span-3"> <!-- Make it span more columns -->
          <label :for="`filter_${field.name}_${dataType}`" class="font-medium mb-1 text-sm">{{ field.label }}</label>
          <input 
            type="text"
            :id="`filter_${field.name}_${dataType}`"
            v-model="localFilters[field.name]"
            @input="debouncedEmitUpdate"
            class="p-2 border rounded-md text-sm"
            :placeholder="field.placeholder || `Enter ${field.label.toLowerCase()}`"
          >
        </div>
      </template>

      <!-- Date Range Filter -->
      <template v-if="dateField">
        <div class="flex flex-col">
          <label :for="`start_date_${dataType}`" class="font-medium mb-1 text-sm">Start Date ({{ dateFieldLabel }})</label>
          <input type="date" :id="`start_date_${dataType}`" v-model="localFilters.start_date" @change="emitFiltersUpdate" class="p-2 border rounded-md text-sm">
        </div>
        <div class="flex flex-col">
          <label :for="`end_date_${dataType}`" class="font-medium mb-1 text-sm">End Date ({{ dateFieldLabel }})</label>
          <input type="date" :id="`end_date_${dataType}`" v-model="localFilters.end_date" @change="emitFiltersUpdate" class="p-2 border rounded-md text-sm">
        </div>
      </template>

      <!-- Dynamic Filters -->
      <div v-for="field in parsedFields.filter(f => f.name !== 'search_term')" :key="field.name" class="flex flex-col"> <!-- Exclude search_term here -->
        <label :for="`filter_${field.name}_${dataType}`" class="font-medium mb-1 text-sm">{{ field.label }}</label>
        
        <input 
          v-if="field.type === 'text' || field.type === 'string' || field.type === 'number'"
          :type="field.type === 'number' ? 'number' : 'text'"
          :id="`filter_${field.name}_${dataType}`"
          v-model="localFilters[field.name]"
          @input="debouncedEmitUpdate"
          class="p-2 border rounded-md text-sm"
          :placeholder="field.placeholder || `Enter ${field.label.toLowerCase()}`"
        >
        
        <select 
          v-else-if="field.type === 'select'"
          :id="`filter_${field.name}_${dataType}`"
          v-model="localFilters[field.name]"
          @change="emitFiltersUpdate"
          class="p-2 border rounded-md text-sm"
        >
          <option value="">All {{ field.label }}</option>
          <option v-for="option in field.options" :key="option.value || option" :value="option.value || option">
            {{ option.label || option }}
          </option>
        </select>

        <div v-else-if="field.type === 'multiselect' && field.options" class="max-h-40 overflow-y-auto border rounded-md p-2">
            <div v-for="option in field.options" :key="option.value || option" class="flex items-center">
                <input 
                    type="checkbox" 
                    :id="`filter_${field.name}_${option.value || option}_${dataType}`"
                    :value="option.value || option"
                    v-model="localFilters[field.name]"
                    @change="emitFiltersUpdate"
                    class="mr-2"
                >
                <label :for="`filter_${field.name}_${option.value || option}_${dataType}`" class="text-sm">{{ option.label || option }}</label>
            </div>
        </div>
        
        <select
          v-else-if="field.type === 'boolean'"
          :id="`filter_${field.name}_${dataType}`"
          v-model="localFilters[field.name]"
          @change="emitFiltersUpdate"
          class="p-2 border rounded-md text-sm"
        >
          <option value="">Any</option>
          <option value="true">Yes</option>
          <option value="false">No</option>
        </select>
        
        <!-- handle date types-->
        <input 
          v-else-if="field.type === 'date'"
          type="date"
          :id="`filter_${field.name}_${dataType}`"
          v-model="localFilters[field.name]"
          @change="emitFiltersUpdate"
          class="p-2 border rounded-md text-sm"
        >
        
      </div>

       <!-- Limit Filter: Only shown if not read-only. If read-only and 'limit' is configurable, it will be rendered by the loop above. -->
        <div v-if="!isReadOnly" class="flex flex-col">
            <label :for="`limit_${dataType}`" class="font-medium mb-1 text-sm">Record Limit</label>
            <input type="number" :id="`limit_${dataType}`" v-model.number="localFilters.limit" @input="debouncedEmitUpdate" class="p-2 border rounded-md text-sm" placeholder="e.g., 1000">
        </div>
    </div>

    <div class="mt-4 flex space-x-2">
      <button @click="applyAndEmitFilters" class="p-2 bg-blue-500 text-white rounded-md text-sm hover:bg-blue-600">Apply Filters</button>
      <button @click="clearAndEmitFilters" class="p-2 bg-gray-300 text-gray-700 rounded-md text-sm hover:bg-gray-400">Clear Filters</button>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, computed, onMounted } from 'vue';

const props = defineProps({
  filterFieldsDescription: { // Expects a JSON string or an array of objects
    type: [String, Array, Object],
    default: () => []
  },
  initialFilters: {
    type: Object,
    default: () => ({})
  },
  dateField: String, // Name of the primary date field for labels, e.g., 'occurred_on_date'
  dataType: String, // To create unique IDs for inputs
  isReadOnly: {      // Added prop
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(['filters-updated']);

const localFilters = ref({});

const dateFieldLabel = computed(() => {
    if (!props.dateField) return '';
    return props.dateField.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
});

const parsedFields = computed(() => {
  if (!props.filterFieldsDescription) return [];
  try {
    let desc = props.filterFieldsDescription;
    if (typeof props.filterFieldsDescription === 'string') {
      // Attempt to parse if it's a string
      desc = JSON.parse(props.filterFieldsDescription);
    }
    
    // Ensure it's an array after potential parsing
    if (Array.isArray(desc)) {
      return desc;
    } else {
      console.warn("filterFieldsDescription was successfully parsed but is not an array. Received:", desc, "Original prop:", props.filterFieldsDescription);
      return [];
    }
  } catch (e) {
    console.error("Failed to parse filterFieldsDescription. Expected a valid JSON string representing an array of filter objects, or an array/object directly. Error:", e, "Received prop:", props.filterFieldsDescription);
    // It's crucial that the model's getFilterableFieldsDescription() returns a valid JSON string.
    // Example of expected JSON: '[{"name": "status", "label": "Status", "type": "select", "options": ["Open", "Closed"]}]'
    return []; // Default to no fields if parsing fails or format is incorrect
  }
});

// Initialize localFilters based on parsedFields and initialFilters
const initializeFilters = () => {
    const newFilters = {
        start_date: props.initialFilters?.start_date || '',
        end_date: props.initialFilters?.end_date || '',
        limit: props.initialFilters?.limit || 1000,
    };
    parsedFields.value.forEach(field => {
        if (props.initialFilters && props.initialFilters.hasOwnProperty(field.name)) {
            if (field.type === 'boolean') {
                // Convert boolean from initialFilters to string "true" or "false" for select
                newFilters[field.name] = String(props.initialFilters[field.name]);
            } else {
                newFilters[field.name] = props.initialFilters[field.name];
            }
        } else {
            // Set default based on type
            if (field.type === 'multiselect') {
                newFilters[field.name] = [];
            } else if (field.type === 'boolean') {
                newFilters[field.name] = ""; // Default to "Any" (empty string)
            } else {
                newFilters[field.name] = '';
            }
        }
    });
    localFilters.value = newFilters;
};

onMounted(() => {
    initializeFilters();
});

watch(() => [props.filterFieldsDescription, props.initialFilters], () => {
    initializeFilters();
}, { deep: true });


const emitFiltersUpdate = () => {
  // Create a clean copy of filters, removing empty strings or empty arrays for some types
  const filtersToEmit = {};
  for (const key in localFilters.value) {
    const value = localFilters.value[key];
    const fieldDescription = parsedFields.value.find(f => f.name === key);

    if (fieldDescription && fieldDescription.type === 'boolean') {
      if (value === "true") {
        filtersToEmit[key] = true;
      } else if (value === "false") {
        filtersToEmit[key] = false;
      }
      // If value is "" (Any), do not add to filtersToEmit
    } else if (value !== '' && value !== null && !(Array.isArray(value) && value.length === 0)) {
      filtersToEmit[key] = value;
    }
    // Note: Booleans are handled above. No need for the `else if (typeof value === 'boolean')` here anymore.
  }
  emit('filters-updated', { ...filtersToEmit });
};

const debounce = (func, delay) => {
  let timeout;
  return function(...args) {
    const context = this;
    clearTimeout(timeout);
    timeout = setTimeout(() => func.apply(context, args), delay);
  };
};

const debouncedEmitUpdate = debounce(() => {
  emitFiltersUpdate();
}, 300); // Adjust debounce time as needed


const applyAndEmitFilters = () => {
    emitFiltersUpdate();
}

const clearAndEmitFilters = () => {
  const clearedFilters = { limit: 1000 }; // Retain default limit or make it configurable
  parsedFields.value.forEach(field => {
    if (field.type === 'multiselect') {
      clearedFilters[field.name] = [];
    } else if (field.type === 'boolean') {
      clearedFilters[field.name] = ""; // Clear to "Any"
    } else {
      clearedFilters[field.name] = '';
    }
  });
  if (props.dateField) {
    clearedFilters.start_date = '';
    clearedFilters.end_date = '';
  }
  localFilters.value = { ...clearedFilters };
  emit('filters-updated', { ...clearedFilters });
};

</script>
