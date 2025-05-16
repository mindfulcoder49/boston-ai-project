<template>
  <div>
    <div class="suggested-prompts flex flex-row items-baseline gap-2 mb-4 justify-end flex-wrap"> 
        <p class="text-sm text-gray-900 mr-2"> 
          {{ languageButtonLabels[currentLocale]?.suggestedPromptsText || 'Suggested Prompts:' }}
        </p>
      <button v-for="prompt in suggestedPrompts" :key="prompt.id"
              @click="$emit('insert-prompt', prompt.text)"
              class="bg-sky-600 hover:bg-sky-700 text-white p-2 rounded-md cursor-pointer text-sm">
        {{ prompt.text }}
      </button>
    </div>

    <form @submit.prevent="handleSubmit" class="text-base clear-both">
      <textarea
        :value="modelValueMessage"
        @input="$emit('update:modelValueMessage', $event.target.value)"
        placeholder="Type your message..."
        class="w-full p-3 border border-gray-600 bg-gray-700 text-white rounded-md text-base focus:ring-sky-500 focus:border-sky-500"
        rows="2"
      ></textarea>

      <div class="flex items-center justify-between mt-3">
        
        <div class="model-selector">
            <!--
          <label for="model" class="text-sm text-gray-300">{{ languageButtonLabels[currentLocale]?.model || 'Select AI Model' }}</label>
          <select 
            id="model" 
            :value="modelValueSelectedModel"
            @change="$emit('update:modelValueSelectedModel', $event.target.value)"
            class="ml-2 p-2 bg-gray-700 text-white border border-gray-600 rounded-md text-sm focus:ring-sky-500 focus:border-sky-500"
          >
            <option value="gemini">Gemini</option>
            <option value="chatgpt">ChatGPT</option>
          </select>
        -->
        </div>
        <div class="flex gap-2">
           <button
              type="button"
              @click="$emit('trigger-stream-report')"
              :disabled="!canStreamReport"
              class="persistent-report-button bg-teal-600 hover:bg-teal-700 text-white p-3 rounded-md cursor-pointer text-sm disabled:opacity-50 disabled:cursor-not-allowed">
              {{ languageButtonLabels[currentLocale]?.generateReportText || 'Generate Full Report' }}
          </button>
          <button type="submit" class="send-button cursor-pointer bg-sky-600 hover:bg-sky-700 text-white p-3 rounded-md text-sm w-auto">
              {{ languageButtonLabels[currentLocale]?.sendText || 'Send' }}
          </button>
        </div>
      </div>
    </form>
  </div>
</template>

<script setup>
import { defineProps, defineEmits } from 'vue';

const props = defineProps({
  modelValueMessage: String,
  modelValueSelectedModel: String,
  canStreamReport: Boolean,
  languageButtonLabels: Object,
  currentLocale: String,
  suggestedPrompts: Array,
});

const emit = defineEmits([
  'update:modelValueMessage',
  'update:modelValueSelectedModel',
  'submit-chat',
  'trigger-stream-report',
  'insert-prompt',
]);

const handleSubmit = () => {
  if (props.modelValueMessage.trim() === '') return;
  emit('submit-chat', props.modelValueMessage, props.modelValueSelectedModel);
};
</script>

<style scoped>
/* Styles specific to ChatInput can be added here if any */
/* For now, most relevant styles are global or handled by Tailwind classes */
</style>
