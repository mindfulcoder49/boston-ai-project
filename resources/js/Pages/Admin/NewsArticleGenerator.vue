<script setup>
import { ref, computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    trends:         { type: Array,  default: () => [] },
    hotspots:       { type: Object, default: () => ({}) },
    recentArticles: { type: Array,  default: () => [] },
});

const page = usePage();
const h3Names = computed(() => page.props.h3LocationNames ?? {});

// ── Trend generation ──────────────────────────────────────────────────────────

const trendBusy    = ref({});   // keyed by trend.id
const trendResults = ref({});   // keyed by trend.id: { success, message }

const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content;

async function generateFromTrend(trend) {
    trendBusy.value[trend.id]    = true;
    trendResults.value[trend.id] = null;
    try {
        const res  = await fetch(route('admin.news-articles.generate-from-trend'), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
            body: JSON.stringify({ trend_id: trend.id }),
        });
        const data = await res.json();
        trendResults.value[trend.id] = data;
    } catch (e) {
        trendResults.value[trend.id] = { success: false, message: e.message };
    } finally {
        trendBusy.value[trend.id] = false;
    }
}

// ── Hotspot generation ────────────────────────────────────────────────────────

const activeCity          = ref(Object.keys(props.hotspots)[0] ?? null);
const hexBusy             = ref({});     // keyed by h3_index
const hexResults          = ref({});     // keyed by h3_index: { success, article } | { success: false, message }

async function generateFromHexagon(hex) {
    hexBusy.value[hex.h3_index]    = true;
    hexResults.value[hex.h3_index] = null;
    const locationName = h3Names.value[hex.h3_index] ?? '';
    try {
        const res  = await fetch(route('admin.news-articles.generate-from-hexagon'), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
            body: JSON.stringify({ h3_index: hex.h3_index, location_name: locationName }),
        });
        const data = await res.json();
        hexResults.value[hex.h3_index] = data;
    } catch (e) {
        hexResults.value[hex.h3_index] = { success: false, message: e.message };
    } finally {
        hexBusy.value[hex.h3_index] = false;
    }
}

// ── Helpers ───────────────────────────────────────────────────────────────────

const articleStatusClass = (status) => ({
    'text-green-700 bg-green-50':   status === 'published',
    'text-amber-700 bg-amber-50':   status === 'generating' || status === 'draft',
    'text-red-700 bg-red-50':       status === 'error',
    'text-gray-500 bg-gray-50':     !status,
});

const cityList = computed(() => Object.keys(props.hotspots).sort());
</script>

<template>
    <AdminLayout>
        <Head title="News Article Generator" />

        <div class="space-y-10">

            <div>
                <h1 class="text-2xl font-bold text-gray-900">News Article Generator</h1>
                <p class="mt-1 text-sm text-gray-500">
                    Generate AI news articles from trend summaries or hotspot hexagon findings using GPT-5.
                </p>
            </div>

            <!-- ── Trend-Based Articles ─────────────────────────────────────── -->
            <section>
                <h2 class="text-lg font-semibold text-gray-800 mb-3">Generate from Trend Analysis</h2>
                <p class="text-sm text-gray-500 mb-4">
                    Each trend's cached summary is used as input — no raw data download required. Dispatched as a background queue job.
                </p>

                <div class="bg-white rounded-lg border shadow-sm overflow-hidden">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 border-b text-xs text-gray-500 uppercase tracking-wide">
                            <tr>
                                <th class="px-4 py-3 text-left">Report</th>
                                <th class="px-4 py-3 text-right">Findings</th>
                                <th class="px-4 py-3 text-right">Hexagons</th>
                                <th class="px-4 py-3 text-left">Top Categories</th>
                                <th class="px-4 py-3 text-left">Article</th>
                                <th class="px-4 py-3 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="trend in trends" :key="trend.id" class="hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <p class="font-medium text-gray-800 leading-tight">{{ trend.title }}</p>
                                    <p class="text-xs text-gray-400 font-mono mt-0.5">res {{ trend.h3_resolution }} · {{ trend.job_id }}</p>
                                </td>
                                <td class="px-4 py-3 text-right tabular-nums">
                                    <span v-if="trend.summary" class="text-gray-700 font-semibold">{{ trend.summary.total_findings.toLocaleString() }}</span>
                                    <span v-else class="text-gray-300">—</span>
                                </td>
                                <td class="px-4 py-3 text-right tabular-nums text-gray-600">
                                    <span v-if="trend.summary">{{ trend.summary.affected_h3_count }}</span>
                                    <span v-else class="text-gray-300">—</span>
                                </td>
                                <td class="px-4 py-3 max-w-xs">
                                    <div v-if="trend.summary?.top_categories?.length" class="flex flex-wrap gap-1">
                                        <span
                                            v-for="cat in trend.summary.top_categories.slice(0, 3)"
                                            :key="cat"
                                            class="text-xs bg-indigo-50 text-indigo-700 px-2 py-0.5 rounded-full"
                                        >{{ cat }}</span>
                                    </div>
                                    <span v-else class="text-xs text-gray-300">No summary cached</span>
                                </td>
                                <td class="px-4 py-3">
                                    <template v-if="trend.article">
                                        <span
                                            class="inline-block px-2 py-0.5 rounded text-xs font-medium"
                                            :class="articleStatusClass(trend.article.status)"
                                        >{{ trend.article.status }}</span>
                                        <Link
                                            v-if="trend.article.status === 'published'"
                                            :href="route('news.show', trend.article.slug)"
                                            class="block text-xs text-indigo-600 hover:underline mt-0.5 truncate max-w-[180px]"
                                        >{{ trend.article.title }}</Link>
                                    </template>
                                    <span v-else class="text-xs text-gray-400">None</span>
                                    <!-- inline result after clicking generate -->
                                    <div v-if="trendResults[trend.id]" class="mt-1">
                                        <span :class="trendResults[trend.id].success ? 'text-green-600' : 'text-red-600'" class="text-xs">
                                            {{ trendResults[trend.id].message }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <button
                                        @click="generateFromTrend(trend)"
                                        :disabled="trendBusy[trend.id] || !trend.summary_cached"
                                        class="px-3 py-1.5 text-xs font-medium rounded-md transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
                                        :class="trend.article?.status === 'published'
                                            ? 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                                            : 'bg-indigo-600 text-white hover:bg-indigo-700'"
                                    >
                                        <span v-if="trendBusy[trend.id]">Queuing…</span>
                                        <span v-else-if="!trend.summary_cached">No Summary</span>
                                        <span v-else-if="trend.article?.status === 'published'">Regenerate</span>
                                        <span v-else>Generate</span>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <div v-if="trends.length === 0" class="text-center py-10 text-gray-400 text-sm">
                        No trend reports found. Run <code class="font-mono bg-gray-100 px-1 rounded">app:pull-analysis-reports</code> first.
                    </div>
                </div>
            </section>

            <!-- ── Hotspot Hexagon Articles ──────────────────────────────────── -->
            <section>
                <h2 class="text-lg font-semibold text-gray-800 mb-1">Generate from Hotspot Hexagon</h2>
                <p class="text-sm text-gray-500 mb-4">
                    Generate a standalone article about a specific location that appears as a hotspot across multiple report types. Generation is synchronous — wait a few seconds for the result.
                </p>

                <!-- City tabs -->
                <div v-if="cityList.length > 1" class="flex gap-1 border-b border-gray-200 mb-4">
                    <button
                        v-for="city in cityList"
                        :key="city"
                        @click="activeCity = city"
                        class="px-4 py-2 text-sm font-medium rounded-t-md border border-b-0 transition-colors"
                        :class="activeCity === city
                            ? 'bg-white border-gray-200 text-indigo-700 -mb-px z-10'
                            : 'bg-gray-50 border-transparent text-gray-500 hover:text-gray-700'"
                    >{{ city }}</button>
                </div>

                <div v-if="activeCity && hotspots[activeCity]?.length" class="space-y-3">
                    <div
                        v-for="hex in hotspots[activeCity]"
                        :key="hex.h3_index"
                        class="bg-white rounded-lg border shadow-sm p-4"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <p class="font-medium text-gray-800 leading-tight">
                                    {{ h3Names[hex.h3_index] || hex.h3_index }}
                                </p>
                                <p class="font-mono text-xs text-gray-400 mt-0.5">{{ hex.h3_index }} · res {{ hex.resolution }}</p>
                                <div class="flex gap-3 mt-1 text-xs text-gray-500">
                                    <span class="bg-red-50 text-red-700 px-2 py-0.5 rounded-full font-medium">{{ hex.report_count }} report type{{ hex.report_count !== 1 ? 's' : '' }}</span>
                                    <span class="text-amber-700 font-medium">⚠ {{ hex.anomaly_count.toLocaleString() }} anomalies</span>
                                    <span class="text-blue-700 font-medium">↗ {{ hex.trend_count.toLocaleString() }} trends</span>
                                </div>
                            </div>
                            <button
                                @click="generateFromHexagon(hex)"
                                :disabled="hexBusy[hex.h3_index]"
                                class="flex-shrink-0 px-3 py-1.5 text-xs font-medium rounded-md bg-indigo-600 text-white hover:bg-indigo-700 transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
                            >
                                {{ hexBusy[hex.h3_index] ? 'Generating…' : 'Generate Article' }}
                            </button>
                        </div>

                        <!-- Result panel -->
                        <div v-if="hexResults[hex.h3_index]" class="mt-3 border-t pt-3">
                            <div v-if="hexResults[hex.h3_index].success" class="text-sm text-green-700 font-medium">
                                Generation queued — article will appear in the Recent Articles list below once complete.
                            </div>
                            <div v-else class="text-sm text-red-600">
                                Error: {{ hexResults[hex.h3_index].message }}
                            </div>
                        </div>
                    </div>
                </div>
                <div v-else class="text-center py-10 text-gray-400 text-sm bg-white rounded-lg border">
                    No hotspot data available. Run <code class="font-mono bg-gray-100 px-1 rounded">app:materialize-hotspot-findings</code> first.
                </div>
            </section>

            <!-- ── Recent Articles ────────────────────────────────────────────── -->
            <section>
                <h2 class="text-lg font-semibold text-gray-800 mb-3">Recent Articles</h2>
                <div class="bg-white rounded-lg border shadow-sm overflow-hidden">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 border-b text-xs text-gray-500 uppercase tracking-wide">
                            <tr>
                                <th class="px-4 py-3 text-left">Title</th>
                                <th class="px-4 py-3 text-left">Source</th>
                                <th class="px-4 py-3 text-left">Status</th>
                                <th class="px-4 py-3 text-left">Updated</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="article in recentArticles" :key="article.id" class="hover:bg-gray-50">
                                <td class="px-4 py-3 max-w-xs truncate text-gray-800 font-medium">{{ article.title }}</td>
                                <td class="px-4 py-3 text-xs text-gray-500">
                                    {{ article.source_model_class ? article.source_model_class.split('\\').pop() : 'Hotspot' }}
                                </td>
                                <td class="px-4 py-3">
                                    <span
                                        class="inline-block px-2 py-0.5 rounded text-xs font-medium"
                                        :class="articleStatusClass(article.status)"
                                    >{{ article.status }}</span>
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-500">
                                    {{ new Date(article.updated_at).toLocaleString() }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <Link
                                        v-if="article.status === 'published'"
                                        :href="route('news.show', article.slug)"
                                        class="text-xs text-indigo-600 hover:underline"
                                    >View</Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <div v-if="recentArticles.length === 0" class="text-center py-10 text-gray-400 text-sm">
                        No articles yet.
                    </div>
                </div>
            </section>

        </div>
    </AdminLayout>
</template>
