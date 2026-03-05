<template>
    <PageTemplate>
        <Head :title="reportTitle" />
        <div class="container mx-auto p-4 md:p-8">
            <h1 class="text-4xl font-bold text-center text-gray-800">{{ reportTitle }}</h1>
            <h2 class="text-lg text-center text-gray-500">Job ID: {{ initialReport.job_id }}</h2>
            <div v-if="sourceTrend" class="text-center mt-2 mb-8">
                <Link
                    :href="route('reports.statistical-analysis.show', { trendId: sourceTrend.trend_id })"
                    class="inline-flex items-center gap-1 text-sm text-indigo-600 hover:text-indigo-800 hover:underline"
                >← Source analysis: {{ sourceTrend.title }}</Link>
            </div>
            <div v-else class="mb-8"></div>

            <!-- Search Section -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
                <div class="bg-white p-6 rounded-lg shadow">
                    <h3 class="text-xl font-semibold mb-4">Find Score by Address</h3>
                    <GoogleAddressSearch @address-selected="handleAddressSelection" />
                </div>
                <div class="bg-white p-6 rounded-lg shadow">
                    <h3 class="text-xl font-semibold mb-4">Find by Hexagon ID</h3>
                    <div class="flex items-center space-x-2">
                        <input type="text" v-model="hexagonIdSearch" placeholder="Enter H3 Index" class="border w-full p-2 rounded-md">
                        <button @click="searchByHexagonId" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                            Search
                        </button>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Map and Controls Column -->
                <div class="lg:col-span-2 space-y-4">
                    <div id="map" class="h-[600px] min-h-[300px] w-full border rounded-lg shadow-md resize-y overflow-auto"></div>
                    <ReportResolutionControl
                        v-if="reportGroup.length > 0"
                        :report-group="reportGroup"
                        v-model="baseResolution"
                        v-model:mode="mapMode"
                        v-model:color-steps="colorSteps"
                    />
                </div>

                <!-- Methodology Column -->
                <div class="space-y-6">
                    <div v-if="currentReportData?.parameters" class="bg-white p-6 rounded-lg shadow">
                        <h3 class="text-xl font-semibold mb-4">Scoring Methodology</h3>
                        <!-- Anomaly-based Report Methodology -->
                        <div v-if="isAnomalyBasedReport" class="text-sm space-y-2">
                            <p>
                                <span class="font-semibold">Formula:</span>
                                <code class="bg-gray-200 text-gray-800 rounded px-1 py-0.5 text-xs">{{ currentReportData.parameters.group_score_formula }}</code>
                            </p>
                            <p>
                                <span class="font-semibold">Aggregation:</span>
                                <span class="capitalize">{{ currentReportData.parameters.h3_aggregation_method }}</span> of weighted scores per hexagon.
                            </p>
                        </div>
                        <!-- Historical Report Methodology -->
                        <div v-else class="text-sm space-y-2">
                             <p>
                                This report calculates a score based on the weighted average of historical incident counts over a
                                <span class="font-semibold">{{ currentReportData.parameters.analysis_period_weeks }} week</span> period.
                            </p>
                            <p>
                                <span class="font-semibold">Aggregation:</span>
                                <span class="capitalize">{{ currentReportData.parameters.h3_aggregation_method }}</span> of weighted scores per hexagon.
                            </p>
                        </div>
                        
                        <!-- Editable Weights -->
                        <div class="mt-4 pt-4 border-t">
                            <p class="font-semibold mb-2">Category Weights (Editable):</p>
                            <div class="space-y-2 max-h-80 overflow-y-auto pr-2">
                                <div v-for="(weight, group) in editableWeights" :key="group" class="flex items-center justify-between text-sm">
                                    <label :for="`weight-${group}`" class="text-gray-700 truncate pr-2">{{ group }}</label>
                                    <input :id="`weight-${group}`" type="number" step="0.1" v-model.number="editableWeights[group]" class="w-24 p-1 border rounded-md text-right font-mono">
                                </div>
                            </div>
                            <div class="flex space-x-2 mt-4">
                                <button @click="recalculateAllScores" class="w-full px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 disabled:bg-gray-400" :disabled="isRecalculating">
                                    {{ isRecalculating ? 'Calculating...' : 'Recalculate Scores' }}
                                </button>
                                <button @click="resetWeights" class="w-full px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600">Reset</button>
                                <button @click="zeroOutWeights" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">Zero All</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Details Section -->
            <div v-if="selectedHexagon" class="mt-8 bg-white p-6 rounded-lg shadow">
                <h3 class="text-2xl font-semibold mb-2">Details for Hexagon</h3>
                <p class="font-mono text-sm text-gray-600 mb-4">{{ selectedHexagon.h3_index }}</p>

                <!-- Score Calculation for Anomaly Report -->
                <div v-if="isAnomalyBasedReport && scoreExplanation.length > 0" class="mb-6 border-b pb-6">
                    <h4 class="font-bold text-xl mb-3">Score Calculation Breakdown</h4>
                    <div class="text-sm space-y-4">
                        <div v-for="(step, index) in scoreExplanation" :key="index" class="p-3 bg-gray-50 rounded-lg border">
                            <p class="font-semibold text-gray-800 text-base">{{ index + 1 }}. Category: {{ step.group }}</p>
                            <ul class="list-disc list-inside mt-2 space-y-1 text-gray-700">
                                <li>The metric <code class="text-xs">{{ step.metricName }}</code> ({{ step.metricDescription }}) has a value of <span class="font-bold">{{ step.metricValue.toFixed(2) }}</span>.</li>
                                <li>The group score is calculated using the formula <code class="text-xs">{{ step.rawFormula }}</code>, resulting in a score of <span class="font-bold">{{ step.groupScore.toFixed(2) }}</span>.</li>
                                <li>This score is multiplied by the category weight of <span class="font-bold">{{ step.weight }}</span>.</li>
                                <li class="font-bold">Resulting Weighted Score: <span class="text-indigo-600">{{ step.weightedScore.toFixed(2) }}</span></li>
                            </ul>
                        </div>
                        <div class="p-4 bg-indigo-50 rounded-lg border border-indigo-200">
                            <p class="font-bold text-indigo-800 text-base">
                                Final Score = {{ currentReportData.parameters.h3_aggregation_method.toUpperCase() }} of all weighted scores = {{ selectedHexagon.score_details.score.toFixed(2) }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Score Calculation for Historical Report -->
                <div v-if="isHistoricalReport && historicalScoreExplanation.length > 0" class="mb-6 border-b pb-6">
                    <h4 class="font-bold text-xl mb-3">Score Calculation Breakdown</h4>
                    <div class="text-sm space-y-4">
                        <div v-for="(step, index) in historicalScoreExplanation" :key="index" class="p-3 bg-gray-50 rounded-lg border">
                            <p class="font-semibold text-gray-800 text-base">{{ index + 1 }}. Category: {{ step.group }}</p>
                             <ul class="list-disc list-inside mt-2 space-y-1 text-gray-700">
                                <li>The average weekly count for this category is <span class="font-bold">{{ step.avg_weekly_count.toFixed(2) }}</span>.</li>
                                <li>This is multiplied by the category weight of <span class="font-bold">{{ step.weight }}</span>.</li>
                                <li class="font-bold">Resulting Weighted Score: <span class="text-indigo-600">{{ step.weighted_score.toFixed(2) }}</span></li>
                            </ul>
                        </div>
                         <div class="p-4 bg-indigo-50 rounded-lg border border-indigo-200">
                            <p class="font-bold text-indigo-800 text-base">
                                Final Score = {{ currentReportData.parameters.h3_aggregation_method.toUpperCase() }} of all weighted scores = {{ selectedHexagon.score_details.score.toFixed(2) }}
                            </p>
                        </div>
                    </div>
                </div>

                <div v-if="selectedHexagon.score_details" class="mb-6">
                    <h4 class="font-bold text-lg">Total Score: {{ selectedHexagon.score_details.score.toFixed(2) }}</h4>
                </div>
                <div v-else class="text-gray-500">No score data for this hexagon.</div>

                <div v-if="isAnomalyBasedReport && selectedHexagon.analysis_details && selectedHexagon.analysis_details.length > 0">
                    <h4 class="font-bold text-lg mt-4 border-t pt-4">Source Analysis</h4>
                    <div v-for="(item, index) in selectedHexagon.analysis_details" :key="index" class="mt-2 text-sm">
                        <p class="font-semibold text-gray-800">{{ item.secondary_group }}</p>
                        <ul class="list-disc list-inside text-gray-600">
                            <li v-for="anomaly in getAnomalies(item)" :key="anomaly.week">
                                Anomaly on {{ anomaly.week }}: Count {{ anomaly.count }} (p={{ anomaly.anomaly_p_value ? anomaly.anomaly_p_value.toPrecision(2) : 'N/A' }}, z={{ anomaly.z_score ? anomaly.z_score.toFixed(2) : 'N/A' }})
                            </li>
                            <li v-for="trend in getTrends(item)" :key="trend.window">
                                Trend ({{ trend.window.replace('_', ' ') }}): {{ trend.details.description }} (p={{ trend.details.p_value ? trend.details.p_value.toPrecision(2) : 'N/A' }})
                            </li>
                        </ul>
                    </div>
                </div>
                 <div v-else-if="isAnomalyBasedReport" class="text-gray-500 mt-4 border-t pt-4">No source analysis data for this hexagon.</div>
            </div>
        </div>
    </PageTemplate>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import PageTemplate from '@/Components/PageTemplate.vue';
import GoogleAddressSearch from '@/Components/GoogleAddressSearch.vue';
import ReportResolutionControl from '@/Components/ReportResolutionControl.vue';
import 'leaflet/dist/leaflet.css';
import * as L from 'leaflet';
import * as h3 from 'h3-js';
import axios from 'axios';

// Leaflet icon fix
import markerIcon from 'leaflet/dist/images/marker-icon.png';
import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png';
import markerShadow from 'leaflet/dist/images/marker-shadow.png';
delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({ iconRetinaUrl: markerIcon2x, iconUrl: markerIcon, shadowUrl: markerShadow });

const props = defineProps({
    reportGroup: Array,
    initialReport: Object,
    reportTitle: String,
    sourceTrend: {
        type: Object,
        default: null,
    },
});

const colorSteps = ref(10);
const selectedHexagon = ref(null);
const hexagonIdSearch = ref('');
let map = null;
let geojsonLayer = null;
let legend = L.control({ position: 'bottomright' });
let addressMarker = null;

const editableWeights = ref({});
const isRecalculating = ref(false);
let sourceAnalysisDataCache = null; // Cache for stage4 data

// New state for multi-resolution support
const baseResolution = ref(props.initialReport.resolution);
const mapMode = ref('select'); // 'select' or 'explode'
const displayHexagons = ref([]); // Hexagons currently displayed on the map
const reportDataByResolution = ref({}); // Processed report data keyed by resolution

const currentReportData = computed(() => reportDataByResolution.value[baseResolution.value]?.scoring_data);

const isAnomalyBasedReport = computed(() => {
    return !!currentReportData.value?.parameters?.group_score_formula;
});

const isHistoricalReport = computed(() => {
    return !isAnomalyBasedReport.value;
});

const PALETTE = ['#9e0142', '#d53e4f', '#f46d43', '#fdae61', '#fee08b', '#ffffbf', '#e6f598', '#abdda4', '#66c2a5', '#3288bd', '#5e4fa2'].reverse();

onMounted(() => {
    console.log("Viewer.vue: Component mounted.");

    // Process and store report data by resolution
    props.reportGroup.forEach(report => {
        reportDataByResolution.value[report.resolution] = {
            ...report,
            // Keep a copy of original results for resets
            original_results: JSON.parse(JSON.stringify(report.scoring_data.results)),
        };
    });

    let initialCenter = [42.3601, -71.0589]; // Default to Boston
    if (currentReportData.value?.results?.length > 0) {
        try {
            const firstHexIndex = currentReportData.value.results[0].h3_index;
            initialCenter = h3.cellToLatLng(firstHexIndex);
        } catch (e) {
            console.error("Viewer.vue: Could not determine center from H3 index, defaulting to Boston.", e);
        }
    }

    const mapElement = document.getElementById('map');
    map = L.map(mapElement).setView(initialCenter, 12);
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>'
    }).addTo(map);

    const resizeObserver = new ResizeObserver(() => {
        map.invalidateSize();
    });
    resizeObserver.observe(mapElement);

    if (currentReportData.value) {
        resetWeights(); // This will also initialize the map
    } else {
        console.error("Viewer.vue: currentReportData is MISSING on mount.");
    }
});

watch(baseResolution, (newRes) => {
    console.log(`Base resolution changed to: ${newRes}`);
    resetAndDrawMap();
});

watch(colorSteps, () => drawMap());

function getColor(score, minScore, maxScore, numSteps) {
    // Special case: 0.00 is always the first color (purple)
    if (score === 0) {
        return PALETTE[0];
    }

    // For scores > 0, use the rest of the palette
    const paletteForPositive = PALETTE.slice(1);
    if (maxScore === minScore) return paletteForPositive[paletteForPositive.length - 1];
    
    const value = (score - minScore) / (maxScore - minScore);
    const index = Math.min(numSteps - 1, Math.floor(value * numSteps));
    const paletteIndex = Math.floor(index * (paletteForPositive.length / numSteps));
    return paletteForPositive[paletteIndex];
}

function drawMap() {
    console.log("Viewer.vue: drawMap() called.");
    const results = displayHexagons.value;
    if (!results || results.length === 0) {
        console.warn("Viewer.vue: drawMap() - No displayHexagons to draw.");
        if (geojsonLayer) map.removeLayer(geojsonLayer);
        return;
    }
    console.log(`Viewer.vue: drawMap() - Drawing ${results.length} hexagons.`);

    const scores = results.map(r => r.score).filter(s => s !== undefined && s !== null);
    const positiveScores = scores.filter(s => s > 0);

    if (scores.length === 0) {
        console.warn("No valid scores to render.");
        return;
    }

    // Calculate min/max only on positive scores for color scaling, if any exist
    const minScore = positiveScores.length > 0 ? Math.min(...positiveScores) : 0;
    const maxScore = positiveScores.length > 0 ? Math.max(...positiveScores) : 0;
    const numSteps = parseInt(colorSteps.value, 10);

    if (geojsonLayer) map.removeLayer(geojsonLayer);
    if (legend) map.removeControl(legend);

    const features = results.map(item => ({
        type: "Feature",
        properties: { h3_index: item.h3_index, score: item.score, resolution: item.resolution },
        geometry: { type: "Polygon", coordinates: [h3.cellToBoundary(item.h3_index, true)] }
    }));

    geojsonLayer = L.geoJson(features, {
        style: feature => ({
            color: 'black',
            weight: 0.5,
            fillColor: getColor(feature.properties.score, minScore, maxScore, numSteps),
            fillOpacity: 0.6
        }),
        onEachFeature: (feature, layer) => {
            layer.on('click', () => {
                handleHexagonClick(feature.properties.h3_index, feature.properties.resolution);
            });
            layer.bindPopup(`<b>Hexagon:</b> ${feature.properties.h3_index}<br><b>Score:</b> ${feature.properties.score?.toFixed(2) ?? 'N/A'}`);
        }
    }).addTo(map);

    legend.onAdd = function (map) {
        const div = L.DomUtil.create('div', 'info legend');
        const scoreRange = maxScore - minScore;
        div.innerHTML += '<b>Score</b><br>';
        
        // Add legend entry for 0
        div.innerHTML += `<i style="background:${PALETTE[0]}"></i> 0.00<br>`;

        // Add legend for positive scores
        for (let i = 0; i < numSteps; i++) {
            const from = minScore + (i / numSteps) * scoreRange;
            const to = minScore + ((i + 1) / numSteps) * scoreRange;
            const color = getColor(from, minScore, maxScore, numSteps);
            div.innerHTML += `<i style="background:${color}"></i> ${from.toFixed(2)} &ndash; ${to.toFixed(2)}<br>`;
        }
        return div;
    };
    legend.addTo(map);
}

function handleHexagonClick(h3Index, resolution) {
    if (mapMode.value === 'select') {
        handleHexagonSelection(h3Index, resolution);
    } else if (mapMode.value === 'explode') {
        explodeHexagon(h3Index, resolution);
    }
}

function explodeHexagon(h3Index, resolution) {
    const availableResolutions = Object.keys(reportDataByResolution.value).map(Number).sort((a, b) => a - b);
    const currentResIndex = availableResolutions.indexOf(resolution);
    const nextRes = availableResolutions[currentResIndex + 1];

    if (!nextRes) {
        console.log("No finer resolution to explode into.");
        handleHexagonSelection(h3Index, resolution); // Fallback to select
        return;
    }

    const children = h3.cellToChildren(h3Index, nextRes);
    const childrenData = children.map(childIndex => {
        const reportForRes = reportDataByResolution.value[nextRes];
        const hexData = reportForRes.scoring_data.results.find(r => r.h3_index === childIndex);
        return {
            h3_index: childIndex,
            score: hexData?.score ?? 0,
            resolution: nextRes,
        };
    });

    // Remove parent, add children
    displayHexagons.value = displayHexagons.value.filter(h => h.h3_index !== h3Index);
    displayHexagons.value.push(...childrenData);

    drawMap();
}

async function handleHexagonSelection(h3Index, resolution) {
    console.log(`Viewer.vue: handleHexagonSelection for H3 index: ${h3Index} at res ${resolution}`);
    const reportForRes = reportDataByResolution.value[resolution];
    if (!reportForRes) {
        console.error(`No report data found for resolution ${resolution}`);
        return;
    }

    const isHistorical = !reportForRes.scoring_data.parameters.group_score_formula;

    if (isHistorical) {
        const scoreDetails = reportForRes.scoring_data.results.find(r => r.h3_index === h3Index);
        selectedHexagon.value = {
            h3_index: h3Index,
            score_details: scoreDetails,
            analysis_details: null,
            analysis_parameters: null,
            resolution: resolution,
        };
        return;
    }

    console.log("Viewer.vue: Anomaly-based report detected. Fetching details from API.");
    try {
        const response = await axios.post(route('scoring-reports.score-for-location'), {
            h3_index: h3Index,
            job_id: reportForRes.job_id,
            artifact_name: reportForRes.artifact_name,
        });
        const scoreDetails = reportForRes.scoring_data.results.find(r => r.h3_index === h3Index);
        response.data.score_details = scoreDetails;
        response.data.resolution = resolution;
        selectedHexagon.value = response.data;
        console.log("Viewer.vue: API response received for hexagon selection:", response.data);
    } catch (error) {
        console.error("Error fetching details for hexagon:", error);
        selectedHexagon.value = {
            h3_index: h3Index,
            score_details: reportForRes.scoring_data.results.find(r => r.h3_index === h3Index),
            analysis_details: [],
            analysis_parameters: null,
            resolution: resolution,
        };
        alert('Could not fetch source analysis details for the selected hexagon.');
    }
}

async function handleAddressSelection({ lat, lng, address }) {
    console.log(`Viewer.vue: handleAddressSelection - Address selected: ${address} (Lat: ${lat}, Lng: ${lng})`);
    if (addressMarker) map.removeLayer(addressMarker);
    addressMarker = L.marker([lat, lng]).addTo(map).bindPopup(address).openPopup();
    map.setView([lat, lng], 15);

    const h3Index = h3.latLngToCell(lat, lng, baseResolution.value);
    console.log(`Viewer.vue: handleAddressSelection - Resolved address to H3 index: ${h3Index} at base resolution.`);

    handleHexagonClick(h3Index, baseResolution.value);
}

function searchByHexagonId() {
    const h3Index = hexagonIdSearch.value.trim();
    if (h3.isValidCell(h3Index)) {
        const res = h3.getResolution(h3Index);
        console.log(`Viewer.vue: searchByHexagonId - Valid H3 index provided: ${h3Index} at res ${res}`);
        
        // Check if this hexagon is currently displayed
        const displayedHex = displayHexagons.value.find(h => h.h3_index === h3Index);
        if (!displayedHex) {
            alert('This hexagon is not currently displayed on the map. Try changing the base resolution or exploding a parent hexagon.');
            return;
        }

        handleHexagonClick(h3Index, res);
        
        try {
            const [lat, lng] = h3.cellToLatLng(h3Index);
            if (addressMarker) map.removeLayer(addressMarker);
            addressMarker = L.marker([lat, lng]).addTo(map).bindPopup(`Hexagon: ${h3Index}`).openPopup();
            map.setView([lat, lng], 15);
        } catch (e) {
            console.error("Could not get coordinates for H3 index:", e);
        }

    } else {
        alert('Invalid H3 Index provided.');
    }
}

function zeroOutWeights() {
    for (const group in editableWeights.value) {
        editableWeights.value[group] = 0;
    }
}

function resetWeights() {
    editableWeights.value = JSON.parse(JSON.stringify(currentReportData.value.parameters.group_weights));
    // Reset all resolutions to their original scores
    for (const res in reportDataByResolution.value) {
        const report = reportDataByResolution.value[res];
        report.scoring_data.results = JSON.parse(JSON.stringify(report.original_results));
    }
    resetAndDrawMap();
}

function resetAndDrawMap() {
    const report = reportDataByResolution.value[baseResolution.value];
    if (!report) return;
    displayHexagons.value = report.scoring_data.results.map(r => ({ ...r, resolution: baseResolution.value }));
    drawMap();
}

async function recalculateAllScores() {
    isRecalculating.value = true;
    console.log("Recalculating all scores with new weights:", editableWeights.value);

    for (const res in reportDataByResolution.value) {
        const report = reportDataByResolution.value[res];
        const isHistorical = !report.scoring_data.parameters.group_score_formula;
        if (isHistorical) {
            recalculateHistoricalScores(report);
        } else {
            await recalculateAnomalyScores(report);
        }
    }

    // After recalculating all, we need to update the displayHexagons with new scores
    displayHexagons.value = displayHexagons.value.map(hex => {
        const report = reportDataByResolution.value[hex.resolution];
        const updatedHex = report.scoring_data.results.find(r => r.h3_index === hex.h3_index);
        return { ...hex, score: updatedHex?.score ?? hex.score };
    });

    drawMap();
    if (selectedHexagon.value) {
        handleHexagonSelection(selectedHexagon.value.h3_index, selectedHexagon.value.resolution);
    }
    isRecalculating.value = false;
}

function recalculateHistoricalScores(report) {
    const newResults = report.original_results.map(hexagon => {
        if (!hexagon.score_composition) return { ...hexagon, score: 0 };

        const newScore = hexagon.score_composition.reduce((sum, item) => {
            const newWeight = editableWeights.value[item.secondary_group] || report.scoring_data.parameters.default_group_weight;
            return sum + (item.avg_weekly_count * newWeight);
        }, 0);

        return { ...hexagon, score: newScore };
    });
    report.scoring_data.results = newResults;
}

async function recalculateAnomalyScores(report) {
    const sourceJobId = report.scoring_data.source_job_id;
    if (!sourceAnalysisDataCache || sourceAnalysisDataCache.job_id !== sourceJobId) {
        try {
            console.log(`Fetching source analysis data for job ${sourceJobId}...`);
            const response = await axios.get(route('scoring-reports.source-analysis', { jobId: sourceJobId }));
            sourceAnalysisDataCache = { ...response.data, job_id: sourceJobId };
            console.log("Source analysis data cached.");
        } catch (error) {
            console.error("Failed to fetch source analysis data:", error);
            alert("Could not fetch source analysis data needed for recalculation.");
            return;
        }
    }

    const h3ResolutionKey = `h3_index_${sourceAnalysisDataCache.parameters.h3_resolution}`;
    const h3Groups = sourceAnalysisDataCache.results.reduce((acc, item) => {
        const h3Index = item[h3ResolutionKey];
        if (!acc[h3Index]) acc[h3Index] = [];
        acc[h3Index].push(item);
        return acc;
    }, {});

    const newResults = [];
    const formula = report.scoring_data.parameters.group_score_formula;

    for (const h3Index in h3Groups) {
        const groupList = h3Groups[h3Index];
        let finalH3Score = 0;
        const h3GroupScores = [];

        groupList.forEach(groupResult => {
            const context = {};
            for (const metricName in report.scoring_data.parameters.metric_definitions) {
                const metricDef = report.scoring_data.parameters.metric_definitions[metricName];
                context[metricName] = getValueFromPath(groupResult, metricDef.path, metricDef.default);
            }
            
            let groupScore = 0;
            try {
                const func = new Function(...Object.keys(context), `return ${formula}`);
                groupScore = func(...Object.values(context));
            } catch (e) {
                groupScore = 0;
            }
            if (isNaN(groupScore)) groupScore = 0;

            const weight = editableWeights.value[groupResult.secondary_group] || report.scoring_data.parameters.default_group_weight;
            h3GroupScores.push(groupScore * weight);
        });

        if (h3GroupScores.length > 0) {
            if (report.scoring_data.parameters.h3_aggregation_method === 'sum') {
                finalH3Score = h3GroupScores.reduce((a, b) => a + b, 0);
            } else { // average
                finalH3Score = h3GroupScores.reduce((a, b) => a + b, 0) / h3GroupScores.length;
            }
        }
        
        const originalHex = report.original_results.find(r => r.h3_index === h3Index) || { lat: 0, lon: 0 };
        newResults.push({
            h3_index: h3Index,
            score: finalH3Score,
            lat: originalHex.lat,
            lon: originalHex.lon,
        });
    }
    report.scoring_data.results = newResults;
}

const scoreExplanation = computed(() => {
    if (!isAnomalyBasedReport.value || !selectedHexagon.value?.analysis_details || !currentReportData.value?.parameters) {
        return [];
    }

    const params = currentReportData.value.parameters;
    const explanation = [];

    selectedHexagon.value.analysis_details.forEach(item => {
        const context = {};
        const rawFormula = params.group_score_formula;

        // This logic assumes a simple formula with one metric for clarity.
        // It can be expanded for more complex formulas.
        const metricName = Object.keys(params.metric_definitions)[0];
        const metricDef = params.metric_definitions[metricName];
        const metricValue = getValueFromPath(item, metricDef.path, metricDef.default);
        context[metricName] = metricValue;

        let groupScore = 0;
        try {
            const func = new Function(...Object.keys(context), `return ${rawFormula}`);
            groupScore = func(...Object.values(context));
        } catch (e) {
            console.error("Could not evaluate formula on frontend:", e);
            groupScore = context[metricName] || 0; // Fallback
        }
        
        if (isNaN(groupScore)) groupScore = 0;

        const weight = editableWeights.value[item.secondary_group] || currentReportData.value.parameters.default_group_weight;
        const weightedScore = groupScore * weight;

        if (Math.abs(weightedScore) > 0.001) {
            explanation.push({
                group: item.secondary_group,
                rawFormula: rawFormula,
                metricName: metricName,
                metricDescription: `Value from path '${metricDef.path}'`,
                metricValue: metricValue,
                groupScore: groupScore,
                weight: weight,
                weightedScore: weightedScore,
            });
        }
    });

    return explanation;
});

const historicalScoreExplanation = computed(() => {
    if (!isHistoricalReport.value || !selectedHexagon.value?.score_details?.score_composition) {
        return [];
    }

    const composition = selectedHexagon.value.score_details.score_composition;
    const report = reportDataByResolution.value[selectedHexagon.value.resolution];

    return composition.map(item => {
        const weight = editableWeights.value[item.secondary_group] || report.scoring_data.parameters.default_group_weight;
        return {
            group: item.secondary_group,
            avg_weekly_count: item.avg_weekly_count,
            weight: weight,
            weighted_score: item.avg_weekly_count * weight, // Recalculate weighted score for display
        };
    }).filter(item => Math.abs(item.weighted_score) > 0.001);
});

function getValueFromPath(obj, path, defaultValue = 0.0) {
    try {
        const keys = path.split('.');
        let value = obj;
        for (const key of keys) {
            if (value === null || value === undefined) return defaultValue;
            if (Array.isArray(value)) {
                const index = parseInt(key, 10);
                value = value.at(index);
            } else {
                value = value[key];
            }
        }
        const numValue = parseFloat(value);
        return isNaN(numValue) ? defaultValue : numValue;
    } catch (e) {
        return defaultValue;
    }
}

const getAnomalies = (item) => {
    const pValue = selectedHexagon.value?.analysis_parameters?.p_value_anomaly || 0.05;
    return (item.anomaly_analysis || []).filter(a => a.anomaly_p_value < pValue);
};

const getTrends = (item) => {
    const pValue = selectedHexagon.value?.analysis_parameters?.p_value_trend || 0.05;
    const trends = [];
    for (const window in item.trend_analysis) {
        const trend = item.trend_analysis[window];
        if (trend.p_value < pValue) {
            trends.push({ window, details: trend });
        }
    }
    return trends;
};

</script>

<style>
.legend { padding: 6px 8px; font: 14px/16px Arial, Helvetica, sans-serif; background: white; background: rgba(255,255,255,0.8); box-shadow: 0 0 15px rgba(0,0,0,0.2); border-radius: 5px; }
.legend i { width: 18px; height: 18px; float: left; margin-right: 8px; opacity: 0.8; }

</style>
