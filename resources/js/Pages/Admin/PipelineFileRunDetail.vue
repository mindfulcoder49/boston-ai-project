<template>
  <AdminLayout>
    <Head :title="`Pipeline Run: ${runId}`" />
    <div class="container mx-auto">
      <div class="mb-6">
        <Link :href="route('admin.pipeline.fileLogs.index')" class="text-indigo-600 hover:text-indigo-800">&larr; Back to Pipeline Runs</Link>
      </div>
      <h1 class="text-2xl font-semibold text-gray-800 mb-2 break-words">Pipeline Run Details</h1>
      <p class="text-sm text-gray-500 mb-6 break-all">Run ID: {{ runId }}</p>

      <div v-if="runDetails" class="bg-white shadow-md rounded-lg p-4 sm:p-6 mb-6">
        <h2 class="text-xl font-semibold text-gray-700 mb-4">Run Summary</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4 text-sm">
          <div><strong class="text-gray-600">Name:</strong> <span class="break-words">{{ runDetails.name }}</span></div>
          <div>
            <strong class="text-gray-600">Status:</strong>
            <span :class="statusClass(runDetails.status)" class="ml-2 px-2 py-0.5 inline-flex text-xs leading-5 font-semibold rounded-full">{{ runDetails.status }}</span>
          </div>
          <div><strong class="text-gray-600">Start Time:</strong> {{ formatDateTime(runDetails.start_time) }}</div>
          <div><strong class="text-gray-600">End Time:</strong> {{ runDetails.end_time ? formatDateTime(runDetails.end_time) : 'N/A' }}</div>
          <div class="md:col-span-2 break-all"><strong class="text-gray-600">Summary File:</strong> {{ runDetails.summary_file_path }}</div>
        </div>
      </div>

      <div v-if="runDetails && runDetails.commands && runDetails.commands.length > 0" class="bg-white shadow-md rounded-lg p-4 sm:p-6">
        <h2 class="text-xl font-semibold text-gray-700 mb-4">Commands Executed</h2>
        <div class="space-y-4">
          <div v-for="(command, index) in runDetails.commands" :key="index" class="border border-gray-200 rounded-md p-4">
            <div class="flex flex-col sm:flex-row justify-between sm:items-center mb-2">
              <h3 class="text-md font-semibold text-gray-800 truncate min-w-0" :title="command.command_name">{{ command.command_name }}</h3>
              <span :class="statusClass(command.status)" class="mt-1 sm:mt-0 px-2 py-0.5 inline-flex text-xs leading-5 font-semibold rounded-full self-start sm:self-center sm:ml-2">
                {{ command.status }}
              </span>
            </div>
            <div class="text-xs text-gray-500 mb-1 truncate break-all" :title="JSON.stringify(command.parameters)">
              <strong class="text-gray-600">Params:</strong> {{ JSON.stringify(command.parameters) }}
            </div>
            <div class="text-sm text-gray-700 mb-1">
              <strong class="text-gray-600">Duration:</strong> {{ command.duration_seconds }}s
            </div>
            <div class="text-sm">
              <strong class="text-gray-600">Log File:</strong>
              <button @click="fetchLogContent(command.log_file)" class="ml-1 text-indigo-600 hover:text-indigo-900 underline break-all text-left">
                {{ command.log_file }}
              </button>
            </div>
          </div>
        </div>
      </div>
      <div v-else-if="runDetails" class="bg-white shadow-md rounded-lg p-6">
        <p class="text-gray-500">No commands found for this run.</p>
      </div>
      <div v-else class="bg-white shadow-md rounded-lg p-6">
        <p class="text-red-500">Could not load run details.</p>
      </div>

      <!-- Log Content Modal -->
      <div v-if="showLogModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-75 p-4" @click.self="closeLogModal">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-4xl max-h-[80vh] flex flex-col">
          <div class="flex justify-between items-center p-4 border-b">
            <h3 class="text-lg font-semibold truncate min-w-0" :title="selectedLogFile">Log: {{ selectedLogFile }}</h3>
            <button @click="closeLogModal" class="text-gray-500 hover:text-gray-700 text-2xl leading-none">&times;</button>
          </div>
          <pre class="p-4 overflow-auto flex-grow text-xs bg-gray-800 text-white rounded-b-lg">{{ logContent }}</pre>
        </div>
      </div>

    </div>
  </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'; // Import AdminLayout
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import axios from 'axios';

const props = defineProps({
  runDetails: Object, // This should be a single object for the run
  runId: String,
});

const showLogModal = ref(false);
const selectedLogFile = ref('');
const logContent = ref('Loading log content...');

const formatDateTime = (dateTimeString) => {
  if (!dateTimeString) return 'N/A';
  return new Date(dateTimeString).toLocaleString();
};

const statusClass = (status) => {
  if (status === 'completed' || status === 'success') return 'bg-green-100 text-green-800';
  if (status === 'failed') return 'bg-red-100 text-red-800';
  if (status === 'running') return 'bg-yellow-100 text-yellow-800';
  return 'bg-gray-100 text-gray-800';
};

const fetchLogContent = async (logFileName) => {
  selectedLogFile.value = logFileName;
  logContent.value = 'Loading log content...';
  showLogModal.value = true;
  try {
    const response = await axios.get(route('admin.pipeline.fileLogs.commandLogContent', { runId: props.runId, logFileName: logFileName }));
    logContent.value = response.data;
  } catch (error) {
    console.error('Error fetching log content:', error);
    logContent.value = `Error loading log file: ${error.response?.data || error.message}`;
  }
};

const closeLogModal = () => {
  showLogModal.value = false;
  selectedLogFile.value = '';
  logContent.value = '';
};

// If you prefer defineOptions for layout:
// import AdminLayout from '@/Layouts/AdminLayout.vue';
// defineOptions({ layout: AdminLayout });
</script>

<style scoped>
pre {
  white-space: pre-wrap; /* Ensure long lines wrap */
  word-wrap: break-word; /* Break words if necessary */
}
</style>
