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
});

const reportData = ref(null);
const isLoading = ref(true);
const error = ref(null);

let mapAnomaliesInstance = null;
let mapTrendsInstance = null;

const P_VALUE_ANOMALY = computed(() => reportData.value?.parameters?.p_value_anomaly || 0.05);
const P_VALUE_TREND = computed(() => reportData.value?.parameters?.p_value_trend || 0.05);

onMounted(async () => {
    try {
        const response = await fetch(`${props.apiBaseUrl}/api/v1/jobs/${props.jobId}/results/stage4_h3_anomaly.json`);
        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);

        const data = await response.json();
        reportData.value = data;

        // --- THE FIX ---
        // 1. We have the data, so set loading to false.
        //    This will trigger Vue to re-render the template and show the v-else-if block.
        isLoading.value = false;

        // 2. Wait for Vue's DOM update to complete.
        await nextTick();

        // 3. Now that the DOM is updated and the map containers exist, initialize everything.
        initializeMaps();
        processAndDisplayData();

    } catch (e) {
        console.error('Failed to fetch or process report data:', e);
        error.value = `Failed to load report data. Please check the analysis job status. (${e.message})`;
        isLoading.value = false; // Also ensure loading is false on error
    }
});


const initializeMaps = () => {
    const anomalyContainer = document.getElementById('map-anomalies');
    const trendsContainer = document.getElementById('map-trends');

    if (!anomalyContainer || !trendsContainer) {
        const errorMessage = "Failed to initialize maps because the map container elements were not found in the DOM.";
        console.error(errorMessage);
        error.value = errorMessage;
        return;
    }

    mapAnomaliesInstance = L.map(anomalyContainer).setView([42.3601, -71.0589], 12);
    mapTrendsInstance = L.map(trendsContainer).setView([42.3601, -71.0589], 12);

    L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>'
    }).addTo(mapAnomaliesInstance);

    L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>'
    }).addTo(mapTrendsInstance);
};

const allFindings = ref([]);
const anomalyFindings = ref([]);
const trendFindings = ref([]);
const findingsByH3 = ref({});

const processAndDisplayData = () => {
    if (!reportData.value) return;

    const params = reportData.value.parameters;
    const h3Col = `h3_index_${params.h3_resolution}`;

    const localAllFindings = [];
    const localAnomalyFindings = [];
    const localTrendFindings = [];

    (reportData.value.results || []).forEach(row => {
        if (row.trend_analysis) {
            const trendP = row.trend_analysis.p_value;
            if (trendP !== null && trendP < P_VALUE_TREND.value) {
                const finding = { type: 'Trend', details: row };
                localAllFindings.push(finding);
                localTrendFindings.push(finding);
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
        const pValA = a.type === 'Trend' ? a.details.trend_analysis.p_value : a.week_details.anomaly_p_value;
        const pValB = b.type === 'Trend' ? b.details.trend_analysis.p_value : b.week_details.anomaly_p_value;
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

    updateAnomaliesMap();
    updateTrendsMap();
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

const updateAnomaliesMap = () => {
    if (anomalyFindings.value.length === 0 || !mapAnomaliesInstance) return;

    const h3Summary = {};
    anomalyFindings.value.forEach(finding => {
        const h3Index = finding.details[`h3_index_${reportData.value.parameters.h3_resolution}`];
        if (!h3Index) return;
        if (!h3Summary[h3Index]) h3Summary[h3Index] = { anomalies: [] };
        h3Summary[h3Index].anomalies.push(finding);
    });

    const allLats = anomalyFindings.value.map(f => f.details.lat).filter(Boolean);
    const allLons = anomalyFindings.value.map(f => f.details.lon).filter(Boolean);
    if (allLats.length > 0) {
        const avgLat = allLats.reduce((a, b) => a + b, 0) / allLats.length;
        const avgLon = allLons.reduce((a, b) => a + b, 0) / allLons.length;
        mapAnomaliesInstance.setView([avgLat, avgLon], 12);
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
        L.polygon(boundary, { color: 'black', weight: 1, fillColor: getAnomalyColor(numAnomalies), fillOpacity: 0.7 })
            .addTo(mapAnomaliesInstance)
            .bindPopup(popupHtml);
    });
};

const updateTrendsMap = () => {
    if (trendFindings.value.length === 0 || !mapTrendsInstance) return;
    
    const h3Summary = {};
    trendFindings.value.forEach(finding => {
        const h3Index = finding.details[`h3_index_${reportData.value.parameters.h3_resolution}`];
        if(!h3Index) return;
        if (!h3Summary[h3Index]) h3Summary[h3Index] = { trends: [], total_slope: 0 };
        h3Summary[h3Index].trends.push(finding);
        h3Summary[h3Index].total_slope += finding.details.trend_analysis.slope;
    });

    const allLats = trendFindings.value.map(f => f.details.lat).filter(Boolean);
    const allLons = trendFindings.value.map(f => f.details.lon).filter(Boolean);
    if (allLats.length > 0) {
        const avgLat = allLats.reduce((a, b) => a + b, 0) / allLats.length;
        const avgLon = allLons.reduce((a, b) => a + b, 0) / allLons.length;
        mapTrendsInstance.setView([avgLat, avgLon], 12);
    }

    Object.keys(h3Summary).forEach(h3Index => {
        const summary = h3Summary[h3Index];
        const boundary = h3.cellToBoundary(h3Index, true).map(p => [p[1], p[0]]);
        let popupHtml = `<b>Hexagon:</b> ${h3Index}<br><b>Trends:</b> ${summary.trends.length}<hr><ul>`;
        summary.trends.forEach(t => {
            const trend = t.details.trend_analysis;
            popupHtml += `<li><strong>${t.details.secondary_group}</strong>: ${trend.description} (p=${(trend.p_value || 0).toPrecision(2)})</li>`;
        });
        popupHtml += "</ul>";
        const avgSlope = summary.total_slope / summary.trends.length;
        L.polygon(boundary, { color: 'black', weight: 1, fillColor: getTrendColor(avgSlope), fillOpacity: 0.7 })
            .addTo(mapTrendsInstance)
            .bindPopup(popupHtml);
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
                
                <section>
                    <h2 class="text-2xl font-semibold border-b pb-2 mb-4">Spatial Overview of Anomalies</h2>
                    <p class="mb-4 text-gray-600">The map displays H3 cells with statistically significant anomalies. Color indicates the number of distinct anomaly types in a cell.</p>
                    <div id="map-anomalies" class="h-[500px] w-full border rounded-lg shadow-md"></div>
                </section>

                <section>
                    <h2 class="text-2xl font-semibold border-b pb-2 mb-4">Spatial Overview of Trends</h2>
                    <p class="mb-4 text-gray-600">The map displays H3 cells with statistically significant trends. Red indicates an upward trend; blue indicates a downward trend.</p>
                    <div id="map-trends" class="h-[500px] w-full border rounded-lg shadow-md"></div>
                </section>

                <section>
                    <h2 class="text-2xl font-semibold border-b pb-2 mb-4">Summary of All Significant Findings</h2>
                    <div class="overflow-x-auto" v-if="allFindings.length > 0">
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
                                <tr v-for="(finding, index) in allFindings" :key="index" class="hover:bg-gray-50">
                                    <td class="py-2 px-4 border-b"><a :href="`#${finding.details[`h3_index_${reportData.parameters.h3_resolution}`]}`" class="text-blue-600 hover:underline">{{ finding.details[`h3_index_${reportData.parameters.h3_resolution}`] }}</a></td>
                                    <td class="py-2 px-4 border-b">{{ finding.details.secondary_group }}</td>
                                    <td class="py-2 px-4 border-b"><span :class="{'bg-yellow-200 text-yellow-800': finding.type === 'Anomaly', 'bg-blue-200 text-blue-800': finding.type === 'Trend'}" class="px-2 py-1 rounded-full text-xs font-medium">{{ finding.type }}</span></td>
                                    <td v-if="finding.type === 'Trend'" class="py-2 px-4 border-b">Last {{ reportData.parameters.analysis_weeks_trend }} Weeks</td>
                                    <td v-else class="py-2 px-4 border-b">{{ finding.week_details.week }}</td>
                                    <td v-if="finding.type === 'Trend'" class="py-2 px-4 border-b">{{ finding.details.trend_analysis.description }}</td>
                                    <td v-else class="py-2 px-4 border-b">Count: {{ finding.week_details.count }} (vs avg {{ finding.details.historical_weekly_avg.toFixed(2) }})</td>
                                    <td v-if="finding.type === 'Trend' && finding.details.trend_analysis.p_value" class="py-2 px-4 border-b">{{ finding.details.trend_analysis.p_value.toPrecision(4) }}</td>
                                    <td v-else-if="finding.type === 'Anomaly'" class="py-2 px-4 border-b">{{ finding.week_details.anomaly_p_value.toPrecision(4) }}</td>
                                    <td v-else class="py-2 px-4 border-b">N/A</td>
                                    <td v-if="finding.type === 'Trend' && finding.details.trend_analysis.slope !== null" class="py-2 px-4 border-b">{{ finding.details.trend_analysis.slope.toFixed(2) }}</td>
                                    <td v-else-if="finding.type === 'Anomaly'" class="py-2 px-4 border-b">{{ finding.week_details.z_score.toFixed(2) }}</td>
                                    <td v-else class="py-2 px-4 border-b">N/A</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                     <div v-else class="text-center py-6 text-gray-500">
                        <p>No statistically significant trends or anomalies were detected based on the current parameters.</p>
                    </div>
                </section>

                <section v-if="allFindings.length > 0">
                    <h2 class="text-2xl font-semibold border-b pb-2 mb-4">Detailed Analysis by H3 Cell</h2>
                     <div class="space-y-6">
                        <div v-for="(h3Data, h3Index) in findingsByH3" :key="h3Index" :id="h3Index" class="p-4 border rounded-lg bg-gray-50 scroll-mt-4">
                            <h4 class="text-xl font-bold mb-3">Hexagon: {{ h3Index }}</h4>
                            <div class="space-y-4">
                               <div v-for="(findingsInGroup, secGroup) in h3Data.findingsBySecGroup" :key="secGroup" class="p-3 bg-white border rounded shadow-sm">
                                    <h5 class="font-semibold">Findings for '{{ secGroup }}'</h5>
                                    <ul>
                                        <li v-for="(finding, fIndex) in findingsInGroup" :key="fIndex">
                                            <template v-if="finding.type === 'Trend'"><strong>Trend</strong>: {{ finding.details.trend_analysis.description }} (Slope: {{ (finding.details.trend_analysis.slope || 0).toFixed(2) }}, p-value: {{ (finding.details.trend_analysis.p_value || 0).toPrecision(4) }})</template>
                                            <template v-if="finding.type === 'Anomaly'"><strong>Anomaly on {{ finding.week_details.week }}</strong>: Count {{ finding.week_details.count }} (vs avg {{ (finding.details.historical_weekly_avg || 0).toFixed(2) }}), Z-Score: {{ (finding.week_details.z_score || 0).toFixed(2) }}, p-value: {{ (finding.week_details.anomaly_p_value || 0).toPrecision(4) }})</template>
                                        </li>
                                    </ul>
                                    <img :src="`${apiBaseUrl}/api/v1/jobs/${jobId}/results/plot_${h3Index}_${sanitizeForFilename(secGroup)}.png`" 
                                         :alt="`Time series plot for ${secGroup} in ${h3Index}`" 
                                         class="mt-4 max-w-full md:max-w-xl rounded"
                                         @error="($event) => $event.target.style.display='none'"/>
                                </div>
                            </div>
                        </div>
                     </div>
                </section>
            </div>
        </div>
    </PageTemplate>
</template>

<style>
/* Add scroll-margin-top for better anchor link positioning with sticky headers */
.scroll-mt-4 {
    scroll-margin-top: 1rem; /* Adjust as needed */
}
</style>