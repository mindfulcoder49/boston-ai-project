<template>
  <div class="mb-4 p-3 border border-gray-300 bg-white shadow rounded" v-if="caseData">
    <button
      @click="fetchLiveData"
      :disabled="isLoading || !caseData.case_enquiry_id"
      class="mb-3 px-3 py-1 bg-blue-500 text-white rounded hover:bg-blue-600 disabled:bg-gray-400"
    >
      {{ isLoading ? 'Loading Live Data...' : 'Refresh Live Data' }}
    </button>
  
    <div v-if="liveData && Object.keys(liveData).length > 0">
      <h3 class="text-md font-semibold text-gray-700">Live Data from BOS:311</h3>
      <ul class="mt-2 text-sm text-gray-600 space-y-1">
        <li v-if="liveData.service_request_id"><strong>Service Request ID:</strong> {{ liveData.service_request_id }}</li>
        <li v-if="liveData.status"><strong>Status:</strong> {{ liveData.status }}</li>
        <li v-if="liveData.status_notes"><strong>Status Notes:</strong> {{ liveData.status_notes }}</li>
        <li v-if="liveData.service_name"><strong>Service Name:</strong> {{ liveData.service_name }}</li>
        <li v-if="liveData.description"><strong>Description:</strong> {{ liveData.description }}</li>
        <li v-if="liveData.address"><strong>Address:</strong> {{ liveData.address }}</li>
        <li v-if="liveData.agency_responsible"><strong>Agency Responsible:</strong> {{ liveData.agency_responsible }}</li>
        <li v-if="liveData.service_notice"><strong>Service Notice:</strong> {{ liveData.service_notice }}</li>
        <li v-if="liveData.requested_datetime"><strong>Reported:</strong> {{ formatDate(liveData.requested_datetime) }}</li>
        <li v-if="liveData.updated_datetime"><strong>Last Updated:</strong> {{ formatDate(liveData.updated_datetime) }}</li>
        <li v-if="liveData.expected_datetime"><strong>Expected Resolution:</strong> {{ formatDate(liveData.expected_datetime) }}</li>
      </ul>
      <div v-if="liveData.media_url" class="mt-2">
        <img :src="liveData.media_url" alt="Live media" class="max-h-[80vh] w-auto border rounded"/>
      </div>
    </div>
    <div v-else-if="!isLoading && !liveDataError" class="text-sm text-gray-500">
      <p>No live data available.</p>
    </div>
    <div v-if="liveDataError" class="mt-3 text-sm text-red-600 bg-red-100 p-2 rounded border border-red-300">
      <p><strong>Error:</strong> {{ liveDataError }}</p>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import axios from 'axios';

const props = defineProps({
  caseData: { type: Object, required: true },
  liveData: { type: Object, required: true, default: () => ({}) }
});

const isLoading = ref(false);
const liveDataError = ref(null);
const liveData = computed(() => props.liveData || {});

function formatDate(date) {
  return date ? new Date(date).toLocaleString() : 'N/A';
}

const fetchLiveData = async () => {
  if (!props.caseData.case_enquiry_id) return;
  isLoading.value = true;
  liveDataError.value = null;
  try {
    const response = await axios.get(`/api/311-case/live/${props.caseData.case_enquiry_id}`);
    console.log('Live data response:', response.data && response.data.data);
    if (response.data && response.data.data) {
      liveData.value = response.data.data;
    } else {
      liveData.value = {};
    }
  } catch (error) {
    console.error('Error fetching live data:', error);
    liveDataError.value = 'Failed to fetch live data.';
  } finally {
    isLoading.value = false;
  }
};

// Automatically fetch live data when component mounts if conditions met.
if (props.caseData.source_city === 'Boston') {
  fetchLiveData();
}
</script>
