<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import 'leaflet/dist/leaflet.css';
import * as L from 'leaflet';
import * as h3 from 'h3-js';

const page = usePage();

const props = defineProps({
    h3Index:         { type: String, required: true },
    h3Resolution:    { type: Number, required: true },
    city:            { type: String, default: '' },
    locationName:    { type: String, default: '' },
    findings:        { type: Array,  default: () => [] },
    config:          { type: Object, default: null },
    existingArticle: { type: Object, default: null },
    defaultPrompt:   { type: String, default: '' },
});

const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content;

// ── Location name ──────────────────────────────────────────────────────────────

const editableLocationName = ref(props.config?.location_name ?? props.locationName ?? '');
const suggestedName = computed(() => page.props.h3LocationNames?.[props.h3Index] ?? null);

function applySuggestion() {
    if (suggestedName.value) editableLocationName.value = suggestedName.value;
}

// ── Intro prompt ───────────────────────────────────────────────────────────────

const introPrompt      = ref(props.config?.intro_prompt ?? null);
const useDefaultPrompt = ref(!props.config?.intro_prompt);
const configStatus     = ref(props.config?.status ?? 'draft');

// ── Report type & category state ───────────────────────────────────────────────

const reportMode = ref(props.config?.included_hotspot_reports ? 'custom' : 'all');

// reportState[key] = { included: bool, catMode: 'all'|'custom', cats: string[] }
const reportState = ref({});

// expandedReports[key] = bool (whether category sub-section is visible)
const expandedReports = ref({});

// Initialize from saved config
;(function initReportState() {
    const savedMap = {};
    if (props.config?.included_hotspot_reports) {
        for (const r of props.config.included_hotspot_reports) {
            savedMap[r.key] = r.categories ?? null;
        }
    }
    const hasSaved = props.config?.included_hotspot_reports != null;
    for (const f of props.findings) {
        const saved = hasSaved ? (f.key in savedMap ? savedMap[f.key] : undefined) : undefined;
        reportState.value[f.key] = {
            included: !hasSaved || f.key in savedMap,
            catMode:  saved !== undefined && saved !== null ? 'custom' : 'all',
            cats:     saved ?? [],
        };
    }
})();

function toggleReportMode(mode) {
    reportMode.value = mode;
}

function selectAllReports() {
    for (const key of Object.keys(reportState.value)) {
        reportState.value[key].included = true;
    }
}

function selectNoReports() {
    for (const key of Object.keys(reportState.value)) {
        reportState.value[key].included = false;
    }
}

function isCategoryIncluded(key, cat) {
    const s = reportState.value[key];
    return !s || s.catMode === 'all' || s.cats.includes(cat);
}

function toggleCategory(key, cat) {
    const s = reportState.value[key];
    if (!s) return;
    if (s.catMode === 'all') {
        const finding = props.findings.find(f => f.key === key);
        s.cats = (finding?.available_categories ?? []).filter(c => c !== cat);
        s.catMode = 'custom';
    } else {
        const idx = s.cats.indexOf(cat);
        if (idx >= 0) s.cats.splice(idx, 1);
        else s.cats.push(cat);
    }
}

function selectAllCategories(key) {
    if (!reportState.value[key]) return;
    reportState.value[key].catMode = 'all';
    reportState.value[key].cats    = [];
}

function selectNoCategories(key) {
    if (!reportState.value[key]) return;
    reportState.value[key].catMode = 'custom';
    reportState.value[key].cats    = [];
}

function toggleExpand(key) {
    expandedReports.value[key] = !expandedReports.value[key];
}

function getIncludedReports() {
    if (reportMode.value === 'all') return null;
    return props.findings
        .filter(f => reportState.value[f.key]?.included)
        .map(f => ({
            key:        f.key,
            categories: reportState.value[f.key].catMode === 'all' ? null : reportState.value[f.key].cats,
        }));
}

// ── Mini map ───────────────────────────────────────────────────────────────────

const CITY_CENTERS = {
    'Boston':               { lat: 42.3601, lng: -71.0589, zoom: 14 },
    'Cambridge':            { lat: 42.3736, lng: -71.1097, zoom: 15 },
    'Everett':              { lat: 42.4084, lng: -71.0537, zoom: 15 },
    'Chicago':              { lat: 41.8781, lng: -87.6298, zoom: 13 },
    'San Francisco':        { lat: 37.7749, lng: -122.4194, zoom: 14 },
    'Seattle':              { lat: 47.6062, lng: -122.3321, zoom: 14 },
    'Montgomery County MD': { lat: 39.1547, lng: -77.2405, zoom: 12 },
};

const miniMapRef = ref(null);
let miniMap = null;

onMounted(() => {
    if (!miniMapRef.value) return;
    try {
        const boundary = h3.cellToBoundary(props.h3Index);
        const latlngs  = boundary.map(([lat, lng]) => [lat, lng]);
        const center   = latlngs.reduce((acc, [lat, lng]) => [acc[0] + lat / latlngs.length, acc[1] + lng / latlngs.length], [0, 0]);

        const fallback = CITY_CENTERS[props.city] ?? { lat: 42.3601, lng: -71.0589, zoom: 14 };
        miniMap = L.map(miniMapRef.value, { zoomControl: false, attributionControl: false })
            .setView(center[0] ? center : [fallback.lat, fallback.lng], fallback.zoom);

        L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', { maxZoom: 19 }).addTo(miniMap);
        L.polygon(latlngs, { color: '#4f46e5', fillColor: '#818cf8', fillOpacity: 0.5, weight: 2 }).addTo(miniMap);
    } catch (_) { /* invalid h3 */ }
});

onBeforeUnmount(() => { if (miniMap) { miniMap.remove(); miniMap = null; } });

// ── Token estimation ───────────────────────────────────────────────────────────

const tokenCount    = ref(null);
const tokenBusy     = ref(false);
const tokenError    = ref(null);
const promptPreview = ref(null); // { system, user }
const showPrompt    = ref(false);

async function estimateTokens() {
    tokenBusy.value     = true;
    tokenError.value    = null;
    tokenCount.value    = null;
    promptPreview.value = null;
    showPrompt.value    = false;
    try {
        const res = await fetch(route('admin.news-articles.estimate-tokens-preview'), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
            body: JSON.stringify({
                source_type:              'hotspot',
                h3_index:                 props.h3Index,
                h3_resolution:            props.h3Resolution,
                intro_prompt:             useDefaultPrompt.value ? null : (introPrompt.value || null),
                included_hotspot_reports: getIncludedReports(),
            }),
        });
        const data = await res.json();
        if (data.error) {
            tokenError.value = data.error;
        } else {
            tokenCount.value    = data.input_tokens;
            promptPreview.value = { system: data.system_prompt, user: data.user_prompt };
        }
    } catch (e) {
        tokenError.value = e.message;
    } finally {
        tokenBusy.value = false;
    }
}

// ── Save & generate ────────────────────────────────────────────────────────────

const saving    = ref(false);
const saveMsg   = ref(null);
const saveMsgOk = ref(true);
const configId  = ref(props.config?.id ?? null);

async function saveConfig() {
    saving.value  = true;
    saveMsg.value = null;
    try {
        const res = await fetch(route('admin.news-articles.hotspots.save-config', props.h3Index), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
            body: JSON.stringify({
                h3_resolution:            props.h3Resolution,
                location_name:            editableLocationName.value || null,
                city:                     props.city || null,
                intro_prompt:             useDefaultPrompt.value ? null : (introPrompt.value || null),
                included_hotspot_reports: getIncludedReports(),
                status:                   configStatus.value,
            }),
        });
        const data = await res.json();
        saveMsgOk.value = data.success;
        saveMsg.value   = data.message;
        if (data.config_id) configId.value = data.config_id;
    } catch (e) {
        saveMsgOk.value = false;
        saveMsg.value   = e.message;
    } finally {
        saving.value = false;
    }
}

const generating  = ref(false);
const generateMsg = ref(null);
const generateOk  = ref(true);

async function saveAndGenerate() {
    await saveConfig();
    if (!saveMsgOk.value) return;
    if (!configId.value) return;
    generating.value  = true;
    generateMsg.value = null;
    try {
        const res = await fetch(route('admin.news-articles.configs.generate', configId.value), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
            body: JSON.stringify({}),
        });
        const data = await res.json();
        generateOk.value  = data.success;
        generateMsg.value = data.message;
    } catch (e) {
        generateOk.value  = false;
        generateMsg.value = e.message;
    } finally {
        generating.value = false;
    }
}
</script>

<template>
    <AdminLayout>
        <Head :title="`Configure Hotspot: ${locationName || h3Index}`" />

        <div class="max-w-4xl mx-auto space-y-6">

            <!-- Breadcrumb -->
            <div class="flex items-center gap-2 text-sm text-gray-500">
                <Link :href="route('admin.news-articles.index')" class="hover:text-indigo-600">News Article Generator</Link>
                <span>›</span>
                <span class="text-gray-800 font-medium">Hotspot: {{ locationName || h3Index }}</span>
            </div>

            <!-- Header with mini map -->
            <div class="bg-white rounded-lg border shadow-sm overflow-hidden">
                <div class="flex">
                    <div ref="miniMapRef" class="w-48 h-48 flex-shrink-0 bg-gray-100"></div>
                    <div class="p-5 flex-1">
                        <div class="mb-3">
                            <label class="block text-xs text-gray-500 mb-1">Location Name</label>
                            <div class="flex gap-2">
                                <input
                                    type="text"
                                    v-model="editableLocationName"
                                    class="text-sm border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 flex-1"
                                    placeholder="Enter a human-readable name…"
                                >
                                <button
                                    v-if="suggestedName && editableLocationName !== suggestedName"
                                    @click="applySuggestion"
                                    class="text-xs px-2 py-1 rounded border border-indigo-300 text-indigo-600 hover:bg-indigo-50 shrink-0 transition-colors"
                                    :title="`Use suggested name: ${suggestedName}`"
                                >Use "{{ suggestedName }}"</button>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-2 text-xs text-gray-500">
                            <span class="bg-gray-100 px-2 py-0.5 rounded">{{ city }}</span>
                            <span class="bg-gray-100 px-2 py-0.5 rounded font-mono">res {{ h3Resolution }}</span>
                            <span class="bg-gray-100 px-2 py-0.5 rounded font-mono">{{ h3Index }}</span>
                        </div>
                        <div class="flex gap-4 mt-2 text-xs text-gray-500">
                            <span><span class="text-red-600 font-semibold">{{ findings.length }}</span> report type{{ findings.length !== 1 ? 's' : '' }}</span>
                            <span><span class="text-amber-600 font-semibold">⚠ {{ findings.reduce((s, f) => s + f.anomaly_count, 0) }}</span> anomalies</span>
                            <span><span class="text-blue-600 font-semibold">↗ {{ findings.reduce((s, f) => s + f.trend_count, 0) }}</span> trends</span>
                        </div>
                        <div v-if="existingArticle" class="mt-3">
                            <span class="inline-block px-2 py-0.5 rounded text-xs font-medium" :class="{
                                'text-green-700 bg-green-50': existingArticle.status === 'published',
                                'text-amber-700 bg-amber-50': existingArticle.status === 'draft' || existingArticle.status === 'generating',
                                'text-red-700 bg-red-50':     existingArticle.status === 'error',
                            }">{{ existingArticle.status }}</span>
                            <Link v-if="existingArticle.status === 'published'" :href="route('news.show', existingArticle.slug)" class="ml-2 text-xs text-indigo-600 hover:underline">View article</Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Intro Prompt -->
            <div class="bg-white rounded-lg border shadow-sm p-5">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="font-semibold text-gray-800">Intro Prompt (System Prompt)</h2>
                    <label class="flex items-center gap-2 text-xs text-gray-600 cursor-pointer">
                        <input type="checkbox" v-model="useDefaultPrompt" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        Use default
                    </label>
                </div>
                <textarea
                    v-if="!useDefaultPrompt"
                    v-model="introPrompt"
                    rows="8"
                    class="w-full text-sm font-mono border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                    placeholder="Enter a custom system prompt for this article..."
                ></textarea>
                <div v-else class="text-xs text-gray-500 bg-gray-50 rounded border p-3 font-mono whitespace-pre-wrap max-h-32 overflow-y-auto">{{ defaultPrompt }}</div>
            </div>

            <!-- Report Types -->
            <div class="bg-white rounded-lg border shadow-sm p-5">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="font-semibold text-gray-800">Report Types to Include</h2>
                    <div class="flex gap-2">
                        <button @click="toggleReportMode('all')" class="text-xs px-2 py-1 rounded border transition-colors" :class="reportMode === 'all' ? 'bg-indigo-600 text-white border-indigo-600' : 'hover:bg-gray-50'">All</button>
                        <button @click="toggleReportMode('custom')" class="text-xs px-2 py-1 rounded border transition-colors" :class="reportMode === 'custom' ? 'bg-indigo-600 text-white border-indigo-600' : 'hover:bg-gray-50'">Custom</button>
                    </div>
                </div>

                <p v-if="reportMode === 'all'" class="text-sm text-gray-500 italic">All {{ findings.length }} report type{{ findings.length !== 1 ? 's' : '' }} will be included.</p>

                <div v-else class="space-y-2">
                    <!-- Select all / none for reports -->
                    <div class="flex gap-3 text-xs mb-3">
                        <button @click="selectAllReports" class="text-indigo-600 hover:underline">Select all</button>
                        <span class="text-gray-300">·</span>
                        <button @click="selectNoReports" class="text-indigo-600 hover:underline">Select none</button>
                    </div>

                    <p v-if="findings.length === 0" class="text-xs text-gray-400 italic">No findings found.</p>

                    <div
                        v-for="finding in findings" :key="finding.key"
                        class="border rounded-md overflow-hidden transition-opacity"
                        :class="{ 'opacity-50': !reportState[finding.key]?.included }"
                    >
                        <!-- Report row -->
                        <div class="flex items-center gap-3 p-2.5 hover:bg-gray-50">
                            <input
                                type="checkbox"
                                :checked="reportState[finding.key]?.included"
                                @change="reportState[finding.key].included = !reportState[finding.key].included"
                                class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 flex-shrink-0"
                            >
                            <div class="flex-1 min-w-0">
                                <span class="font-medium text-gray-800 text-sm">{{ finding.type_label }}</span>
                                <div class="flex gap-3 mt-0.5 text-xs text-gray-500">
                                    <span class="text-amber-600">⚠ {{ finding.anomaly_count }} anomalies</span>
                                    <span class="text-blue-600">↗ {{ finding.trend_count }} trends</span>
                                </div>
                            </div>
                            <!-- Categories toggle -->
                            <button
                                v-if="finding.available_categories?.length"
                                @click="toggleExpand(finding.key)"
                                class="text-xs text-indigo-600 hover:underline px-2 shrink-0"
                            >
                                <template v-if="!expandedReports[finding.key]">
                                    Categories
                                    <span v-if="reportState[finding.key]?.catMode === 'custom'" class="text-gray-400 ml-0.5">({{ reportState[finding.key].cats.length }}/{{ finding.available_categories.length }})</span>
                                </template>
                                <template v-else>Hide</template>
                            </button>
                        </div>

                        <!-- Category sub-section -->
                        <div
                            v-if="expandedReports[finding.key] && finding.available_categories?.length"
                            class="border-t bg-gray-50 px-3 py-2.5"
                        >
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs font-medium text-gray-600">Categories</span>
                                <div class="flex gap-2 text-xs">
                                    <button @click="selectAllCategories(finding.key)" class="text-indigo-600 hover:underline">All</button>
                                    <span class="text-gray-300">·</span>
                                    <button @click="selectNoCategories(finding.key)" class="text-indigo-600 hover:underline">None</button>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-x-4 gap-y-1">
                                <label
                                    v-for="cat in finding.available_categories" :key="cat"
                                    class="flex items-center gap-2 text-xs cursor-pointer p-1 rounded hover:bg-gray-100 select-none"
                                >
                                    <input
                                        type="checkbox"
                                        :checked="isCategoryIncluded(finding.key, cat)"
                                        @change="toggleCategory(finding.key, cat)"
                                        class="h-3.5 w-3.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 flex-shrink-0"
                                    >
                                    <span class="truncate">{{ cat }}</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status -->
            <div class="bg-white rounded-lg border shadow-sm p-5">
                <h2 class="font-semibold text-gray-800 mb-3">Config Status</h2>
                <div class="flex gap-4">
                    <label v-for="opt in [['draft','Draft'],['finalized','Finalized'],['active_auto','Active for Auto-Run']]" :key="opt[0]" class="flex items-center gap-2 text-sm cursor-pointer">
                        <input type="radio" v-model="configStatus" :value="opt[0]" class="border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        {{ opt[1] }}
                    </label>
                </div>
            </div>

            <!-- Prompt Preview (shown after estimation) -->
            <div v-if="promptPreview" class="bg-white rounded-lg border shadow-sm overflow-hidden">
                <button
                    @click="showPrompt = !showPrompt"
                    class="w-full flex items-center justify-between px-5 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors"
                >
                    <span>Prompt Preview <span class="text-xs font-normal text-gray-400 ml-1">({{ tokenCount?.toLocaleString() }} tokens)</span></span>
                    <span class="text-gray-400 text-xs">{{ showPrompt ? '▲ hide' : '▼ show' }}</span>
                </button>
                <div v-if="showPrompt" class="border-t divide-y divide-gray-100">
                    <div class="px-5 py-3">
                        <div class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">System Prompt</div>
                        <pre class="text-xs text-gray-700 whitespace-pre-wrap bg-gray-50 rounded p-3 max-h-48 overflow-y-auto font-mono">{{ promptPreview.system }}</pre>
                    </div>
                    <div class="px-5 py-3">
                        <div class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">User Prompt</div>
                        <pre class="text-xs text-gray-700 whitespace-pre-wrap bg-gray-50 rounded p-3 max-h-96 overflow-y-auto font-mono">{{ promptPreview.user }}</pre>
                    </div>
                </div>
            </div>

            <!-- Action Bar -->
            <div class="sticky bottom-4 bg-white border rounded-lg shadow-lg p-4 flex items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <button @click="estimateTokens" :disabled="tokenBusy" class="px-4 py-2 text-sm font-medium rounded-md border border-gray-300 hover:bg-gray-50 disabled:opacity-50 transition-colors">
                        {{ tokenBusy ? 'Estimating…' : 'Estimate Tokens' }}
                    </button>
                    <span v-if="tokenCount !== null" class="text-sm font-semibold text-gray-700">{{ tokenCount.toLocaleString() }} tokens</span>
                    <span v-if="tokenError" class="text-xs text-red-600">Error: {{ tokenError }}</span>
                </div>

                <div class="flex items-center gap-3">
                    <span v-if="saveMsg"    class="text-xs" :class="saveMsgOk  ? 'text-green-600' : 'text-red-600'">{{ saveMsg }}</span>
                    <span v-if="generateMsg" class="text-xs" :class="generateOk ? 'text-green-600' : 'text-red-600'">{{ generateMsg }}</span>
                    <button @click="saveConfig" :disabled="saving" class="px-4 py-2 text-sm font-medium rounded-md bg-white border border-gray-300 hover:bg-gray-50 disabled:opacity-50 transition-colors">
                        {{ saving ? 'Saving…' : 'Save Configuration' }}
                    </button>
                    <button @click="saveAndGenerate" :disabled="saving || generating" class="px-4 py-2 text-sm font-medium rounded-md bg-indigo-600 text-white hover:bg-indigo-700 disabled:opacity-50 transition-colors">
                        {{ generating ? 'Queuing…' : 'Save & Generate Now' }}
                    </button>
                </div>
            </div>

        </div>
    </AdminLayout>
</template>
