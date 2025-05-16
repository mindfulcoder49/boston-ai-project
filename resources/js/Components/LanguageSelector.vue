<template>
    <div class="flex flex-wrap justify-center gap-2 p-2">
      <button
        v-for="code in Object.keys(languageButtonLabels)"
        :key="code"
        @click="handleLanguageToggle(code)"
        class="px-4 py-2 rounded-md shadow-sm transition-colors duration-150 ease-in-out focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
        :class="{
          'bg-blue-600 text-white hover:bg-blue-700': currentLanguageCodes.includes(code),
          'bg-gray-100 text-gray-700 hover:bg-gray-200 border border-gray-300': !currentLanguageCodes.includes(code),
        }"
      >
        {{ currentLanguageCodes.includes(code) ? languageButtonLabels[code].deselect : languageButtonLabels[code].select }}
      </button>
    </div>
  </template>
  
  <script setup>
  import { defineProps, defineEmits } from 'vue';
  
  const props = defineProps({
    languageButtonLabels: Object,
    currentLanguageCodes: Array,
  });
  
  const emit = defineEmits(['language-code-selected']);
  
  const handleLanguageToggle = (code) => {
    emit('language-code-selected', code);
  };
  </script>