<script setup>
import { ref, nextTick } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import PageTemplate from '@/Components/PageTemplate.vue';

const props = defineProps({
  listingCaches:  Array,
  globalCaches:   Array,
  summaryStats:   Object,
  snapshotCounts: Object,
});

// ── Pull Reports (SSE streaming) ─────────────────────────────────────────────

const pullOptions   = ref({ fresh: false, skip_hotspots: false });
const pullRunning   = ref(false);
const pullLines     = ref([]);   // { text, stderr }
const pullResult    = ref(null); // { success, exitCode }
const pullConsole   = ref(null); // template ref for scroll

function startPullReports() {
  if (pullRunning.value) return;
  pullRunning.value = true;
  pullLines.value   = [];
  pullResult.value  = null;

  const params = new URLSearchParams();
  if (pullOptions.value.fresh)         params.set('fresh', '1');
  if (pullOptions.value.skip_hotspots) params.set('skip_hotspots', '1');

  const url = route('admin.cache-manager.pull-reports.stream') + (params.toString() ? '?' + params : '');
  const source = new EventSource(url);

  source.onmessage = async (event) => {
    const data = JSON.parse(event.data);

    if (data.type === 'line') {
      pullLines.value.push({ text: data.text, stderr: !!data.stderr });
      await nextTick();
      if (pullConsole.value) pullConsole.value.scrollTop = pullConsole.value.scrollHeight;
    } else if (data.type === 'done') {
      pullResult.value  = { success: data.exitCode === 0, exitCode: data.exitCode };
      pullRunning.value = false;
      source.close();
    }
  };

  source.onerror = () => {
    pullLines.value.push({ text: 'Connection error — stream closed.', stderr: true });
    pullResult.value  = { success: false };
    pullRunning.value = false;
    source.close();
  };
}

// ── Quick commands (fetch → JSON output) ─────────────────────────────────────

const hotspotRunning = ref(false);
const hotspotOutput  = ref(null); // { success, output }

async function runMaterializeHotspots() {
  hotspotRunning.value = true;
  hotspotOutput.value  = null;
  try {
    const res = await jsonPost(route('admin.cache-manager.materialize-hotspots'));
    hotspotOutput.value = res;
  } finally {
    hotspotRunning.value = false;
  }
}

const metricsRunning = ref(false);
const metricsOutput  = ref(null);

async function runWarmMetrics() {
  metricsRunning.value = true;
  metricsOutput.value  = null;
  try {
    const res = await jsonPost(route('admin.cache-manager.warm-metrics'));
    metricsOutput.value = res;
  } finally {
    metricsRunning.value = false;
  }
}

async function jsonPost(url) {
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
  const res = await fetch(url, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': csrfToken,
      'Accept': 'application/json',
    },
    body: JSON.stringify({}),
  });
  return res.json();
}

// ── Cache operations (Inertia, instant) ──────────────────────────────────────

const busy = ref({});

function inertiPost(routeName, data = {}, busyKey = null) {
  if (busyKey) busy.value[busyKey] = true;
  router.post(route(routeName), data, {
    preserveScroll: true,
    onFinish: () => { if (busyKey) delete busy.value[busyKey]; },
  });
}

function forgetCache(key)      { inertiPost('admin.cache-manager.forget', { key }, `forget_${key}`); }
function forgetAllListing()    { inertiPost('admin.cache-manager.forget-all-listing', {}, 'forget_listing'); }
function forgetAllSummaries()  { inertiPost('admin.cache-manager.forget-all-summaries', {}, 'forget_summaries'); }

// ── Inline sub-component ──────────────────────────────────────────────────────

const CommandResult = {
  props: { result: Object, label: String },
  template: `
    <div class="rounded-lg border overflow-hidden text-xs"
      :class="result.success ? 'border-green-200' : 'border-red-200'">
      <div class="flex items-center justify-between px-3 py-1.5 font-mono"
        :class="result.success ? 'bg-green-800 text-green-100' : 'bg-red-800 text-red-100'">
        <span>{{ label }}</span>
        <span>{{ result.success ? '✓ done' : '✗ failed' }}</span>
      </div>
      <pre class="font-mono bg-gray-900 text-gray-100 px-3 py-2 whitespace-pre-wrap max-h-48 overflow-y-auto">{{ result.output }}</pre>
    </div>
  `,
};
</script>

<template>
  <PageTemplate>
    <Head title="Cache &amp; Report Manager" />

    <div class="max-w-5xl mx-auto px-4 py-10 space-y-10">

      <div>
        <h1 class="text-3xl font-bold text-gray-900">Cache &amp; Report Import Manager</h1>
        <p class="mt-2 text-gray-500 text-sm">
          Manage all application caches and trigger report import jobs. Most caches are permanent (no TTL)
          and must be manually cleared to pick up new data.
        </p>
      </div>

      <!-- Flash messages (for cache operations) -->
      <div v-if="$page.props.flash.success"
        class="flex items-start gap-3 bg-green-50 border border-green-300 text-green-800 px-4 py-3 rounded-lg text-sm">
        <span class="shrink-0 mt-0.5">&#10003;</span>
        <span>{{ $page.props.flash.success }}</span>
      </div>
      <div v-if="$page.props.flash.error"
        class="flex items-start gap-3 bg-red-50 border border-red-300 text-red-800 px-4 py-3 rounded-lg text-sm">
        <span class="shrink-0 mt-0.5">&#10007;</span>
        <span>{{ $page.props.flash.error }}</span>
      </div>

      <!-- ── SECTION 1: Report Import ─────────────────────────────────────── -->
      <section class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 bg-indigo-50 border-b border-indigo-100">
          <h2 class="text-lg font-semibold text-indigo-900">Report Import</h2>
          <p class="text-sm text-indigo-700 mt-1">
            Pull analysis artifacts from S3 into the local snapshot database, rebuild derived data, and warm caches.
            Run this after new Stage 4 / scoring jobs complete on the analysis server.
          </p>
        </div>

        <div class="divide-y divide-gray-100">

          <!-- Pull Analysis Reports (SSE) -->
          <div class="px-6 py-5 space-y-4">
            <div class="flex items-start justify-between gap-6">
              <div class="flex-1 min-w-0">
                <h3 class="font-medium text-gray-900">Pull Analysis Reports from S3</h3>
                <p class="text-sm text-gray-500 mt-1">
                  Scans S3 for <code class="bg-gray-100 px-1 rounded">stage4_h3_anomaly.json</code>,
                  <code class="bg-gray-100 px-1 rounded">stage2_yearly_count_comparison.json</code>,
                  <code class="bg-gray-100 px-1 rounded">scoring_results*</code>, and
                  <code class="bg-gray-100 px-1 rounded">stage6*</code> artifacts. Writes them into
                  <code class="bg-gray-100 px-1 rounded">analysis_report_snapshots</code>,
                  re-materializes hotspot findings, pre-warms all trend summary caches, then clears
                  listing caches so they rebuild with fresh data.
                  <strong class="text-gray-700"> May take 30–120 seconds</strong> — output streams live below.
                </p>
                <div class="mt-3 flex flex-wrap gap-4">
                  <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer select-none">
                    <input type="checkbox" v-model="pullOptions.fresh" :disabled="pullRunning"
                      class="rounded border-gray-300 text-indigo-600" />
                    <span><strong>--fresh</strong> — re-pull artifacts that already exist in the snapshot table</span>
                  </label>
                  <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer select-none">
                    <input type="checkbox" v-model="pullOptions.skip_hotspots" :disabled="pullRunning"
                      class="rounded border-gray-300 text-indigo-600" />
                    <span><strong>--skip-hotspots</strong> — skip re-materializing hotspot findings</span>
                  </label>
                </div>
                <div class="mt-3 flex flex-wrap gap-3 text-xs text-gray-400">
                  <span>Snapshots in DB:</span>
                  <span class="bg-gray-100 px-2 py-0.5 rounded">Stage 4: {{ snapshotCounts.stage4 }}</span>
                  <span class="bg-gray-100 px-2 py-0.5 rounded">Stage 2: {{ snapshotCounts.stage2 }}</span>
                  <span class="bg-gray-100 px-2 py-0.5 rounded">Scoring: {{ snapshotCounts.scoring }}</span>
                  <span class="bg-gray-100 px-2 py-0.5 rounded">Stage 6: {{ snapshotCounts.stage6 }}</span>
                </div>
              </div>
              <button @click="startPullReports" :disabled="pullRunning"
                class="shrink-0 px-4 py-2 text-sm font-medium rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 disabled:bg-indigo-300 transition-colors">
                {{ pullRunning ? 'Running…' : 'Pull Reports' }}
              </button>
            </div>

            <!-- Live output console -->
            <div v-if="pullRunning || pullLines.length > 0 || pullResult"
              class="rounded-lg border border-gray-200 overflow-hidden">
              <div class="flex items-center justify-between px-3 py-1.5 bg-gray-800 text-gray-400 text-xs">
                <span class="font-mono">app:pull-analysis-reports</span>
                <span v-if="pullRunning" class="flex items-center gap-1.5 text-green-400">
                  <span class="inline-block w-1.5 h-1.5 rounded-full bg-green-400 animate-pulse" />
                  running
                </span>
                <span v-else-if="pullResult?.success" class="text-green-400">&#10003; completed</span>
                <span v-else-if="pullResult" class="text-red-400">&#10007; failed (exit {{ pullResult.exitCode }})</span>
              </div>
              <div ref="pullConsole"
                class="font-mono text-xs bg-gray-900 text-gray-100 p-3 h-64 overflow-y-auto space-y-0.5">
                <div v-for="(line, i) in pullLines" :key="i"
                  :class="line.stderr ? 'text-yellow-300' : 'text-gray-100'">
                  {{ line.text }}
                </div>
                <div v-if="pullRunning" class="text-gray-500 animate-pulse">▌</div>
              </div>
            </div>
          </div>

          <!-- Materialize Hotspots -->
          <div class="px-6 py-5 space-y-3">
            <div class="flex items-start justify-between gap-6">
              <div class="flex-1 min-w-0">
                <h3 class="font-medium text-gray-900">Materialize Hotspot Findings</h3>
                <p class="text-sm text-gray-500 mt-1">
                  Rebuilds the <code class="bg-gray-100 px-1 rounded">h3_hotspot_findings</code> table from
                  Stage 4 snapshots already in the database. Run this if the hotspot map looks stale.
                  Runs automatically as part of Pull Reports (unless --skip-hotspots).
                </p>
              </div>
              <button @click="runMaterializeHotspots" :disabled="hotspotRunning"
                class="shrink-0 px-4 py-2 text-sm font-medium rounded-lg bg-gray-700 text-white hover:bg-gray-800 disabled:bg-gray-400 transition-colors">
                {{ hotspotRunning ? 'Running…' : 'Re-materialize' }}
              </button>
            </div>
            <CommandResult v-if="hotspotOutput" :result="hotspotOutput" label="app:materialize-hotspot-findings" />
          </div>

          <!-- Metrics Snapshot -->
          <div class="px-6 py-5 space-y-3">
            <div class="flex items-start justify-between gap-6">
              <div class="flex-1 min-w-0">
                <h3 class="font-medium text-gray-900">Rebuild Dashboard Metrics Snapshot</h3>
                <p class="text-sm text-gray-500 mt-1">
                  Recomputes the statistical summary snapshot stored in the
                  <code class="bg-gray-100 px-1 rounded">metrics_snapshots</code> table.
                  Runs automatically at the end of the data pipeline. Run manually if dashboard metrics
                  or homepage totals look stale after a seeding run.
                </p>
              </div>
              <button @click="runWarmMetrics" :disabled="metricsRunning"
                class="shrink-0 px-4 py-2 text-sm font-medium rounded-lg bg-gray-700 text-white hover:bg-gray-800 disabled:bg-gray-400 transition-colors">
                {{ metricsRunning ? 'Running…' : 'Rebuild Metrics' }}
              </button>
            </div>
            <CommandResult v-if="metricsOutput" :result="metricsOutput" label="app:cache-metrics-data" />
          </div>

        </div>
      </section>

      <!-- ── SECTION 2: Listing Caches ────────────────────────────────────── -->
      <section class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
          <div>
            <h2 class="text-lg font-semibold text-gray-900">Listing Caches</h2>
            <p class="text-sm text-gray-500 mt-0.5">
              Permanent caches that power the main index pages. Cleared and rebuilt automatically by Pull Reports.
            </p>
          </div>
          <button @click="forgetAllListing" :disabled="busy.forget_listing"
            class="shrink-0 px-3 py-1.5 text-sm font-medium rounded-lg border border-red-300 text-red-600 hover:bg-red-50 disabled:opacity-50 transition-colors">
            {{ busy.forget_listing ? 'Clearing…' : 'Clear All' }}
          </button>
        </div>
        <ul class="divide-y divide-gray-100">
          <li v-for="cache in listingCaches" :key="cache.key"
            class="px-6 py-4 flex items-start justify-between gap-4">
            <div class="flex items-start gap-3 flex-1 min-w-0">
              <span class="mt-1 shrink-0 w-2.5 h-2.5 rounded-full"
                :class="cache.exists ? 'bg-green-400' : 'bg-gray-300'" />
              <div class="min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                  <span class="font-medium text-gray-800 text-sm">{{ cache.label }}</span>
                  <code class="text-xs text-gray-400">{{ cache.key }}</code>
                  <span class="text-xs px-1.5 py-0.5 rounded"
                    :class="cache.exists ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'">
                    {{ cache.exists ? 'Cached' : 'Empty' }}
                  </span>
                </div>
                <p class="text-sm text-gray-500 mt-1">{{ cache.description }}</p>
              </div>
            </div>
            <button v-if="cache.exists"
              @click="forgetCache(cache.key)" :disabled="busy[`forget_${cache.key}`]"
              class="shrink-0 px-3 py-1 text-xs font-medium rounded border border-gray-300 text-gray-600 hover:bg-gray-50 disabled:opacity-50 transition-colors">
              {{ busy[`forget_${cache.key}`] ? '…' : 'Clear' }}
            </button>
          </li>
        </ul>
      </section>

      <!-- ── SECTION 3: Global Caches ──────────────────────────────────────── -->
      <section class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
          <h2 class="text-lg font-semibold text-gray-900">Global Page Caches</h2>
          <p class="text-sm text-gray-500 mt-0.5">Caches shared across all page loads.</p>
        </div>
        <ul class="divide-y divide-gray-100">
          <li v-for="cache in globalCaches" :key="cache.key"
            class="px-6 py-4 flex items-start justify-between gap-4">
            <div class="flex items-start gap-3 flex-1 min-w-0">
              <span class="mt-1 shrink-0 w-2.5 h-2.5 rounded-full"
                :class="cache.exists ? 'bg-green-400' : 'bg-gray-300'" />
              <div class="min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                  <span class="font-medium text-gray-800 text-sm">{{ cache.label }}</span>
                  <code class="text-xs text-gray-400">{{ cache.key }}</code>
                  <span v-if="cache.ttl" class="text-xs text-gray-400">TTL {{ cache.ttl / 60 }}m</span>
                  <span class="text-xs px-1.5 py-0.5 rounded"
                    :class="cache.exists ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'">
                    {{ cache.exists ? 'Cached' : 'Empty' }}
                  </span>
                </div>
                <p class="text-sm text-gray-500 mt-1">{{ cache.description }}</p>
              </div>
            </div>
            <button v-if="cache.exists"
              @click="forgetCache(cache.key)" :disabled="busy[`forget_${cache.key}`]"
              class="shrink-0 px-3 py-1 text-xs font-medium rounded border border-gray-300 text-gray-600 hover:bg-gray-50 disabled:opacity-50 transition-colors">
              {{ busy[`forget_${cache.key}`] ? '…' : 'Clear' }}
            </button>
          </li>
        </ul>
      </section>

      <!-- ── SECTION 4: Trend Summary Caches ──────────────────────────────── -->
      <section class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
          <div>
            <h2 class="text-lg font-semibold text-gray-900">Trend Summary Caches</h2>
            <p class="text-sm text-gray-500 mt-0.5">
              One permanent cache per Stage 4 job
              (<code class="bg-gray-100 px-1 rounded text-xs">trend_summary_v5_{jobId}</code>).
              Holds pre-computed anomaly/trend counts, top categories, and top findings shown on the Trends page.
              Warmed by Pull Reports; computed lazily on first Trends page visit per job.
              Clearing forces a full recompute.
            </p>
          </div>
          <button @click="forgetAllSummaries" :disabled="busy.forget_summaries"
            class="shrink-0 px-3 py-1.5 text-sm font-medium rounded-lg border border-red-300 text-red-600 hover:bg-red-50 disabled:opacity-50 transition-colors">
            {{ busy.forget_summaries ? 'Clearing…' : 'Clear All' }}
          </button>
        </div>
        <div class="px-6 py-5 flex flex-wrap gap-8">
          <div class="text-center">
            <div class="text-3xl font-bold text-gray-900">{{ summaryStats.total }}</div>
            <div class="text-xs text-gray-500 mt-0.5">Total Stage 4 jobs</div>
          </div>
          <div class="text-center">
            <div class="text-3xl font-bold text-green-600">{{ summaryStats.cached }}</div>
            <div class="text-xs text-gray-500 mt-0.5">Summaries cached</div>
          </div>
          <div class="text-center">
            <div class="text-3xl font-bold"
              :class="summaryStats.missing > 0 ? 'text-amber-500' : 'text-gray-300'">
              {{ summaryStats.missing }}
            </div>
            <div class="text-xs text-gray-500 mt-0.5">Missing (lazy on visit)</div>
          </div>
        </div>
      </section>

    </div>
  </PageTemplate>
</template>
