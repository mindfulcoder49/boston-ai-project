<script setup>
import { ref, onMounted, computed, nextTick } from 'vue';
import { Head } from '@inertiajs/vue3';
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
    const newActiveGroup = activeSecondaryGroup.value === secGroup ? null : secGroup;
    activeSecondaryGroup.value = newActiveGroup;

    if (newActiveGroup) {
        await nextTick();
        initializeMapsForGroup(newActiveGroup);
    }
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

const filteredFindings = computed(() => {
    if (!activeSecondaryGroup.value) {
        return [];
    }
    return allFindings.value.filter(finding => finding.details.secondary_group === activeSecondaryGroup.value);
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
                
                <section v-if="allFindings.length > 0" class="p-6 border rounded-lg bg-gray-50/50">
                    <h2 class="text-2xl font-semibold border-b pb-2 mb-4">Summary by Category</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div v-for="(groupData, secGroup) in findingsBySecondaryGroup" :key="`summary-${secGroup}`" 
                             class="p-4 border rounded-lg bg-white shadow hover:shadow-md transition-shadow cursor-pointer"
                             :class="{ 'ring-2 ring-indigo-500': activeSecondaryGroup === secGroup }"
                             @click="toggleSecondaryGroup(secGroup)">
                            <h3 class="font-bold text-lg text-indigo-700">
                                {{ secGroup }}
                            </h3>
                            <p class="text-sm text-gray-600 mt-2">
                                Found <strong>{{ groupData.anomalies.length }}</strong> significant anomalies and <strong>{{ groupData.trends.length }}</strong> significant trends.
                            </p>
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

                    <section>
                        <h2 class="text-2xl font-semibold border-b pb-2 mb-4">Summary of Significant Findings</h2>
                        <div class="overflow-x-auto" v-if="filteredFindings.length > 0">
                            <table class="min-w-full bg-white border rounded-lg">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="py-2 px-4 border-b">H3 Cell</th>
                                        <th class="py-2 px-4 border-b">{{ reportData.parameters.secondary_group_col || 'Secondary Group' }}</th>
                                        <th class="py-2 px-4 border-b">Finding Type</th>
                                        <th class="py-2 px-4 border-b">Date/Period</th>
                                        <th class="py-2 px-4 border-b">Details</th>
                                        <th class="py-2 px-4 border-b">P-Value</th>
                                        <th class="py-2 px-4 border-b">Z-Score / Slope</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(finding, index) in filteredFindings" :key="index" class="hover:bg-gray-50">
                                        <td class="py-2 px-4 border-b">
                                            <div class="flex items-center space-x-2">
                                                <a :href="`#${finding.details[`h3_index_${reportData.parameters.h3_resolution}`]}`" class="text-blue-600 hover:underline">{{ finding.details[`h3_index_${reportData.parameters.h3_resolution}`] }}</a>
                                                <button @click="viewHexagonOnMap(finding.details[`h3_index_${reportData.parameters.h3_resolution}`], finding.type, finding.details.secondary_group, finding.trend_window)" class="text-xs bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold py-1 px-2 rounded">
                                                    View on Map
                                                </button>
                                            </div>
                                        </td>
                                        <td class="py-2 px-4 border-b">{{ finding.details.secondary_group }}</td>
                                        <td class="py-2 px-4 border-b"><span :class="{'bg-yellow-200 text-yellow-800': finding.type === 'Anomaly', 'bg-blue-200 text-blue-800': finding.type === 'Trend'}" class="px-2 py-1 rounded-full text-xs font-medium">{{ finding.type }}</span></td>
                                        <td v-if="finding.type === 'Trend'" class="py-2 px-4 border-b">{{ finding.trend_window.replace('_', ' ') }}</td>
                                        <td v-else class="py-2 px-4 border-b">{{ finding.week_details.week }}</td>
                                        <td v-if="finding.type === 'Trend'" class="py-2 px-4 border-b">{{ finding.trend_details.description }}</td>
                                        <td v-else class="py-2 px-4 border-b">Count: {{ finding.week_details.count }} (vs avg {{ finding.details.historical_weekly_avg.toFixed(2) }})</td>
                                        <td v-if="finding.type === 'Trend' && finding.trend_details.p_value" class="py-2 px-4 border-b">{{ finding.trend_details.p_value.toPrecision(4) }}</td>
                                        <td v-else-if="finding.type === 'Anomaly'" class="py-2 px-4 border-b">{{ finding.week_details.anomaly_p_value.toPrecision(4) }}</td>
                                        <td v-else class="py-2 px-4 border-b">N/A</td>
                                        <td v-if="finding.type === 'Trend' && finding.trend_details.slope !== null" class="py-2 px-4 border-b">{{ finding.trend_details.slope.toFixed(2) }}</td>
                                        <td v-else-if="finding.type === 'Anomaly'" class="py-2 px-4 border-b">{{ finding.week_details.z_score.toFixed(2) }}</td>
                                        <td v-else class="py-2 px-4 border-b">N/A</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
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