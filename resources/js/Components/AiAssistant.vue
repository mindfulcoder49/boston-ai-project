<template>
  <div class="ai-assistant border border-gray-700  shadow-lg p-4 bg-gray-900/25 relative z-2">
      <div ref="chatHistory" class="p-2 bg-transparent chat-history max-h-[69vh]  overflow-y-auto mb-4 scrollbar-thin scrollbar-thumb-gray-500 scrollbar-track-gray-800">
          <div class="assistant-message text-gray-800 bg-gradient-to-r from-gray-200 to-gray-300 p-4 mr-1  inline-block max-w-[95%] float-left mb-2 text-left">
              <p>{{ welcomeMessage }}</p>
          </div>
          <div v-for="(message, index) in messages" :key="index" class="message-item mb-2 clear-both">
              <p v-if="message.role === 'user'" class="user-message text-gray-800 bg-gradient-to-r from-blue-100 to-blue-200 p-4 ml-2  inline-block max-w-[95%] float-right mb-2 text-right">
                  {{ message.content }}
              </p>
              <div v-if="message.role === 'assistant'" class="assistant-message text-gray-800 bg-gradient-to-r from-gray-200 to-gray-300 p-4 mr-1  inline-block max-w-[95%] float-left mb-2 text-left">
                  <div v-html="renderMarkdown(message.content)"></div>
              </div>
          </div>
          <div v-if="loading" class="loading-indicator text-gray-800 mt-4 italic">
              <p>...</p>
          </div>
      </div>

      <div class="suggested-prompts flex flex-row gap-2 mb-4 float-right flex-wrap">
          <button v-for="(prompt, index) in suggestedPrompts" :key="index" 
                  @click="insertPrompt(prompt)" 
                  class="bg-gradient-to-r from-blue-700 to-blue-800 text-white p-2  cursor-pointer">
              {{ prompt }}
          </button>
      </div>

      <form @submit.prevent="handleResponse" class="text-lg">
          <textarea
              v-model="form.message"
              placeholder="Type your message..."
              class="w-full p-3  border-none bg-gradient-to-r from-blue-100 to-blue-200 text-gray-800 text-lg"
              rows="2"
          ></textarea>

          <div class="model-selector mb-4">
          <button type="submit" class="send-button cursor-pointer  border border-white bg-gradient-to-r from-gray-200 to-gray-300 text-gray-800 p-4 mt-4 w-full">
              {{ languageButtonLabels[getSingleLanguageCode].sendText }}
          </button>
          
          <label for="model" class="">{{ languageButtonLabels[getSingleLanguageCode].model }}</label>
          <select id="model" v-model="selectedModel" class="ml-2 p-2 bg-gray-700 text-white">
              <option value="gemini">Gemini</option>
              <option value="chatgpt">ChatGPT</option>
          </select>
      </div>
      </form>
  </div>
</template>

<style scoped>
.scrollbar-thin {
scrollbar-width: thin;
}
.scrollbar-thumb-gray-500 {
scrollbar-color: #6b7280 #1f2937;
}
</style>

<script setup>
import { reactive, ref, nextTick, watch, computed, defineProps } from 'vue';
import { useForm } from '@inertiajs/vue3';
import markdownit from 'markdown-it';
import markdownItLinkAttributes from 'markdown-it-link-attributes';

const props = defineProps({
context: {
  type: Array,
  default: () => [],
},
language_codes: {
  type: Array,
  default: () => ['en-US'],
},
});

const md = markdownit({
html: true,
linkify: true,
typographer: true
});

md.use(markdownItLinkAttributes, {
attrs: {
  target: "_blank",
  rel: "noopener",
},
});

const form = reactive(useForm({
message: '',
errors: {}
}));

const messages = ref([]);
const loading = ref(false);
const chatHistory = ref(null);
const context = ref(props.context); // Store context

const suggestedPrompts = ref([
  "Summarize all the events on this report for me",
  "Write a daily report of all the events",
]);

const languageButtonLabels = {
  'en-US': {
    sendText: 'Send',
    model: 'Select AI Model',
  },
  'es-MX': {
    sendText: 'Enviar',
    model: 'Seleccionar modelo de IA',
  },
  'zh-CN': {
    sendText: '发送',
    model: '选择AI模型',
  },
  'ht-HT': {
    sendText: 'Voye',
    model: 'Chwazi modèl AI',
  },
  'vi-VN': {
    sendText: 'Gửi',
    model: 'Chọn mô hình AI',
  },
  'pt-BR': {
    sendText: 'Enviar',
    model: 'Selecione o modelo de IA',
  },
};

const setSuggestedPrompts = () => {
  // get the language codes from the props
  const languageCodes = props.language_codes;
  //could be one of six languages. Define translations of the two suggested prompts for each lanaguge code
  const translations = {
    'en-US': [
      "Summarize all the events on this report for me",
      "Write a daily story of all the events",
    ],
    'es-MX': [
      "Resuma todos los eventos de este informe para mí",
      "Escribe una historia diaria de todos los eventos",
    ],
    'zh-CN': [
      "为我总结此报告中的所有事件",
      "写一篇关于所有事件的日常故事",
    ],
    'ht-HT': [
      "Resime tout evènman nan rapò sa a pou mwen",
      "Ekri yon istwa chak jou sou tout evènman yo",
    ],
    'vi-VN': [
      "Tóm tắt tất cả các sự kiện trong báo cáo này cho tôi",
      "Viết một câu chuyện hàng ngày về tất cả các sự kiện",
    ],
    'pt-BR': [
      "Resuma todos os eventos deste relatório para mim",
      "Escreva uma história diária de todos os eventos",
    ],
  };

  // get the translations for the current language code
  const currentTranslations = translations[languageCodes[0]];

  // set the suggested prompts to the translations
  suggestedPrompts.value = currentTranslations;
};

const welcomeMessage = ref("Hi! I'm the Boston App AI Assistant, based on Gemini's model. I can see all the data points in the map and answer questions about them in many languages. How can I help you today?")

const welcomeMessageTranslations = {
  'en-US': "Hi! I'm the Boston App AI Assistant, based on Gemini's model. I can see all the data points in the map and answer questions about them in many languages. How can I help you today?",
  'es-MX': "¡Hola! Soy el asistente de IA de la aplicación de Boston, basado en el modelo de Gemini. Puedo ver todos los puntos de datos en el mapa y responder preguntas sobre ellos en muchos idiomas. ¿Cómo puedo ayudarte hoy?",
  'zh-CN': "你好！我是波士顿应用程序的AI助手，基于Gemini的模型。我可以查看地图中的所有数据点并用多种语言回答有关它们的问题。我今天能帮你什么？",
  'ht-HT': "Bonjou! Mwen se asistan AI nan aplikasyon Boston an, ki baze sou modèl Gemini. Mwen ka wè tout pwen done nan kat la ak reponn kesyon sou yo nan anpil lang. Kijan mwen ka ede ou jodi a?",
  'vi-VN': "Chào bạn! Tôi là trợ lý trí tuệ nhân tạo của ứng dụng Boston, dựa trên mô hình Gemini. Tôi có thể xem tất cả các điểm dữ liệu trên bản đồ và trả lời câu hỏi về chúng bằng nhiều ngôn ngữ. Hôm nay tôi có thể giúp gì cho bạn?",
  'pt-BR': "Oi! Eu sou o assistente de IA do aplicativo Boston, baseado no modelo Gemini. Eu posso ver todos os pontos de dados no mapa e responder perguntas sobre eles em muitos idiomas. Como posso te ajudar hoje?",
}

const setWelcomeMessage = () => {
  // get the language codes from the props
  const languageCodes = props.language_codes;
  // get the welcome message translations
  const translations = welcomeMessageTranslations;
  // get the translations for the current language code
  const currentTranslation = translations[languageCodes[0]];
  // set the welcome message to the current translation
  welcomeMessage.value = currentTranslation;
};

const getSingleLanguageCode = computed(() => props.language_codes[0]);

const scrollToBottom = () => {
nextTick(() => {
  if (chatHistory.value) {
    chatHistory.value.scrollTop = chatHistory.value.scrollHeight;
  }
});
};

// Insert prompt into the textarea
const insertPrompt = (prompt) => {
form.message = prompt;
handleResponse();
suggestedPrompts.value = suggestedPrompts.value.filter((item) => item !== prompt);
};

const selectedModel = ref('gemini'); // Default model

const handleResponse = async () => {
if (form.message.trim() === '') return;

messages.value.push({ role: 'user', content: form.message });
loading.value = true;

const userMessage = form.message;
form.message = '';

scrollToBottom(); // Scroll after user message is added

const requestBody = {
  message: userMessage,
  history: messages.value,
  context: JSON.stringify(context.value),
  model: selectedModel.value, // Use selected model
};

const response = await fetch(route('ai.assistant'), {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
  },
  body: JSON.stringify(requestBody)
});

const reader = response.body.getReader();
const decoder = new TextDecoder();
let assistantMessage = '';
let chunk;

messages.value.push({ role: 'assistant', content: '' }); // Prepare to append the assistant message

while (!(chunk = await reader.read()).done) {
  assistantMessage += decoder.decode(chunk.value, { stream: true });

  const assistantMessageIndex = messages.value.findLastIndex((message) => message.role === 'assistant');
  messages.value[assistantMessageIndex].content = assistantMessage;

  scrollToBottom();
}

loading.value = false;
};

const renderMarkdown = (content) => {
return md.render(content);
};

//watch for changes in the context and update the context
watch(() => props.context, (newContext) => {
  context.value = newContext;
});

watch(() => props.language_codes, (newLanguageCodes) => {
  setSuggestedPrompts();
  setWelcomeMessage();
});
</script>

<style scoped>

@media screen and (min-width: 768px) {

  .ai-assistant {
    width: 50%;
  }
}

</style>