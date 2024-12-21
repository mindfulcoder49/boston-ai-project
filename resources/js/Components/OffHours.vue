<template>
    <div v-if="data" class="p-4 bg-gray-100 rounded-lg shadow">
      <h2 class="text-xl font-bold text-gray-800">Construction Off Hour</h2>
      <p class="text-gray-700 mb-4">
        <strong>Start Date:</strong> {{ new Date(data.info.start_datetime).toLocaleString() }}
      </p>
         <p class="text-gray-700 mb-4">
        <strong>End Date:</strong> {{ formatDate(data.info.stop_datetime) }}
      </p>
      <ul class="space-y-2">
           <li><strong>Application Number:</strong> {{ data.info.app_no }}</li>
            <li><strong>Address:</strong> {{ data.info.address }}</li>
            <li><strong>Ward:</strong> {{ data.info.ward }}</li>
             <li><strong>Latitude:</strong> {{ data.latitude }}</li>
              <li><strong>Longitude:</strong> {{ data.longitude }}</li>

      </ul>
    </div>
  </template>
  
  <script setup>
  defineProps({
    data: {
      type: Object,
      required: true,
    },
  });
  
  function formatDate(date) {
    return date ? new Date(date).toLocaleString() : "N/A";
  }
  </script>