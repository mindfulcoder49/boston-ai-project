<template>
  <AdminLayout>
    <Head title="Backend Health" />

    <div class="container mx-auto space-y-6">
      <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 class="text-2xl font-semibold text-gray-800">Backend Health</h1>
          <p class="text-sm text-gray-500">Daily operations status for ingestion, dependencies, alerts, metrics, and storage.</p>
        </div>
        <div class="text-xs text-gray-500">
          Snapshot generated: {{ formatDateTime(snapshot.generated_at) }}
        </div>
      </div>

      <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <div class="rounded-lg bg-white p-5 shadow">
          <div class="text-sm font-medium text-gray-500">Latest Run</div>
          <div class="mt-2 text-lg font-semibold text-gray-900">{{ snapshot.latestRun?.status ?? 'No runs found' }}</div>
          <div class="mt-2 break-all text-sm text-gray-600">{{ snapshot.latestRun?.run_id ?? 'No run id available' }}</div>
          <div v-if="snapshot.latestRun?.freshness" class="mt-3">
            <span :class="pillClass(snapshot.latestRun.freshness.status)" class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold">
              {{ snapshot.latestRun.freshness.label }}
            </span>
            <span class="ml-2 text-xs text-gray-500">{{ snapshot.latestRun.freshness.age_human }}</span>
          </div>
          <Link
            v-if="snapshot.latestRun?.run_id"
            :href="route('admin.pipeline.fileLogs.show', snapshot.latestRun.run_id)"
            class="mt-4 inline-block text-sm font-medium text-indigo-600 hover:text-indigo-800"
          >
            View latest run
          </Link>
        </div>

        <div class="rounded-lg bg-white p-5 shadow">
          <div class="text-sm font-medium text-gray-500">Core Freshness</div>
          <div class="mt-2 text-lg font-semibold text-gray-900">{{ snapshot.latestRun?.core_freshness?.label ?? 'Not evaluated' }}</div>
          <div class="mt-3 space-y-1 text-sm text-gray-600">
            <div v-for="(component, key) in snapshot.latestRun?.core_freshness?.components ?? {}" :key="key">
              <span class="font-medium">{{ component.label }}:</span>
              {{ component.status }}
            </div>
          </div>
        </div>

        <div class="rounded-lg bg-white p-5 shadow">
          <div class="text-sm font-medium text-gray-500">Metrics Freshness</div>
          <div class="mt-2 text-lg font-semibold text-gray-900">{{ snapshot.metricsFreshness?.status ?? 'Unknown' }}</div>
          <div class="mt-2 text-sm text-gray-600">
            Last updated: {{ snapshot.metricsFreshness?.last_updated ? formatDateTime(snapshot.metricsFreshness.last_updated) : 'Unknown' }}
          </div>
          <div class="mt-1 text-sm text-gray-500">{{ snapshot.metricsFreshness?.age_human ?? 'No metrics freshness data' }}</div>
        </div>
      </div>

      <div v-if="snapshot.topAlert" class="rounded-lg border border-red-200 bg-red-50 p-5">
        <div class="flex items-center justify-between gap-3">
          <div>
            <div class="text-sm font-medium text-red-700">Active Backend Alert</div>
            <div class="mt-1 text-lg font-semibold text-red-900">{{ snapshot.topAlert.title }}</div>
          </div>
          <span :class="pillClass(snapshot.topAlert.severity)" class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold">
            {{ snapshot.topAlert.severity }}
          </span>
        </div>
        <div class="mt-2 text-sm text-red-800">{{ snapshot.topAlert.message }}</div>
      </div>

      <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <div class="rounded-lg bg-white p-5 shadow">
          <h2 class="text-lg font-semibold text-gray-800">Dependency Health</h2>
          <div class="mt-4 space-y-4 text-sm">
            <div class="rounded-md border border-gray-200 p-4">
              <div class="flex items-center justify-between">
                <div class="font-medium text-gray-800">Scraper</div>
                <span :class="pillClass(snapshot.dependencyHealth?.scraper?.status)" class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold">
                  {{ snapshot.dependencyHealth?.scraper?.label ?? 'Unknown' }}
                </span>
              </div>
              <div class="mt-2 break-words text-gray-600">{{ snapshot.dependencyHealth?.scraper?.message ?? 'No scraper snapshot available.' }}</div>
            </div>

            <div class="rounded-md border border-gray-200 p-4">
              <div class="flex items-center justify-between">
                <div class="font-medium text-gray-800">DNS Sync</div>
                <span :class="pillClass(snapshot.dependencyHealth?.dns_sync?.status)" class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold">
                  {{ snapshot.dependencyHealth?.dns_sync?.label ?? 'Unknown' }}
                </span>
              </div>
              <div class="mt-2 break-words text-gray-600">{{ snapshot.dependencyHealth?.dns_sync?.message ?? 'No DNS sync snapshot available.' }}</div>
            </div>

            <div class="rounded-md border border-gray-200 p-4">
              <div class="flex items-center justify-between">
                <div class="font-medium text-gray-800">Queue Worker</div>
                <span :class="pillClass(snapshot.dependencyHealth?.queue_worker?.status)" class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold">
                  {{ snapshot.dependencyHealth?.queue_worker?.label ?? 'Unknown' }}
                </span>
              </div>
              <div class="mt-2 text-gray-600">
                Last seen: {{ snapshot.dependencyHealth?.queue_worker?.last_seen_at ? formatDateTime(snapshot.dependencyHealth.queue_worker.last_seen_at) : 'No heartbeat recorded' }}
              </div>
            </div>
          </div>
        </div>

        <div class="rounded-lg bg-white p-5 shadow">
          <h2 class="text-lg font-semibold text-gray-800">Storage Pressure</h2>
          <div class="mt-4 space-y-3 text-sm">
            <div
              v-for="target in snapshot.storagePressure?.targets ?? []"
              :key="target.slug"
              class="rounded-md border border-gray-200 p-4"
            >
              <div class="flex items-center justify-between">
                <div class="font-medium text-gray-800">{{ target.label }}</div>
                <span :class="pillClass(target.status)" class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold">
                  {{ target.status }}
                </span>
              </div>
              <div class="mt-2 text-gray-600">{{ target.size_human }}</div>
              <div class="mt-1 break-all text-xs text-gray-500">{{ target.path }}</div>
            </div>
          </div>
        </div>
      </div>

      <div v-if="snapshot.alerts?.length" class="rounded-lg bg-white p-5 shadow">
        <h2 class="text-lg font-semibold text-gray-800">Alert Inventory</h2>
        <div class="mt-4 space-y-3">
          <div
            v-for="alert in snapshot.alerts"
            :key="alert.signature"
            class="rounded-md border border-gray-200 p-4"
          >
            <div class="flex items-center justify-between gap-3">
              <div class="font-medium text-gray-800">{{ alert.title }}</div>
              <span :class="pillClass(alert.severity)" class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold">
                {{ alert.severity }}
              </span>
            </div>
            <div class="mt-2 text-sm text-gray-600">{{ alert.message }}</div>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <div class="rounded-lg bg-white p-5 shadow">
          <h2 class="text-lg font-semibold text-gray-800">Failure Focus</h2>
          <div v-if="snapshot.latestRun?.first_failed_command" class="mt-4 space-y-2 text-sm">
            <div><span class="font-medium">Command:</span> {{ snapshot.latestRun.first_failed_command.command_name }}</div>
            <div v-if="snapshot.latestRun.first_failed_command.stage_name"><span class="font-medium">Stage:</span> {{ snapshot.latestRun.first_failed_command.stage_name }}</div>
            <div v-if="snapshot.latestRun.first_failed_command.failure_excerpt" class="rounded-md bg-red-50 p-3 text-red-800">
              {{ snapshot.latestRun.first_failed_command.failure_excerpt }}
            </div>
          </div>
          <div v-else class="mt-4 text-sm text-gray-600">No failed command is recorded on the latest run.</div>
        </div>

        <div class="rounded-lg bg-white p-5 shadow">
          <h2 class="text-lg font-semibold text-gray-800">Operator Actions</h2>
          <div class="mt-4 flex flex-wrap gap-3 text-sm">
            <Link :href="route('admin.pipeline.fileLogs.index')" class="rounded-md bg-indigo-600 px-3 py-2 font-medium text-white hover:bg-indigo-700">Pipeline Logs</Link>
            <Link :href="route('admin.job-dispatcher.index')" class="rounded-md bg-indigo-600 px-3 py-2 font-medium text-white hover:bg-indigo-700">Job Dispatcher</Link>
            <Link :href="route('admin.cache-manager.index')" class="rounded-md bg-indigo-600 px-3 py-2 font-medium text-white hover:bg-indigo-700">Cache Manager</Link>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
  snapshot: {
    type: Object,
    required: true,
  },
});

const formatDateTime = (dateTimeString) => {
  if (!dateTimeString) return 'N/A';
  return new Date(dateTimeString).toLocaleString();
};

const pillClass = (status) => {
  if (['healthy', 'fresh', 'preserved'].includes(status)) return 'bg-green-100 text-green-800';
  if (['warning', 'aging', 'partial'].includes(status)) return 'bg-amber-100 text-amber-800';
  if (['failed', 'stale', 'critical'].includes(status)) return 'bg-red-100 text-red-800';
  if (status === 'running') return 'bg-blue-100 text-blue-800';
  return 'bg-gray-100 text-gray-800';
};
</script>
