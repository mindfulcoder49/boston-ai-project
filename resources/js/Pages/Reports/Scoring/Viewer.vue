<template>
    <PageTemplate>
        <Head :title="reportTitle" />
        <div class="container mx-auto p-4 md:p-8">
            <h1 class="text-4xl font-bold text-center text-gray-800">{{ reportTitle }}</h1>
            <h2 class="text-lg text-center text-gray-500 mb-8">Job ID: {{ jobId }}</h2>

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
                    <div id="map" class="h-[600px] w-full border rounded-lg shadow-md"></div>
                    <div class="map-controls bg-white p-4 rounded-lg shadow">
                        <label for="color-steps" class="font-weight-bold mr-2">Color Steps:</label>
                        <input type="range" id="color-steps" min="5" max="20" v-model="colorSteps" @input="drawMap">
                        <span class="ml-2">{{ colorSteps }}</span>
                    </div>
                </div>

                <!-- Methodology Column -->
                <div class="space-y-6">
                    <div v-if="scoringData.parameters" class="bg-white p-6 rounded-lg shadow">
                        <h3 class="text-xl font-semibold mb-4">Scoring Methodology</h3>
                        <!-- Anomaly-based Report Methodology -->
                        <div v-if="isAnomalyBasedReport" class="text-sm space-y-2">
                            <p>
                                <span class="font-semibold">Formula:</span>
                                <code class="bg-gray-200 text-gray-800 rounded px-1 py-0.5 text-xs">{{ scoringData.parameters.group_score_formula }}</code>
                            </p>
                            <p>
                                <span class="font-semibold">Aggregation:</span>
                                <span class="capitalize">{{ scoringData.parameters.h3_aggregation_method }}</span> of weighted scores per hexagon.
                            </p>
                        </div>
                        <!-- Historical Report Methodology -->
                        <div v-else class="text-sm space-y-2">
                             <p>
                                This report calculates a score based on the weighted average of historical incident counts over a
                                <span class="font-semibold">{{ scoringData.parameters.analysis_period_weeks }} week</span> period.
                            </p>
                            <p>
                                <span class="font-semibold">Aggregation:</span>
                                <span class="capitalize">{{ scoringData.parameters.h3_aggregation_method }}</span> of weighted scores per hexagon.
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
                                Final Score = {{ scoringData.parameters.h3_aggregation_method.toUpperCase() }} of all weighted scores = {{ selectedHexagon.score_details.score.toFixed(2) }}
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
                                Final Score = {{ scoringData.parameters.h3_aggregation_method.toUpperCase() }} of all weighted scores = {{ selectedHexagon.score_details.score.toFixed(2) }}
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
import { Head } from '@inertiajs/vue3';
import PageTemplate from '@/Components/PageTemplate.vue';
import GoogleAddressSearch from '@/Components/GoogleAddressSearch.vue';
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
    jobId: String,
    artifactName: String,
    scoringData: Object,
    reportTitle: String,
});

const colorSteps = ref(10);
const selectedHexagon = ref(null);
const hexagonIdSearch = ref('');
let map = null;
let geojsonLayer = null;
let legend = L.control({ position: 'bottomright' });
let addressMarker = null;

const editableWeights = ref({});
const recalculatedResults = ref([]);
const isRecalculating = ref(false);
let sourceAnalysisDataCache = null; // Cache for stage4 data

const isAnomalyBasedReport = computed(() => {
    // Anomaly-based reports have a 'group_score_formula', historical ones do not.
    return !!props.scoringData?.parameters?.group_score_formula;
});

const isHistoricalReport = computed(() => {
    return !isAnomalyBasedReport.value;
});

const PALETTE = ['#9e0142', '#d53e4f', '#f46d43', '#fdae61', '#fee08b', '#ffffbf', '#e6f598', '#abdda4', '#66c2a5', '#3288bd', '#5e4fa2'].reverse();

onMounted(() => {
    console.log("Viewer.vue: Component mounted.");
    map = L.map('map').setView([42.3601, -71.0589], 12);
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>'
    }).addTo(map);

    if (props.scoringData) {
        console.log("Viewer.vue: scoringData prop is present. Calling drawMap().", props.scoringData);
        resetWeights(); // Initialize editable weights and results
        drawMap();
    } else {
        console.error("Viewer.vue: scoringData prop is MISSING on mount.");
    }
});

function getColor(score, minScore, maxScore, numSteps) {
    if (maxScore === minScore) return PALETTE[PALETTE.length - 1];
    const value = (score - minScore) / (maxScore - minScore);
    const index = Math.min(numSteps - 1, Math.floor(value * numSteps));
    const paletteIndex = Math.floor(index * (PALETTE.length / numSteps));
    return PALETTE[paletteIndex];
}

function drawMap() {
    console.log("Viewer.vue: drawMap() called.");
    const results = recalculatedResults.value; // Use recalculated results
    if (!results || results.length === 0) {
        console.error("Viewer.vue: drawMap() - No results found in recalculatedResults or results array is empty.", recalculatedResults.value);
        return;
    }
    console.log(`Viewer.vue: drawMap() - Found ${results.length} results.`);

    const scores = results.map(r => r.score);
    const minScore = Math.min(...scores);
    const maxScore = Math.max(...scores);
    const numSteps = parseInt(colorSteps.value, 10);
    console.log(`Viewer.vue: drawMap() - Score range: ${minScore} to ${maxScore}. Steps: ${numSteps}.`);

    if (geojsonLayer) {
        console.log("Viewer.vue: drawMap() - Removing existing geojsonLayer.");
        map.removeLayer(geojsonLayer);
    }
    if (legend) {
        console.log("Viewer.vue: drawMap() - Removing existing legend.");
        map.removeControl(legend);
    }

    const features = results.map(item => ({
        type: "Feature",
        properties: { h3_index: item.h3_index, score: item.score },
        geometry: { type: "Polygon", coordinates: [h3.cellToBoundary(item.h3_index, true)] }
    }));
    console.log(`Viewer.vue: drawMap() - Created ${features.length} GeoJSON features.`, features.slice(0, 3));

    console.log("Viewer.vue: drawMap() - Adding new geojsonLayer to map.");
    geojsonLayer = L.geoJson(features, {
        style: feature => ({
            color: 'black',
            weight: 0.5,
            fillColor: getColor(feature.properties.score, minScore, maxScore, numSteps),
            fillOpacity: 0.6
        }),
        onEachFeature: (feature, layer) => {
            layer.on('click', () => {
                handleHexagonSelection(feature.properties.h3_index);
            });
            layer.bindPopup(`<b>Hexagon:</b> ${feature.properties.h3_index}<br><b>Score:</b> ${feature.properties.score.toFixed(2)}`);
        }
    }).addTo(map);
    console.log("Viewer.vue: drawMap() - GeojsonLayer added.");

    legend.onAdd = function (map) {
        const div = L.DomUtil.create('div', 'info legend');
        const scoreRange = maxScore - minScore;
        div.innerHTML += '<b>Score</b><br>';
        for (let i = 0; i < numSteps; i++) {
            const from = minScore + (i / numSteps) * scoreRange;
            const to = minScore + ((i + 1) / numSteps) * scoreRange;
            const color = getColor(from, minScore, maxScore, numSteps);
            div.innerHTML += `<i style="background:${color}"></i> ${from.toFixed(2)} &ndash; ${to.toFixed(2)}<br>`;
        }
        return div;
    };
    legend.addTo(map);
    console.log("Viewer.vue: drawMap() - Legend added.");
}

async function handleHexagonSelection(h3Index) {
    console.log(`Viewer.vue: handleHexagonSelection called for H3 index: ${h3Index}`);
    if (isHistoricalReport.value) {
        console.log("Viewer.vue: Historical report detected. Finding score details locally.");
        // For historical reports, we only have score data, no analysis to fetch.
        const scoreDetails = recalculatedResults.value.find(r => r.h3_index === h3Index);
        console.log("Viewer.vue: Found score details for historical report:", scoreDetails);
        selectedHexagon.value = {
            h3_index: h3Index,
            score_details: scoreDetails,
            analysis_details: null,
            analysis_parameters: null,
        };
        return;
    }

    console.log("Viewer.vue: Anomaly-based report detected. Fetching details from API.");
    try {
        const response = await axios.post(route('scoring-reports.score-for-location'), {
            h3_index: h3Index,
            job_id: props.jobId,
            artifact_name: props.artifactName,
        });
        // We get analysis details from API, but score from our recalculated data
        const scoreDetails = recalculatedResults.value.find(r => r.h3_index === h3Index);
        response.data.score_details = scoreDetails;
        selectedHexagon.value = response.data;
        console.log("Viewer.vue: API response received for hexagon selection:", response.data);
    } catch (error) {
        console.error("Error fetching details for hexagon:", error);
        // Still show basic info if API fails but we have score data locally
        selectedHexagon.value = {
            h3_index: h3Index,
            score_details: recalculatedResults.value.find(r => r.h3_index === h3Index),
            analysis_details: [],
            analysis_parameters: null,
        };
        alert('Could not fetch source analysis details for the selected hexagon.');
    }
}

async function handleAddressSelection({ lat, lng, address }) {
    console.log(`Viewer.vue: handleAddressSelection - Address selected: ${address} (Lat: ${lat}, Lng: ${lng})`);
    if (addressMarker) map.removeLayer(addressMarker);
    addressMarker = L.marker([lat, lng]).addTo(map).bindPopup(address).openPopup();
    map.setView([lat, lng], 15);

    const h3Resolution = props.scoringData?.parameters?.h3_resolution || 8;
    const h3Index = h3.latLngToCell(lat, lng, h3Resolution);
    console.log(`Viewer.vue: handleAddressSelection - Resolved address to H3 index: ${h3Index}`);

    // This will now trigger the API call to get all details
    handleHexagonSelection(h3Index);
}

function searchByHexagonId() {
    const h3Index = hexagonIdSearch.value.trim();
    if (h3.isValidCell(h3Index)) {
        console.log(`Viewer.vue: searchByHexagonId - Valid H3 index provided: ${h3Index}`);
        handleHexagonSelection(h3Index);
        
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
    editableWeights.value = JSON.parse(JSON.stringify(props.scoringData.parameters.group_weights));
    recalculatedResults.value = JSON.parse(JSON.stringify(props.scoringData.results));
    if (map) {
        drawMap();
    }
}

async function recalculateAllScores() {
    isRecalculating.value = true;
    console.log("Recalculating all scores with new weights:", editableWeights.value);

    if (isHistoricalReport.value) {
        recalculateHistoricalScores();
    } else {
        await recalculateAnomalyScores();
    }

    drawMap();
    // If a hexagon is selected, refresh its details
    if (selectedHexagon.value) {
        handleHexagonSelection(selectedHexagon.value.h3_index);
    }
    isRecalculating.value = false;
}

function recalculateHistoricalScores() {
    const newResults = props.scoringData.results.map(hexagon => {
        if (!hexagon.score_composition) return { ...hexagon, score: 0 };

        const newScore = hexagon.score_composition.reduce((sum, item) => {
            const newWeight = editableWeights.value[item.secondary_group] || props.scoringData.parameters.default_group_weight;
            return sum + (item.avg_weekly_count * newWeight);
        }, 0);

        return { ...hexagon, score: newScore };
    });
    recalculatedResults.value = newResults;
}

async function recalculateAnomalyScores() {
    if (!sourceAnalysisDataCache) {
        try {
            console.log("Fetching source analysis data for the first time...");
            const response = await axios.get(route('scoring-reports.source-analysis', { jobId: props.scoringData.source_job_id }));
            sourceAnalysisDataCache = response.data;
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
    const formula = props.scoringData.parameters.group_score_formula;

    for (const h3Index in h3Groups) {
        const groupList = h3Groups[h3Index];
        let finalH3Score = 0;
        const h3GroupScores = [];

        groupList.forEach(groupResult => {
            const context = {};
            for (const metricName in props.scoringData.parameters.metric_definitions) {
                const metricDef = props.scoringData.parameters.metric_definitions[metricName];
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

            const weight = editableWeights.value[groupResult.secondary_group] || props.scoringData.parameters.default_group_weight;
            h3GroupScores.push(groupScore * weight);
        });

        if (h3GroupScores.length > 0) {
            if (props.scoringData.parameters.h3_aggregation_method === 'sum') {
                finalH3Score = h3GroupScores.reduce((a, b) => a + b, 0);
            } else { // average
                finalH3Score = h3GroupScores.reduce((a, b) => a + b, 0) / h3GroupScores.length;
            }
        }
        
        const originalHex = props.scoringData.results.find(r => r.h3_index === h3Index) || { lat: 0, lon: 0 };
        newResults.push({
            h3_index: h3Index,
            score: finalH3Score,
            lat: originalHex.lat,
            lon: originalHex.lon,
        });
    }
    recalculatedResults.value = newResults;
}

const scoreExplanation = computed(() => {
    if (!isAnomalyBasedReport.value || !selectedHexagon.value?.analysis_details || !props.scoringData?.parameters) {
        return [];
    }

    const params = props.scoringData.parameters;
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

        const weight = editableWeights.value[item.secondary_group] || props.scoringData.parameters.default_group_weight;
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

    return composition.map(item => {
        const weight = editableWeights.value[item.secondary_group] || props.scoringData.parameters.default_group_weight;
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
