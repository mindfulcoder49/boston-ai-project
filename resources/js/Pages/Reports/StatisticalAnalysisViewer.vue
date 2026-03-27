<script setup>
import { ref, onMounted, computed, nextTick } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import axios from 'axios';
import PageTemplate from '@/Components/PageTemplate.vue';
import { useH3Names } from '@/composables/useH3Names';
import { ensureGeoJsonBoundaries, getGeoJsonBoundary } from '@/Utils/h3Boundaries';
const { getName } = useH3Names();
import 'leaflet/dist/leaflet.css';
import * as L from 'leaflet';

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
    reportSummary: {
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
const loadingSecondaryGroup = ref(false);
const groupDetails = ref({});

let mapAnomaliesInstances = {};
let mapTrendsInstances = {};
let h3Layers = {};

const parameters = computed(() => props.reportSummary?.parameters ?? {
    h3_resolution: 8,
    p_value_anomaly: 0.05,
    p_value_trend: 0.05,
    analysis_weeks_trend: [],
});
const TREND_WINDOWS = computed(() => parameters.value.analysis_weeks_trend ?? []);
const h3Key = computed(() => `h3_index_${parameters.value.h3_resolution}`);

const anomalySortKey = ref('z_score');
const trendSortKey = ref('slope');

const setAnomalySort = (key) => { anomalySortKey.value = key; };
const setTrendSort = (key) => { trendSortKey.value = key; };

const groupCounts = computed(() => props.reportSummary?.group_counts ?? {});
const sortedSecondaryGroups = computed(() => props.reportSummary?.group_order ?? Object.keys(groupCounts.value));

const topAnomalyFindings = computed(() => {
    const findings = [...(props.reportSummary?.top_anomalies ?? [])];
    const comparators = {
        z_score: (a, b) => (b.week_details.z_score ?? 0) - (a.week_details.z_score ?? 0),
        p_value: (a, b) => (a.week_details.anomaly_p_value ?? 1) - (b.week_details.anomaly_p_value ?? 1),
        count: (a, b) => (b.week_details.count ?? 0) - (a.week_details.count ?? 0),
        week: (a, b) => (b.week_details.week ?? '').localeCompare(a.week_details.week ?? ''),
    };

    return findings.sort(comparators[anomalySortKey.value] ?? comparators.z_score);
});

const topTrendsByWindow = computed(() => {
    const compare = {
        slope: (a, b) => Math.abs(b.trend_details.slope ?? 0) - Math.abs(a.trend_details.slope ?? 0),
        p_value: (a, b) => (a.trend_details.p_value ?? 1) - (b.trend_details.p_value ?? 1),
        category: (a, b) => (a.details.secondary_group ?? '').localeCompare(b.details.secondary_group ?? ''),
    }[trendSortKey.value] ?? ((a, b) => Math.abs(b.trend_details.slope ?? 0) - Math.abs(a.trend_details.slope ?? 0));

    const grouped = {};
    Object.entries(props.reportSummary?.top_trends_by_window ?? {}).forEach(([window, findings]) => {
        grouped[window] = [...findings].sort(compare);
    });

    return grouped;
});

const groupsWithAnomalies = computed(() =>
    sortedSecondaryGroups.value.filter(group => (groupCounts.value[group]?.anomaly_count ?? 0) > 0)
);

const groupsWithTrends = computed(() =>
    sortedSecondaryGroups.value.filter(group => (groupCounts.value[group]?.trend_count ?? 0) > 0)
);

const currentGroupDetail = computed(() =>
    activeSecondaryGroup.value ? groupDetails.value[activeSecondaryGroup.value] ?? null : null
);

const filteredAnomalyFindings = computed(() => currentGroupDetail.value?.anomalies ?? []);
const filteredTrendsByWindow = computed(() => currentGroupDetail.value?.trends_by_window ?? {});
const filteredFindingsByH3 = computed(() => currentGroupDetail.value?.findings_by_h3 ?? {});
const activeTrendWindows = computed(() => {
    const windows = Object.keys(currentGroupDetail.value?.trends_by_window ?? {});
    return windows.sort((a, b) => parseInt(a, 10) - parseInt(b, 10));
});

onMounted(async () => {
    if (!props.reportSummary) {
        error.value = 'Failed to load report data. The analysis results might not be available.';
        return;
    }

    if (sortedSecondaryGroups.value.length > 0) {
        await setActiveSecondaryGroup(sortedSecondaryGroups.value[0]);
    }
});

function sanitizeForFilename(name) {
    return String(name).replace(/[\\/*?:"<>|]/g, "");
}

function destroyGroupMaps(secGroup) {
    mapAnomaliesInstances[secGroup]?.remove();
    delete mapAnomaliesInstances[secGroup];

    Object.values(mapTrendsInstances[secGroup] ?? {}).forEach(map => map.remove());
    delete mapTrendsInstances[secGroup];
    delete h3Layers[secGroup];
}

async function loadSecondaryGroup(secGroup) {
    if (!secGroup) {
        return null;
    }

    if (groupDetails.value[secGroup]) {
        return groupDetails.value[secGroup];
    }

    loadingSecondaryGroup.value = true;
    try {
        const response = await axios.get(route('reports.statistical-analysis.group-detail', { jobId: props.jobId }), {
            params: { secondary_group: secGroup },
        });

        groupDetails.value = {
            ...groupDetails.value,
            [secGroup]: response.data,
        };

        return response.data;
    } catch (err) {
        console.error('Failed to load group detail:', err);
        error.value = 'Could not load detailed findings for this category.';
        return null;
    } finally {
        loadingSecondaryGroup.value = false;
    }
}

async function initializeMapsForGroup(secGroup) {
    const detail = groupDetails.value[secGroup];
    if (!detail) {
        return;
    }

    const sanitizedSecGroup = sanitizeForFilename(secGroup);
    h3Layers[secGroup] ??= { anomalies: {}, trends: {} };
    mapTrendsInstances[secGroup] ??= {};

    const anomalyContainerId = `map-anomalies-${sanitizedSecGroup}`;
    const anomalyContainer = document.getElementById(anomalyContainerId);
    if (anomalyContainer && !mapAnomaliesInstances[secGroup] && (detail.anomaly_cells?.length ?? 0) > 0) {
        const map = L.map(anomalyContainer).setView([42.3601, -71.0589], 12);
        L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>'
        }).addTo(map);
        mapAnomaliesInstances[secGroup] = map;
        await updateAnomaliesMap(secGroup);
    }

    for (const window of activeTrendWindows.value) {
        const trendContainerId = `map-trends-${sanitizedSecGroup}-${window}`;
        const trendContainer = document.getElementById(trendContainerId);
        const cells = detail.trend_cells_by_window?.[window] ?? [];

        if (trendContainer && !mapTrendsInstances[secGroup][window] && cells.length > 0) {
            const map = L.map(trendContainer).setView([42.3601, -71.0589], 12);
            L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>'
            }).addTo(map);
            mapTrendsInstances[secGroup][window] = map;
            await updateTrendsMap(secGroup, window);
        }
    }
}

async function setActiveSecondaryGroup(secGroup) {
    const prevGroup = activeSecondaryGroup.value;
    if (prevGroup) {
        destroyGroupMaps(prevGroup);
    }

    activeSecondaryGroup.value = secGroup;
    if (!secGroup) {
        return;
    }

    const detail = await loadSecondaryGroup(secGroup);
    if (!detail) {
        return;
    }

    await nextTick();
    await initializeMapsForGroup(secGroup);
}

async function toggleSecondaryGroup(secGroup) {
    if (activeSecondaryGroup.value === secGroup) {
        if (activeSecondaryGroup.value) {
            destroyGroupMaps(activeSecondaryGroup.value);
        }
        activeSecondaryGroup.value = null;
        return;
    }

    await setActiveSecondaryGroup(secGroup);
}

async function activateAndScrollTo(secGroup) {
    if (activeSecondaryGroup.value !== secGroup) {
        await setActiveSecondaryGroup(secGroup);
    }

    await nextTick();
    document.getElementById(`analysis-${sanitizeForFilename(secGroup)}`)
        ?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function getAnomalyColor(count) {
    if (count >= 1 && count <= 10) {
        const scale = ['#FFFFE5', '#FFF7BC', '#FEE391', '#FEC44F', '#FE9929', '#EC7014', '#CC4C02', '#993404', '#662506', '#E31A1C'];
        return scale[count - 1];
    }
    if (count > 10 && count <= 20) return '#800080';
    return '#000000';
}

function getTrendColor(slope) {
    return slope > 0 ? '#d73027' : '#4575b4';
}

async function updateAnomaliesMap(secGroup) {
    const mapInstance = mapAnomaliesInstances[secGroup];
    const cells = groupDetails.value[secGroup]?.anomaly_cells ?? [];

    if (!mapInstance || cells.length === 0) {
        return;
    }

    const lats = cells.map(cell => cell.lat).filter(value => value !== null && value !== undefined);
    const lons = cells.map(cell => cell.lon).filter(value => value !== null && value !== undefined);
    if (lats.length > 0 && lons.length > 0) {
        const avgLat = lats.reduce((sum, value) => sum + value, 0) / lats.length;
        const avgLon = lons.reduce((sum, value) => sum + value, 0) / lons.length;
        mapInstance.setView([avgLat, avgLon], 12);
    }

    await ensureGeoJsonBoundaries(cells.map(cell => cell.h3_index));

    cells.forEach(cell => {
        const boundary = getGeoJsonBoundary(cell.h3_index)?.map(point => [point[1], point[0]]);
        if (!boundary) {
            return;
        }

        let popupHtml = `<b>${getName(cell.h3_index)}</b><br><span style="font-family:monospace;font-size:11px;color:#6b7280">${cell.h3_index}</span><br><b>Anomalies:</b> ${cell.anomalies.length}<hr><ul>`;
        cell.anomalies.forEach(anomaly => {
            popupHtml += `<li>${anomaly.secondary_group} on ${anomaly.week}: Count ${anomaly.count} (p=${anomaly.anomaly_p_value?.toPrecision?.(2) ?? 'N/A'})</li>`;
        });
        popupHtml += '</ul>';

        const polygon = L.polygon(boundary, {
            color: 'black',
            weight: 1,
            fillColor: getAnomalyColor(cell.anomalies.length),
            fillOpacity: 0.7,
        }).addTo(mapInstance).bindPopup(popupHtml);

        h3Layers[secGroup].anomalies[cell.h3_index] = polygon;
    });
}

async function updateTrendsMap(secGroup, trendWindow) {
    const mapInstance = mapTrendsInstances[secGroup]?.[trendWindow];
    const cells = groupDetails.value[secGroup]?.trend_cells_by_window?.[trendWindow] ?? [];

    if (!mapInstance || cells.length === 0) {
        return;
    }

    const lats = cells.map(cell => cell.lat).filter(value => value !== null && value !== undefined);
    const lons = cells.map(cell => cell.lon).filter(value => value !== null && value !== undefined);
    if (lats.length > 0 && lons.length > 0) {
        const avgLat = lats.reduce((sum, value) => sum + value, 0) / lats.length;
        const avgLon = lons.reduce((sum, value) => sum + value, 0) / lons.length;
        mapInstance.setView([avgLat, avgLon], 12);
    }

    await ensureGeoJsonBoundaries(cells.map(cell => cell.h3_index));

    cells.forEach(cell => {
        const boundary = getGeoJsonBoundary(cell.h3_index)?.map(point => [point[1], point[0]]);
        if (!boundary) {
            return;
        }

        let popupHtml = `<b>${getName(cell.h3_index)}</b><br><span style="font-family:monospace;font-size:11px;color:#6b7280">${cell.h3_index}</span><br><b>Trends:</b> ${cell.trends.length}<hr><ul>`;
        cell.trends.forEach(trend => {
            popupHtml += `<li><strong>${trend.secondary_group}</strong>: ${trend.description} (p=${trend.p_value?.toPrecision?.(2) ?? 'N/A'})</li>`;
        });
        popupHtml += '</ul>';

        const polygon = L.polygon(boundary, {
            color: 'black',
            weight: 1,
            fillColor: getTrendColor(cell.avg_slope),
            fillOpacity: 0.7,
        }).addTo(mapInstance).bindPopup(popupHtml);

        h3Layers[secGroup].trends[trendWindow] ??= {};
        h3Layers[secGroup].trends[trendWindow][cell.h3_index] = polygon;
    });
}

function viewHexagonOnMap(h3Index, findingType, secGroup, trendWindow = null) {
    const sanitizedSecGroup = sanitizeForFilename(secGroup);
    const mapType = findingType === 'Anomaly' ? 'anomalies' : 'trends';
    const mapId = findingType === 'Trend'
        ? `map-${mapType}-${sanitizedSecGroup}-${trendWindow}`
        : `map-${mapType}-${sanitizedSecGroup}`;

    document.getElementById(mapId)?.scrollIntoView({ behavior: 'smooth', block: 'center' });

    const layer = findingType === 'Trend'
        ? h3Layers[secGroup]?.[mapType]?.[trendWindow]?.[h3Index]
        : h3Layers[secGroup]?.[mapType]?.[h3Index];

    if (layer) {
        setTimeout(() => {
            layer.openPopup();
        }, 500);
    }
}

function formatPValue(p) {
    if (p == null) return 'N/A';
    if (p === 0) return '< 1e-15';
    if (p < 0.001) return p.toExponential(2);
    if (p < 0.01) return p.toFixed(4);
    return p.toFixed(3);
}
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
            <div v-else-if="reportSummary" class="space-y-12">
                <h1 class="text-4xl font-bold text-center text-gray-800">{{ reportTitle }}</h1>
                <h2 class="text-lg text-center text-gray-500">Job ID: {{ jobId }}</h2>

                <div v-if="relatedScoringReports.length > 0" class="flex flex-wrap items-center justify-center gap-2">
                    <span class="text-sm text-gray-500">Scoring reports derived from this analysis:</span>
                    <Link
                        v-for="sr in relatedScoringReports"
                        :key="`${sr.job_id}-${sr.artifact_name}`"
                        :href="route('scoring-reports.show', { jobId: sr.job_id, artifactName: sr.artifact_name })"
                        class="inline-flex items-center gap-1 text-sm px-3 py-1 bg-indigo-50 text-indigo-700 rounded-full border border-indigo-200 hover:bg-indigo-100 transition-colors"
                    >{{ sr.city }} · res {{ sr.resolution }} →</Link>
                </div>

                <section v-if="topAnomalyFindings.length > 0 || Object.keys(topTrendsByWindow).length > 0" class="space-y-6">
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
                                    <th class="text-left pb-1.5 pr-3 text-sm text-gray-500 font-medium">Area</th>
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
                                    <td class="py-1.5 pr-3 text-xs">
                                        <template v-if="f.details[h3Key]">
                                            <span class="text-gray-700">{{ getName(f.details[h3Key]) }}</span><br>
                                            <span class="font-mono text-gray-400">{{ f.details[h3Key] }}</span>
                                        </template>
                                        <span v-else class="text-gray-300">—</span>
                                    </td>
                                    <td class="py-1.5 px-2 text-right font-semibold text-amber-700 tabular-nums whitespace-nowrap">{{ (f.week_details.z_score ?? 0).toFixed(1) }}</td>
                                    <td class="py-1.5 px-2 text-right text-gray-500 tabular-nums whitespace-nowrap">{{ formatPValue(f.week_details.anomaly_p_value) }}</td>
                                    <td class="py-1.5 px-2 text-right text-gray-500 tabular-nums">{{ f.week_details.count }}</td>
                                    <td class="py-1.5 px-2 text-right text-gray-400 whitespace-nowrap">{{ f.week_details.week }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

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
                                        <th class="text-left pb-1.5 pr-3 text-sm text-gray-500 font-medium">Area</th>
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
                                        <td class="py-1.5 pr-3 text-xs">
                                            <template v-if="f.details[h3Key]">
                                                <span class="text-gray-700">{{ getName(f.details[h3Key]) }}</span><br>
                                                <span class="font-mono text-gray-400">{{ f.details[h3Key] }}</span>
                                            </template>
                                            <span v-else class="text-gray-300">—</span>
                                        </td>
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

                <section v-if="groupsWithAnomalies.length > 0" class="p-6 border border-amber-200 rounded-lg bg-amber-50/20">
                    <h2 class="text-xl font-semibold text-amber-800 border-b border-amber-200 pb-2 mb-4">
                        ⚠ Categories with Anomalies
                        <span class="text-sm font-normal text-amber-600 ml-2">sorted by anomaly count</span>
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                        <div
                            v-for="secGroup in groupsWithAnomalies"
                            :key="`sa-${secGroup}`"
                            class="p-3 border border-amber-100 rounded-lg bg-white hover:bg-amber-50 cursor-pointer transition-colors"
                            :class="{ 'ring-2 ring-amber-400': activeSecondaryGroup === secGroup }"
                            @click="toggleSecondaryGroup(secGroup)"
                        >
                            <h3 class="font-semibold text-sm text-gray-800 truncate" :title="secGroup">{{ secGroup }}</h3>
                            <div class="flex gap-2 mt-1.5">
                                <span class="inline-flex items-center gap-1 text-xs font-semibold px-2 py-0.5 rounded-full bg-amber-100 text-amber-800">
                                    ⚠ {{ groupCounts[secGroup].anomaly_count }} anomal{{ groupCounts[secGroup].anomaly_count === 1 ? 'y' : 'ies' }}
                                </span>
                                <span v-if="groupCounts[secGroup].trend_count > 0" class="inline-flex items-center gap-1 text-xs font-semibold px-2 py-0.5 rounded-full bg-blue-100 text-blue-800">
                                    ↗ {{ groupCounts[secGroup].trend_count }}
                                </span>
                            </div>
                        </div>
                    </div>
                </section>

                <section v-if="groupsWithTrends.length > 0" class="p-6 border border-blue-200 rounded-lg bg-blue-50/20">
                    <h2 class="text-xl font-semibold text-blue-800 border-b border-blue-200 pb-2 mb-4">
                        ↗ Categories with Trends
                        <span class="text-sm font-normal text-blue-600 ml-2">sorted by trend count</span>
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                        <div
                            v-for="secGroup in groupsWithTrends"
                            :key="`st-${secGroup}`"
                            class="p-3 border border-blue-100 rounded-lg bg-white hover:bg-blue-50 cursor-pointer transition-colors"
                            :class="{ 'ring-2 ring-blue-400': activeSecondaryGroup === secGroup }"
                            @click="toggleSecondaryGroup(secGroup)"
                        >
                            <h3 class="font-semibold text-sm text-gray-800 truncate" :title="secGroup">{{ secGroup }}</h3>
                            <div class="flex gap-2 mt-1.5">
                                <span class="inline-flex items-center gap-1 text-xs font-semibold px-2 py-0.5 rounded-full bg-blue-100 text-blue-800">
                                    ↗ {{ groupCounts[secGroup].trend_count }} trend{{ groupCounts[secGroup].trend_count === 1 ? '' : 's' }}
                                </span>
                                <span v-if="groupCounts[secGroup].anomaly_count > 0" class="inline-flex items-center gap-1 text-xs font-semibold px-2 py-0.5 rounded-full bg-amber-100 text-amber-800">
                                    ⚠ {{ groupCounts[secGroup].anomaly_count }}
                                </span>
                            </div>
                        </div>
                    </div>
                </section>

                <div v-if="reportSummary.total_findings > 0 && activeSecondaryGroup" class="space-y-12">
                    <section :id="`analysis-${sanitizeForFilename(activeSecondaryGroup)}`" class="p-6 border rounded-lg bg-gray-50/50 scroll-mt-4">
                        <h2 class="text-3xl font-bold mb-4 text-gray-700">Detailed Analysis for: <span class="text-indigo-600">{{ activeSecondaryGroup }}</span></h2>

                        <div v-if="loadingSecondaryGroup" class="text-center py-12 text-gray-500">
                            Loading detailed findings...
                        </div>

                        <div v-else-if="currentGroupDetail" class="space-y-8">
                            <div>
                                <h3 class="text-2xl font-semibold border-b pb-2 mb-4">Spatial Overview of Anomalies</h3>
                                <div v-if="currentGroupDetail.anomaly_cells.length > 0">
                                    <p class="mb-4 text-gray-600">The map displays H3 cells with statistically significant anomalies. Color indicates the number of distinct anomaly types in a cell.</p>
                                    <div :id="`map-anomalies-${sanitizeForFilename(activeSecondaryGroup)}`" class="h-[500px] w-full border rounded-lg shadow-md"></div>
                                </div>
                                <div v-else class="flex items-center justify-center h-[500px] border rounded-lg bg-gray-100 text-gray-500">
                                    No significant anomalies found for this category.
                                </div>
                            </div>

                            <div>
                                <h3 class="text-2xl font-semibold border-b pb-2 mb-4">Spatial Overview of Trends</h3>
                                <div v-if="activeTrendWindows.length > 0" class="space-y-8">
                                    <div v-for="window in activeTrendWindows" :key="`trend-map-${window}`">
                                        <h4 class="text-xl font-medium mb-2">{{ window.replace('_', ' ') }} Trend Analysis</h4>
                                        <p class="mb-4 text-gray-600">The map displays H3 cells with statistically significant trends for this period. Red indicates an upward trend; blue indicates a downward trend.</p>
                                        <div :id="`map-trends-${sanitizeForFilename(activeSecondaryGroup)}-${window}`" class="h-[500px] w-full border rounded-lg shadow-md"></div>
                                    </div>
                                </div>
                                <div v-else class="flex items-center justify-center h-[500px] border rounded-lg bg-gray-100 text-gray-500">
                                    No significant trends found for this category.
                                </div>
                            </div>
                        </div>
                    </section>

                    <section v-if="filteredAnomalyFindings.length > 0 || Object.keys(filteredTrendsByWindow).length > 0" class="space-y-6">
                        <h2 class="text-2xl font-semibold border-b pb-2">Summary of Significant Findings</h2>

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
                                        <td class="py-2 px-4">
                                            <div class="text-sm text-gray-800">{{ getName(f.details[h3Key]) }}</div>
                                            <div class="font-mono text-xs text-gray-400">{{ f.details[h3Key] }}</div>
                                        </td>
                                        <td class="py-2 px-4 whitespace-nowrap">{{ f.week_details.week }}</td>
                                        <td class="py-2 px-4 text-right tabular-nums font-semibold text-amber-700">{{ f.week_details.count }}</td>
                                        <td class="py-2 px-4 text-right tabular-nums text-gray-500">{{ (f.details.historical_weekly_avg ?? 0).toFixed(1) }}</td>
                                        <td class="py-2 px-4 text-right tabular-nums font-semibold text-amber-700">{{ (f.week_details.z_score ?? 0).toFixed(2) }}</td>
                                        <td class="py-2 px-4 text-right tabular-nums text-gray-500">{{ formatPValue(f.week_details.anomaly_p_value) }}</td>
                                        <td class="py-2 px-4">
                                            <button @click="viewHexagonOnMap(f.details[h3Key], 'Anomaly', f.details.secondary_group)" class="text-xs bg-gray-100 hover:bg-gray-200 text-gray-700 py-1 px-2 rounded whitespace-nowrap">Map</button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

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
                                            <td class="py-2 px-4">
                                                <div class="text-sm text-gray-800">{{ getName(f.details[h3Key]) }}</div>
                                                <div class="font-mono text-xs text-gray-400">{{ f.details[h3Key] }}</div>
                                            </td>
                                            <td class="py-2 px-4 text-right tabular-nums font-semibold" :class="(f.trend_details.slope ?? 0) > 0 ? 'text-red-600' : 'text-blue-600'">
                                                {{ f.trend_details.slope != null ? ((f.trend_details.slope > 0 ? '+' : '') + f.trend_details.slope.toFixed(2)) : '—' }}
                                            </td>
                                            <td class="py-2 px-4 text-right tabular-nums text-gray-500">{{ formatPValue(f.trend_details.p_value) }}</td>
                                            <td class="py-2 px-4 text-gray-600 text-sm">{{ f.trend_details.description }}</td>
                                            <td class="py-2 px-4">
                                                <button @click="viewHexagonOnMap(f.details[h3Key], 'Trend', f.details.secondary_group, f.trend_window)" class="text-xs bg-gray-100 hover:bg-gray-200 text-gray-700 py-1 px-2 rounded whitespace-nowrap">Map</button>
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
                                <h4 class="text-xl font-bold mb-1">{{ getName(h3Index) }}</h4>
                                <p class="font-mono text-xs text-gray-400 mb-3">{{ h3Index }}</p>
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
                                        <img
                                            :src="`${apiBaseUrl}/api/v1/jobs/${jobId}/results/plot_${h3Index}_${sanitizeForFilename(secGroup)}.png`"
                                            :alt="`Time series plot for ${secGroup} in ${h3Index}`"
                                            class="mt-4 rounded"
                                            loading="lazy"
                                            @error="($event) => $event.target.style.display='none'"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>

                <div v-else-if="reportSummary.total_findings === 0" class="text-center py-6 text-gray-500">
                    <p>No statistically significant trends or anomalies were detected based on the current parameters.</p>
                </div>
            </div>
        </div>
    </PageTemplate>
</template>

<style>
.scroll-mt-4 {
    scroll-margin-top: 4rem;
}
</style>
