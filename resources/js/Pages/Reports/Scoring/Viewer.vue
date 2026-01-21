<template>
    <PageTemplate>
        <Head :title="reportTitle" />
        <div class="container mx-auto p-4 md:p-8">
            <h1 class="text-4xl font-bold text-center text-gray-800">{{ reportTitle }}</h1>
            <h2 class="text-lg text-center text-gray-500 mb-8">Job ID: {{ jobId }}</h2>

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

                <!-- Search and Details Column -->
                <div class="space-y-6">
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

                    <div v-if="selectedHexagon" class="bg-white p-6 rounded-lg shadow">
                        <h3 class="text-xl font-semibold mb-2">Details for Hexagon</h3>
                        <p class="font-mono text-sm text-gray-600 mb-4">{{ selectedHexagon.h3_index }}</p>

                        <div v-if="selectedHexagon.score_details" class="mb-6">
                            <h4 class="font-bold text-lg">Score: {{ selectedHexagon.score_details.score.toFixed(2) }}</h4>
                        </div>
                        <div v-else class="text-gray-500">No score data for this hexagon.</div>

                        <div v-if="selectedHexagon.analysis_details && selectedHexagon.analysis_details.length > 0">
                            <h4 class="font-bold text-lg mt-4 border-t pt-4">Source Analysis</h4>
                            <div v-for="(item, index) in selectedHexagon.analysis_details" :key="index" class="mt-2 text-sm">
                                <p class="font-semibold text-gray-800">{{ item.secondary_group }}</p>
                                <ul class="list-disc list-inside text-gray-600">
                                    <li v-for="anomaly in getAnomalies(item)" :key="anomaly.week">
                                        Anomaly on {{ anomaly.week }}: Count {{ anomaly.count }} (p={{ anomaly.anomaly_p_value ? anomaly.anomaly_p_value.toPrecision(2) : 'N/A' }})
                                    </li>
                                    <li v-for="trend in getTrends(item)" :key="trend.window">
                                        Trend ({{ trend.window.replace('_', ' ') }}): {{ trend.details.description }} (p={{ trend.details.p_value ? trend.details.p_value.toPrecision(2) : 'N/A' }})
                                    </li>
                                </ul>
                            </div>
                        </div>
                         <div v-else class="text-gray-500 mt-4 border-t pt-4">No source analysis data for this hexagon.</div>
                    </div>
                </div>
            </div>
        </div>
    </PageTemplate>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
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

const PALETTE = ['#9e0142', '#d53e4f', '#f46d43', '#fdae61', '#fee08b', '#ffffbf', '#e6f598', '#abdda4', '#66c2a5', '#3288bd', '#5e4fa2'].reverse();

onMounted(() => {
    map = L.map('map').setView([42.3601, -71.0589], 12);
    L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>'
    }).addTo(map);

    if (props.scoringData) {
        drawMap();
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
    const results = props.scoringData.results;
    if (!results || results.length === 0) return;

    const scores = results.map(r => r.score);
    const minScore = Math.min(...scores);
    const maxScore = Math.max(...scores);
    const numSteps = parseInt(colorSteps.value, 10);

    if (geojsonLayer) map.removeLayer(geojsonLayer);
    if (legend) map.removeControl(legend);

    const features = results.map(item => ({
        type: "Feature",
        properties: { h3_index: item.h3_index, score: item.score },
        geometry: { type: "Polygon", coordinates: [h3.cellToBoundary(item.h3_index, true)] }
    }));

    geojsonLayer = L.geoJson(features, {
        style: feature => ({
            color: 'black',
            weight: 0.5,
            fillColor: getColor(feature.properties.score, minScore, maxScore, numSteps),
            fillOpacity: 0.75
        }),
        onEachFeature: (feature, layer) => {
            layer.on('click', () => {
                handleHexagonSelection(feature.properties.h3_index);
            });
            layer.bindPopup(`<b>Hexagon:</b> ${feature.properties.h3_index}<br><b>Score:</b> ${feature.properties.score.toFixed(2)}`);
        }
    }).addTo(map);

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
}

async function handleHexagonSelection(h3Index) {
    try {
        const response = await axios.post(route('scoring-reports.score-for-location'), {
            h3_index: h3Index,
            job_id: props.jobId,
            artifact_name: props.artifactName,
        });
        selectedHexagon.value = response.data;
    } catch (error) {
        console.error("Error fetching details for hexagon:", error);
        // Still show basic info if API fails but we have score data locally
        selectedHexagon.value = {
            h3_index: h3Index,
            score_details: props.scoringData.results.find(r => r.h3_index === h3Index),
            analysis_details: [],
            analysis_parameters: null,
        };
        alert('Could not fetch source analysis details for the selected hexagon.');
    }
}

async function handleAddressSelection({ lat, lng, address }) {
    if (addressMarker) map.removeLayer(addressMarker);
    addressMarker = L.marker([lat, lng]).addTo(map).bindPopup(address).openPopup();
    map.setView([lat, lng], 15);

    const h3Resolution = props.scoringData?.parameters?.h3_resolution || 8;
    const h3Index = h3.latLngToCell(lat, lng, h3Resolution);

    // This will now trigger the API call to get all details
    handleHexagonSelection(h3Index);
}

function searchByHexagonId() {
    const h3Index = hexagonIdSearch.value.trim();
    if (h3.isValidCell(h3Index)) {
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
