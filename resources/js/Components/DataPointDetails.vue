<template>
    <div v-if="dataPoint" class="p-4 bg-gray-100 rounded-lg shadow">
      <h2 class="text-xl font-bold text-gray-800">{{ dataPoint.type }}</h2>
      <p class="text-gray-700"><strong>Date:</strong> {{ new Date(dataPoint.date).toLocaleString() }}</p>
      <ul>
        <li v-for="(value, key) in dataPoint.info" :key="key">
          <strong>{{ key }}:</strong> {{ value }}
        </li>
      </ul>
    </div>
  </template>
  
  <script setup>
  defineProps({
    dataPoint: {
      type: Object,
      required: false,
      default: null,
    },
  });
  </script>
  
  <style>


  </style>
  