<template>
  <PageTemplate>
    <Head>
      <title>Single Data Map</title>
    </Head>
    <SubscriptionBanner />
    <MapToolbar :model-toolbar-configs-prop="allModelConfigurationsForToolbar" />
    <DataMapComponent
      :initial-data-prop="initialData"
      :page-filters-prop="filters"
      :data-type-prop="dataType"
      :data-type-config-prop="dataTypeConfig"
      :is-read-only="false" 
      :initial-map-settings="initialMapSettings"
      :configurable-filter-fields-for-view="configurableFilterFieldsForView"
    />
  </PageTemplate>
</template>

<script setup>
import DataMapComponent from '@/Components/DataMapComponent.vue';
import PageTemplate from '@/Components/PageTemplate.vue';
import MapToolbar from '@/Components/MapToolbar.vue';
import SubscriptionBanner from '@/Components/SubscriptionBanner.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
  initialData: Array,
  filters: Object,
  dataType: String, // modelKey
  dataTypeConfig: Object, // Contains all config for the current dataType
  allModelConfigurationsForToolbar: Array, // For MapToolbar
  // Props for read-only view (though this page is typically not read-only, good for consistency if structure is shared)
  isReadOnly: {
    type: Boolean,
    default: false 
  },
  initialMapSettings: {
    type: Object,
    default: () => ({ center: [42.3601, -71.0589], zoom: 12 })
  },
  configurableFilterFieldsForView: {
    type: Array,
    default: () => []
  }
});

const page = usePage();

const title = computed(() => {
  if (props.dataTypeConfig && props.dataTypeConfig.humanName) {
    return `${props.dataTypeConfig.humanName} Map`;
  }
  const dt = props.dataType || page.props.dataType; // Fallback if dataTypeConfig not fully populated
  if (dt) {
    const formattedDataType = dt.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
    return `${formattedDataType} Map`;
  }
  return 'Data Map';
});
</script>

<style scoped>
/* Styles for the page if any */
</style>
