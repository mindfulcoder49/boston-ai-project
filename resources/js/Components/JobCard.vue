<template>
  <div class="bg-white shadow-md rounded-lg p-6">
    <div class="flex flex-col sm:flex-row justify-between sm:items-start">
      <div>
        <h2 class="text-xl font-semibold text-gray-800">{{ title }}</h2>
        <p class="text-sm text-gray-500 mt-1">{{ description }}</p>
        <p class="text-xs text-gray-400 mt-1 font-mono">Command: {{ command }}</p>
      </div>
      <button @click="$emit('dispatch')" class="mt-4 sm:mt-0 sm:ml-4 px-6 py-2 bg-indigo-600 text-white font-semibold rounded-md shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed whitespace-nowrap">
        <slot name="button-text">Dispatch Job</slot>
      </button>
    </div>
    <div v-if="$slots.default" class="mt-6 pt-4 border-t border-gray-200">
      <h3 class="text-md font-medium text-gray-700 mb-3">Parameters</h3>
      <slot></slot>
    </div>
  </div>
</template>

<script setup>
defineProps({
  command: String,
  title: String,
  description: String,
});

defineEmits(['dispatch']);
</script>
