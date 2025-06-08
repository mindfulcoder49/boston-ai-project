<template>
  <PageTemplate>
    <Head>
      <title>Combined Data Map</title>
    </Head>
    <SubscriptionBanner />
    <MapToolbar />
    <CombinedDataMapComponent
      :model-mapping-prop="modelMapping"
      :initial-data-type-prop="initialDataType"
      :initial-data-sets-prop="initialDataSets"
      :initial-filters-prop="initialFilters"
      :all-data-type-details-prop="allDataTypeDetails"
    />
  </PageTemplate>
</template>

<script setup>
import CombinedDataMapComponent from '@/Components/CombinedDataMapComponent.vue';
import PageTemplate from '@/Components/PageTemplate.vue';
import MapToolbar from '@/Components/MapToolbar.vue'; // Import the toolbar
import SubscriptionBanner from '@/Components/SubscriptionBanner.vue'; // Import the banner
import { Head } from '@inertiajs/vue3';

const props = defineProps({
  modelMapping: Object, // The mapping from DataMapController
  initialDataType: String, // The first data type to show
  initialDataSets: Object, // Changed from initialData: Array. Data for initial types, keyed by dataType.
  initialFilters: Object, // Filters for the initialDataType
  allDataTypeDetails: Object, // Object keyed by dataType, containing { dateField, externalIdField, filterFieldsDescription, modelNameForHumans }
});
</script>

<style scoped>
/* Styles for the page if any */
</style>
