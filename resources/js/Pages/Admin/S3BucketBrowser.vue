<template>
  <AdminLayout>
    <Head title="Admin - S3 Bucket Browser" />
    <div class="container mx-auto">

      <!-- Header -->
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-2">
        <div>
          <h1 class="text-2xl font-semibold text-gray-800">S3 Bucket Browser</h1>
          <p class="text-sm text-gray-500 mt-1">Manage analysis job directories and files in S3.</p>
        </div>
        <div class="flex items-center gap-2 self-end sm:self-auto">
          <Link :href="route('admin.index')" class="text-sm text-indigo-600 hover:text-indigo-800">&larr; Dashboard</Link>
          <button @click="refreshListing" :disabled="isRefreshing"
            class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm hover:bg-indigo-700 disabled:bg-indigo-300 transition-colors">
            {{ isRefreshing ? 'Refreshing…' : 'Refresh Listing' }}
          </button>
        </div>
      </div>

      <!-- Flash Messages -->
      <div v-if="$page.props.flash?.success" class="mb-4 p-3 bg-green-100 text-green-700 rounded-md text-sm">
        {{ $page.props.flash.success }}
      </div>
      <div v-if="$page.props.flash?.error" class="mb-4 p-3 bg-red-100 text-red-700 rounded-md text-sm">
        {{ $page.props.flash.error }}
      </div>
      <div v-if="error" class="mb-4 p-3 bg-red-100 text-red-700 rounded-md text-sm">
        <strong>Error loading S3 listing:</strong> {{ error }}
      </div>

      <!-- Summary Stats -->
      <div class="grid grid-cols-3 gap-4 mb-6">
        <div class="bg-white p-4 rounded-lg shadow text-center">
          <div class="text-2xl font-bold text-indigo-600">{{ jobs.length }}</div>
          <div class="text-xs text-gray-500 mt-1">Job Directories</div>
        </div>
        <div class="bg-white p-4 rounded-lg shadow text-center">
          <div class="text-2xl font-bold text-indigo-600">{{ totalFiles }}</div>
          <div class="text-xs text-gray-500 mt-1">Total Files</div>
        </div>
        <div class="bg-white p-4 rounded-lg shadow text-center">
          <div class="text-2xl font-bold text-indigo-600">{{ formatBytes(totalSize) }}</div>
          <div class="text-xs text-gray-500 mt-1">Total Size</div>
        </div>
      </div>

      <!-- Filters -->
      <div class="bg-white p-4 rounded-lg shadow mb-3 space-y-3">
        <div class="flex flex-wrap items-center gap-3">
          <input v-model="search" type="text" placeholder="Search job ID…"
            class="border rounded-md px-3 py-1.5 text-sm w-56 focus:outline-none focus:ring-1 focus:ring-indigo-500">
          <select v-model="typeFilter" class="border rounded-md px-3 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-indigo-500">
            <option value="all">All Types</option>
            <option value="stage4">Has Stage 4</option>
            <option value="scoring">Has Scoring / Stage 6</option>
            <option value="not_in_db">Not in DB</option>
          </select>
          <div class="flex items-center gap-2">
            <label class="text-xs text-gray-500 whitespace-nowrap">Modified after</label>
            <input v-model="dateAfter" type="date"
              class="border rounded-md px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-indigo-500">
          </div>
          <div class="flex items-center gap-2">
            <label class="text-xs text-gray-500 whitespace-nowrap">before</label>
            <input v-model="dateBefore" type="date"
              class="border rounded-md px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-indigo-500">
          </div>
          <button v-if="hasActiveFilters" @click="clearFilters"
            class="text-xs text-indigo-600 hover:text-indigo-800 underline">
            Clear filters
          </button>
          <span class="text-xs text-gray-400 ml-1">{{ filteredJobs.length }} of {{ jobs.length }} shown</span>
        </div>

        <!-- Bulk action bar -->
        <div class="flex flex-wrap items-center gap-2 pt-2 border-t border-gray-100">
          <button @click="selectAllVisible"
            class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-md text-sm hover:bg-gray-200">
            Select All ({{ filteredJobs.length }})
          </button>
          <button @click="deselectAll" :disabled="selected.size === 0"
            class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-md text-sm hover:bg-gray-200 disabled:opacity-40">
            Select None
          </button>
          <span v-if="selected.size > 0" class="text-sm text-gray-600 font-medium ml-2">
            {{ selected.size }} selected
          </span>
          <div class="flex-1"></div>
          <button v-if="selected.size > 0" @click="deleteSelected"
            class="px-3 py-1.5 bg-red-600 text-white rounded-md text-sm hover:bg-red-700">
            Delete Selected ({{ selected.size }})
          </button>
          <button @click="deleteAllVisible"
            class="px-3 py-1.5 bg-red-700 text-white rounded-md text-sm hover:bg-red-800">
            Delete All Shown ({{ filteredJobs.length }})
          </button>
        </div>
      </div>

      <!-- Table -->
      <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-3 w-8">
                <input type="checkbox" :checked="allVisibleSelected" :indeterminate="someVisibleSelected"
                  @change="toggleSelectAll" class="rounded">
              </th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Job ID / Model
              </th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Contents
              </th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Size
              </th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Last Modified
              </th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Actions
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <template v-for="job in filteredJobs" :key="job.job_id">

              <!-- Main row -->
              <tr class="hover:bg-gray-50 transition-colors"
                :class="{ 'bg-indigo-50 hover:bg-indigo-50': selected.has(job.job_id) }">
                <td class="px-4 py-3">
                  <input type="checkbox" :checked="selected.has(job.job_id)"
                    @change="toggleSelect(job.job_id)" class="rounded">
                </td>

                <td class="px-4 py-3 max-w-xs">
                  <div class="font-mono text-xs text-gray-800 break-all" :title="job.job_id">
                    {{ job.job_id }}
                  </div>
                  <!-- DB-derived model info -->
                  <div v-if="job.model_class" class="text-xs text-indigo-600 mt-0.5 font-medium">
                    {{ modelBaseName(job.model_class) }}
                    <span class="text-gray-400 font-normal"> / {{ job.column_name }}</span>
                  </div>
                  <!-- Parsed from job ID if no DB record -->
                  <div v-else-if="job.parsed?.model_col" class="text-xs text-gray-400 mt-0.5">
                    {{ job.parsed.model_col }}
                    <span v-if="job.parsed.resolution"> · res{{ job.parsed.resolution }}</span>
                  </div>
                  <span v-if="!job.trend_id"
                    class="inline-block mt-1 px-1.5 py-0.5 text-xs bg-yellow-100 text-yellow-700 rounded-sm">
                    Not in DB
                  </span>
                </td>

                <td class="px-4 py-3">
                  <div class="flex flex-wrap gap-1 mb-1">
                    <span v-if="job.has_stage4"
                      class="px-1.5 py-0.5 text-xs bg-blue-100 text-blue-700 rounded">Stage 4</span>
                    <span v-if="job.has_scoring"
                      class="px-1.5 py-0.5 text-xs bg-green-100 text-green-700 rounded">Scoring</span>
                    <span v-if="job.has_stage6"
                      class="px-1.5 py-0.5 text-xs bg-purple-100 text-purple-700 rounded">Stage 6</span>
                  </div>
                  <button @click="toggleExpand(job.job_id)"
                    class="text-xs text-gray-400 hover:text-gray-700 underline">
                    {{ expanded.has(job.job_id) ? '▲ hide files' : `▼ ${job.file_count} file(s)` }}
                  </button>
                </td>

                <td class="px-4 py-3 text-xs text-gray-600 whitespace-nowrap">
                  {{ formatBytes(job.total_size) }}
                </td>
                <td class="px-4 py-3 text-xs text-gray-600 whitespace-nowrap">
                  {{ formatDate(job.last_modified) }}
                </td>
                <td class="px-4 py-3">
                  <button @click="deleteDirectory(job)"
                    class="text-xs text-red-600 hover:text-red-800 font-medium">
                    Delete
                  </button>
                </td>
              </tr>

              <!-- Expanded files row -->
              <tr v-if="expanded.has(job.job_id)" :key="job.job_id + '-files'">
                <td colspan="6" class="px-8 py-3 bg-gray-50 border-t border-gray-100">
                  <div class="space-y-1.5">
                    <div v-for="file in job.files" :key="file.name"
                      class="flex items-center justify-between py-1 border-b border-gray-100 last:border-0 text-xs">
                      <div class="flex items-center gap-4">
                        <span class="font-mono text-gray-700">{{ file.name }}</span>
                        <span class="text-gray-400">{{ formatBytes(file.size) }}</span>
                        <span class="text-gray-400">{{ formatDate(file.last_modified) }}</span>
                      </div>
                      <button @click="deleteFile(job.job_id, file.name)"
                        class="text-red-500 hover:text-red-700 font-medium ml-4 flex-shrink-0">
                        Delete
                      </button>
                    </div>
                  </div>
                </td>
              </tr>

            </template>

            <!-- Empty state -->
            <tr v-if="filteredJobs.length === 0">
              <td colspan="6" class="px-4 py-10 text-center text-gray-400 text-sm">
                {{ jobs.length === 0 ? 'No job directories found in S3.' : 'No jobs match the current filters.' }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>

    </div>
  </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
  jobs:  { type: Array,  default: () => [] },
  error: { type: String, default: null },
});

const isRefreshing = ref(false);
const search      = ref('');
const typeFilter  = ref('all');
const dateAfter   = ref('');
const dateBefore  = ref('');
const selected    = ref(new Set());
const expanded    = ref(new Set());

// ── Computed ─────────────────────────────────────────────────────────────────

const hasActiveFilters = computed(() =>
  search.value || typeFilter.value !== 'all' || dateAfter.value || dateBefore.value
);

const filteredJobs = computed(() => {
  const afterTs  = dateAfter.value  ? new Date(dateAfter.value  + 'T00:00:00').getTime() / 1000 : null;
  const beforeTs = dateBefore.value ? new Date(dateBefore.value + 'T23:59:59').getTime() / 1000 : null;

  return props.jobs.filter(job => {
    if (search.value && !job.job_id.toLowerCase().includes(search.value.toLowerCase())) return false;
    if (typeFilter.value === 'stage4'    && !job.has_stage4)                     return false;
    if (typeFilter.value === 'scoring'   && !job.has_scoring && !job.has_stage6) return false;
    if (typeFilter.value === 'not_in_db' && job.trend_id)                        return false;
    if (afterTs  && job.last_modified < afterTs)  return false;
    if (beforeTs && job.last_modified > beforeTs) return false;
    return true;
  });
});

const totalFiles = computed(() => props.jobs.reduce((s, j) => s + j.file_count, 0));
const totalSize  = computed(() => props.jobs.reduce((s, j) => s + j.total_size, 0));

const allVisibleSelected  = computed(() =>
  filteredJobs.value.length > 0 && filteredJobs.value.every(j => selected.value.has(j.job_id))
);
const someVisibleSelected = computed(() =>
  filteredJobs.value.some(j => selected.value.has(j.job_id)) && !allVisibleSelected.value
);

// ── Helpers ───────────────────────────────────────────────────────────────────

function modelBaseName(modelClass) {
  return modelClass?.split('\\').pop() ?? modelClass;
}

function formatBytes(bytes) {
  if (!bytes) return '0 B';
  const k = 1024;
  const sizes = ['B', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
}

function formatDate(ts) {
  if (!ts) return '—';
  return new Date(ts * 1000).toLocaleString();
}

// ── Selection ─────────────────────────────────────────────────────────────────

function toggleSelect(jobId) {
  const s = new Set(selected.value);
  s.has(jobId) ? s.delete(jobId) : s.add(jobId);
  selected.value = s;
}

function toggleSelectAll() {
  const s = new Set(selected.value);
  if (allVisibleSelected.value) {
    filteredJobs.value.forEach(j => s.delete(j.job_id));
  } else {
    filteredJobs.value.forEach(j => s.add(j.job_id));
  }
  selected.value = s;
}

function selectAllVisible() {
  const s = new Set(selected.value);
  filteredJobs.value.forEach(j => s.add(j.job_id));
  selected.value = s;
}

function deselectAll() {
  selected.value = new Set();
}

function clearFilters() {
  search.value     = '';
  typeFilter.value = 'all';
  dateAfter.value  = '';
  dateBefore.value = '';
}

// ── Expand ────────────────────────────────────────────────────────────────────

function toggleExpand(jobId) {
  const e = new Set(expanded.value);
  e.has(jobId) ? e.delete(jobId) : e.add(jobId);
  expanded.value = e;
}

// ── Actions ───────────────────────────────────────────────────────────────────

function refreshListing() {
  isRefreshing.value = true;
  router.post(route('admin.s3-bucket.refresh'), {}, {
    onFinish: () => { isRefreshing.value = false; },
    preserveState: false,
  });
}

function deleteDirectory(job) {
  const label = job.model_class
    ? `${modelBaseName(job.model_class)} / ${job.column_name}`
    : job.job_id;
  if (!confirm(`Delete all files in job directory for "${label}"?\n\nThis removes ${job.file_count} file(s) from S3 and any matching DB record. This cannot be undone.`)) return;
  router.delete(route('admin.s3-bucket.destroy-directory', { jobId: job.job_id }), {
    preserveState: false,
  });
}

function deleteFile(jobId, filename) {
  if (!confirm(`Delete "${filename}" from job "${jobId}"?\n\nThis cannot be undone.`)) return;
  router.delete(route('admin.s3-bucket.destroy-file', { jobId, filename }), {
    preserveState: false,
  });
}

function deleteSelected() {
  const ids = Array.from(selected.value);
  if (!confirm(`Permanently delete ${ids.length} selected job director${ids.length === 1 ? 'y' : 'ies'}?\n\nAll files and any matching DB records will be removed. This cannot be undone.`)) return;
  router.post(route('admin.s3-bucket.bulk-destroy'), { job_ids: ids }, {
    preserveState: false,
    onSuccess: () => { selected.value = new Set(); },
  });
}

function deleteAllVisible() {
  const ids = filteredJobs.value.map(j => j.job_id);
  if (ids.length === 0) return;
  if (!confirm(`Permanently delete ALL ${ids.length} shown job director${ids.length === 1 ? 'y' : 'ies'}?\n\nThis deletes every job currently visible after filters are applied.\nAll files and any matching DB records will be removed. This cannot be undone.`)) return;
  router.post(route('admin.s3-bucket.bulk-destroy'), { job_ids: ids }, {
    preserveState: false,
    onSuccess: () => { selected.value = new Set(); },
  });
}
</script>
