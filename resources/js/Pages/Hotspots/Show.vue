<script setup>
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
const page = usePage();
import PageTemplate from '@/Components/PageTemplate.vue';
import GoogleAddressSearch from '@/Components/GoogleAddressSearch.vue';
import { useH3Names } from '@/composables/useH3Names';
import 'leaflet/dist/leaflet.css';
import * as L from 'leaflet';
import * as h3 from 'h3-js';

import markerIcon   from 'leaflet/dist/images/marker-icon.png';
import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png';
import markerShadow from 'leaflet/dist/images/marker-shadow.png';
delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({ iconRetinaUrl: markerIcon2x, iconUrl: markerIcon, shadowUrl: markerShadow });

const props = defineProps({
    city:                 { type: Object,  default: null },
    cities:               { type: Array,   default: () => [] },
    hotspotsByResolution: { type: Object,  default: () => ({}) },
    cityCenter:           { type: Array,   default: () => [42.3601, -71.0589] },
});

// ---- h3 names ----
const { getName, hasName } = useH3Names();

// ---- helpers ----
const formatWindow = (w) => {
    if (!w) return '';
    const m = w.match(/^(\d+)/);
    return m ? `${m[1]} weeks` : w.replace(/_/g, ' ');
};

const formatPValue = (p) => {
    if (p == null) return 'N/A';
    if (p === 0)   return '< 1e-15';
    if (p < 0.001) return p.toExponential(2);
    if (p < 0.01)  return p.toFixed(4);
    return p.toFixed(3);
};

// ---- state ----
const activeResolution = ref(null);
const activeHexagon    = ref(null);
const sortKey          = ref('report_count');
const hexOpacity       = ref(0.65);

let mapInstance   = null;
let polygonLayers = {};
let legendControl = null;
let addressMarker = null;

// ---- computed ----
const resolutions = computed(() =>
    Object.keys(props.hotspotsByResolution).map(Number).sort((a, b) => a - b)
);

const hotspots = computed(() =>
    activeResolution.value !== null
        ? (props.hotspotsByResolution[activeResolution.value] ?? [])
        : []
);

const sortedHotspots = computed(() => {
    const arr = [...hotspots.value];
    if (sortKey.value === 'anomaly_count') return arr.sort((a, b) => b.anomaly_count - a.anomaly_count);
    if (sortKey.value === 'trend_count')   return arr.sort((a, b) => b.trend_count   - a.trend_count);
    return arr.sort((a, b) =>
        b.report_count !== a.report_count
            ? b.report_count - a.report_count
            : (b.anomaly_count + b.trend_count) - (a.anomaly_count + a.trend_count)
    );
});

const stats = computed(() => {
    const all = hotspots.value;
    if (!all.length) return { hexagons: 0, maxReports: 0, totalAnomalies: 0, totalTrends: 0, uniqueReports: 0 };
    const reportIds = new Set(all.flatMap(h => h.reports.map(r => r.job_id)));
    return {
        hexagons:       all.length,
        maxReports:     Math.max(...all.map(h => h.report_count)),
        totalAnomalies: all.reduce((s, h) => s + h.anomaly_count, 0),
        totalTrends:    all.reduce((s, h) => s + h.trend_count, 0),
        uniqueReports:  reportIds.size,
    };
});

const hasData = computed(() => resolutions.value.length > 0 && props.city);

// ---- heat scale (dynamic) ----
// 10-step palette: light yellow → dark red, matching the anomaly scale in StatisticalAnalysisViewer
const HEAT_PALETTE = ['#FFFFE5', '#FFF7BC', '#FEE391', '#FEC44F', '#FE9929', '#EC7014', '#CC4C02', '#993404', '#662506', '#E31A1C'];

// Compute which palette bucket a count falls into given the actual maximum in the data.
// Spreads the full palette across [1, maxCount] so no two different values share a colour
// unless there are more distinct values than palette steps.
const colorIndex = (count, maxCount) => {
    if (maxCount <= 1) return HEAT_PALETTE.length - 1;
    const t = (count - 1) / (maxCount - 1);
    return Math.min(Math.round(t * (HEAT_PALETTE.length - 1)), HEAT_PALETTE.length - 1);
};
const getHeatColor = (count, maxCount) => HEAT_PALETTE[colorIndex(count, maxCount)];

const maxReportCount = computed(() =>
    hotspots.value.length ? Math.max(...hotspots.value.map(h => h.report_count)) : 1
);

// ---- map ----
const initMap = () => {
    const el = document.getElementById('hotspot-map');
    if (!el) return;
    if (mapInstance) { mapInstance.remove(); mapInstance = null; }
    mapInstance = L.map(el).setView(props.cityCenter, 12);
    L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
    }).addTo(mapInstance);
};

const renderHexagons = () => {
    if (!mapInstance) return;
    Object.values(polygonLayers).forEach(p => p.remove());
    polygonLayers = {};

    hotspots.value.forEach(h => {
        const boundary = h3.cellToBoundary(h.h3_index, true).map(p => [p[1], p[0]]);
        const poly = L.polygon(boundary, {
            color:       '#555',
            weight:      1,
            fillColor:   getHeatColor(h.report_count, maxReportCount.value),
            fillOpacity: hexOpacity.value,
        }).addTo(mapInstance);

        const locationName = page.props.h3LocationNames?.[h.h3_index];
        poly.bindTooltip(
            (locationName ? `<strong>${locationName}</strong><br>` : '') +
            `<span class="font-mono text-xs text-gray-400">${h.h3_index}</span><br>` +
            `<strong>${h.report_count} report type${h.report_count !== 1 ? 's' : ''}</strong><br>` +
            `${h.anomaly_count} anomalies · ${h.trend_count} trends`,
            { sticky: true }
        );
        poly.on('click', () => selectHexagon(h));
        polygonLayers[h.h3_index] = poly;
    });

    if (hotspots.value.length > 0) {
        const group = L.featureGroup(Object.values(polygonLayers));
        mapInstance.fitBounds(group.getBounds().pad(0.1));
    }

    // Dynamic legend built from actual report_count range in the data
    if (legendControl) mapInstance.removeControl(legendControl);
    legendControl = L.control({ position: 'bottomright' });
    legendControl.onAdd = function () {
        const div = L.DomUtil.create('div', 'info legend');
        const maxR = hotspots.value.length
            ? Math.max(...hotspots.value.map(h => h.report_count))
            : 1;
        div.innerHTML = '<strong>Report types</strong><br>';
        // One row per palette bucket that has at least one count mapping to it,
        // showing the actual count range for that bucket.
        const shown = new Set();
        for (let count = 1; count <= maxR; count++) {
            const ci = colorIndex(count, maxR);
            if (!shown.has(ci)) {
                shown.add(ci);
                let next = count + 1;
                while (next <= maxR && colorIndex(next, maxR) === ci) next++;
                const hi = next - 1;
                const label = hi > count ? `${count}–${hi}` : `${count}`;
                div.innerHTML += `<i style="background:${HEAT_PALETTE[ci]}"></i>${label}<br>`;
            }
        }
        return div;
    };
    legendControl.addTo(mapInstance);
};

const applyHighlight = (h3Index) => {
    Object.entries(polygonLayers).forEach(([idx, poly]) => {
        if (idx === h3Index) {
            poly.setStyle({ color: '#1e40af', weight: 3, fillOpacity: hexOpacity.value });
            poly.bringToFront();
        } else {
            const h = hotspots.value.find(x => x.h3_index === idx);
            if (h) poly.setStyle({ color: '#555', weight: 1, fillColor: getHeatColor(h.report_count, maxReportCount.value), fillOpacity: hexOpacity.value });
        }
    });
    if (polygonLayers[h3Index]) {
        mapInstance?.panTo(polygonLayers[h3Index].getBounds().getCenter());
    }
};

const selectHexagon = async (h) => {
    activeHexagon.value = h;
    await nextTick();
    document.getElementById('hotspot-detail')?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
};

// ---- address search ----
const handleAddressSelection = ({ lat, lng, address }) => {
    if (!mapInstance) return;
    if (addressMarker) { mapInstance.removeLayer(addressMarker); addressMarker = null; }
    addressMarker = L.marker([lat, lng]).addTo(mapInstance).bindPopup(address).openPopup();
    mapInstance.setView([lat, lng], 15);

    if (activeResolution.value === null) return;
    const h3Index = h3.latLngToCell(lat, lng, activeResolution.value);
    const hotspot = hotspots.value.find(h => h.h3_index === h3Index);
    if (hotspot) {
        selectHexagon(hotspot);
    }
};

// ---- watchers ----
watch(hexOpacity, () => {
    Object.entries(polygonLayers).forEach(([idx, poly]) => {
        const isActive = activeHexagon.value?.h3_index === idx;
        poly.setStyle({ fillOpacity: hexOpacity.value });
    });
});

watch(activeHexagon, (h) => { if (h) applyHighlight(h.h3_index); });

watch(activeResolution, async () => {
    activeHexagon.value = null;
    await nextTick();
    renderHexagons();
});

// ---- lifecycle ----
onMounted(async () => {
    if (resolutions.value.length > 0) {
        activeResolution.value = resolutions.value[0];
    }
    await nextTick();
    initMap();
    renderHexagons();
});

onUnmounted(() => {
    if (mapInstance) { mapInstance.remove(); mapInstance = null; }
    polygonLayers = {};
    legendControl = null;
    addressMarker = null;
});
</script>

<template>
    <PageTemplate>
        <Head :title="city ? `${city.name} Hotspot Map` : 'Hotspot Map'" />

        <div class="py-10">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

                <!-- Header -->
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Hotspot Map</h1>
                    <p class="mt-1 text-sm text-gray-500">
                        H3 hexagons flagged with significant findings across multiple report types.
                        Darker hexagons appear in more independent reports — true cross-data-type hotspots.
                    </p>
                </div>

                <!-- City tabs -->
                <div v-if="cities.length > 0" class="flex flex-wrap gap-1 border-b border-gray-200">
                    <Link
                        v-for="c in cities"
                        :key="c.slug"
                        :href="route('hotspots.show', { citySlug: c.slug })"
                        class="px-4 py-2 text-sm font-medium rounded-t-md border border-b-0 transition-colors"
                        :class="city?.slug === c.slug
                            ? 'bg-white border-gray-200 text-indigo-700 -mb-px z-10'
                            : 'bg-gray-50 border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-100'"
                    >{{ c.name }}</Link>
                </div>

                <!-- No data empty state -->
                <div v-if="!hasData" class="text-center py-16 bg-white rounded-lg border">
                    <p class="text-gray-500 text-lg">No trend analysis data available{{ city ? ` for ${city.name}` : '' }}.</p>
                    <p class="text-sm text-gray-400 mt-2 font-mono">php artisan app:materialize-hotspot-findings</p>
                </div>

                <template v-else>

                    <!-- Resolution tabs -->
                    <div v-if="resolutions.length > 1" class="flex items-center gap-3">
                        <span class="text-xs text-gray-500 font-medium uppercase tracking-wide">Resolution:</span>
                        <div class="flex rounded-md shadow-sm border border-gray-300 overflow-hidden text-sm">
                            <button
                                v-for="r in resolutions"
                                :key="r"
                                class="px-3 py-1.5 transition-colors"
                                :class="activeResolution === r ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50'"
                                @click="activeResolution = r"
                            >{{ r }}</button>
                        </div>
                        <span class="text-xs text-gray-400 hidden sm:block">Hexagons at different resolutions cover different areas and are not directly comparable</span>
                    </div>

                    <!-- Stats bar -->
                    <div v-if="hotspots.length > 0" class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                        <div class="bg-white rounded-lg shadow-sm border p-4 text-center">
                            <p class="text-3xl font-bold text-gray-700">{{ stats.hexagons.toLocaleString() }}</p>
                            <p class="text-xs text-gray-500 mt-1 uppercase tracking-wide">Hotspot Hexagons</p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm border p-4 text-center">
                            <p class="text-3xl font-bold text-red-700">{{ stats.maxReports }}</p>
                            <p class="text-xs text-gray-500 mt-1 uppercase tracking-wide">Max Report Types / Hex</p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm border p-4 text-center">
                            <p class="text-3xl font-bold text-amber-600">{{ stats.totalAnomalies.toLocaleString() }}</p>
                            <p class="text-xs text-gray-500 mt-1 uppercase tracking-wide">Total Anomalies</p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm border p-4 text-center">
                            <p class="text-3xl font-bold text-blue-600">{{ stats.totalTrends.toLocaleString() }}</p>
                            <p class="text-xs text-gray-500 mt-1 uppercase tracking-wide">Total Trends</p>
                        </div>
                    </div>

                    <!-- Main: table + map -->
                    <div class="flex flex-col lg:flex-row gap-6 items-start">

                        <!-- Ranked table -->
                        <div class="lg:w-2/5 w-full bg-white rounded-lg shadow-sm border overflow-hidden flex flex-col max-h-[640px]">
                            <div class="overflow-y-auto flex-1">
                            <table class="w-full text-sm table-fixed">
                                <thead class="bg-gray-50 border-b sticky top-0 z-10">
                                    <tr>
                                        <th class="py-2 px-3 text-left text-xs text-gray-400 font-medium w-8">#</th>
                                        <th class="py-2 px-3 text-left text-xs text-gray-500 font-medium uppercase tracking-wide">Hexagon</th>
                                        <th
                                            v-for="col in [['report_count','Reports'],['anomaly_count','Anom.'],['trend_count','Trends']]"
                                            :key="col[0]"
                                            class="py-2 px-2 text-right text-xs font-medium cursor-pointer select-none whitespace-nowrap uppercase tracking-wide transition-colors"
                                            :class="sortKey === col[0] ? 'text-indigo-700 underline' : 'text-gray-400 hover:text-gray-600'"
                                            @click="sortKey = col[0]"
                                        >{{ col[1] }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <tr
                                        v-for="(h, i) in sortedHotspots"
                                        :key="h.h3_index"
                                        class="cursor-pointer hover:bg-indigo-50 transition-colors"
                                        :class="{ 'bg-indigo-50 ring-1 ring-inset ring-indigo-200': activeHexagon?.h3_index === h.h3_index }"
                                        @click="selectHexagon(h)"
                                    >
                                        <td class="py-2 px-3 text-gray-400 text-xs tabular-nums">{{ i + 1 }}</td>
                                        <td class="py-2 px-3">
                                            <div v-if="hasName(h.h3_index)" class="text-sm text-gray-800 font-medium leading-tight">{{ getName(h.h3_index) }}</div>
                                            <div class="font-mono text-xs text-gray-400">{{ h.h3_index }}</div>
                                        </td>
                                        <td class="py-2 px-2 text-right">
                                            <span
                                                class="inline-block px-1.5 py-0.5 rounded text-xs font-bold text-white tabular-nums"
                                                :style="{ background: getHeatColor(h.report_count, maxReportCount) }"
                                            >{{ h.report_count }}</span>
                                        </td>
                                        <td class="py-2 px-2 text-right text-amber-700 font-semibold tabular-nums text-sm">{{ h.anomaly_count.toLocaleString() }}</td>
                                        <td class="py-2 px-2 text-right text-blue-700 font-semibold tabular-nums text-sm">{{ h.trend_count.toLocaleString() }}</td>
                                    </tr>
                                </tbody>
                            </table>
                            <div v-if="sortedHotspots.length === 0" class="text-center py-10 text-gray-400 text-sm">
                                No hotspot hexagons found at this resolution.
                            </div>
                            </div><!-- /overflow-y-auto -->
                        </div>

                        <!-- Leaflet map -->
                        <div class="lg:w-3/5 w-full space-y-2">
                            <div class="bg-white rounded-lg border shadow-sm px-3 py-2 relative">
                                <GoogleAddressSearch @address-selected="handleAddressSelection" />
                                <div class="flex items-center gap-3 mt-2 pt-2 border-t border-gray-100">
                                    <label class="text-xs text-gray-500 whitespace-nowrap">Opacity</label>
                                    <input type="range" min="0" max="1" step="0.01" v-model.number="hexOpacity" class="flex-1 accent-indigo-600" />
                                    <span class="text-xs text-gray-500 tabular-nums w-8 text-right">{{ Math.round(hexOpacity * 100) }}%</span>
                                </div>
                            </div>
                            <div id="hotspot-map" class="h-[600px] rounded-lg border shadow-sm" />
                        </div>

                    </div>

                    <!-- Detail panel -->
                    <div
                        v-if="activeHexagon"
                        id="hotspot-detail"
                        class="bg-white rounded-lg shadow-sm border p-6 space-y-4 scroll-mt-4"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h2 class="text-xl font-bold text-gray-800">
                                    {{ getName(activeHexagon.h3_index) }}
                                </h2>
                                <p class="font-mono text-xs text-gray-400 mt-0.5">{{ activeHexagon.h3_index }}</p>
                                <p class="text-sm text-gray-500 mt-0.5">
                                    H3 Resolution {{ activeResolution }} ·
                                    <span class="font-semibold text-gray-700">
                                        {{ activeHexagon.report_count }} independent report type{{ activeHexagon.report_count !== 1 ? 's' : '' }}
                                    </span>
                                    flagged this hexagon
                                </p>
                            </div>
                            <button
                                class="text-gray-400 hover:text-gray-600 text-2xl leading-none flex-shrink-0"
                                @click="activeHexagon = null"
                            >&times;</button>
                        </div>

                        <div class="flex gap-3 flex-wrap text-sm">
                            <span class="bg-amber-100 text-amber-800 px-3 py-1 rounded-full font-semibold">
                                ⚠ {{ activeHexagon.anomaly_count.toLocaleString() }} anomalies
                            </span>
                            <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full font-semibold">
                                ↗ {{ activeHexagon.trend_count.toLocaleString() }} trends
                            </span>
                        </div>

                        <div>
                            <h3 class="text-sm font-semibold text-gray-600 uppercase tracking-wide mb-3">
                                Reports with findings at this hexagon
                            </h3>
                            <div class="space-y-3">
                                <div
                                    v-for="r in activeHexagon.reports"
                                    :key="r.job_id"
                                    class="border rounded-lg p-4 hover:bg-gray-50 transition-colors"
                                >
                                    <!-- Report header -->
                                    <div class="flex items-start justify-between gap-3 mb-2">
                                        <div>
                                            <p class="text-sm font-semibold text-gray-800">{{ r.label }}</p>
                                            <div class="flex gap-3 mt-0.5">
                                                <span v-if="r.anomalies > 0" class="text-xs text-amber-700 font-medium">⚠ {{ r.anomalies }} anomal{{ r.anomalies !== 1 ? 'ies' : 'y' }}</span>
                                                <span v-if="r.trends    > 0" class="text-xs text-blue-700 font-medium">↗ {{ r.trends }} trend{{ r.trends !== 1 ? 's' : '' }}</span>
                                            </div>
                                        </div>
                                        <Link
                                            :href="route('reports.statistical-analysis.show', { jobId: r.job_id })"
                                            class="text-xs text-indigo-600 hover:text-indigo-800 whitespace-nowrap flex-shrink-0 font-medium"
                                        >Full Report →</Link>
                                    </div>

                                    <!-- Top anomalies -->
                                    <div v-if="r.top_anomalies?.length" class="mt-2">
                                        <p class="text-xs font-semibold text-amber-700 uppercase tracking-wide mb-1">⚠ Top Anomalies</p>
                                        <div class="space-y-0.5">
                                            <p
                                                v-for="a in r.top_anomalies"
                                                :key="`${a.secondary_group}-${a.week}`"
                                                class="text-sm text-gray-600 leading-snug pl-1"
                                            >
                                                <span :class="(a.z_score ?? 0) >= 0 ? 'text-red-500' : 'text-blue-500'" class="font-bold mr-1">{{ (a.z_score ?? 0) >= 0 ? '↑' : '↓' }}</span>
                                                <span class="font-medium text-gray-800">{{ a.secondary_group }}</span>
                                                <span class="text-gray-400"> · {{ a.week }}</span>
                                                <span> · {{ a.count }} vs avg {{ a.historical_avg }}</span>
                                                <span class="text-amber-700 font-semibold"> z-score={{ a.z_score }}</span>
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Top trends -->
                                    <div v-if="r.top_trends?.length" class="mt-2">
                                        <p class="text-xs font-semibold text-blue-700 uppercase tracking-wide mb-1">↗ Top Trends</p>
                                        <div class="space-y-0.5">
                                            <p
                                                v-for="t in r.top_trends"
                                                :key="`${t.secondary_group}-${t.window}`"
                                                class="text-sm text-gray-600 leading-snug pl-1"
                                            >
                                                <span :class="(t.slope ?? 0) > 0 ? 'text-red-500' : 'text-blue-500'" class="font-bold mr-1">{{ (t.slope ?? 0) > 0 ? '↑' : '↓' }}</span>
                                                <span class="font-medium text-gray-800">{{ t.secondary_group }}</span>
                                                <span :class="(t.slope ?? 0) > 0 ? 'text-red-600' : 'text-blue-600'" class="font-semibold"> slope {{ (t.slope ?? 0) > 0 ? '+' : '' }}{{ t.slope?.toFixed(2) }}</span>
                                                <span class="text-gray-500"> p={{ formatPValue(t.p_value) }}</span>
                                                <span v-if="t.window" class="text-gray-400 text-xs"> [{{ formatWindow(t.window) }}]</span>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </template>

            </div>
        </div>
    </PageTemplate>
</template>

<style>
.info.legend {
    padding: 6px 8px;
    font: 13px/18px Arial, Helvetica, sans-serif;
    background: rgba(255, 255, 255, 0.88);
    box-shadow: 0 0 15px rgba(0, 0, 0, 0.2);
    border-radius: 5px;
    line-height: 1.6;
}
.info.legend strong {
    display: block;
    margin-bottom: 4px;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #4b5563;
}
.info.legend i {
    width: 16px;
    height: 16px;
    float: left;
    margin-right: 6px;
    margin-top: 1px;
    opacity: 0.85;
    border: 1px solid rgba(0,0,0,0.15);
    border-radius: 2px;
}
</style>
