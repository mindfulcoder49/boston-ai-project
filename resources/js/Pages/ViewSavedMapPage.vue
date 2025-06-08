<template>
  <PageTemplate>
    <Head :title="`View Map: ${savedMap.name}`" />
    
    <div class="p-4">

      <!-- Display Original Saved Filters -->
      <SavedFiltersDisplay 
        :filters="savedMap.filters"
        :map-type="savedMap.map_type"
        :all-data-type-details="allDataTypeDetails"
      />

      <div class="mb-4">
        <h1 class="text-3xl font-bold">{{ savedMap.name }}</h1>
        <p v-if="savedMap.description" class="text-gray-600 mt-1">{{ savedMap.description }}</p>
        <p class="text-sm text-gray-500 mt-2">
          Created by: 
          <span class="font-semibold">{{ savedMap.creator_display_name || savedMap.user?.name || 'Anonymous' }}</span>
          <span v-if="savedMap.user?.creator_tier_display_name" class="ml-2 px-2 py-0.5 text-xs rounded-full"
                :class="getTierBadgeClass(savedMap.user?.creator_tier_name)">
            {{ savedMap.user.creator_tier_display_name }}
          </span>
        </p>
        <p class="text-sm text-gray-500">Last updated: {{ new Date(savedMap.updated_at).toLocaleDateString() }}</p>
      </div>

      <div v-if="savedMap.map_type === 'single'">
        <DataMapComponent
          :initial-data-prop="mapDataSets[savedMap.data_type] || []"
          :page-filters-prop="savedMap.filters" 
          :data-type-prop="savedMap.data_type"
          :date-field-prop="allDataTypeDetails[savedMap.data_type]?.dateField"
          :external-id-field-prop="allDataTypeDetails[savedMap.data_type]?.externalIdField"
          :filter-fields-description-prop="allDataTypeDetails[savedMap.data_type]?.filterFieldsDescription"
          :searchable-columns-prop="allDataTypeDetails[savedMap.data_type]?.searchableColumns || []"
          :is-read-only="true"
          :initial-map-settings="mapSettings"
          :configurable-filter-fields-for-view="savedMap.configurable_filter_fields"
        />
      </div>
      <div v-else-if="savedMap.map_type === 'combined'">
        <CombinedDataMapComponent
          :model-mapping-prop="props.modelMapping"
          :initial-data-type-prop="determineInitialDataTypeForCombined()"
          :initial-data-sets-prop="mapDataSets"
          :initial-filters-prop="savedMap.filters" 
          :all-data-type-details-prop="allDataTypeDetails"
          :is-read-only="true"
          :initial-map-settings="mapSettings"
          :configurable-filter-fields-for-view="savedMap.configurable_filter_fields"
        />
      </div>
      <div v-else class="text-red-500 p-4">
        Error: Unknown map type.
      </div>
    </div>
  </PageTemplate>
</template>

<script setup>
import DataMapComponent from '@/Components/DataMapComponent.vue';
import CombinedDataMapComponent from '@/Components/CombinedDataMapComponent.vue';
import PageTemplate from '@/Components/PageTemplate.vue';
import MapToolbar from '@/Components/MapToolbar.vue';
import SubscriptionBanner from '@/Components/SubscriptionBanner.vue';
import SavedFiltersDisplay from '@/Components/SavedFiltersDisplay.vue'; // Import new component
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
  savedMap: Object, // Contains name, description, map_type, data_type (if single), filters, user, configurable_filter_fields
  mapDataSets: Object, // Keyed by dataType, contains array of data points
  allDataTypeDetails: Object, // Keyed by dataType, contains { dateField, externalIdField, filterFieldsDescription, modelNameForHumans }
  mapSettings: Object, // Contains { center, zoom, selected_data_types (for combined), active_filter_tab (for combined) }
  isReadOnly: Boolean, // Should always be true when passed from SavedMapController@view
  modelMapping: Object, // Added prop from controller
});

const page = usePage();
// Expose modelMapping from global props if available (passed from AppServiceProvider or similar)
// This is a fallback, ideally DataMapController would pass this if needed for CombinedDataMapComponent
const pageProps = computed(() => page.props);

const creatorName = computed(() => {
  return props.savedMap.creator_display_name || props.savedMap.user?.name || 'Anonymous';
});

const determineInitialDataTypeForCombined = () => {
  if (props.savedMap.map_type === 'combined') {
    // Use the saved active tab, or the first selected data type, or the first available data type
    if (props.mapSettings?.active_filter_tab && props.allDataTypeDetails[props.mapSettings.active_filter_tab]) {
      return props.mapSettings.active_filter_tab;
    }
    if (props.mapSettings?.selected_data_types?.length > 0 && props.allDataTypeDetails[props.mapSettings.selected_data_types[0]]) {
      return props.mapSettings.selected_data_types[0];
    }
    // Fallback to the first key in allDataTypeDetails if available
    const availableTypes = Object.keys(props.allDataTypeDetails || {});
    if (availableTypes.length > 0) {
      return availableTypes[0];
    }
  }
  return null;
};

// Ensure modelMapping is available for CombinedDataMapComponent
// If not passed globally, you might need to fetch it or pass it from SavedMapController
// For now, assuming it's available via page.props if needed by CombinedDataMapComponent
// (e.g., if CombinedDataMapComponent relies on it for availableDataTypes computed prop)
// The `modelMappingProp` for CombinedDataMapComponent should be the full mapping from DataMapController.
// The `SavedMapController@view` should ideally pass this.
// If `page.props.modelMapping` is not set up globally, you'll need to adjust.
// A simple way is to add 'modelMapping' => $this->dataMapController->getModelMapping() to the Inertia::render in SavedMapController.
// For now, I'll assume `page.props.modelMapping` is available or CombinedDataMapComponent can work without it if `allDataTypeDetails` is comprehensive.
// The `CombinedDataMapComponent` uses `modelMappingProp` to derive `availableDataTypes`.
// So, it's better to pass it from the controller.
// Let's assume `page.props.ziggy. μεγάλο_model_mapping_from_controller` is passed.
// For this example, I'll rely on `allDataTypeDetails` to imply available types for the saved map.
// The `CombinedDataMapComponent`'s `availableDataTypes` computed prop uses `props.modelMappingProp`.
// So, `SavedMapController` needs to pass the full `modelMapping` from `DataMapController`.
// I will update `SavedMapController` to pass this.

</script>

<style scoped>
/* Styles for the page if any */
</style>
