<template>
  <div class="p-2 mb-4">
    <div class="flex flex-wrap items-center justify-start gap-2">
      <span class="font-semibold text-gray-700 mr-2 text-sm">VIEW:</span>
      <Link
        :href="route('data-map.combined')"
        class="px-4 py-2 rounded-md shadow-sm transition-colors duration-150 ease-in-out focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
        :class="{
          'bg-blue-600 text-white hover:bg-blue-700': route().current('data-map.combined'),
          'bg-gray-100 text-gray-700 hover:bg-gray-200 border border-gray-300': !route().current('data-map.combined')
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
  }
});

const page = usePage();

// Use the prop passed from the parent page (DataMap.vue or CombinedDataMap.vue)
const modelToolbarConfigs = computed(() => {
    // Fallback to page.props if the direct prop isn't passed (e.g. from a page not yet updated)
    return props.modelToolbarConfigsProp.length > 0 ? props.modelToolbarConfigsProp : (page.props.allModelConfigurationsForToolbar || []);
});

const isActive = (dataType) => {
  return route().current('data-map.index') && page.props.dataType === dataType;
};

// getIconClassForDataType is no longer needed here, as iconClass comes from modelToolbarConfigs
</script>

<style scoped>
/* Scoped styles can be added here if further customization beyond Tailwind is needed */
</style>
