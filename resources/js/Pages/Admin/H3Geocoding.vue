<script setup>
import { ref, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import * as h3 from 'h3-js';

const props = defineProps({
    hexagons: Array, // [{ h3_index, h3_resolution, city, location_name }]
});

// ---- selection state ----
// Use a plain object (h3_index → true) so Vue tracks it reactively
const selected = ref({});

const isSelected   = (idx) => !!selected.value[idx];
const selectedList = computed(() => props.hexagons.filter(h => selected.value[h.h3_index]));
const selectedCount = computed(() => selectedList.value.length);
const selectedNew   = computed(() => selectedList.value.filter(h => !effectiveName(h)).length);

// ---- geocoding results overlay ----
// Updated names from geocoding are overlaid here without mutating props
const geocodedNames = ref({}); // h3_index → name
const effectiveName = (h) => geocodedNames.value[h.h3_index] ?? h.location_name;

// ---- grouping ----
const grouped = computed(() => {
    const out = {}; // city → resolution → [hexagons]
    for (const h of props.hexagons) {
        out[h.city] ??= {};
        out[h.city][h.h3_resolution] ??= [];
        out[h.city][h.h3_resolution].push(h);
    }
    return out;
});

const cities = computed(() => Object.keys(grouped.value).sort((a, b) =>
    a === 'Boston' ? -1 : b === 'Boston' ? 1 : a.localeCompare(b)
));

const totalCount  = computed(() => props.hexagons.length);
const namedCount  = computed(() => props.hexagons.filter(h => effectiveName(h)).length);
const unnamedCount = computed(() => totalCount.value - namedCount.value);

// ---- selection helpers ----
function selectHexes(hexes) {
    const next = { ...selected.value };
    for (const h of hexes) next[h.h3_index] = true;
    selected.value = next;
}
function deselectHexes(hexes) {
    const next = { ...selected.value };
    for (const h of hexes) delete next[h.h3_index];
    selected.value = next;
}
function toggleGroup(hexes) {
    const allSelected = hexes.every(h => selected.value[h.h3_index]);
    allSelected ? deselectHexes(hexes) : selectHexes(hexes);
}

function selectAllUnnamed() {
    selectHexes(props.hexagons.filter(h => !effectiveName(h)));
}
function selectTest5(hexes = null) {
    const pool = (hexes ?? props.hexagons).filter(h => !effectiveName(h) && !selected.value[h.h3_index]);
    selectHexes(pool.slice(0, 5));
}
function clearAll() {
    selected.value = {};
}

function groupSelected(hexes) {
    return hexes.filter(h => selected.value[h.h3_index]).length;
}
function groupAllSelected(hexes) {
    return hexes.length > 0 && hexes.every(h => selected.value[h.h3_index]);
}

// ---- city collapse ----
const collapsed = ref({});
function toggleCity(city) {
    collapsed.value = { ...collapsed.value, [city]: !collapsed.value[city] };
}

// ---- geocoding ----
const geocoding  = ref(false);
const progress   = ref({ done: 0, total: 0 });
const results    = ref([]);
const geocodeError = ref(null);

const BATCH_SIZE = 20;

async function runGeocode() {
    if (!selectedCount.value || geocoding.value) return;

    geocoding.value  = true;
    geocodeError.value = null;
    results.value    = [];
    progress.value   = { done: 0, total: selectedCount.value };

    const toProcess = selectedList.value.map(h => {
        const [lat, lng] = h3.cellToLatLng(h.h3_index);
        return { h3_index: h.h3_index, lat, lng, resolution: h.h3_resolution };
    });

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

    for (let i = 0; i < toProcess.length; i += BATCH_SIZE) {
        const batch = toProcess.slice(i, i + BATCH_SIZE);
        try {
            const resp = await fetch(route('admin.h3-geocoding.geocode'), {
                method:  'POST',
                headers: {
                    'Content-Type':  'application/json',
                    'X-CSRF-TOKEN':  csrfToken,
                    'Accept':        'application/json',
                },
                body: JSON.stringify({ hexagons: batch }),
            });

            if (!resp.ok) throw new Error(`HTTP ${resp.status}`);

            const data = await resp.json();
            for (const r of data.results) {
                results.value.push(r);
                if (r.location_name) geocodedNames.value[r.h3_index] = r.location_name;
            }
        } catch (e) {
            geocodeError.value = `Batch failed: ${e.message}`;
            for (const item of batch) {
                results.value.push({ h3_index: item.h3_index, location_name: null, status: 'error' });
            }
        }

        progress.value.done = Math.min(i + BATCH_SIZE, toProcess.length);
    }

    geocoding.value = false;
}

const progressPct = computed(() =>
    progress.value.total ? Math.round((progress.value.done / progress.value.total) * 100) : 0
);

const resultCounts = computed(() => ({
    ok:        results.value.filter(r => r.status === 'ok').length,
    no_result: results.value.filter(r => r.status === 'no_result').length,
    error:     results.value.filter(r => r.status === 'error').length,
}));

// ---- JSON viewer ----
const expandedJson = ref(null); // h3_index of the row with JSON expanded
function toggleJson(h3Index) {
    expandedJson.value = expandedJson.value === h3Index ? null : h3Index;
}
function prettyJson(raw) {
    try { return JSON.stringify(raw, null, 2); } catch { return String(raw); }
}
</script>

<template>
    <AdminLayout>
        <Head title="H3 Geocoding Manager" />

        <h1 class="text-2xl font-semibold text-gray-800 mb-1">H3 Location Name Manager</h1>
        <p class="text-sm text-gray-500 mb-6">
            Reverse-geocode H3 hexagons using Google's API. Names are stored in
            <code class="font-mono text-xs bg-gray-100 px-1 rounded">h3_location_names</code>
            and surfaced across the hotspot map.
        </p>

        <!-- Summary bar -->
        <div class="grid grid-cols-3 gap-4 mb-6">
            <div class="bg-white rounded-lg border p-4 text-center shadow-sm">
                <p class="text-2xl font-bold text-gray-700">{{ totalCount }}</p>
                <p class="text-xs text-gray-400 uppercase tracking-wide mt-0.5">Total hexagons</p>
            </div>
            <div class="bg-white rounded-lg border p-4 text-center shadow-sm">
                <p class="text-2xl font-bold text-green-600">{{ namedCount }}</p>
                <p class="text-xs text-gray-400 uppercase tracking-wide mt-0.5">Already named</p>
            </div>
            <div class="bg-white rounded-lg border p-4 text-center shadow-sm">
                <p class="text-2xl font-bold text-amber-600">{{ unnamedCount }}</p>
                <p class="text-xs text-gray-400 uppercase tracking-wide mt-0.5">Unnamed</p>
            </div>
        </div>

        <!-- Quick select -->
        <div class="flex items-center gap-3 mb-6">
            <span class="text-sm font-medium text-gray-600">Quick select:</span>
            <button @click="selectAllUnnamed"
                class="px-3 py-1.5 text-sm border border-gray-300 rounded-md hover:bg-gray-50 transition-colors">
                All unnamed ({{ unnamedCount }})
            </button>
            <button @click="selectTest5"
                class="px-3 py-1.5 text-sm border border-amber-300 text-amber-700 rounded-md hover:bg-amber-50 transition-colors">
                + 5 unnamed (test)
            </button>
            <button @click="clearAll"
                class="px-3 py-1.5 text-sm border border-gray-300 rounded-md hover:bg-gray-50 transition-colors text-gray-500">
                Clear all
            </button>
        </div>

        <!-- City / resolution groups -->
        <div class="space-y-3 mb-6">
            <div v-for="city in cities" :key="city" class="bg-white rounded-lg border shadow-sm overflow-hidden">

                <!-- City header -->
                <button
                    class="w-full flex items-center justify-between px-4 py-3 hover:bg-gray-50 transition-colors text-left"
                    @click="toggleCity(city)"
                >
                    <div class="flex items-center gap-3">
                        <span class="font-semibold text-gray-800">{{ city }}</span>
                        <span class="text-xs text-gray-400">
                            {{ Object.values(grouped[city]).flat().length }} hexagons
                        </span>
                    </div>
                    <div class="flex items-center gap-2">
                        <button
                            class="px-2 py-0.5 text-xs border border-gray-300 rounded hover:bg-gray-100 transition-colors"
                            @click.stop="selectHexes(Object.values(grouped[city]).flat())"
                        >Select all</button>
                        <button
                            class="px-2 py-0.5 text-xs border border-gray-300 rounded hover:bg-gray-100 transition-colors"
                            @click.stop="selectHexes(Object.values(grouped[city]).flat().filter(h => !effectiveName(h)))"
                        >Select unnamed</button>
                        <button
                            class="px-2 py-0.5 text-xs border border-gray-300 rounded hover:bg-gray-100 transition-colors"
                            @click.stop="deselectHexes(Object.values(grouped[city]).flat())"
                        >Deselect</button>
                        <span class="text-gray-400 text-sm ml-1">{{ collapsed[city] ? '▶' : '▼' }}</span>
                    </div>
                </button>

                <!-- Resolution rows -->
                <div v-if="!collapsed[city]" class="border-t divide-y divide-gray-100">
                    <div
                        v-for="(hexes, res) in grouped[city]"
                        :key="res"
                        class="flex items-center gap-4 px-4 py-2.5 text-sm"
                    >
                        <!-- Resolution badge -->
                        <span class="w-24 shrink-0">
                            <span class="inline-block bg-gray-100 text-gray-600 font-mono rounded px-2 py-0.5 text-xs">
                                res {{ res }}
                            </span>
                        </span>

                        <!-- Stats -->
                        <span class="w-28 text-gray-500 text-xs shrink-0">
                            {{ hexes.length }} hexagons ·
                            <span class="text-green-600 font-medium">{{ hexes.filter(h => effectiveName(h)).length }} named</span>
                        </span>

                        <!-- Selection progress -->
                        <span class="text-xs text-indigo-700 w-24 shrink-0">
                            <template v-if="groupSelected(hexes) > 0">
                                {{ groupSelected(hexes) }} selected
                            </template>
                        </span>

                        <!-- Actions -->
                        <div class="flex gap-2 ml-auto">
                            <button
                                class="px-2 py-0.5 text-xs rounded border transition-colors"
                                :class="groupAllSelected(hexes)
                                    ? 'bg-indigo-600 text-white border-indigo-600'
                                    : 'border-gray-300 hover:bg-gray-50'"
                                @click="toggleGroup(hexes)"
                            >{{ groupAllSelected(hexes) ? 'Deselect all' : 'Select all' }}</button>
                            <button
                                class="px-2 py-0.5 text-xs border border-gray-300 rounded hover:bg-gray-50 transition-colors"
                                @click="selectHexes(hexes.filter(h => !effectiveName(h)))"
                            >Select unnamed ({{ hexes.filter(h => !effectiveName(h)).length }})</button>
                            <button
                                v-if="hexes.some(h => !effectiveName(h) && !isSelected(h.h3_index))"
                                class="px-2 py-0.5 text-xs border border-amber-300 text-amber-700 rounded hover:bg-amber-50 transition-colors"
                                @click="selectTest5(hexes)"
                            >+ Test 5</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action bar -->
        <div class="sticky bottom-4 z-10">
            <div class="bg-white border rounded-xl shadow-lg px-5 py-4 flex items-center justify-between gap-4">
                <div class="text-sm text-gray-700">
                    <span v-if="selectedCount === 0" class="text-gray-400">No hexagons selected</span>
                    <template v-else>
                        <span class="font-semibold text-gray-900">{{ selectedCount }}</span> hexagons selected ·
                        <span class="text-green-700 font-medium">{{ selectedNew }} new</span>,
                        <span class="text-amber-700 font-medium">{{ selectedCount - selectedNew }} refresh</span>
                        · ~{{ selectedCount }} API requests
                    </template>
                </div>

                <button
                    :disabled="selectedCount === 0 || geocoding"
                    class="px-5 py-2 rounded-lg text-sm font-semibold transition-colors"
                    :class="selectedCount > 0 && !geocoding
                        ? 'bg-indigo-600 text-white hover:bg-indigo-700'
                        : 'bg-gray-200 text-gray-400 cursor-not-allowed'"
                    @click="runGeocode"
                >
                    <template v-if="geocoding">Geocoding…</template>
                    <template v-else>Geocode {{ selectedCount || '' }} hexagons</template>
                </button>
            </div>
        </div>

        <!-- Progress -->
        <div v-if="geocoding || results.length > 0" class="mt-6 space-y-4">

            <div v-if="geocoding || progress.done > 0" class="bg-white border rounded-lg p-4 shadow-sm">
                <div class="flex items-center justify-between text-sm mb-2">
                    <span class="font-medium text-gray-700">
                        {{ geocoding ? 'Geocoding…' : 'Complete' }}
                    </span>
                    <span class="text-gray-500">{{ progress.done }} / {{ progress.total }}</span>
                </div>
                <div class="w-full bg-gray-100 rounded-full h-2.5">
                    <div
                        class="h-2.5 rounded-full transition-all duration-300"
                        :class="geocoding ? 'bg-indigo-500' : 'bg-green-500'"
                        :style="{ width: progressPct + '%' }"
                    />
                </div>
                <div v-if="!geocoding && results.length > 0" class="flex gap-4 mt-2 text-xs">
                    <span class="text-green-600">✓ {{ resultCounts.ok }} named</span>
                    <span v-if="resultCounts.no_result > 0" class="text-amber-600">⚠ {{ resultCounts.no_result }} no result</span>
                    <span v-if="resultCounts.error > 0" class="text-red-600">✕ {{ resultCounts.error }} error</span>
                </div>
                <div v-if="geocodeError" class="mt-2 text-xs text-red-600">{{ geocodeError }}</div>
            </div>

            <!-- Results table -->
            <div v-if="results.length > 0" class="bg-white border rounded-lg shadow-sm overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="py-2 px-3 text-left text-xs text-gray-500 font-medium uppercase tracking-wide">H3 Index</th>
                            <th class="py-2 px-3 text-left text-xs text-gray-500 font-medium uppercase tracking-wide">Res</th>
                            <th class="py-2 px-3 text-left text-xs text-gray-500 font-medium uppercase tracking-wide">Name</th>
                            <th class="py-2 px-3 text-left text-xs text-gray-500 font-medium uppercase tracking-wide">Status</th>
                            <th class="py-2 px-3 text-left text-xs text-gray-500 font-medium uppercase tracking-wide">JSON</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <template v-for="r in results" :key="r.h3_index">
                            <tr class="hover:bg-gray-50">
                                <td class="py-1.5 px-3 font-mono text-xs text-gray-500">{{ r.h3_index }}</td>
                                <td class="py-1.5 px-3 text-gray-500 text-xs">
                                    {{ props.hexagons.find(h => h.h3_index === r.h3_index)?.h3_resolution ?? '—' }}
                                </td>
                                <td class="py-1.5 px-3 text-gray-800 font-medium">
                                    {{ r.location_name ?? '—' }}
                                </td>
                                <td class="py-1.5 px-3">
                                    <span v-if="r.status === 'ok'"
                                        class="text-xs text-green-700 bg-green-50 px-2 py-0.5 rounded-full">named</span>
                                    <span v-else-if="r.status === 'no_result'"
                                        class="text-xs text-amber-700 bg-amber-50 px-2 py-0.5 rounded-full">no result</span>
                                    <span v-else
                                        class="text-xs text-red-700 bg-red-50 px-2 py-0.5 rounded-full">error</span>
                                </td>
                                <td class="py-1.5 px-3">
                                    <button
                                        v-if="r.raw_response"
                                        class="text-xs text-indigo-600 hover:text-indigo-800 font-mono underline"
                                        @click="toggleJson(r.h3_index)"
                                    >{{ expandedJson === r.h3_index ? 'hide' : 'show' }}</button>
                                    <span v-else class="text-xs text-gray-300">—</span>
                                </td>
                            </tr>
                            <tr v-if="expandedJson === r.h3_index" class="bg-gray-50">
                                <td colspan="5" class="px-3 pb-3 pt-1">
                                    <pre class="text-xs font-mono text-gray-700 bg-gray-100 rounded p-3 overflow-x-auto max-h-96 overflow-y-auto whitespace-pre">{{ prettyJson(r.raw_response) }}</pre>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

    </AdminLayout>
</template>
