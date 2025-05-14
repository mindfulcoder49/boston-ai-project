<template>
    <div class="flex flex-wrap justify-center">
      <button
        v-for="code in Object.keys(languageButtonLabels)"
        :key="code"
        @click="handleLanguageToggle(code)"
        class="px-4 py-2 border-white border"
        :class="{
          'bg-blue-500 text-white': currentLanguageCodes.includes(code),
          'bg-gray-200 hover:bg-gray-300': !currentLanguageCodes.includes(code),
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