<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch, nextTick } from 'vue';
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import 'leaflet/dist/leaflet.css';
import * as L from 'leaflet';
import * as h3 from 'h3-js';

const props = defineProps({
    trends:         { type: Array,  default: () => [] },
    hotspots:       { type: Object, default: () => ({}) },
    recentArticles: { type: Array,  default: () => [] },
});

const page = usePage();
const h3Names = computed(() => page.props.h3LocationNames ?? {});

// ── Trend filters (client-side) ───────────────────────────────────────────────

const filterCity       = ref('');
const filterModel      = ref('');
const filterCategory   = ref('');
const filterResolution = ref(null);
const filterType       = ref('all');

const CITY_ORDER = ['Boston', 'Cambridge', 'Everett', 'Chicago', 'San Francisco', 'Seattle', 'Montgomery County MD'];

const availableCities = computed(() => {
    const cities = [...new Set(props.trends.map(t => t.city))];
    return cities.sort((a, b) => {
        const ai = CITY_ORDER.indexOf(a), bi = CITY_ORDER.indexOf(b);
        return (ai === -1 ? 999 : ai) - (bi === -1 ? 999 : bi);
    });
});

const availableModels = computed(() => {
    const seen = new Set();
    return props.trends
        .filter(t => { if (seen.has(t.model_key)) return false; seen.add(t.model_key); return true; })
        .map(t => ({ key: t.model_key, name: t.model_name }))
        .sort((a, b) => a.name.localeCompare(b.name));
});

const availableResolutions = computed(() =>
    [...new Set(props.trends.map(t => t.h3_resolution))].sort((a, b) => a - b)
);

const filteredTrends = computed(() =>
    props.trends.filter(t => {
        if (filterCity.value       && t.city        !== filterCity.value)       return false;
        if (filterModel.value      && t.model_key   !== filterModel.value)      return false;
        if (filterCategory.value   && t.column_label !== filterCategory.value)  return false;
        if (filterResolution.value !== null && t.h3_resolution !== filterResolution.value) return false;
        if (filterType.value === 'anomaly' && (t.summary?.anomaly_count ?? 0) === 0) return false;
        if (filterType.value === 'trend'   && (t.summary?.trend_count   ?? 0) === 0) return false;
        return true;
    })
);

const availableCategories = computed(() => {
    const pool = filterModel.value
        ? props.trends.filter(t => t.model_key === filterModel.value)
        : props.trends;
    return [...new Set(pool.map(t => t.column_label))].sort();
});

watch(filterModel, () => { filterCategory.value = ''; });

// ── Trend pagination ──────────────────────────────────────────────────────────

const PAGE_SIZE   = 25;
const currentPage = ref(1);

watch([filterCity, filterModel, filterCategory, filterResolution, filterType], () => {
    currentPage.value = 1;
});

const totalPages = computed(() => Math.max(1, Math.ceil(filteredTrends.value.length / PAGE_SIZE)));

const pagedTrends = computed(() => {
    const start = (currentPage.value - 1) * PAGE_SIZE;
    return filteredTrends.value.slice(start, start + PAGE_SIZE);
});

const pageRange = computed(() => {
    const cur = currentPage.value, last = totalPages.value;
    if (last <= 7) return Array.from({ length: last }, (_, i) => i + 1);
    const pages = new Set([1, last, cur, cur - 1, cur + 1, cur - 2, cur + 2]);
    return [...pages].filter(p => p >= 1 && p <= last).sort((a, b) => a - b);
});

// ── Hotspot map ───────────────────────────────────────────────────────────────

const CITY_CENTERS = {
    'Boston':               { lat: 42.3601, lng: -71.0589, zoom: 12 },
    'Cambridge':            { lat: 42.3736, lng: -71.1097, zoom: 13 },
    'Everett':              { lat: 42.4084, lng: -71.0537, zoom: 14 },
    'Chicago':              { lat: 41.8781, lng: -87.6298, zoom: 11 },
    'San Francisco':        { lat: 37.7749, lng: -122.4194, zoom: 12 },
    'Seattle':              { lat: 47.6062, lng: -122.3321, zoom: 12 },
    'Montgomery County MD': { lat: 39.1547, lng: -77.2405, zoom: 10 },
};

const cityList       = computed(() => Object.keys(props.hotspots).sort((a, b) => {
    const ai = CITY_ORDER.indexOf(a), bi = CITY_ORDER.indexOf(b);
    return (ai === -1 ? 999 : ai) - (bi === -1 ? 999 : bi);
}));
const activeCity     = ref(cityList.value[0] ?? null);
const activeResolution = ref(null);

// When city changes, pick first available resolution
watch(activeCity, (city) => {
    if (!city) return;
    const resolutions = Object.keys(props.hotspots[city] ?? {}).sort();
    activeResolution.value = resolutions[0] ? parseInt(resolutions[0]) : null;
}, { immediate: true });

const availableResolutionsForCity = computed(() => {
    if (!activeCity.value) return [];
    return Object.keys(props.hotspots[activeCity.value] ?? {}).map(Number).sort((a, b) => a - b);
});

const activeHexagons = computed(() => {
    if (!activeCity.value || activeResolution.value === null) return [];
    return props.hotspots[activeCity.value]?.[String(activeResolution.value)] ?? [];
});

const mapContainerRef = ref(null);
let leafletMap = null;
let hexLayers  = [];

function hexColor(hex) {
    if (hex.report_count >= 4) return '#ef4444';
    if (hex.report_count >= 2) return '#f97316';
    return '#eab308';
}

function buildMap() {
    if (!mapContainerRef.value) return;
    if (leafletMap) { leafletMap.remove(); leafletMap = null; }

    const city   = activeCity.value;
    const center = CITY_CENTERS[city] ?? { lat: 42.3601, lng: -71.0589, zoom: 12 };

    leafletMap = L.map(mapContainerRef.value).setView([center.lat, center.lng], center.zoom);
    L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
        attribution: '© OpenStreetMap © CARTO',
        maxZoom: 19,
    }).addTo(leafletMap);

    renderHexagons();
    leafletMap.invalidateSize();
}

function renderHexagons() {
    if (!leafletMap) return;
    hexLayers.forEach(l => l.remove());
    hexLayers = [];

    for (const hex of activeHexagons.value) {
        try {
            const boundary = h3.cellToBoundary(hex.h3_index);
            const latlngs  = boundary.map(([lat, lng]) => [lat, lng]);
            const poly     = L.polygon(latlngs, {
                color:       hexColor(hex),
                fillColor:   hexColor(hex),
                fillOpacity: 0.5,
                weight:      1.5,
            }).addTo(leafletMap);

            const name = h3Names.value[hex.h3_index] ?? hex.h3_index;
            const configUrl = route('admin.news-articles.hotspots.configure', { h3: hex.h3_index }) + `?resolution=${activeResolution.value}`;

            poly.bindPopup(`
                <div class="text-sm">
                    <p class="font-semibold">${name}</p>
                    <p class="text-xs text-gray-500 font-mono">${hex.h3_index}</p>
                    <div class="flex gap-2 mt-1 text-xs">
                        <span class="text-red-600 font-medium">${hex.report_count} report type${hex.report_count !== 1 ? 's' : ''}</span>
                        <span class="text-amber-600">⚠ ${hex.anomaly_count}</span>
                        <span class="text-blue-600">↗ ${hex.trend_count}</span>
                    </div>
                    <a href="${configUrl}" class="block mt-2 text-xs text-indigo-600 hover:underline font-medium">Configure →</a>
                </div>
            `);

            hexLayers.push(poly);
        } catch (_) { /* skip invalid h3 index */ }
    }
}

watch([activeCity, activeResolution], async () => {
    await nextTick();
    if (leafletMap) {
        const city   = activeCity.value;
        const center = CITY_CENTERS[city] ?? { lat: 42.3601, lng: -71.0589, zoom: 12 };
        leafletMap.setView([center.lat, center.lng], center.zoom);
        renderHexagons();
    }
});

onMounted(() => {
    if (mapContainerRef.value) buildMap();
});

onBeforeUnmount(() => {
    if (leafletMap) { leafletMap.remove(); leafletMap = null; }
});

// ── Helpers ───────────────────────────────────────────────────────────────────

const articleStatusClass = (status) => ({
    'text-green-700 bg-green-50':   status === 'published',
    'text-amber-700 bg-amber-50':   status === 'generating' || status === 'draft',
    'text-red-700 bg-red-50':       status === 'error',
    'text-gray-500 bg-gray-50':     !status,
});

const configStatusClass = (status) => ({
    'text-green-700 bg-green-100':  status === 'active_auto',
    'text-indigo-700 bg-indigo-50': status === 'finalized',
    'text-gray-500 bg-gray-100':    status === 'draft',
});
</script>

<template>
    <AdminLayout>
        <Head title="News Article Generator" />

        <div class="space-y-10">

            <div>
                <h1 class="text-2xl font-bold text-gray-900">News Article Generator</h1>
                <p class="mt-1 text-sm text-gray-500">Configure and generate AI news articles from trend analysis reports or hotspot hexagons.</p>
            </div>

            <!-- ── Trend-Based Articles ───────────────────────────────────────── -->
            <section>
                <h2 class="text-lg font-semibold text-gray-800 mb-3">Trend Analysis Reports</h2>

                <!-- Filters -->
                <div class="bg-white border rounded-lg p-3 mb-3 space-y-2">
                    <div class="flex flex-wrap gap-2 items-center">
                        <!-- Type -->
                        <div class="flex rounded border overflow-hidden text-xs">
                            <button @click="filterType = 'all'"     :class="filterType === 'all'     ? 'bg-indigo-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50'" class="px-3 py-1.5 transition-colors">All</button>
                            <button @click="filterType = 'anomaly'" :class="filterType === 'anomaly' ? 'bg-amber-500 text-white' : 'bg-white text-gray-600 hover:bg-gray-50'" class="px-3 py-1.5 border-l transition-colors">Anomalies</button>
                            <button @click="filterType = 'trend'"   :class="filterType === 'trend'   ? 'bg-blue-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50'" class="px-3 py-1.5 border-l transition-colors">Trends</button>
                        </div>

                        <!-- City -->
                        <select v-model="filterCity" class="text-xs border-gray-300 rounded shadow-sm focus:ring-indigo-500 focus:border-indigo-500 py-1.5">
                            <option value="">All cities</option>
                            <option v-for="c in availableCities" :key="c" :value="c">{{ c }}</option>
                        </select>

                        <!-- Model -->
                        <select v-model="filterModel" class="text-xs border-gray-300 rounded shadow-sm focus:ring-indigo-500 focus:border-indigo-500 py-1.5">
                            <option value="">All data sources</option>
                            <option v-for="m in availableModels" :key="m.key" :value="m.key">{{ m.name }}</option>
                        </select>

                        <!-- Category -->
                        <select v-model="filterCategory" class="text-xs border-gray-300 rounded shadow-sm focus:ring-indigo-500 focus:border-indigo-500 py-1.5">
                            <option value="">All categories</option>
                            <option v-for="cat in availableCategories" :key="cat" :value="cat">{{ cat }}</option>
                        </select>

                        <span class="text-xs text-gray-400 ml-auto">{{ filteredTrends.length }} report{{ filteredTrends.length !== 1 ? 's' : '' }}</span>
                        <span v-if="totalPages > 1" class="text-xs text-gray-400">· page {{ currentPage }}/{{ totalPages }}</span>
                    </div>

                    <!-- Resolution pills -->
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <span class="text-xs text-gray-400 mr-1">Resolution:</span>
                        <button @click="filterResolution = null" class="px-2 py-0.5 text-xs rounded-full border transition-colors" :class="filterResolution === null ? 'bg-gray-700 text-white border-gray-700' : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50'">All</button>
                        <button v-for="res in availableResolutions" :key="res" @click="filterResolution = filterResolution === res ? null : res" class="px-2 py-0.5 text-xs rounded-full border font-mono transition-colors" :class="filterResolution === res ? 'bg-gray-700 text-white border-gray-700' : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50'">res {{ res }}</button>
                    </div>
                </div>

                <div class="bg-white rounded-lg border shadow-sm overflow-hidden">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 border-b text-xs text-gray-500 uppercase tracking-wide">
                            <tr>
                                <th class="px-4 py-3 text-left">Report</th>
                                <th class="px-4 py-3 text-right">Findings</th>
                                <th class="px-4 py-3 text-left">Top Categories</th>
                                <th class="px-4 py-3 text-left">Config</th>
                                <th class="px-4 py-3 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="trend in pagedTrends" :key="trend.id" class="hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <p class="font-medium text-gray-800 leading-tight">{{ trend.title }}</p>
                                    <p class="text-xs text-gray-400 mt-0.5">{{ trend.city }} · res {{ trend.h3_resolution }}</p>
                                </td>
                                <td class="px-4 py-3 text-right tabular-nums">
                                    <div v-if="trend.summary" class="flex flex-col items-end gap-0.5">
                                        <span class="text-xs text-amber-600">⚠ {{ trend.summary.anomaly_count }}</span>
                                        <span class="text-xs text-blue-600">↗ {{ trend.summary.trend_count }}</span>
                                    </div>
                                    <span v-else class="text-gray-300 text-xs">—</span>
                                </td>
                                <td class="px-4 py-3 max-w-xs">
                                    <div v-if="trend.summary?.top_categories?.length" class="flex flex-wrap gap-1">
                                        <span v-for="cat in trend.summary.top_categories.slice(0, 3)" :key="cat" class="text-xs bg-indigo-50 text-indigo-700 px-2 py-0.5 rounded-full">{{ cat }}</span>
                                    </div>
                                    <span v-else class="text-xs text-gray-300">No summary</span>
                                </td>
                                <td class="px-4 py-3">
                                    <span v-if="trend.config_status" class="inline-block px-2 py-0.5 rounded text-xs font-medium" :class="configStatusClass(trend.config_status)">{{ trend.config_status }}</span>
                                    <span v-else class="text-xs text-gray-400">—</span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <Link
                                        :href="route('admin.news-articles.trends.configure', trend.id)"
                                        class="px-3 py-1.5 text-xs font-medium rounded-md bg-indigo-600 text-white hover:bg-indigo-700 transition-colors inline-block"
                                    >Configure →</Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <div v-if="filteredTrends.length === 0" class="text-center py-10 text-gray-400 text-sm">
                        No trend reports match the current filters.
                    </div>

                    <!-- Pagination -->
                    <div v-if="totalPages > 1" class="flex items-center justify-between px-4 py-3 border-t bg-gray-50 text-sm text-gray-600">
                        <span class="text-xs">Page {{ currentPage }} of {{ totalPages }} ({{ filteredTrends.length }} total)</span>
                        <div class="flex gap-1 items-center">
                            <button @click="currentPage--" :disabled="currentPage <= 1" class="px-2.5 py-1 rounded border text-xs font-medium disabled:opacity-40 disabled:cursor-not-allowed hover:bg-gray-100">← Prev</button>
                            <template v-for="(p, idx) in pageRange" :key="p">
                                <span v-if="idx > 0 && p > pageRange[idx - 1] + 1" class="px-1 text-gray-400">…</span>
                                <button @click="currentPage = p" class="px-2.5 py-1 rounded border text-xs font-medium transition-colors" :class="p === currentPage ? 'bg-indigo-600 text-white border-indigo-600' : 'hover:bg-gray-100'">{{ p }}</button>
                            </template>
                            <button @click="currentPage++" :disabled="currentPage >= totalPages" class="px-2.5 py-1 rounded border text-xs font-medium disabled:opacity-40 disabled:cursor-not-allowed hover:bg-gray-100">Next →</button>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ── Hotspot Hexagon Map ────────────────────────────────────────── -->
            <section>
                <h2 class="text-lg font-semibold text-gray-800 mb-1">Hotspot Hexagons</h2>
                <p class="text-sm text-gray-500 mb-3">Select a hexagon on the map to configure article generation for that location.</p>

                <div v-if="cityList.length === 0" class="text-center py-10 text-gray-400 text-sm bg-white rounded-lg border">
                    No hotspot data available. Run <code class="font-mono bg-gray-100 px-1 rounded">app:materialize-hotspot-findings</code> first.
                </div>

                <div v-else>
                    <!-- City tabs -->
                    <div class="flex gap-1 border-b border-gray-200 mb-0">
                        <button
                            v-for="city in cityList" :key="city"
                            @click="activeCity = city"
                            class="px-4 py-2 text-sm font-medium rounded-t-md border border-b-0 transition-colors"
                            :class="activeCity === city ? 'bg-white border-gray-200 text-indigo-700 -mb-px z-10' : 'bg-gray-50 border-transparent text-gray-500 hover:text-gray-700'"
                        >{{ city }}</button>
                    </div>

                    <div class="bg-white border border-t-0 rounded-b-lg rounded-tr-lg shadow-sm overflow-hidden">
                        <!-- Resolution subtabs -->
                        <div v-if="availableResolutionsForCity.length > 1" class="flex gap-1 px-3 pt-3 border-b">
                            <button
                                v-for="res in availableResolutionsForCity" :key="res"
                                @click="activeResolution = res"
                                class="px-3 py-1 text-xs font-mono rounded-t border border-b-0 transition-colors"
                                :class="activeResolution === res ? 'bg-gray-100 text-gray-900 border-gray-300' : 'border-transparent text-gray-500 hover:text-gray-700'"
                            >res {{ res }}</button>
                        </div>

                        <!-- Map -->
                        <div ref="mapContainerRef" class="w-full" style="height: 420px;"></div>

                        <div class="px-4 py-2 border-t bg-gray-50 text-xs text-gray-500">
                            {{ activeHexagons.length }} hexagon{{ activeHexagons.length !== 1 ? 's' : '' }} shown · click a hexagon to configure article generation
                        </div>
                    </div>
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
                                <td class="px-4 py-3 text-xs text-gray-500">{{ article.source_model_class ? article.source_model_class.split('\\').pop() : 'Hotspot' }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-block px-2 py-0.5 rounded text-xs font-medium" :class="articleStatusClass(article.status)">{{ article.status }}</span>
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-500">{{ new Date(article.updated_at).toLocaleString() }}</td>
                                <td class="px-4 py-3 text-right">
                                    <Link v-if="article.status === 'published'" :href="route('news.show', article.slug)" class="text-xs text-indigo-600 hover:underline">View</Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <div v-if="recentArticles.length === 0" class="text-center py-10 text-gray-400 text-sm">No articles yet.</div>
                </div>
            </section>

        </div>
    </AdminLayout>
</template>
