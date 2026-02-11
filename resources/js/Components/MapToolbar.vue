<template>
  <div class="p-2 mb-4">
    <div class="flex flex-wrap items-center justify-start gap-2">
      <span class="font-semibold text-gray-700 mr-2 text-sm">VIEW:</span>
      <Link
        :href="route('data-map.combined')"
        class="px-4 py-2 rounded-md shadow-sm transition-colors duration-150 ease-in-out focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
        :class="{
          'bg-blue-600 text-white hover:bg-blue-700': isCombinedActive && !hasTypesParam,
          'bg-gray-100 text-gray-700 hover:bg-gray-200 border border-gray-300': !isCombinedActive || hasTypesParam
        }"
      >
      <!-- Static icons for "All Datasets" button -->
          <span class="toolbar-icon-display crime-div-icon"></span>
          <span class="toolbar-icon-display case-div-icon no-photo"></span>
          <span class="toolbar-icon-display property-violation-div-icon"></span>
          <span class="toolbar-icon-display permit-div-icon"></span>
          <span class="toolbar-icon-display food-inspection-div-icon"></span>
          <span class="toolbar-icon-display construction-off-hour-div-icon"></span>
        All Datasets
      </Link>

      <!-- City group links -->
      <template v-if="modelCityGroups.length > 0">
        <span class="text-gray-300 mx-1">|</span>
        <Link
          v-for="group in modelCityGroups"
          :key="group.name"
          :href="cityGroupUrl(group)"
          class="px-4 py-2 rounded-md shadow-sm transition-colors duration-150 ease-in-out focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500"
          :class="{
            'bg-emerald-600 text-white hover:bg-emerald-700': isCityGroupActive(group),
            'bg-gray-100 text-gray-700 hover:bg-gray-200 border border-gray-300': !isCityGroupActive(group)
          }"
        >
          {{ group.name }}
          <span class="text-xs opacity-75 ml-1">({{ group.keys.length }})</span>
        </Link>
        <span class="text-gray-300 mx-1">|</span>
      </template>

      <Link
        v-for="mapConfig in modelToolbarConfigs"
        :key="mapConfig.dataType"
        :href="route('data-map.index', { dataType: mapConfig.dataType })"
        class="px-4 py-2 rounded-md shadow-sm transition-colors duration-150 ease-in-out focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
        :class="{
          'bg-indigo-600 text-white hover:bg-indigo-700': isActive(mapConfig.dataType),
          'bg-gray-100 text-gray-700 hover:bg-gray-200 border border-gray-300': !isActive(mapConfig.dataType)
        }"
      >
      <span :class="mapConfig.iconClass" class="toolbar-icon-display"></span>
        {{ mapConfig.name }}
      </Link>
    </div>
  </div>
</template>

<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
  modelToolbarConfigsProp: {
    type: Array,
    default: () => []
  },
  modelCityGroupsProp: {
    type: Array,
    default: () => []
  }
});

const page = usePage();

// Use the prop passed from the parent page (DataMap.vue or CombinedDataMap.vue)
const modelToolbarConfigs = computed(() => {
    // Fallback to page.props if the direct prop isn't passed (e.g. from a page not yet updated)
    return props.modelToolbarConfigsProp.length > 0 ? props.modelToolbarConfigsProp : (page.props.allModelConfigurationsForToolbar || []);
});

const modelCityGroups = computed(() => {
    return props.modelCityGroupsProp;
});

const isCombinedActive = computed(() => route().current('data-map.combined'));

const hasTypesParam = computed(() => {
    const url = page.url || '';
    return url.includes('types=');
});

const isActive = (dataType) => {
  return route().current('data-map.index') && page.props.dataType === dataType;
};

const cityGroupUrl = (group) => {
    if (group.keys.length === 1) {
        return route('data-map.index', { dataType: group.keys[0] });
    }
    return route('data-map.combined') + '?types=' + group.keys.join(',');
};

const isCityGroupActive = (group) => {
    if (group.keys.length === 1) {
        return isActive(group.keys[0]);
    }
    // Active if on combined map with exactly these types selected
    if (!isCombinedActive.value) return false;
    const url = page.url || '';
    const match = url.match(/[?&]types=([^&]*)/);
    if (!match) return false;
    const urlTypes = match[1].split(',').sort();
    const groupTypes = [...group.keys].sort();
    return urlTypes.length === groupTypes.length && urlTypes.every((t, i) => t === groupTypes[i]);
};
</script>

<style scoped>
/* Scoped styles can be added here if further customization beyond Tailwind is needed */
</style>
