<template>
    <div class="map-controls">
      <div class="filter-container flex flex-col justify-center">
        <div
          v-for="(isActive, type) in internalFiltersState"
          :key="type"
          @click="handleToggleFilter(type)"
          :class="[
            { 'active': isActive, 'inactive': !isActive },
            `${type.toLowerCase().replace(/\s/g, '-').replace(/\d/g, 'a')}-filter-button`,
            filterWidthClass
          ]"
          class="filter-button shadow-lg disabled:bg-gray-400 transition-colors text-base"
        >
          <div class="invisible filter-button-text lg:visible">{{ getDataTypeTranslationLabel(type) }}</div>
        </div>
      </div>
  
      <div class="date-filter-container flex flex-col w-full">
        <div class="flex flex-col justify-between">
            <button
            @click="handleClearDateSelections"
            class="px-4 py-2 bg-blue-500 text-white hover:bg-blue-400 transition-colors show-all-dates"
          >
            {{ translations.localizationLabelsByLanguageCode[singleLanguageCodeToUse]?.allDatesButton }}
          </button>
          <button
            v-for="(date, index) in availableDates"
            :key="index"
            @click="handleToggleDateSelection(date)"
            :class="{
              'bg-blue-500 text-white': internalSelectedDates.includes(date),
              'bg-gray-200 hover:bg-gray-300': !internalSelectedDates.includes(date),
            }"
            class="px-4 py-2 shadow transition-colors"
          >
            {{ new Date(date).toLocaleDateString(singleLanguageCodeToUse, { weekday: 'short', month: 'short', day: 'numeric' }) }}
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
      currentDate.setDate(currentDate.getDate() + 1);
    }
    //sort the dates in descending order
    dates.sort((a, b) => new Date(b) - new Date(a));
    return dates;
  });
  
  const filterWidthClass = computed(() => {
    const count = Object.keys(internalFiltersState.value).length;
    if (count > 6) return 'w-1/12';
    if (count === 6) return 'w-1/6';
    if (count === 5) return 'w-1/5';
    if (count === 4) return 'w-1/4';
    if (count === 3) return 'w-1/3';
    if (count === 2) return 'w-1/2';
    return 'w-full';
  });
  
  const getDataTypeTranslationLabel = (type) => {
    return props.translations?.dataTypeMapByLanguageCode?.[singleLanguageCodeToUse.value]?.[type] || type;
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
  .filter-button-text {
    width:100%;
    height: 100%;
    font-weight: 800;
    font-size: 1.5rem;
    align-content: center;
    border-radius: 50%;
  }
    .filter-container div {
      background-position: center;
      text-align: center;
      padding: 0.5rem;
    }
    .date-filter-container {
      width:auto;
    }
    .date-filter-container button {
      width:100%;
      font-size: 0.8rem;
    }
    .map-controls {
      width: 20%;
      max-height: 70vh;
        overflow-y: auto;
    }
    .show-all-dates {
      width:auto;
    }
    div {
        width:100%;
    }
  
  </style>