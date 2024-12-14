<template>
    <div v-if="data" class="p-4 bg-gray-100 rounded-lg shadow">
      <h2 class="text-xl font-bold text-gray-800">Crime Report</h2>
      <p class="text-gray-700 mb-4">
        <strong>Date:</strong> {{ new Date(data.date).toLocaleString() }}
      </p>
      <ul class="space-y-2">
        <li><strong>Incident Number:</strong> {{ data.info.incident_number }}</li>
        <li><strong>Offense:</strong> {{ data.info.offense_description }}</li>
        <li><strong>Category:</strong> {{ data.info.offense_category }}</li>
        <li><strong>District:</strong> {{ data.info.district }}</li>
        <li><strong>Street:</strong> {{ data.info.street }}</li>
        <li><strong>Day:</strong> {{ data.info.day_of_week }}</li>
        <li><strong>Time:</strong> {{ formatTime(data.info.hour) }}</li>
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
  
  function formatTime(hour) {
    const date = new Date();
    date.setHours(hour, 0, 0);
    return date.toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" });
  }
  </script>
  