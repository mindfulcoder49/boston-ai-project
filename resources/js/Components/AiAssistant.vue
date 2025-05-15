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
          <button v-for="prompt in suggestedPrompts" :key="prompt.id" 
                  @click="insertPrompt(prompt)" 
                  class="bg-gradient-to-r from-blue-700 to-blue-800 text-white p-2  cursor-pointer">
              {{ prompt.text }}
          </button>
      </div>

      <form @submit.prevent="handleRegularChatSubmit" class="text-lg">
          <textarea
              v-model="form.message"
              placeholder="Type your message..."
              class="w-full p-3  border-none bg-gradient-to-r from-blue-100 to-blue-200 text-gray-800 text-lg"
              rows="2"
          ></textarea>

          <div class="model-selector mb-4">
          <button type="submit" class="send-button cursor-pointer  border border-white bg-gradient-to-r from-gray-200 to-gray-300 text-gray-800 p-4 mt-4 w-full">
              {{ languageButtonLabels[getSingleLanguageCodeFromLocale].sendText }}
          </button>
          
          <label for="model" class="">{{ languageButtonLabels[getSingleLanguageCodeFromLocale].model }}</label>
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
import { reactive, ref, nextTick, watch, computed, defineProps, onMounted } from 'vue';
import { useForm } from '@inertiajs/vue3';
import markdownit from 'markdown-it';
import markdownItLinkAttributes from 'markdown-it-link-attributes';

const props = defineProps({
  context: {
    type: Array,
    default: () => [],
  },
  language_codes: { // For UI translations, e.g., ['en-US']
    type: Array,
    default: () => ['en-US'],
  },
  centralLocation: { // For streaming report API: { latitude, longitude, address }
    type: Object,
    default: () => ({ latitude: null, longitude: null, address: '' })
  },
  radius: { // For streaming report API
    type: Number,
    default: 0.25
  },
  currentMapLanguage: { // For streaming report API: 'en', 'es', 'zh-CN' etc.
    type: String,
    default: 'en'
  }
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
const localContext = ref(props.context); // Store context locally

// Structure for suggested prompts
const baseSuggestedPrompts = [
  { id: 'summarize_stream_report', textKey: 'summarizeAllEventsStream' },
  { id: 'ask_about_context', textKey: 'askAboutContext' },
];

const promptTranslations = {
  'en-US': {
    summarizeAllEventsStream: "Generate Full Report (Stream)",
    askAboutContext: "What can you tell me about these events?",
  },
  'es-MX': {
    summarizeAllEventsStream: "Generar Informe Completo (Stream)",
    askAboutContext: "¿Qué me puedes decir sobre estos eventos?",
  },
  'zh-CN': {
    summarizeAllEventsStream: "生成完整报告 (串流)",
    askAboutContext: "关于这些事件你能告诉我什么？",
  },
  'ht-HT': {
    summarizeAllEventsStream: "Jenere Rapò Konplè (Kouran)",
    askAboutContext: "Kisa ou ka di mwen sou evènman sa yo?",
  },
  'vi-VN': {
    summarizeAllEventsStream: "Tạo Báo cáo Đầy đủ (Luồng)",
    askAboutContext: "Bạn có thể cho tôi biết gì về những sự kiện này?",
  },
  'pt-BR': {
    summarizeAllEventsStream: "Gerar Relatório Completo (Stream)",
    askAboutContext: "O que você pode me dizer sobre esses eventos?",
  },
};

const suggestedPrompts = ref([]);

const languageButtonLabels = {
  'en-US': { sendText: 'Send', model: 'Select AI Model' },
  'es-MX': { sendText: 'Enviar', model: 'Seleccionar modelo de IA' },
  'zh-CN': { sendText: '发送', model: '选择AI模型' },
  'ht-HT': { sendText: 'Voye', model: 'Chwazi modèl AI' },
  'vi-VN': { sendText: 'Gửi', model: 'Chọn mô hình AI' },
  'pt-BR': { sendText: 'Enviar', model: 'Selecione o modelo de IA' },
};

const welcomeMessage = ref("");
const welcomeMessageTranslations = {
  'en-US': "Hi! I'm the Boston App AI Assistant. I can see all the data points in the map and answer questions about them. How can I help you today?",
  'es-MX': "¡Hola! Soy el asistente de IA de la aplicación de Boston. Puedo ver todos los puntos de datos en el mapa y responder preguntas sobre ellos. ¿Cómo puedo ayudarte hoy?",
  'zh-CN': "你好！我是波士顿应用程序的AI助手。我可以查看地图中的所有数据点并用多种语言回答有关它们的问题。我今天能帮你什么？",
  'ht-HT': "Bonjou! Mwen se asistan AI nan aplikasyon Boston an. Mwen ka wè tout pwen done nan kat la ak reponn kesyon sou yo. Kijan mwen ka ede ou jodi a?",
  'vi-VN': "Chào bạn! Tôi là trợ lý trí tuệ nhân tạo của ứng dụng Boston. Tôi có thể xem tất cả các điểm dữ liệu trên bản đồ và trả lời câu hỏi về chúng. Hôm nay tôi có thể giúp gì cho bạn?",
  'pt-BR': "Oi! Eu sou o assistente de IA do aplicativo Boston. Eu posso ver todos os pontos de dados no mapa e responder perguntas sobre eles. Como posso te ajudar hoje?",
};

const getSingleLanguageCodeFromLocale = computed(() => props.language_codes[0] || 'en-US');

const canStreamReport = computed(() => {
  console.log('Central Location:', props.centralLocation);
  return props.centralLocation && 
         props.centralLocation.latitude !== null && 
         props.centralLocation.longitude !== null &&
         //typeof props.centralLocation.latitude === 'number' && // Ensure they are numbers
         //typeof props.centralLocation.longitude === 'number' &&
         props.centralLocation.address && 
         props.centralLocation.address.trim() !== '';
});

const setUiText = () => {
  const langCode = getSingleLanguageCodeFromLocale.value;
  welcomeMessage.value = welcomeMessageTranslations[langCode] || welcomeMessageTranslations['en-US'];
  
  let availableBasePrompts = [...baseSuggestedPrompts];
  if (!canStreamReport.value) {
    // If report cannot be streamed, filter out the prompt for it
    availableBasePrompts = availableBasePrompts.filter(p => p.id !== 'summarize_stream_report');
  }

  suggestedPrompts.value = availableBasePrompts.map(p => ({
    id: p.id,
    text: promptTranslations[langCode]?.[p.textKey] || p.textKey // Fallback to key if no translation
  }));
};

const scrollToBottom = () => {
  nextTick(() => {
    if (chatHistory.value) {
      //chatHistory.value.scrollTop = chatHistory.value.scrollHeight;
    }
  });
};

const insertPrompt = (prompt) => {
  form.message = prompt.text; // Set the textarea for user visibility
  if (prompt.id === 'summarize_stream_report') {
    handleStreamReportRequest(prompt.text);
  } else {
    // For other prompts, or if user types and hits send, use the regular chat
    handleRegularChatSubmit(); 
  }
  // Remove the clicked prompt from suggestions
  suggestedPrompts.value = suggestedPrompts.value.filter((item) => item.id !== prompt.id);
};

const selectedModel = ref('gemini'); // Default model for regular chat

const handleRegularChatSubmit = () => {
  if (form.message.trim() === '') return;
  handleChatResponse(form.message, selectedModel.value, localContext.value);
  form.message = ''; // Clear textarea after submitting
};

const handleChatResponse = async (userMessageText, modelToUse, contextForChat) => {
  messages.value.push({ role: 'user', content: userMessageText });
  loading.value = true;
  scrollToBottom();

  const requestBody = {
    message: userMessageText,
    history: messages.value.slice(0, -1), // Send history *before* current user message
    context: JSON.stringify(contextForChat),
    model: modelToUse,
  };

  try {
    const response = await fetch(route('ai.assistant'), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      body: JSON.stringify(requestBody)
    });

    if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
    }
    if (!response.body) {
        throw new Error('Response body is null');
    }

    const reader = response.body.getReader();
    const decoder = new TextDecoder();
    let assistantMessageContent = '';
    messages.value.push({ role: 'assistant', content: '' }); // Prepare to append

    while (true) {
      const { done, value } = await reader.read();
      if (done) break;
      assistantMessageContent += decoder.decode(value, { stream: true });
      messages.value[messages.value.length - 1].content = assistantMessageContent;
      scrollToBottom();
    }
  } catch (error) {
    console.error('Error fetching AI chat response:', error);
    messages.value.push({ role: 'assistant', content: `Error: Could not get response. ${error.message}` });
  } finally {
    loading.value = false;
    scrollToBottom();
  }
};

const handleStreamReportRequest = async (userPromptText) => {
  // The check for canStreamReport is implicitly handled by not showing the prompt
  // but an explicit check here remains a good safeguard if the method is called otherwise.
  console.log('Can stream Report:', canStreamReport.value);
  if (!canStreamReport.value) {
    messages.value.push({ role: 'assistant', content: 'Cannot generate report: Central location details are missing or invalid.' });
    scrollToBottom();
    return;
  }

  messages.value.push({ role: 'user', content: userPromptText });
  loading.value = true;
  scrollToBottom();
  
  messages.value.push({ role: 'assistant', content: '' }); // Placeholder for streamed report
  let assistantMessageIndex = messages.value.length - 1;
  let accumulatedContent = "";

  try {
    const requestBody = {
      latitude: props.centralLocation.latitude,
      longitude: props.centralLocation.longitude,
      address: props.centralLocation.address,
      radius: props.radius,
      language: props.currentMapLanguage,
    };

    const response = await fetch(route('ai.stream-location-report'), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        'Accept': 'text/event-stream'
      },
      body: JSON.stringify(requestBody)
    });

    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
    if (!response.body) throw new Error('Response body is null');

    const reader = response.body.getReader();
    const decoder = new TextDecoder();

    let buffer = '';
    while (true) {
      const { done, value } = await reader.read();
      if (done) break;
      
      buffer += decoder.decode(value, { stream: true });
      
      let position;
      while ((position = buffer.indexOf('\n\n')) >= 0) {
        const chunk = buffer.slice(0, position);
        buffer = buffer.slice(position + 2);

        if (chunk.startsWith('data: ')) {
          const jsonData = chunk.substring(6);
          try {
            const parsedData = JSON.parse(jsonData);
            if (parsedData.type === 'markdown' && parsedData.content) {
              accumulatedContent += parsedData.content;
              messages.value[assistantMessageIndex].content = accumulatedContent;
            } else if (parsedData.type === 'status' && parsedData.message) {
              accumulatedContent += `\n*Status: ${parsedData.message}*\n`;
              messages.value[assistantMessageIndex].content = accumulatedContent;
            } else if (parsedData.type === 'error' && parsedData.message) {
              accumulatedContent += `\n**Error: ${parsedData.message}**\n`;
              messages.value[assistantMessageIndex].content = accumulatedContent;
            } else if (parsedData.type === 'control' && parsedData.action === 'close') {
              // Stream closed by server
              loading.value = false;
              scrollToBottom();
              return; // Exit loop and function
            }
            scrollToBottom();
          } catch (e) {
            console.error('Error parsing streamed JSON:', e, jsonData);
          }
        }
      }
    }
  } catch (error) {
    console.error('Error fetching streaming report:', error);
    messages.value[assistantMessageIndex].content = accumulatedContent + `\n**Error generating report: ${error.message}**`;
  } finally {
    loading.value = false;
    scrollToBottom();
  }
};

const renderMarkdown = (content) => {
  return md.render(content);
};

watch(() => props.context, (newContext) => {
  localContext.value = newContext;
});

watch(() => props.language_codes, () => {
  setUiText(); // This will now also re-evaluate suggested prompts based on canStreamReport
}, { immediate: true });

watch(() => props.centralLocation, (newValue, oldValue) => {
  // If centralLocation changes, re-evaluate suggested prompts
  if (JSON.stringify(newValue) !== JSON.stringify(oldValue)) {
    setUiText();
  }
}, { deep: true, immediate: true });

onMounted(() => {
  setUiText();
});

</script>

<style scoped>
/* ...existing styles... */
</style>