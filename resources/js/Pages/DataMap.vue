<template>
  <PageTemplate>
    <Head>
      <title>{{ title }}</title>
    </Head>
    <SubscriptionBanner />
    <MapToolbar />
    <DataMapComponent
      :initial-data-prop="initialData"
      :page-filters-prop="filters"
      :data-type-prop="dataType"
      :date-field-prop="dateField"
      :external-id-field-prop="externalIdField"
      :filter-fields-description-prop="filterFieldsDescription"
    />
  </PageTemplate>
</template>

<script setup>
import DataMapComponent from '@/Components/DataMapComponent.vue';
import PageTemplate from '@/Components/PageTemplate.vue';
import MapToolbar from '@/Components/MapToolbar.vue'; // Import the toolbar
import SubscriptionBanner from '@/Components/SubscriptionBanner.vue'; // Import the banner
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
  initialData: Array,
  filters: Object, // Filters from URL query params or controller
  dataType: String,
  dateField: String,
  externalIdField: String,
  filterFieldsDescription: [String, Array, Object], // Can be JSON string or pre-parsed
});

const page = usePage();

const title = computed(() => {
  const dt = props.dataType || page.props.dataType;
  if (dt) {
    // Capitalize and replace underscores for a nicer title
    const formattedDataType = dt.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
    return `${formattedDataType} Map`;
  }
  return 'Data Map';
});
</script>

<style scoped>
/* Styles for the page if any */
</style>
