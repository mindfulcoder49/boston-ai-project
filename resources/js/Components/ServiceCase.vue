<template>
    <div v-if="data" class="p-4 bg-gray-100 rounded-lg shadow">
      <h2 class="text-xl font-bold text-gray-800">311 Case</h2>
      <p class="text-gray-700 mb-4">
        <strong>Date:</strong> {{ new Date(data.date).toLocaleString() }}
      </p>
      <ul class="space-y-2">
        <li><strong>Case ID:</strong> {{ data.info.case_enquiry_id }}</li>
        <li><strong>Status:</strong> {{ data.info.case_status }}</li>
        <li><strong>Title:</strong> {{ data.info.case_title }}</li>
        <li><strong>Reason:</strong> {{ data.info.reason }}</li>
        <li><strong>Subject:</strong> {{ data.info.subject }}</li>
        <li><strong>Location:</strong> {{ data.info.location }}</li>
        <li><strong>Neighborhood:</strong> {{ data.info.neighborhood }}</li>
        <li><strong>Source:</strong> {{ data.info.source }}</li>
        <li><strong>Department:</strong> {{ data.info.department }}</li>
        <li><strong>Closure Date:</strong> {{ formatDate(data.info.closed_dt) }}</li>
      </ul>
      <div v-if="data.info.closed_photo" class="mt-4">
      <img :src="data.info.closed_photo" alt="Closed Case Photo" class="w-full rounded-lg shadow">
    </div>
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
  