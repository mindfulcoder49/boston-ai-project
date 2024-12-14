<template>
    <div v-if="data" class="p-4 bg-gray-100 rounded-lg shadow">
      <h2 class="text-xl font-bold text-gray-800">Building Permit</h2>
      <p class="text-gray-700 mb-4">
        <strong>Date:</strong> {{ new Date(data.date).toLocaleString() }}
      </p>
      <ul class="space-y-2">
        <li><strong>Permit Number:</strong> {{ data.info.permitnumber }}</li>
        <li><strong>Work Type:</strong> {{ data.info.worktype }}</li>
        <li><strong>Description:</strong> {{ data.info.description }}</li>
        <li><strong>Status:</strong> {{ data.info.status }}</li>
        <li><strong>Declared Value:</strong> {{ data.info.declared_valuation }}</li>
        <li><strong>Total Fees:</strong> {{ data.info.total_fees }}</li>
        <li><strong>Expiration Date:</strong> {{ formatDate(data.info.expiration_date) }}</li>
        <li><strong>Address:</strong> {{ data.info.address }}, {{ data.info.city }}, {{ data.info.state }} {{ data.info.zip }}</li>
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
  