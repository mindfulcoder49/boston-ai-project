<template>
  <div ref="chatHistoryContainerRef" class="p-2 bg-gray-600 chat-history max-h-[60vh] overflow-y-auto mb-4 scrollbar-thin scrollbar-thumb-gray-500 scrollbar-track-gray-700">
    <!--FLoating scroll the the bottom button on the right, transparent, round with a downward arrow-->
    <div class="absolute top-4 right-6 z-10">
      <button @click="scrollToBottom" class="bg-gray-700 text-white rounded-full p-2 shadow hover:bg-gray-800 focus:outline-none">
        ⬇️
      </button>
    </div>
    <div class="relative assistant-message bg-gray-700 text-gray-100 p-3 rounded-lg shadow mr-10 inline-block max-w-[95%] float-left mb-2 text-left">
      <p class="text-gray-100">&nbsp;&nbsp;&nbsp;&nbsp;{{ welcomeMessage }}</p>
      <!-- Three dots for welcome message -->
      <div class="absolute top-1 left-1 sm:top-2 sm:left-2">
        <button @click="toggleWelcomeMessageActions" title="Actions" class="action-button">⋮</button>
        <div v-if="welcomeMessageActionsVisible" class="absolute left-0 top-full mt-1 flex flex-col gap-1 bg-gray-800 p-1 rounded shadow-lg z-20 min-w-max">
          <button @click="handleCopyWelcomeMessage" class="action-button-menu-item">📋 Copy</button>
        </div>
      </div>
    </div>
    <div v-for="message in messages" :key="message.id" class="message-item mb-2 clear-both relative">
      <p v-if="message.role === 'user' && editingMessageId !== message.id" class="user-message bg-sky-600 text-white p-3 rounded-lg shadow ml-10 inline-block max-w-[95%] float-right mb-2 text-right pr-10">
        {{ message.content }}
      </p>
      <div v-if="message.role === 'assistant' && editingMessageId !== message.id" class="assistant-message bg-gray-700 text-gray-100 p-3 rounded-lg shadow mr-10 inline-block max-w-[95%] float-left mb-2 text-left">
        <div v-html="renderMarkdown(message.content)"></div>
      </div>

      <!-- Editing UI -->
      <div v-if="editingMessageId === message.id" class="message-edit-area my-2 p-2 bg-gray-700 rounded">
        <textarea
          :value="editedMessageContent"
          @input="$emit('update:editedMessageContent', $event.target.value)"
          class="w-full p-2 border border-gray-500 bg-gray-600 text-white rounded text-sm"
          rows="3"
        ></textarea>
        <div class="edit-actions mt-2 text-right">
          <button @click="$emit('save-edit', message.id)" class="text-xs bg-green-500 hover:bg-green-600 text-white py-1 px-3 rounded mr-2">Save</button>
          <button @click="$emit('cancel-edit')" class="text-xs bg-gray-500 hover:bg-gray-600 text-white py-1 px-3 rounded">Cancel</button>
        </div>
      </div>

      <!-- Container for "three dots" button and action menu -->
      <div v-if="editingMessageId !== message.id"
           class="absolute"
           :class="message.role === 'user' ? 'top-1 right-1 sm:top-2 sm:right-2' : 'top-1 left-1 sm:top-2 sm:left-2'">
        <button @click="toggleMessageActions(message.id)" title="Actions" class="action-button">
          ⋮
        </button>
        <!-- Action Buttons Menu -->
        <div v-if="activeMessageMenuId === message.id"
             class="absolute mt-1 flex flex-col gap-1 bg-gray-800 p-1 rounded shadow-lg z-20 min-w-max"
             :class="message.role === 'user' ? 'right-0 top-full' : 'left-0 top-full'">
          <button @click="handleEdit(message)" class="action-button-menu-item">✏️ Edit</button>
          <button @click="handleCopy(message.content, message.role === 'assistant')" class="action-button-menu-item">📋 Copy</button>
          <button @click="handleDelete(message.id)" class="action-button-menu-item">🗑️ Delete</button>
        </div>
      </div>
    </div>
    <div v-if="loading" class="loading-indicator text-gray-400 mt-4 italic">
      <p>...</p>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, nextTick, defineProps, defineEmits, defineExpose } from 'vue';

const props = defineProps({
  messages: Array,
  loading: Boolean,
  editingMessageId: String,
  editedMessageContent: String,
  welcomeMessage: String,
  renderMarkdown: Function,
});

const emit = defineEmits([
  'update:editedMessageContent',
  'save-edit',
  'cancel-edit',
  'start-edit',
  'delete-message',
  'copy-message',
]);

const chatHistoryContainerRef = ref(null);
const activeMessageMenuId = ref(null);
const welcomeMessageActionsVisible = ref(false);

const scrollToBottom = () => {
  if (chatHistoryContainerRef.value) {
    chatHistoryContainerRef.value.scrollTop = chatHistoryContainerRef.value.scrollHeight;
  }
};

const toggleMessageActions = (messageId) => {
  if (activeMessageMenuId.value === messageId) {
    activeMessageMenuId.value = null;
  } else {
    activeMessageMenuId.value = messageId;
    // Close welcome message menu if open
    welcomeMessageActionsVisible.value = false;
  }
};

const toggleWelcomeMessageActions = () => {
  welcomeMessageActionsVisible.value = !welcomeMessageActionsVisible.value;
  // Close dynamic message menu if open
  if (welcomeMessageActionsVisible.value) {
    activeMessageMenuId.value = null;
  }
};

const handleEdit = (message) => {
  emit('start-edit', message);
  activeMessageMenuId.value = null;
};

const handleCopy = (content, isAssistant) => {
  emit('copy-message', content, isAssistant);
  activeMessageMenuId.value = null;
};

const handleDelete = (messageId) => {
  emit('delete-message', messageId);
  activeMessageMenuId.value = null;
};

const handleCopyWelcomeMessage = () => {
  emit('copy-message', props.welcomeMessage, true); // true because welcome message is from assistant
  welcomeMessageActionsVisible.value = false;
};

/* auto scrolling is annoying all the time, so we will only scroll when the user clicks the button
watch(() => props.messages, () => {
  nextTick(() => scrollToBottom());
}, { deep: true });

watch(() => props.loading, (newValue, oldValue) => {
  if (newValue === false && oldValue === true) { // When loading finishes
    nextTick(() => scrollToBottom());
  }
});
*/

defineExpose({ scrollToBottom });
</script>

<style scoped>
.scrollbar-thin {
  scrollbar-width: thin;
}
.scrollbar-thumb-gray-500 {
  scrollbar-color: #6b7280 #374151; /* thumb / track (gray-500 / gray-700) */
}

.action-button {
  background-color: rgba(107, 114, 128, 0.3); /* gray-500 with less opacity */
  color: white;
  padding: 2px 6px; /* Slightly smaller */
  border-radius: 4px;
  font-size: 0.9rem; /* Adjusted for better visibility of dots */
  cursor: pointer;
  border: none;
  line-height: 1;
  opacity: 0.7;
}
.action-button:hover {
  background-color: rgba(75, 85, 99, 0.5); /* gray-600 with opacity */
  opacity: 1;
}

.message-item:hover .action-button,
.assistant-message:hover .action-button {
    opacity: 1;
}


.action-button-menu-item {
  background-color: transparent;
  color: white;
  padding: 6px 10px;
  border-radius: 3px;
  font-size: 0.85rem;
  cursor: pointer;
  border: none;
  text-align: left;
  width: 100%;
  display: flex;
  align-items: center;
  gap: 8px; /* Space between icon and text */
}
.action-button-menu-item:hover {
  background-color: rgba(75, 85, 99, 0.9); /* gray-600 for hover */
}

.chat-history {
  flex-grow: 1;
}

.user-message {
  border-top-right-radius: 0.25rem; /* Slightly flatter on one corner */
}
.assistant-message {
  border-top-left-radius: 0.25rem; /* Slightly flatter on one corner */
}
.assistant-message div {
  color: #ffffff;
}

</style>
