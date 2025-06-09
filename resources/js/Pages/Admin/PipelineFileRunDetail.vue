<template>
  <AdminLayout>
    <Head :title="`Pipeline Run: ${runId}`" />
    <div class="container mx-auto">
      <div class="mb-6">
        <Link :href="route('admin.pipeline.fileLogs.index')" class="text-indigo-600 hover:text-indigo-800">&larr; Back to Pipeline Runs</Link>
      </div>
      <h1 class="text-2xl font-semibold text-gray-800 mb-2">Pipeline Run Details</h1>
      <p class="text-sm text-gray-500 mb-6">Run ID: {{ runId }}</p>

      <div v-if="runDetails" class="bg-white shadow-md rounded-lg p-6 mb-6">
        <h2 class="text-xl font-semibold text-gray-700 mb-4">Run Summary</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
          <div><strong>Name:</strong> {{ runDetails.name }}</div>
          <div><strong>Status:</strong> <span :class="statusClass(runDetails.status)" class="px-2 py-0.5 inline-flex text-xs leading-5 font-semibold rounded-full">{{ runDetails.status }}</span></div>
          <div><strong>Start Time:</strong> {{ formatDateTime(runDetails.start_time) }}</div>
          <div><strong>End Time:</strong> {{ runDetails.end_time ? formatDateTime(runDetails.end_time) : 'N/A' }}</div>
          <div><strong>Summary File:</strong> {{ runDetails.summary_file_path }}</div>
        </div>
      </div>

      <div v-if="runDetails && runDetails.commands && runDetails.commands.length > 0" class="bg-white shadow-md rounded-lg">
        <h2 class="text-xl font-semibold text-gray-700 mb-4 px-6 pt-6">Commands Executed</h2>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Command</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Duration</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Log File</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <!-- Corrected: Iterate over runDetails.commands -->
              <tr v-for="(command, index) in runDetails.commands" :key="index">
                <td class="px-6 py-4">
                  <div class="text-sm font-medium text-gray-900">{{ command.command_name }}</div>
                  <div class="text-xs text-gray-500 truncate max-w-md" :title="JSON.stringify(command.parameters)">Params: {{ JSON.stringify(command.parameters) }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">
                  <span :class="statusClass(command.status)" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                    {{ command.status }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ command.duration_seconds }}s</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-indigo-600 hover:text-indigo-900">
                  <button @click="fetchLogContent(command.log_file)" class="underline">
                    {{ command.log_file }}
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <div v-else-if="runDetails" class="bg-white shadow-md rounded-lg p-6">
        <p class="text-gray-500">No commands found for this run.</p>
      </div>
      <div v-else class="bg-white shadow-md rounded-lg p-6">
        <p class="text-red-500">Could not load run details.</p>
      </div>

      <!-- Log Content Modal -->
      <div v-if="showLogModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-75" @click.self="closeLogModal">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-4xl max-h-[80vh] flex flex-col">
          <div class="flex justify-between items-center p-4 border-b">
            <h3 class="text-lg font-semibold">Log: {{ selectedLogFile }}</h3>
            <button @click="closeLogModal" class="text-gray-500 hover:text-gray-700">&times;</button>
          </div>
          <pre class="p-4 overflow-auto flex-grow text-xs bg-gray-800 text-white">{{ logContent }}</pre>
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
