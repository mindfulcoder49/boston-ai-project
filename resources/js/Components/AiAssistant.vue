<template>
  <div class="ai-assistant border border-gray-700 shadow-lg p-4 bg-gray-400 text-gray-100 relative z-2">
      <!-- Data Context Display -->
      <div class="context-info-bar p-2 mb-3 bg-gray-700 text-md text-gray-300 rounded flex items-center gap-x-4 gap-y-2 flex-wrap">
          <span title="Total items in context" class="flex items-center">
              <svg class="inline h-5 w-5 mr-1" viewBox="0 0 20 20" fill="currentColor">
                 <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
              </svg>
              {{ contextSummary.total }} items
          </span>
          <span title="Date range of items" class="flex items-center">
              <svg class="inline h-5 w-5 mr-1" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
              </svg>
              {{ contextSummary.dateRange }}
          </span>
          <div class="context-types flex gap-x-3 gap-y-1 flex-wrap">
              <span v-for="(count, type) in contextSummary.types" :key="type" :title="`${count} ${type} items`"
                    class="flex items-center p-1 text-md">
                  <span class="context-data-icon"
                        :class="{
                          'context-icon-crime': type === 'Crime',
                          'context-icon-311-case': type === '311 Case',
                          'context-icon-building-permit': type === 'Building Permit',
                          'context-icon-property-violation': type === 'Property Violation',
                          'context-icon-construction-off-hour': type === 'Construction Off Hour',
                          'context-icon-food-inspection': type === 'Food Inspection',
                          'context-icon-unknown': !['Crime', '311 Case', 'Building Permit', 'Property Violation', 'Construction Off Hour', 'Food Establishment Violation'].includes(type)
                        }">
                  </span>
                  {{ type }}: {{ count }}
              </span>
          </div>
      </div>

      <ChatHistory
          ref="chatHistoryComponentRef"
          :messages="messages"
          :loading="loading"
          :detailedLoadingInfo="detailedLoadingInfo"
          :editingMessageId="editingMessageId"
          :editedMessageContent="editedMessageContent"
          :welcomeMessage="welcomeMessage"
          :renderMarkdown="renderMarkdown"
          @update:editedMessageContent="editedMessageContent = $event"
          @save-edit="saveMessageEdit"
          @cancel-edit="cancelMessageEdit"
          @start-edit="startMessageEdit"
          @delete-message="deleteMessage"
          @copy-message="copyMessageContent"
      />

      <!--put detailed LoadingMessage here and not just in chatHistory. -->
      <div v-if="showDetailedLoadingMessage" class="detailed-loading-message text-gray-900 text-center">
          <p class="font-mono text-lg mt-1 animate-pulse">{{ detailedLoadingText }}</p>
          <p class="text-sm">Elapsed: {{ elapsedSeconds }} seconds</p>
      </div>
      <!-- else just show a message about how many data points will be analyzed along with any message-->
      <div v-else class="loading-message text-gray-900 text-center">
          <p class="text-lg mt-1">{{ contextSummary.total }} data points in the map will be analyzed to respond to your query.</p>
          <p class="text-sm">This would take {{ contextSummary.total < 300 ? 'a few seconds' : contextSummary.total < 700 ? 'ten seconds or more' : 'thirty seconds or longer' }}.</p>
      </div>

      <ChatInput
          v-model:modelValueMessage="currentMessageInput"
          v-model:modelValueSelectedModel="selectedModel"
          :canStreamReport="canStreamReport"
          :languageButtonLabels="languageButtonLabels"
          :currentLocale="getSingleLanguageCodeFromLocale"
          :suggestedPrompts="suggestedPrompts"
          @submit-chat="handleRegularChatSubmit"
          @trigger-stream-report="triggerStreamReport"
          @insert-prompt="insertPromptText"
      />
  </div>
</template>

<script setup>
import { reactive, ref, nextTick, watch, computed, defineProps, onMounted } from 'vue';
import markdownit from 'markdown-it';
import markdownItLinkAttributes from 'markdown-it-link-attributes';
import ChatHistory from './ChatHistory.vue';
import ChatInput from './ChatInput.vue';

const props = defineProps({
  context: {
    type: Array,
    default: () => [],
  },
  language_codes: {
    type: Array,
    default: () => ['en-US'],
  },
  centralLocation: {
    type: Object,
    default: () => ({ latitude: null, longitude: null, address: '' })
  },
  radius: {
    type: Number,
    default: 0.25
  },
  currentMapLanguage: {
    type: String,
    default: 'en'
  }
});

const md = markdownit({
  html: true,
  linkify: true,
  typographer: true,
  breaks: true,
});

md.use(markdownItLinkAttributes, {
  attrs: {
    target: "_blank",
    rel: "noopener",
  },
});

const currentMessageInput = ref('');
const messages = ref([]);
const loading = ref(false); // Controls overall loading state for ChatHistory's loading section
const chatHistoryComponentRef = ref(null);
const localContext = ref(props.context);
const editingMessageId = ref(null);
const editedMessageContent = ref('');
const selectedModel = ref('gemini');

// --- NEW Reactive State for Detailed Loading Message ---
const showDetailedLoadingMessage = ref(false); // Controls if the detailed message logic is active
const detailedLoadingText = ref('');           // Stores the "Analyzing X data points..." message string
const elapsedSeconds = ref(0);                 // Stores the running seconds for the timer
const timerIntervalId = ref(null);             // Stores the setInterval ID for the timer
// --- END NEW Reactive State ---

const baseSuggestedPrompts = [
  { id: 'ask_about_context', textKey: 'askAboutContext' },
];
const promptTranslations = {
  'en-US': { askAboutContext: "What can you tell me about these events?" },
  'es-MX': { askAboutContext: "¿Qué me puedes decir sobre estos eventos?" },
  'zh-CN': { askAboutContext: "关于这些事件你能告诉我什么？" },
  'ht-HT': { askAboutContext: "Kisa ou ka di mwen sou evènman sa yo?" },
  'vi-VN': { askAboutContext: "Bạn có thể cho tôi biết gì về những sự kiện này?" },
  'pt-BR': { askAboutContext: "O que você pode me dizer sobre esses eventos?" },
};
const suggestedPrompts = ref([]);

const languageButtonLabels = {
  'en-US': { sendText: 'Send', model: 'Select AI Model', generateReportText: 'Generate Full Report' },
  'es-MX': { sendText: 'Enviar', model: 'Seleccionar modelo de IA', generateReportText: 'Generar Informe Completo' },
  'zh-CN': { sendText: '发送', model: '选择AI模型', generateReportText: '生成完整报告' },
  'ht-HT': { sendText: 'Voye', model: 'Chwazi modèl AI', generateReportText: 'Jenere Rapò Konplè' },
  'vi-VN': { sendText: 'Gửi', model: 'Chọn mô hình AI', generateReportText: 'Tạo Báo cáo Đầy đủ' },
  'pt-BR': { sendText: 'Enviar', model: 'Selecione o modelo de IA', generateReportText: 'Gerar Relatório Completo' },
};

const welcomeMessage = ref("");
const welcomeMessageTranslations = {
  'en-US': "Hi! I'm the BostonScope AI Assistant. I can see all the data points in the map and answer questions about them. How can I help you today?",
  'es-MX': "¡Hola! Soy el asistente de IA de la aplicación de Boston. Puedo ver todos los puntos de datos en el mapa y responder preguntas sobre ellos. ¿Cómo puedo ayudarte hoy?",
  'zh-CN': "你好！我是波士顿应用程序的AI助手。我可以查看地图中的所有数据点并用多种语言回答有关它们的问题。我今天能帮你什么？",
  'ht-HT': "Bonjou! Mwen se asistan AI nan aplikasyon Boston an. Mwen ka wè tout pwen done nan kat la ak reponn kesyon sou yo. Kijan mwen ka ede ou jodi a?",
  'vi-VN': "Chào bạn! Tôi là trợ lý trí tuệ nhân tạo của ứng dụng Boston. Tôi có thể xem tất cả các điểm dữ liệu trên bản đồ và trả lời câu hỏi về chúng. Hôm nay tôi có thể giúp gì cho bạn?",
  'pt-BR': "Oi! Eu sou o assistente de IA do aplicativo Boston. Eu posso ver todos os pontos de dados no mapa e responder perguntas sobre eles. Como posso te ajudar hoje?",
};

// --- NEW Translations for Loading Messages ---
const loadingMessageTranslations = {
  'en-US': {
    analyzing: "I'm analyzing", dataPoint: "data point", dataPoints: "data points", inTheMap: "in the map",
    fewSeconds: "and should have an answer in a few seconds.",
    tenSeconds: "this might take ten seconds or more.",
    thirtySeconds: "please be patient, this could take up to 30 seconds or a bit longer.",
    noData: "I'm ready to help! There are currently no specific data points in the map view for me to analyze with this query, but feel free to ask general questions or add data to the map.",
  },
  'es-MX': {
    analyzing: "Estoy analizando", dataPoint: "punto de dato", dataPoints: "puntos de datos", inTheMap: "en el mapa",
    fewSeconds: "y debería tener una respuesta en unos segundos.",
    tenSeconds: "esto podría tardar diez segundos o más.",
    thirtySeconds: "por favor ten paciencia, esto podría tardar hasta 30 segundos o un poco más.",
    noData: "¡Estoy listo para ayudar! Actualmente no hay puntos de datos específicos en la vista del mapa para analizar con esta consulta, pero no dudes en hacer preguntas generales o agregar datos al mapa.",
  },
  'zh-CN': {
    analyzing: "我正在分析", dataPoint: "个数据点", dataPoints: "个数据点", inTheMap: "在地图上",
    fewSeconds: "并应在几秒钟内给出答案。",
    tenSeconds: "这可能需要十秒或更长时间。",
    thirtySeconds: "请耐心等待，这可能需要长达30秒或更长时间。",
    noData: "我准备好帮助您了！当前地图视图中没有可供我通过此查询分析的特定数据点，但欢迎您提出一般性问题或向地图添加数据。",
  },
  'ht-HT': {
    analyzing: "M ap analize", dataPoint: "pwen done", dataPoints: "pwen done", inTheMap: "nan kat la",
    fewSeconds: "epi mwen ta dwe gen yon repons nan kèk segond.",
    tenSeconds: "sa ka pran dis segond oswa plis.",
    thirtySeconds: "tanpri pran pasyans, sa ka pran jiska 30 segond oswa yon ti kras plis.",
    noData: "Mwen pare pou ede! Kounye a pa gen pwen done espesifik nan vi kat la pou m analize avèk demann sa a, men ou lib pou poze kesyon jeneral oswa ajoute done sou kat la.",
  },
  'vi-VN': {
    analyzing: "Tôi đang phân tích", dataPoint: "điểm dữ liệu", dataPoints: "điểm dữ liệu", inTheMap: "trên bản đồ",
    fewSeconds: "và sẽ có câu trả lời trong vài giây.",
    tenSeconds: "việc này có thể mất mười giây hoặc hơn.",
    thirtySeconds: "vui lòng kiên nhẫn, việc này có thể mất đến 30 giây hoặc lâu hơn một chút.",
    noData: "Tôi sẵn sàng giúp đỡ! Hiện tại không có điểm dữ liệu cụ thể nào trong chế độ xem bản đồ để tôi phân tích với truy vấn này, nhưng bạn cứ tự nhiên đặt câu hỏi chung hoặc thêm dữ liệu vào bản đồ.",
  },
  'pt-BR': {
    analyzing: "Estou analisando", dataPoint: "ponto de dado", dataPoints: "pontos de dados", inTheMap: "no mapa",
    fewSeconds: "e devo ter uma resposta em alguns segundos.",
    tenSeconds: "isso pode levar dez segundos ou mais.",
    thirtySeconds: "por favor, seja paciente, isso pode levar até 30 segundos ou um pouco mais.",
    noData: "Estou pronto para ajudar! Atualmente não há pontos de dados específicos na visualização do mapa para eu analisar com esta consulta, mas sinta-se à vontade para fazer perguntas gerais ou adicionar dados ao mapa.",
  },
};
// --- END NEW Translations ---

const getSingleLanguageCodeFromLocale = computed(() => props.language_codes[0] || 'en-US');

// --- NEW Computed property for ChatHistory ---
const detailedLoadingInfo = computed(() => {
  if (showDetailedLoadingMessage.value) {
    return {
      text: detailedLoadingText.value,
      seconds: elapsedSeconds.value,
    };
  }
  return null; // If not active, ChatHistory will show its default loading (e.g., "...") or nothing if loading is false
});
// --- END NEW Computed property ---

const contextSummary = computed(() => {
  if (!localContext.value || localContext.value.length === 0) {
    return { total: 0, types: {}, dateRange: 'N/A' };
  }
  const types = localContext.value.reduce((acc, item) => {
    const type = item.alcivartech_type || 'Unknown';
    acc[type] = (acc[type] || 0) + 1;
    return acc;
  }, {});
  const dates = localContext.value
                  .map(item => item.alcivartech_date ? new Date(item.alcivartech_date) : null)
                  .filter(date => date && !isNaN(date.getTime()));
  let dateRange = 'N/A';
  if (dates.length > 0) {
    const minDate = new Date(Math.min.apply(null, dates));
    const maxDate = new Date(Math.max.apply(null, dates));
    if (!isNaN(minDate.getTime()) && !isNaN(maxDate.getTime())) {
        dateRange = `${minDate.toLocaleDateString()} - ${maxDate.toLocaleDateString()}`;
    } else {
        dateRange = 'Invalid date range';
    }
  }
  return { total: localContext.value.length, types, dateRange };
});

const canStreamReport = computed(() => {
  return props.centralLocation && 
         props.centralLocation.latitude !== null && 
         props.centralLocation.longitude !== null &&
         props.centralLocation.address && 
         props.centralLocation.address.trim() !== '';
});

const setUiText = () => {
  const langCode = getSingleLanguageCodeFromLocale.value;
  welcomeMessage.value = welcomeMessageTranslations[langCode] || welcomeMessageTranslations['en-US'];
  let availableBasePrompts = [...baseSuggestedPrompts];
  suggestedPrompts.value = availableBasePrompts.map(p => ({
    id: p.id,
    text: promptTranslations[langCode]?.[p.textKey] || 
          (promptTranslations['en-US']?.[p.textKey] || p.textKey)
  }));
};

const scrollToBottomInChild = () => {
  nextTick(() => {
    if (chatHistoryComponentRef.value) {
      chatHistoryComponentRef.value.scrollToBottom();
    }
  });
};

// --- NEW Helper Functions for Detailed Loading Message & Timer ---
const getDynamicLoadingTextForChat = (dataCount) => {
    const langCode = getSingleLanguageCodeFromLocale.value;
    const translations = loadingMessageTranslations[langCode] || loadingMessageTranslations['en-US'];
    const fallbackTranslations = loadingMessageTranslations['en-US']; // Ensure fallback

    if (dataCount === 0) {
        return translations.noData || fallbackTranslations.noData;
    }

    let base;
    const analyzingText = translations.analyzing || fallbackTranslations.analyzing;
    const dataPointText = translations.dataPoint || fallbackTranslations.dataPoint;
    const dataPointsText = translations.dataPoints || fallbackTranslations.dataPoints;
    const inTheMapText = translations.inTheMap || fallbackTranslations.inTheMap;

    // Handling for Chinese number placement (Number before '个数据点')
    if (langCode === 'zh-CN') {
        base = `${analyzingText} ${dataCount}${dataPointsText}`; // e.g., 我正在分析 143个数据点
    } else {
        base = `${analyzingText} ${dataCount} ${dataCount === 1 ? dataPointText : dataPointsText}`;
    }
    
    base += ` ${inTheMapText}`;

    let estimate = "";
    if (dataCount > 0 && dataCount < 300) {
        estimate = translations.fewSeconds || fallbackTranslations.fewSeconds;
    } else if (dataCount >= 300 && dataCount <= 700) {
        estimate = translations.tenSeconds || fallbackTranslations.tenSeconds;
    } else if (dataCount > 700) {
        estimate = translations.thirtySeconds || fallbackTranslations.thirtySeconds;
    }
    return `${base}, ${estimate}`;
};

const startLoadingTimer = () => {
    if (timerIntervalId.value) clearInterval(timerIntervalId.value); // Clear existing timer
    elapsedSeconds.value = 0; // Reset counter each time
    timerIntervalId.value = setInterval(() => {
        elapsedSeconds.value++;
    }, 1000);
};

const stopLoadingTimer = () => {
    if (timerIntervalId.value) {
        clearInterval(timerIntervalId.value);
        timerIntervalId.value = null;
    }
};

// Setup detailed loading state
const setupDetailedLoading = (dataCount) => {
    detailedLoadingText.value = getDynamicLoadingTextForChat(dataCount);
    showDetailedLoadingMessage.value = true; // Activates the detailedLoadingInfo computed property
    loading.value = true; // This prop is used by ChatHistory to show its loading section
    startLoadingTimer();
    scrollToBottomInChild(); // Scroll to make loading message visible
};

// Clear detailed loading state
const clearDetailedLoading = () => {
    stopLoadingTimer();
    showDetailedLoadingMessage.value = false;
    loading.value = false; 
    // detailedLoadingText.value = ''; // Not strictly necessary as it's hidden by showDetailedLoadingMessage
    // elapsedSeconds.value = 0; // Reset by startLoadingTimer next time
};
// --- END NEW Helper Functions ---

const insertPromptText = (promptText) => {
  currentMessageInput.value = promptText;
  handleRegularChatSubmit(promptText, selectedModel.value);
};

const handleRegularChatSubmit = (messageText, model) => {
  if (messageText.trim() === '') return;
  const userMessageText = messageText;
  currentMessageInput.value = ''; 
  handleChatResponse(userMessageText, model, localContext.value);
};

const generateUniqueId = () => `msg_${Date.now()}_${Math.random().toString(36).substring(2, 9)}`;

const handleChatResponse = async (userMessageText, modelToUse, contextForChat) => {
  messages.value.push({ id: generateUniqueId(), role: 'user', content: userMessageText });
  // scrollToBottomInChild(); // Moved to setupDetailedLoading

  const dataCount = contextForChat.length;
  setupDetailedLoading(dataCount); // Sets loading.value = true, shows detailed message, starts timer

  const requestBody = {
    message: userMessageText,
    history: messages.value.slice(0, -1), // Exclude current user message for history
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

    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
    if (!response.body) throw new Error('Response body is null');

    const reader = response.body.getReader();
    const decoder = new TextDecoder();
    let assistantMessageContent = '';
    const assistantMessageId = generateUniqueId();
    messages.value.push({ id: assistantMessageId, role: 'assistant', content: '' }); // Add shell for AI response

    while (true) {
      const { done, value } = await reader.read();
      if (done) break;
      assistantMessageContent += decoder.decode(value, { stream: true });
      const assistantMsgIndex = messages.value.findIndex(m => m.id === assistantMessageId);
      if (assistantMsgIndex !== -1) {
        messages.value[assistantMsgIndex].content = assistantMessageContent;
      }
      scrollToBottomInChild(); // Scroll as content streams
    }
  } catch (error) {
    console.error('Error fetching AI chat response:', error);
    const errorId = generateUniqueId();
    // If there was a placeholder for assistant message, update it, else push new.
    const lastMessage = messages.value[messages.value.length -1];
    if(lastMessage && lastMessage.role === 'assistant' && lastMessage.content === '') {
        lastMessage.content = `Error: Could not get response. ${error.message}`;
    } else {
        messages.value.push({ id: errorId, role: 'assistant', content: `Error: Could not get response. ${error.message}` });
    }
  } finally {
    clearDetailedLoading(); // Sets loading.value = false, stops timer, hides detailed message
    scrollToBottomInChild(); // Scroll after response or error
  }
};

const triggerStreamReport = () => {
  if (!canStreamReport.value) return;
  const langCode = getSingleLanguageCodeFromLocale.value;
  const reportPromptText = languageButtonLabels[langCode]?.generateReportText || 
                           languageButtonLabels['en-US'].generateReportText;
  handleStreamReportRequest(reportPromptText);
};

const handleStreamReportRequest = async (userPromptText) => {
  if (!canStreamReport.value) {
    messages.value.push({ id: generateUniqueId(), role: 'assistant', content: 'Cannot generate report: Central location details are missing or invalid.' });
    scrollToBottomInChild();
    return;
  }

  messages.value.push({ id: generateUniqueId(), role: 'user', content: userPromptText });
  
  const dataCount = localContext.value.length; // Context data points are still relevant for user expectation
  setupDetailedLoading(dataCount);

  const assistantMessageId = generateUniqueId();
  messages.value.push({ id: assistantMessageId, role: 'assistant', content: '' }); // Shell for streaming report
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
            const assistantMsgIndex = messages.value.findIndex(m => m.id === assistantMessageId);
            if (assistantMsgIndex === -1) continue;

            if (parsedData.type === 'markdown' && parsedData.content) {
              accumulatedContent += parsedData.content;
              messages.value[assistantMsgIndex].content = accumulatedContent;
            } else if (parsedData.type === 'status' && parsedData.message) {
              accumulatedContent += `\n*Status: ${parsedData.message}*\n`;
              messages.value[assistantMsgIndex].content = accumulatedContent;
            } else if (parsedData.type === 'error' && parsedData.message) {
              accumulatedContent += `\n**Error: ${parsedData.message}**\n`;
              messages.value[assistantMsgIndex].content = accumulatedContent;
            } else if (parsedData.type === 'control' && parsedData.action === 'close') {
              // clearDetailedLoading(); // This will be handled in finally
              // scrollToBottomInChild();
              return; // Exit the loop and function, finally will run
            }
            scrollToBottomInChild();
          } catch (e) {
            console.error('Error parsing streamed JSON:', e, jsonData);
          }
        }
      }
    }
  } catch (error) {
    console.error('Error fetching streaming report:', error);
    const assistantMsgIndex = messages.value.findIndex(m => m.id === assistantMessageId);
    if (assistantMsgIndex !== -1) {
        messages.value[assistantMsgIndex].content = accumulatedContent + `\n**Error generating report: ${error.message}**`;
    } else {
        // This case should ideally not happen if shell is always pushed
        messages.value.push({ id: generateUniqueId(), role: 'assistant', content: accumulatedContent + `\n**Error generating report: ${error.message}**` });
    }
  } finally {
    clearDetailedLoading();
    scrollToBottomInChild();
  }
};

const renderMarkdown = (content) => md.render(content);

const deleteMessage = (messageId) => {
  if (window.confirm("Are you sure you want to delete this message?")) {
    messages.value = messages.value.filter(msg => msg.id !== messageId);
  }
};

const copyMessageContent = async (content) => {
  try {
    await navigator.clipboard.writeText(content);
    alert('Content copied to clipboard!'); 
  } catch (err) {
    console.error('Failed to copy: ', err);
    alert('Failed to copy content.');
  }
};

const startMessageEdit = (message) => {
  editingMessageId.value = message.id;
  editedMessageContent.value = message.content;
};

const saveMessageEdit = (messageId) => {
  const messageIndex = messages.value.findIndex(msg => msg.id === messageId);
  if (messageIndex !== -1) {
    messages.value[messageIndex].content = editedMessageContent.value;
  }
  cancelMessageEdit();
};

const cancelMessageEdit = () => {
  editingMessageId.value = null;
  editedMessageContent.value = '';
};

watch(() => props.context, (newContext) => { localContext.value = newContext; });
watch(() => props.language_codes, () => { setUiText(); }, { immediate: true });
watch(() => props.centralLocation, (newValue, oldValue) => {
  if (JSON.stringify(newValue) !== JSON.stringify(oldValue)) { setUiText(); }
}, { deep: true, immediate: true });

onMounted(() => { setUiText(); });

</script>

<style scoped>
.context-data-icon {
  display: inline-block;
  width: 16px;
  height: 16px;
  background-size: contain;
  background-repeat: no-repeat;
  background-position: center;
  margin-right: 5px;
  vertical-align: middle;
  filter: invert(0.9) saturate(0.5) brightness(1.5);
}
.context-icon-crime { background-image: url("/images/crimeshieldicon.svg"); }
.context-icon-311-case { background-image: url("/images/boston311icon.svg"); }
.context-icon-building-permit { background-image: url("/images/permiticon.svg"); }
.context-icon-property-violation { background-image: url("/images/propertyviolationicon.svg"); }
.context-icon-construction-off-hour { background-image: url("/images/constructionoffhouricon.svg"); }
.context-icon-food-inspection { background-image: url("/images/foodinspectionicon.svg"); }
.context-icon-unknown { border: 1px solid currentColor; border-radius: 3px; }
.ai-assistant { display: flex; flex-direction: column; height: 100%; }
</style>