<template>
    <div class="map-controls bg-gray-50 p-3 shadow-lg rounded-lg max-h-[70vh] overflow-y-auto">
      <!-- Filter Type Buttons -->
      <div class="filter-type-container mb-4">
        <h3 class="text-sm font-semibold text-gray-700 mb-2">{{ translations.localizationLabelsByLanguageCode[singleLanguageCodeToUse]?.filterByTypeTitle || 'Filter by Type' }}</h3>
        <div class="flex flex-row flex-wrap gap-2">
          <button
            v-for="(isActive, type) in internalFiltersState"
            :key="type"
            @click="handleToggleFilter(type)"
            :class="[
              'filter-button',
              isActive ? 'active' : 'inactive',
              `${type.toLowerCase().replace(/\s/g, '-').replace(/\d/g, 'a')}-filter-button`
            ]"
            class="flex-grow basis-1/3 md:basis-1/4 lg:basis-auto p-2 rounded-md text-xs font-medium transition-all duration-150 ease-in-out focus:outline-none focus:ring-2 focus:ring-opacity-50"
            :title="getDataTypeTranslationLabel(type)"
          >
            <span class="filter-button-icon"></span> <!-- Icon will be via CSS -->
            <span class="filter-button-text break-all hidden lg:inline ml-1">{{ getDataTypeTranslationLabel(type) }}</span>
          </button>
        </div>
      </div>
  
      <!-- Date Filter Section -->
      <div class="date-filter-container">
        <h3 class="text-sm font-semibold text-gray-700 mb-2">{{ translations.localizationLabelsByLanguageCode[singleLanguageCodeToUse]?.filterByDateTitle || 'Filter by Date' }}</h3>
        <button
          @click="handleClearDateSelections"
          class="w-full px-3 py-2 mb-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors text-xs font-semibold shadow"
        >
          {{ translations.localizationLabelsByLanguageCode[singleLanguageCodeToUse]?.allDatesButton || 'Show All Dates' }}
        </button>
        <div class="date-buttons-scroll-container overflow-y-auto space-y-1 pr-1">
          <button
            v-for="(date, index) in availableDates"
            :key="index"
            @click="handleToggleDateSelection(date)"
            :class="{
              'bg-indigo-500 text-white shadow-md': internalSelectedDates.includes(date),
              'bg-gray-200 text-gray-700 hover:bg-gray-300': !internalSelectedDates.includes(date),
            }"
            class="w-full px-3 py-2 rounded-md transition-colors text-xs text-left"
          >
            {{ displayLocalDateFromUtcString(date, singleLanguageCodeToUse, { weekday: 'short', month: 'short', day: 'numeric' }) }}
          </button>
        </div>
      </div>
    </div>
  </template>
  
  <script setup>
  import { ref, computed, watch, defineProps, defineEmits, onMounted } from 'vue';
  
  const props = defineProps({
    initialFilterTypeState: Object,
    initialSelectedDates: Array,
    minDateForFilter: String,
    maxDateForFilter: String,
    translations: Object,
    singleLanguageCode: String,
  });
  
  const emit = defineEmits(['filters-updated']);
  
  const internalFiltersState = ref({});
  const internalSelectedDates = ref([]);
  
  const singleLanguageCodeToUse = computed(() => props.singleLanguageCode || 'en-US');
  
  onMounted(() => {
    internalFiltersState.value = { ...props.initialFilterTypeState };
    internalSelectedDates.value = [...props.initialSelectedDates];
  });
  
  watch(() => props.initialFilterTypeState, (newVal) => {
    internalFiltersState.value = { ...newVal };
  }, { deep: true });
  
  watch(() => props.initialSelectedDates, (newVal) => {
    internalSelectedDates.value = [...newVal];
  }, { deep: true });
  
  
  const availableDates = computed(() => {
    if (!props.minDateForFilter || !props.maxDateForFilter) return [];
    const dates = [];
    const currentDate = new Date(props.minDateForFilter);
    const endDate = new Date(props.maxDateForFilter);
    while (currentDate <= endDate) {
      dates.push(new Date(currentDate).toISOString().split('T')[0]);
      currentDate.setDate(currentDate.getDate()+1);
    }
    //sort the dates in descending order
    dates.sort((a, b) => new Date(b) - new Date(a));
    return dates;
  });
  
  const filterWidthClass = computed(() => {
    const count = Object.keys(internalFiltersState.value).length;
    // Adjusted for flex-wrap, this might not be strictly needed if buttons have intrinsic sizing
    if (count > 4) return 'sm:w-1/3 md:w-1/4'; // Example: 3 or 4 per row on larger screens
    if (count > 2) return 'sm:w-1/2 md:w-1/3';
    return 'w-full sm:w-1/2';
  });
  
  const getDataTypeTranslationLabel = (type) => {
    return props.translations?.dataTypeMapByLanguageCode?.[singleLanguageCodeToUse.value]?.[type] || type;
  };

  const displayLocalDateFromUtcString = (utcDateString, locale, options) => {
    if (!utcDateString) return '';
    // Assuming utcDateString is in "YYYY-MM-DD" format
    const parts = utcDateString.split('-');
    if (parts.length !== 3) {
      console.warn('Invalid date string format for displayLocalDateFromUtcString:', utcDateString);
      return utcDateString; // Return original string or handle error appropriately
    }
  
    const year = parseInt(parts[0], 10);
    const month = parseInt(parts[1], 10) - 1; // JavaScript months are 0-indexed
    const day = parseInt(parts[2], 10);
  
    if (isNaN(year) || isNaN(month) || isNaN(day)) {
      console.warn('Invalid date components after parsing for displayLocalDateFromUtcString:', utcDateString);
      return utcDateString; // Return original string or handle error appropriately
    }
  
    // Create a Date object for noon UTC on the given YYYY-MM-DD.
    // Using noon helps avoid timezone conversion issues around midnight.
    const dateObjUtc = new Date(Date.UTC(year, month, day, 12, 0, 0));
    
    // toLocaleDateString will convert this UTC time to the specified locale's timezone
    // and format it.
    return dateObjUtc.toLocaleDateString(locale, options);
  };
  
  const handleToggleFilter = (type) => {
    internalFiltersState.value[type] = !internalFiltersState.value[type];
    emitFiltersUpdate();
  };
  
  const handleToggleDateSelection = (date) => {
    const index = internalSelectedDates.value.indexOf(date);
    if (index > -1) {
      internalSelectedDates.value.splice(index, 1);
    } else {
      internalSelectedDates.value.push(date);
    }
    emitFiltersUpdate();
  };
  
  const handleClearDateSelections = () => {
    internalSelectedDates.value = [];
    emitFiltersUpdate();
  };
  
  const emitFiltersUpdate = () => {
    emit('filters-updated', {
      activeTypes: { ...internalFiltersState.value },
      selectedDates: [...internalSelectedDates.value ],
    });
  };
  
  watch([internalFiltersState, internalSelectedDates], () => {
      // This watcher is primarily for internal consistency if needed,
      // major updates are emitted.
  }, {deep: true});
  
  </script>
  
  <style scoped>
  .map-controls {
    width: 20%; /* Full width on small screens */
    display: flex;
    flex-direction: column;
  }
  
  .filter-button {
    min-height: 50px; /* Ensures buttons are tall enough */
    min-width: 50px; /* Ensures buttons are wide enough */
    display: flex;
    align-items: center;
    justify-content: center; /* Center icon when text is hidden */
    border: 1px solid transparent;
  }
  
  .filter-button.active {
    border-color: #4A5568; /* Darker border for active */
    box-shadow: inset 0 2px 4px 0 rgba(0, 0, 0, 0.06);
  }
  
  .filter-button.inactive {
    background-color: #E2E8F0; /* bg-gray-300 */
    color: #4A5568; /* text-gray-700 */
  }
  .filter-button.inactive:hover {
    background-color: #CBD5E0; /* bg-gray-400 */
  }
  
  .filter-button-icon {
    background-size: contain;
    background-repeat: no-repeat;
    background-position: center;
    display: inline-block;
  }
  
  .date-buttons-scroll-container::-webkit-scrollbar {
    width: 6px;
  }
  .date-buttons-scroll-container::-webkit-scrollbar-thumb {
    background-color: #A0AEC0; /* bg-gray-500 */
    border-radius: 3px;
  }
  .date-buttons-scroll-container::-webkit-scrollbar-track {
    background-color: #EDF2F7; /* bg-gray-200 */
  }
  
  /* Responsive adjustments for map-controls width */
  @media (min-width: 768px) { /* md breakpoint */
    .map-controls {
      max-width: 20%;
    }
  }
  @media (min-width: 1024px) { /* lg breakpoint */
    .map-controls {
      max-width: 20%
    }
    .filter-button-text {
      font-size: 0.7rem; /* Smaller text on larger screens for filter buttons */
    }
  }
  
  </style>