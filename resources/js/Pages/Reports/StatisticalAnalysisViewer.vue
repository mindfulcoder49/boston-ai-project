<script setup>
import { ref, onMounted, computed, nextTick } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import PageTemplate from '@/Components/PageTemplate.vue';
import 'leaflet/dist/leaflet.css';
import * as L from 'leaflet';
import * as h3 from 'h3-js';

// Fix for default icon path issues with Vite/Leaflet
import markerIcon from 'leaflet/dist/images/marker-icon.png';
import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png';
import markerShadow from 'leaflet/dist/images/marker-shadow.png';

delete L.Icon.Default.prototype._getIconUrl;

L.Icon.Default.mergeOptions({
  iconRetinaUrl: markerIcon2x,
  iconUrl: markerIcon,
  shadowUrl: markerShadow,
});

const props = defineProps({
    jobId: String,
    apiBaseUrl: String,
    reportTitle: String,
    reportData: {
        type: Object,
        required: false,
        default: null,
    },
    relatedScoringReports: {
        type: Array,
        default: () => [],
    },
});

const isLoading = ref(false);
const error = ref(null);
const activeSecondaryGroup = ref(null);

let mapAnomaliesInstances = {};
let mapTrendsInstances = {};
let h3Layers = {}; // To store references to H3 polygon layers

const P_VALUE_ANOMALY = computed(() => props.reportData?.parameters?.p_value_anomaly || 0.05);
const P_VALUE_TREND = computed(() => props.reportData?.parameters?.p_value_trend || 0.05);
const TREND_WINDOWS = computed(() => props.reportData?.parameters?.analysis_weeks_trend || []);

onMounted(async () => {
    if (!props.reportData) {
        error.value = 'Failed to load report data. The analysis results might not be available.';
        return;
    }

    try {
        processAndDisplayData();
        // Auto-activate the most significant category so reporters land on something useful
        await nextTick();
        if (sortedSecondaryGroups.value.length > 0) {
            const topGroup = sortedSecondaryGroups.value[0];
            activeSecondaryGroup.value = topGroup;
            await nextTick();
            initializeMapsForGroup(topGroup);
        }
    } catch (e) {
        console.error('Failed to process report data:', e);
        error.value = `Failed to process report data. (${e.message})`;
    }
});

const initializeMapsForGroup = (secGroup) => {
    if (!secGroup || !findingsBySecondaryGroup.value[secGroup]) return;

    const sanitizedSecGroup = sanitizeForFilename(secGroup);
    if (!h3Layers[secGroup]) {
        h3Layers[secGroup] = { anomalies: {}, trends: {} };
    }
    if (!mapTrendsInstances[secGroup]) {
        mapTrendsInstances[secGroup] = {};
    }

    // Initialize Anomaly Map for the group
    const anomalyContainerId = `map-anomalies-${sanitizedSecGroup}`;
    const anomalyContainer = document.getElementById(anomalyContainerId);
    if (anomalyContainer && !mapAnomaliesInstances[secGroup]) {
        const map = L.map(anomalyContainer).setView([42.3601, -71.0589], 12);
        L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>'
        }).addTo(map);
        mapAnomaliesInstances[secGroup] = map;
        updateAnomaliesMap(secGroup);
    }

    // Initialize Trend Maps for the group for each window
    TREND_WINDOWS.value.forEach(window => {
        const trendWindowKey = `${window}_weeks`;
        const trendContainerId = `map-trends-${sanitizedSecGroup}-${trendWindowKey}`;
        const trendContainer = document.getElementById(trendContainerId);
        if (trendContainer && !mapTrendsInstances[secGroup][trendWindowKey]) {
            const map = L.map(trendContainer).setView([42.3601, -71.0589], 12);
            L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>'
            }).addTo(map);
            mapTrendsInstances[secGroup][trendWindowKey] = map;
            updateTrendsMap(secGroup, trendWindowKey);
        }
    });
};

const toggleSecondaryGroup = async (secGroup) => {
    const prevGroup = activeSecondaryGroup.value;
    const newActiveGroup = prevGroup === secGroup ? null : secGroup;

    // Destroy old Leaflet instances before the shared DOM container changes group
    if (prevGroup) {
        mapAnomaliesInstances[prevGroup]?.remove();
        delete mapAnomaliesInstances[prevGroup];
        Object.values(mapTrendsInstances[prevGroup] ?? {}).forEach(m => m.remove());
        delete mapTrendsInstances[prevGroup];
        delete h3Layers[prevGroup];
    }

    activeSecondaryGroup.value = newActiveGroup;

    if (newActiveGroup) {
        await nextTick();
        initializeMapsForGroup(newActiveGroup);
    }
};

// Used by top-findings rows: always activates (never deactivates) and scrolls into view
const activateAndScrollTo = async (secGroup) => {
    if (activeSecondaryGroup.value !== secGroup) {
        await toggleSecondaryGroup(secGroup);
    }
    await nextTick();
    document.getElementById(`analysis-${sanitizeForFilename(secGroup)}`)
        ?.scrollIntoView({ behavior: 'smooth', block: 'start' });
};

const allFindings = ref([]);
const anomalyFindings = ref([]);
const trendFindings = ref([]);
const findingsByH3 = ref({});

const findingsBySecondaryGroup = computed(() => {
    const grouped = {};
    allFindings.value.forEach(finding => {
        const secGroup = finding.details.secondary_group;
        if (!secGroup) return;

        if (!grouped[secGroup]) {
            grouped[secGroup] = {
                anomalies: [],
                trends: [],
            };
        }
        if (finding.type === 'Anomaly') {
            grouped[secGroup].anomalies.push(finding);
        } else if (finding.type === 'Trend') {
            grouped[secGroup].trends.push(finding);
        }
    });
    return grouped;
});

// Top individual findings — sortable
const anomalySortKey = ref('z_score');   // 'z_score' | 'p_value' | 'count' | 'week'
const trendSortKey   = ref('slope');     // 'slope' | 'p_value' | 'category'

const setAnomalySort = (key) => { anomalySortKey.value = key; };
const setTrendSort   = (key) => { trendSortKey.value   = key; };

const topAnomalyFindings = computed(() => {
    const comparators = {
        z_score:  (a, b) => (b.week_details.z_score ?? 0) - (a.week_details.z_score ?? 0),
        p_value:  (a, b) => (a.week_details.anomaly_p_value ?? 1) - (b.week_details.anomaly_p_value ?? 1),
        count:    (a, b) => (b.week_details.count ?? 0) - (a.week_details.count ?? 0),
        week:     (a, b) => (b.week_details.week ?? '').localeCompare(a.week_details.week ?? ''),
    };
    return [...anomalyFindings.value].sort(comparators[anomalySortKey.value] ?? comparators.z_score).slice(0, 8);
});

// Trends grouped by window, each window sorted independently
const topTrendsByWindow = computed(() => {
    const compare = {
        slope:    (a, b) => Math.abs(b.trend_details.slope ?? 0) - Math.abs(a.trend_details.slope ?? 0),
        p_value:  (a, b) => (a.trend_details.p_value ?? 1) - (b.trend_details.p_value ?? 1),
        category: (a, b) => (a.details.secondary_group ?? '').localeCompare(b.details.secondary_group ?? ''),
    }[trendSortKey.value] ?? ((a, b) => Math.abs(b.trend_details.slope ?? 0) - Math.abs(a.trend_details.slope ?? 0));

    const byWindow = {};
    for (const f of trendFindings.value) {
        if (!byWindow[f.trend_window]) byWindow[f.trend_window] = [];
        byWindow[f.trend_window].push(f);
    }
    const result = {};
    for (const w of Object.keys(byWindow).sort((a, b) => parseInt(a) - parseInt(b))) {
        result[w] = [...byWindow[w]].sort(compare).slice(0, 8);
    }
    return result;
});

const formatPValue = (p) => {
    if (p == null) return 'N/A';
    if (p === 0)   return '< 1e-15';
    if (p < 0.001) return p.toExponential(2);
    if (p < 0.01)  return p.toFixed(4);
    return p.toFixed(3);
};

// Secondary groups sorted by total findings descending — so the most significant appears first
const sortedSecondaryGroups = computed(() => {
    return Object.keys(findingsBySecondaryGroup.value).sort((a, b) => {
        const totalA = (findingsBySecondaryGroup.value[a].anomalies.length + findingsBySecondaryGroup.value[a].trends.length);
        const totalB = (findingsBySecondaryGroup.value[b].anomalies.length + findingsBySecondaryGroup.value[b].trends.length);
        return totalB - totalA;
    });
});

// Separate lists for the split summary view
const groupsWithAnomalies = computed(() =>
    Object.keys(findingsBySecondaryGroup.value)
        .filter(g => findingsBySecondaryGroup.value[g].anomalies.length > 0)
        .sort((a, b) => findingsBySecondaryGroup.value[b].anomalies.length - findingsBySecondaryGroup.value[a].anomalies.length)
);

const groupsWithTrends = computed(() =>
    Object.keys(findingsBySecondaryGroup.value)
        .filter(g => findingsBySecondaryGroup.value[g].trends.length > 0)
        .sort((a, b) => findingsBySecondaryGroup.value[b].trends.length - findingsBySecondaryGroup.value[a].trends.length)
);

const filteredFindings = computed(() => {
    if (!activeSecondaryGroup.value) return [];
    return allFindings.value.filter(f => f.details.secondary_group === activeSecondaryGroup.value);
});

const filteredAnomalyFindings = computed(() =>
    filteredFindings.value.filter(f => f.type === 'Anomaly')
        .sort((a, b) => (b.week_details.z_score ?? 0) - (a.week_details.z_score ?? 0))
);

const filteredTrendsByWindow = computed(() => {
    const byWindow = {};
    for (const f of filteredFindings.value.filter(f => f.type === 'Trend')) {
        if (!byWindow[f.trend_window]) byWindow[f.trend_window] = [];
        byWindow[f.trend_window].push(f);
    }
    const result = {};
    for (const w of Object.keys(byWindow).sort((a, b) => parseInt(a) - parseInt(b))) {
        result[w] = byWindow[w].sort((a, b) =>
            Math.abs(b.trend_details.slope ?? 0) - Math.abs(a.trend_details.slope ?? 0)
        );
    }
    return result;
});

const filteredFindingsByH3 = computed(() => {
    if (!activeSecondaryGroup.value) {
        return {};
    }
    const filtered = {};
    for (const h3Index in findingsByH3.value) {
        const h3Data = findingsByH3.value[h3Index];
        if (h3Data.findingsBySecGroup[activeSecondaryGroup.value]) {
            filtered[h3Index] = {
                findingsBySecGroup: {
                    [activeSecondaryGroup.value]: h3Data.findingsBySecGroup[activeSecondaryGroup.value]
                }
            };
        }
    }
    return filtered;
});

const processAndDisplayData = () => {
    if (!props.reportData) return;

    const params = props.reportData.parameters;
    const h3Col = `h3_index_${params.h3_resolution}`;

    const localAllFindings = [];
    const localAnomalyFindings = [];
    const localTrendFindings = [];

    (props.reportData.results || []).forEach(row => {
        if (row.trend_analysis) {
            for (const trendWindow in row.trend_analysis) {
                const trendDetails = row.trend_analysis[trendWindow];
                const trendP = trendDetails.p_value;
                if (trendP !== null && trendP < P_VALUE_TREND.value) {
                    const finding = {
                        type: 'Trend',
                        details: row,
                        trend_details: trendDetails,
                        trend_window: trendWindow,
                    };
                    localAllFindings.push(finding);
                    localTrendFindings.push(finding);
                }
            }
        }
        if (row.anomaly_analysis) {
            row.anomaly_analysis.forEach(week => {
                const anomalyP = week.anomaly_p_value;
                if (anomalyP !== null && anomalyP < P_VALUE_ANOMALY.value) {
                    if (row.historical_weekly_avg < 1 && week.count === 1) return;
                    const finding = { type: 'Anomaly', details: row, week_details: week };
                    localAllFindings.push(finding);
                    localAnomalyFindings.push(finding);
                }
            });
        }
    });

    localAllFindings.sort((a, b) => {
        const pValA = a.type === 'Trend' ? a.trend_details.p_value : a.week_details.anomaly_p_value;
        const pValB = b.type === 'Trend' ? b.trend_details.p_value : b.week_details.anomaly_p_value;
        return (a.details[h3Col] || '').localeCompare(b.details[h3Col] || '') || pValA - pValB;
    });

    const localFindingsByH3 = {};
    localAllFindings.forEach(f => {
        const h3Index = f.details[h3Col];
        if (!h3Index) return;
        
        if (!localFindingsByH3[h3Index]) {
            localFindingsByH3[h3Index] = { findingsBySecGroup: {} };
        }
        
        const secGroup = f.details.secondary_group;
        if (!localFindingsByH3[h3Index].findingsBySecGroup[secGroup]) {
             localFindingsByH3[h3Index].findingsBySecGroup[secGroup] = [];
        }
        localFindingsByH3[h3Index].findingsBySecGroup[secGroup].push(f);
    });

    allFindings.value = localAllFindings;
    anomalyFindings.value = localAnomalyFindings;
    trendFindings.value = localTrendFindings;
    findingsByH3.value = localFindingsByH3;
};

const getAnomalyColor = (count) => {
    if (count >= 1 && count <= 10) {
        const scale = ['#FFFFE5', '#FFF7BC', '#FEE391', '#FEC44F', '#FE9929', '#EC7014', '#CC4C02', '#993404', '#662506', '#E31A1C'];
        return scale[count - 1];
    }
    if (count > 10 && count <= 20) return '#800080';
    return '#000000';
};

const getTrendColor = (slope) => (slope > 0 ? '#d73027' : '#4575b4');
const sanitizeForFilename = (name) => String(name).replace(/[\\/*?:"<>|]/g, "");

const viewHexagonOnMap = (h3Index, findingType, secGroup, trendWindow = null) => {
    const sanitizedSecGroup = sanitizeForFilename(secGroup);
    const mapType = findingType === 'Anomaly' ? 'anomalies' : 'trends';
    const mapId = findingType === 'Trend'
        ? `map-${mapType}-${sanitizedSecGroup}-${trendWindow}`
        : `map-${mapType}-${sanitizedSecGroup}`;
    
    const mapElement = document.getElementById(mapId);
    if (mapElement) {
        mapElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    const layer = findingType === 'Trend'
        ? h3Layers[secGroup]?.[mapType]?.[trendWindow]?.[h3Index]
        : h3Layers[secGroup]?.[mapType]?.[h3Index];

    if (layer) {
        // Use a timeout to ensure scrolling is complete before opening popup
        setTimeout(() => {
            layer.openPopup();
        }, 500); // Adjust delay if needed
    }
};

const updateAnomaliesMap = (secGroup) => {
    const mapInstance = mapAnomaliesInstances[secGroup];
    const findings = findingsBySecondaryGroup.value[secGroup]?.anomalies || [];

    if (findings.length === 0 || !mapInstance) return;

    const h3Summary = {};
    findings.forEach(finding => {
        const h3Index = finding.details[`h3_index_${props.reportData.parameters.h3_resolution}`];
        if (!h3Index) return;
        if (!h3Summary[h3Index]) h3Summary[h3Index] = { anomalies: [] };
        h3Summary[h3Index].anomalies.push(finding);
    });

    const allLats = findings.map(f => f.details.lat).filter(Boolean);
    const allLons = findings.map(f => f.details.lon).filter(Boolean);
    if (allLats.length > 0) {
        const avgLat = allLats.reduce((a, b) => a + b, 0) / allLats.length;
        const avgLon = allLons.reduce((a, b) => a + b, 0) / allLons.length;
        mapInstance.setView([avgLat, avgLon], 12);
    }

    Object.keys(h3Summary).forEach(h3Index => {
        const summary = h3Summary[h3Index];
        const boundary = h3.cellToBoundary(h3Index, true).map(p => [p[1], p[0]]);
        const numAnomalies = summary.anomalies.length;
        let popupHtml = `<b>Hexagon:</b> ${h3Index}<br><b>Anomalies:</b> ${numAnomalies}<hr><ul>`;
        summary.anomalies.forEach(a => {
            popupHtml += `<li>${a.details.secondary_group} on ${a.week_details.week}: Count ${a.week_details.count} (p=${a.week_details.anomaly_p_value.toPrecision(2)})</li>`
        });
        popupHtml += "</ul>";
        const polygon = L.polygon(boundary, { color: 'black', weight: 1, fillColor: getAnomalyColor(numAnomalies), fillOpacity: 0.7 })
            .addTo(mapInstance)
            .bindPopup(popupHtml);
        h3Layers[secGroup].anomalies[h3Index] = polygon;
    });
};

const updateTrendsMap = (secGroup, trendWindowKey) => {
    const mapInstance = mapTrendsInstances[secGroup]?.[trendWindowKey];
    const findings = (findingsBySecondaryGroup.value[secGroup]?.trends || []).filter(f => f.trend_window === trendWindowKey);
    
    if (findings.length === 0 || !mapInstance) return;
    
    const h3Summary = {};
    findings.forEach(finding => {
        const h3Index = finding.details[`h3_index_${props.reportData.parameters.h3_resolution}`];
        if(!h3Index) return;
        if (!h3Summary[h3Index]) h3Summary[h3Index] = { trends: [], total_slope: 0 };
        h3Summary[h3Index].trends.push(finding);
        h3Summary[h3Index].total_slope += finding.trend_details.slope;
    });

    const allLats = findings.map(f => f.details.lat).filter(Boolean);
    const allLons = findings.map(f => f.details.lon).filter(Boolean);
    if (allLats.length > 0) {
        const avgLat = allLats.reduce((a, b) => a + b, 0) / allLats.length;
        const avgLon = allLons.reduce((a, b) => a + b, 0) / allLons.length;
        mapInstance.setView([avgLat, avgLon], 12);
    }

    Object.keys(h3Summary).forEach(h3Index => {
        const summary = h3Summary[h3Index];
        const boundary = h3.cellToBoundary(h3Index, true).map(p => [p[1], p[0]]);
        let popupHtml = `<b>Hexagon:</b> ${h3Index}<br><b>Trends:</b> ${summary.trends.length}<hr><ul>`;
        summary.trends.forEach(t => {
            const trend = t.trend_details;
            popupHtml += `<li><strong>${t.details.secondary_group}</strong>: ${trend.description} (p=${(trend.p_value || 0).toPrecision(2)})</li>`;
        });
        popupHtml += "</ul>";
        const avgSlope = summary.total_slope / summary.trends.length;
        const polygon = L.polygon(boundary, { color: 'black', weight: 1, fillColor: getTrendColor(avgSlope), fillOpacity: 0.7 })
            .addTo(mapInstance)
            .bindPopup(popupHtml);
        
        if (!h3Layers[secGroup].trends[trendWindowKey]) {
            h3Layers[secGroup].trends[trendWindowKey] = {};
        }
        h3Layers[secGroup].trends[trendWindowKey][h3Index] = polygon;
    });
};

</script>

<template>
    <PageTemplate>
        <Head :title="reportTitle" />
        <div class="container mx-auto p-4 md:p-8">
            <div v-if="isLoading" class="text-center py-20">
                <p class="text-lg text-gray-600">Loading Analysis Report...</p>
            </div>
            <div v-else-if="error" class="text-center py-20 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                <strong class="font-bold">Error!</strong>
                <span class="block sm:inline">{{ error }}</span>
            </div>
            <div v-else-if="reportData" class="space-y-12">
                <h1 class="text-4xl font-bold text-center text-gray-800">{{ reportTitle }}</h1>
                <h2 class="text-lg text-center text-gray-500">Job ID: {{ jobId }}</h2>

                <!-- Related scoring reports derived from this analysis -->
                <div v-if="relatedScoringReports.length > 0" class="flex flex-wrap items-center justify-center gap-2">
                    <span class="text-sm text-gray-500">Scoring reports derived from this analysis:</span>
                    <Link
                        v-for="sr in relatedScoringReports"
                        :key="`${sr.job_id}-${sr.artifact_name}`"
                        :href="route('scoring-reports.show', { jobId: sr.job_id, artifactName: sr.artifact_name })"
                        class="inline-flex items-center gap-1 text-sm px-3 py-1 bg-indigo-50 text-indigo-700 rounded-full border border-indigo-200 hover:bg-indigo-100 transition-colors"
                    >{{ sr.city }} · res {{ sr.resolution }} →</Link>
                </div>
                
                <!-- Top Findings: most significant individual anomalies and trends across all categories -->
                <section v-if="topAnomalyFindings.length > 0 || Object.keys(topTrendsByWindow).length > 0" class="space-y-6">

                    <!-- Anomalies table -->
                    <div v-if="topAnomalyFindings.length > 0" class="p-6 border border-amber-200 rounded-lg bg-amber-50/30">
                        <h2 class="text-lg font-semibold text-amber-800 border-b border-amber-200 pb-2 mb-3">
                            ⚠ Most Significant Anomalies
                            <span class="text-xs font-normal text-amber-600 ml-2">click a header to sort · click a row to jump to that category</span>
                        </h2>
                        <table class="w-full text-base">
                            <thead>
                                <tr>
                                    <th class="pb-1.5 w-6"></th>
                                    <th class="text-left pb-1.5 pr-3 text-sm text-gray-500 font-medium">Category</th>
                                    <th
                                        v-for="col in [['z_score','Z-score'],['p_value','p-value'],['count','Count'],['week','Week']]"
                                        :key="col[0]"
                                        class="pb-1.5 px-2 text-sm font-medium text-right cursor-pointer select-none whitespace-nowrap transition-colors"
                                        :class="anomalySortKey === col[0] ? 'text-amber-700 underline font-semibold' : 'text-gray-400 hover:text-gray-700'"
                                        @click="setAnomalySort(col[0])"
                                    >{{ col[1] }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr
                                    v-for="(f, i) in topAnomalyFindings"
                                    :key="`ta-${i}`"
                                    class="cursor-pointer hover:bg-amber-100/60 transition-colors"
                                    :class="{ 'bg-amber-100/60 font-semibold': activeSecondaryGroup === f.details.secondary_group }"
                                    @click="activateAndScrollTo(f.details.secondary_group)"
                                >
                                    <td class="py-1.5 w-6 text-center">
                                        <span :class="(f.week_details.z_score ?? 0) >= 0 ? 'text-red-500' : 'text-blue-500'" class="font-bold">{{ (f.week_details.z_score ?? 0) >= 0 ? '↑' : '↓' }}</span>
                                    </td>
                                    <td class="py-1.5 pr-3 text-gray-800">{{ f.details.secondary_group }}</td>
                                    <td class="py-1.5 px-2 text-right font-semibold text-amber-700 tabular-nums whitespace-nowrap">{{ (f.week_details.z_score ?? 0).toFixed(1) }}</td>
                                    <td class="py-1.5 px-2 text-right text-gray-500 tabular-nums whitespace-nowrap">{{ formatPValue(f.week_details.anomaly_p_value) }}</td>
                                    <td class="py-1.5 px-2 text-right text-gray-500 tabular-nums">{{ f.week_details.count }}</td>
                                    <td class="py-1.5 px-2 text-right text-gray-400 whitespace-nowrap">{{ f.week_details.week }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Trends by window -->
                    <template v-for="(findings, window) in topTrendsByWindow" :key="`tw-${window}`">
                        <div class="p-6 border border-blue-200 rounded-lg bg-blue-50/30">
                            <h2 class="text-lg font-semibold text-blue-800 border-b border-blue-200 pb-2 mb-3">
                                ↗ {{ window.replace('_', ' ') }} Trends
                                <span class="text-xs font-normal text-blue-600 ml-2">click a header to sort · click a row to jump</span>
                            </h2>
                            <table class="w-full text-base">
                                <thead>
                                    <tr>
                                        <th class="pb-1.5 w-6"></th>
                                        <th class="text-left pb-1.5 pr-3 text-sm text-gray-500 font-medium">Category</th>
                                        <th
                                            v-for="col in [['slope','Slope'],['p_value','p-value'],['category','A–Z']]"
                                            :key="col[0]"
                                            class="pb-1.5 px-2 text-sm font-medium text-right cursor-pointer select-none whitespace-nowrap transition-colors"
                                            :class="trendSortKey === col[0] ? 'text-blue-700 underline font-semibold' : 'text-gray-400 hover:text-gray-700'"
                                            @click="setTrendSort(col[0])"
                                        >{{ col[1] }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <tr
                                        v-for="(f, i) in findings"
                                        :key="`tt-${window}-${i}`"
                                        class="cursor-pointer hover:bg-blue-100/60 transition-colors"
                                        :class="{ 'bg-blue-100/60 font-semibold': activeSecondaryGroup === f.details.secondary_group }"
                                        @click="activateAndScrollTo(f.details.secondary_group)"
                                    >
                                        <td class="py-1.5 w-6 text-center">
                                            <span :class="(f.trend_details.slope ?? 0) > 0 ? 'text-red-500' : 'text-blue-500'" class="font-bold">{{ (f.trend_details.slope ?? 0) > 0 ? '↑' : '↓' }}</span>
                                        </td>
                                        <td class="py-1.5 pr-3 text-gray-800">{{ f.details.secondary_group }}</td>
                                        <td class="py-1.5 px-2 text-right font-semibold tabular-nums whitespace-nowrap" :class="(f.trend_details.slope ?? 0) > 0 ? 'text-red-600' : 'text-blue-600'">
                                            {{ f.trend_details.slope != null ? ((f.trend_details.slope > 0 ? '+' : '') + f.trend_details.slope.toFixed(2)) : '—' }}
                                        </td>
                                        <td class="py-1.5 px-2 text-right text-gray-500 tabular-nums whitespace-nowrap">{{ formatPValue(f.trend_details.p_value) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </template>

                </section>

                <!-- Anomaly categories -->
                <section v-if="groupsWithAnomalies.length > 0" class="p-6 border border-amber-200 rounded-lg bg-amber-50/20">
                    <h2 class="text-xl font-semibold text-amber-800 border-b border-amber-200 pb-2 mb-4">
                        ⚠ Categories with Anomalies
                        <span class="text-sm font-normal text-amber-600 ml-2">sorted by anomaly count</span>
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                        <div v-for="secGroup in groupsWithAnomalies" :key="`sa-${secGroup}`"
                             class="p-3 border border-amber-100 rounded-lg bg-white hover:bg-amber-50 cursor-pointer transition-colors"
                             :class="{ 'ring-2 ring-amber-400': activeSecondaryGroup === secGroup }"
                             @click="toggleSecondaryGroup(secGroup)">
                            <h3 class="font-semibold text-sm text-gray-800 truncate" :title="secGroup">{{ secGroup }}</h3>
                            <div class="flex gap-2 mt-1.5">
                                <span class="inline-flex items-center gap-1 text-xs font-semibold px-2 py-0.5 rounded-full bg-amber-100 text-amber-800">
                                    ⚠ {{ findingsBySecondaryGroup[secGroup].anomalies.length }} anomal{{ findingsBySecondaryGroup[secGroup].anomalies.length === 1 ? 'y' : 'ies' }}
                                </span>
                                <span v-if="findingsBySecondaryGroup[secGroup].trends.length > 0" class="inline-flex items-center gap-1 text-xs font-semibold px-2 py-0.5 rounded-full bg-blue-100 text-blue-800">
                                    ↗ {{ findingsBySecondaryGroup[secGroup].trends.length }}
                                </span>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Trend categories -->
                <section v-if="groupsWithTrends.length > 0" class="p-6 border border-blue-200 rounded-lg bg-blue-50/20">
                    <h2 class="text-xl font-semibold text-blue-800 border-b border-blue-200 pb-2 mb-4">
                        ↗ Categories with Trends
                        <span class="text-sm font-normal text-blue-600 ml-2">sorted by trend count</span>
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                        <div v-for="secGroup in groupsWithTrends" :key="`st-${secGroup}`"
                             class="p-3 border border-blue-100 rounded-lg bg-white hover:bg-blue-50 cursor-pointer transition-colors"
                             :class="{ 'ring-2 ring-blue-400': activeSecondaryGroup === secGroup }"
                             @click="toggleSecondaryGroup(secGroup)">
                            <h3 class="font-semibold text-sm text-gray-800 truncate" :title="secGroup">{{ secGroup }}</h3>
                            <div class="flex gap-2 mt-1.5">
                                <span class="inline-flex items-center gap-1 text-xs font-semibold px-2 py-0.5 rounded-full bg-blue-100 text-blue-800">
                                    ↗ {{ findingsBySecondaryGroup[secGroup].trends.length }} trend{{ findingsBySecondaryGroup[secGroup].trends.length === 1 ? '' : 's' }}
                                </span>
                                <span v-if="findingsBySecondaryGroup[secGroup].anomalies.length > 0" class="inline-flex items-center gap-1 text-xs font-semibold px-2 py-0.5 rounded-full bg-amber-100 text-amber-800">
                                    ⚠ {{ findingsBySecondaryGroup[secGroup].anomalies.length }}
                                </span>
                            </div>
                        </div>
                    </div>
                </section>

                <div v-if="allFindings.length > 0 && activeSecondaryGroup" class="space-y-12">
                    <section :id="`analysis-${sanitizeForFilename(activeSecondaryGroup)}`" class="p-6 border rounded-lg bg-gray-50/50 scroll-mt-4">
                        <h2 class="text-3xl font-bold mb-4 text-gray-700">Detailed Analysis for: <span class="text-indigo-600">{{ activeSecondaryGroup }}</span></h2>
                        
                        <div class="space-y-8">
                            <div>
                                <h3 class="text-2xl font-semibold border-b pb-2 mb-4">Spatial Overview of Anomalies</h3>
                                <div v-if="findingsBySecondaryGroup[activeSecondaryGroup].anomalies.length > 0">
                                    <p class="mb-4 text-gray-600">The map displays H3 cells with statistically significant anomalies. Color indicates the number of distinct anomaly types in a cell.</p>
                                    <div :id="`map-anomalies-${sanitizeForFilename(activeSecondaryGroup)}`" class="h-[500px] w-full border rounded-lg shadow-md"></div>
                                </div>
                                <div v-else class="flex items-center justify-center h-[500px] border rounded-lg bg-gray-100 text-gray-500">
                                    No significant anomalies found for this category.
                                </div>
                            </div>

                            <div>
                                <h3 class="text-2xl font-semibold border-b pb-2 mb-4">Spatial Overview of Trends</h3>
                                <div v-if="findingsBySecondaryGroup[activeSecondaryGroup].trends.length > 0" class="space-y-8">
                                    <div v-for="window in TREND_WINDOWS" :key="`trend-map-${window}`">
                                        <h4 class="text-xl font-medium mb-2">{{ window }}-Week Trend Analysis</h4>
                                        <p class="mb-4 text-gray-600">The map displays H3 cells with statistically significant trends for the {{ window }}-week period. Red indicates an upward trend; blue indicates a downward trend.</p>
                                        <div :id="`map-trends-${sanitizeForFilename(activeSecondaryGroup)}-${window}_weeks`" class="h-[500px] w-full border rounded-lg shadow-md"></div>
                                    </div>
                                </div>
                                <div v-else class="flex items-center justify-center h-[500px] border rounded-lg bg-gray-100 text-gray-500">
                                    No significant trends found for this category.
                                </div>
                            </div>
                        </div>
                    </section>

                    <section v-if="filteredFindings.length > 0" class="space-y-6">
                        <h2 class="text-2xl font-semibold border-b pb-2">Summary of Significant Findings</h2>

                        <!-- Anomalies -->
                        <div v-if="filteredAnomalyFindings.length > 0" class="overflow-x-auto rounded-lg border border-amber-200 bg-amber-50/20">
                            <table class="min-w-full text-base bg-white">
                                <thead class="bg-amber-50">
                                    <tr>
                                        <th class="py-2 px-2 border-b w-8"></th>
                                        <th class="py-2 px-4 border-b text-left text-amber-800 font-semibold">⚠ Anomalies</th>
                                        <th class="py-2 px-4 border-b text-left">H3 Cell</th>
                                        <th class="py-2 px-4 border-b">Week</th>
                                        <th class="py-2 px-4 border-b">Count</th>
                                        <th class="py-2 px-4 border-b">Avg</th>
                                        <th class="py-2 px-4 border-b">Z-Score</th>
                                        <th class="py-2 px-4 border-b">p-value</th>
                                        <th class="py-2 px-4 border-b"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(f, i) in filteredAnomalyFindings" :key="`fa-${i}`" class="hover:bg-amber-50/40 border-b">
                                        <td class="py-2 px-2 text-center">
                                            <span :class="(f.week_details.z_score ?? 0) >= 0 ? 'text-red-500' : 'text-blue-500'" class="font-bold">{{ (f.week_details.z_score ?? 0) >= 0 ? '↑' : '↓' }}</span>
                                        </td>
                                        <td class="py-2 px-4 text-gray-800">{{ f.details.secondary_group }}</td>
                                        <td class="py-2 px-4 font-mono text-xs text-gray-500">{{ f.details[`h3_index_${reportData.parameters.h3_resolution}`] }}</td>
                                        <td class="py-2 px-4 whitespace-nowrap">{{ f.week_details.week }}</td>
                                        <td class="py-2 px-4 text-right tabular-nums font-semibold text-amber-700">{{ f.week_details.count }}</td>
                                        <td class="py-2 px-4 text-right tabular-nums text-gray-500">{{ (f.details.historical_weekly_avg ?? 0).toFixed(1) }}</td>
                                        <td class="py-2 px-4 text-right tabular-nums font-semibold text-amber-700">{{ (f.week_details.z_score ?? 0).toFixed(2) }}</td>
                                        <td class="py-2 px-4 text-right tabular-nums text-gray-500">{{ formatPValue(f.week_details.anomaly_p_value) }}</td>
                                        <td class="py-2 px-4">
                                            <button @click="viewHexagonOnMap(f.details[`h3_index_${reportData.parameters.h3_resolution}`], 'Anomaly', f.details.secondary_group)" class="text-xs bg-gray-100 hover:bg-gray-200 text-gray-700 py-1 px-2 rounded whitespace-nowrap">Map</button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Trends by window -->
                        <template v-for="(findings, window) in filteredTrendsByWindow" :key="`ft-${window}`">
                            <div class="overflow-x-auto rounded-lg border border-blue-200 bg-blue-50/20">
                                <table class="min-w-full text-base bg-white">
                                    <thead class="bg-blue-50">
                                        <tr>
                                            <th class="py-2 px-2 border-b w-8"></th>
                                            <th class="py-2 px-4 border-b text-left text-blue-800 font-semibold">↗ {{ window.replace('_', ' ') }} Trends</th>
                                            <th class="py-2 px-4 border-b text-left">H3 Cell</th>
                                            <th class="py-2 px-4 border-b">Slope</th>
                                            <th class="py-2 px-4 border-b">p-value</th>
                                            <th class="py-2 px-4 border-b text-left">Description</th>
                                            <th class="py-2 px-4 border-b"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(f, i) in findings" :key="`ft-${window}-${i}`" class="hover:bg-blue-50/40 border-b">
                                            <td class="py-2 px-2 text-center">
                                                <span :class="(f.trend_details.slope ?? 0) > 0 ? 'text-red-500' : 'text-blue-500'" class="font-bold">{{ (f.trend_details.slope ?? 0) > 0 ? '↑' : '↓' }}</span>
                                            </td>
                                            <td class="py-2 px-4 text-gray-800">{{ f.details.secondary_group }}</td>
                                            <td class="py-2 px-4 font-mono text-xs text-gray-500">{{ f.details[`h3_index_${reportData.parameters.h3_resolution}`] }}</td>
                                            <td class="py-2 px-4 text-right tabular-nums font-semibold" :class="(f.trend_details.slope ?? 0) > 0 ? 'text-red-600' : 'text-blue-600'">
                                                {{ f.trend_details.slope != null ? ((f.trend_details.slope > 0 ? '+' : '') + f.trend_details.slope.toFixed(2)) : '—' }}
                                            </td>
                                            <td class="py-2 px-4 text-right tabular-nums text-gray-500">{{ formatPValue(f.trend_details.p_value) }}</td>
                                            <td class="py-2 px-4 text-gray-600 text-sm">{{ f.trend_details.description }}</td>
                                            <td class="py-2 px-4">
                                                <button @click="viewHexagonOnMap(f.details[`h3_index_${reportData.parameters.h3_resolution}`], 'Trend', f.details.secondary_group, f.trend_window)" class="text-xs bg-gray-100 hover:bg-gray-200 text-gray-700 py-1 px-2 rounded whitespace-nowrap">Map</button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </template>
                    </section>

                    <section>
                        <h2 class="text-2xl font-semibold border-b pb-2 mb-4">Detailed Analysis by H3 Cell</h2>
                         <div class="space-y-6">
                            <div v-for="(h3Data, h3Index) in filteredFindingsByH3" :key="h3Index" :id="h3Index" class="p-4 border rounded-lg bg-gray-50 scroll-mt-4">
                                <h4 class="text-xl font-bold mb-3">Hexagon: {{ h3Index }}</h4>
                                <div class="space-y-4">
                                   <div v-for="(findingsInGroup, secGroup) in h3Data.findingsBySecGroup" :key="secGroup" class="p-3 bg-white border rounded shadow-sm">
                                        <h5 class="font-semibold">Findings for '{{ secGroup }}'</h5>
                                        <ul>
                                            <li v-for="(finding, fIndex) in findingsInGroup" :key="fIndex">
                                                <template v-if="finding.type === 'Trend'"><strong>Trend ({{ finding.trend_window.replace('_', ' ') }})</strong>: {{ finding.trend_details.description }} (Slope: {{ (finding.trend_details.slope || 0).toFixed(2) }}, p-value: {{ (finding.trend_details.p_value || 0).toPrecision(4) }})</template>
                                                <template v-if="finding.type === 'Anomaly'"><strong>Anomaly on {{ finding.week_details.week }}</strong>: Count {{ finding.week_details.count }} (vs avg {{ (finding.details.historical_weekly_avg || 0).toFixed(2) }}), Z-Score: {{ (finding.week_details.z_score || 0).toFixed(2) }}, p-value: {{ (finding.week_details.anomaly_p_value || 0).toPrecision(4) }})</template>
                                                <button @click="viewHexagonOnMap(h3Index, finding.type, secGroup, finding.trend_window)" class="ml-2 text-xs bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold py-1 px-2 rounded">
                                                    View on Map
                                                </button>
                                            </li>
                                        </ul>
                                        <img :src="`${apiBaseUrl}/api/v1/jobs/${jobId}/results/plot_${h3Index}_${sanitizeForFilename(secGroup)}.png`" 
                                             :alt="`Time series plot for ${secGroup} in ${h3Index}`" 
                                             class="mt-4 rounded"
                                             @error="($event) => $event.target.style.display='none'"/>
                                    </div>
                                </div>
                            </div>
                         </div>
                    </section>
                </div>

                <div v-else-if="allFindings.length === 0" class="text-center py-6 text-gray-500">
                    <p>No statistically significant trends or anomalies were detected based on the current parameters.</p>
                </div>
            </div>
        </div>
    </PageTemplate>
</template>

<style>
/* Add scroll-margin-top for better anchor link positioning with sticky headers */
.scroll-mt-4 {
    scroll-margin-top: 4rem; /* Adjust as needed */
}
</style>