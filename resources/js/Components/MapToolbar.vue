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
      <!-- make a span for every dataType to show all the icons -->
          <span class="toolbar-icon-display crime-div-icon"></span>
          <span class="toolbar-icon-display case-div-icon no-photo"></span>
          <span class="toolbar-icon-display property-violation-div-icon"></span>
          <span class="toolbar-icon-display building-permit-div-icon"></span>
          <span class="toolbar-icon-display food-inspection-div-icon"></span>
          <span class="toolbar-icon-display construction-off-hour-div-icon"></span>

        All Datasets
      </Link>
      <Link
        v-for="mapType in mapTypes"
        :key="mapType.dataType"
        :href="route('data-map.index', { dataType: mapType.dataType })"
        class="px-4 py-2 rounded-md shadow-sm transition-colors duration-150 ease-in-out focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
        :class="{
          'bg-indigo-600 text-white hover:bg-indigo-700': isActive(mapType.dataType),
          'bg-gray-100 text-gray-700 hover:bg-gray-200 border border-gray-300': !isActive(mapType.dataType)
        }"
      >
      <span :class="getIconClassForDataType(mapType.dataType)" class="toolbar-icon-display"></span>
        {{ mapType.name }}
      </Link>
    </div>
  </div>
</template>

<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const page = usePage();

// These would ideally be passed as props or fetched,
// matching the keys in DataMapController's $modelMapping
// For now, keeping them static as in the previous version.
// Consider populating this from page.props if available, e.g., from allDataTypeDetails on combined map.
const mapTypes = ref([
  { name: 'Crime Reports', dataType: 'crime' },
  { name: '311 Cases', dataType: '311_cases' },
  { name: 'Property Violations', dataType: 'property_violations' },
  { name: 'Building Permits', dataType: 'building_permits' },
  { name: 'Food Inspections', dataType: 'food_inspections' },
  { name: 'Construction Off Hours', dataType: 'construction_off_hours' },
  // Add other map types here
]);

const isActive = (dataType) => {
  // Check if the current route is 'data-map.index' and the dataType prop matches
  return route().current('data-map.index') && page.props.dataType === dataType;
};

const getIconClassForDataType = (dataType) => {
  if (!dataType) return '';
  const dtLower = dataType.toLowerCase();
  if (dtLower.includes('crime')) return 'crime-div-icon';
  if (dtLower.includes('case') || dtLower.includes('311')) return 'case-div-icon no-photo'; // Example for 311/cases
  if (dtLower.includes('permit')) return 'permit-div-icon'; // Or 'building-permit-div-icon' if that's the class
  if (dtLower.includes('property_violation') || dtLower.includes('violation')) return 'property-violation-div-icon';
  if (dtLower.includes('construction_off_hour') || dtLower.includes('construction')) return 'construction-off-hour-div-icon';
  if (dtLower.includes('food_inspection') || dtLower.includes('food') || dtLower.includes('inspection')) return 'food-inspection-div-icon';
  // Add more mappings as needed based on your dataTypes and CSS classes
  return ''; // Return empty string or a default icon class if no match
};
</script>

<style scoped>
/* Scoped styles can be added here if further customization beyond Tailwind is needed */
</style>
