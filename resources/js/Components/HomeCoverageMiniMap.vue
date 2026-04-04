<template>
  <div ref="mapElement" class="coverage-mini-map"></div>
</template>

<script setup>
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
import * as L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const props = defineProps({
  city: {
    type: Object,
    required: true,
  },
  accent: {
    type: String,
    default: '#22d3ee',
  },
});

const mapElement = ref(null);

let mapInstance = null;
let circleLayer = null;
let centerLayer = null;

function normalizedRadiusMiles() {
  const rawMiles = Number(props.city?.radiusMiles);
  const fallbackMiles = 6;
  const boundedMiles = Number.isFinite(rawMiles) ? rawMiles : fallbackMiles;

  return Math.min(Math.max(boundedMiles, 3.5), 12);
}

function updateMap() {
  if (!mapInstance || !mapElement.value) {
    return;
  }

  const latitude = Number(props.city?.lat);
  const longitude = Number(props.city?.lng);

  if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) {
    return;
  }

  const center = L.latLng(latitude, longitude);
  const radiusMeters = normalizedRadiusMiles() * 1609.34;
  const bounds = center.toBounds(radiusMeters * 2.4);

  mapInstance.fitBounds(bounds, {
    animate: false,
    padding: [8, 8],
  });

  if (circleLayer) {
    circleLayer.remove();
  }

  if (centerLayer) {
    centerLayer.remove();
  }

  circleLayer = L.circle(center, {
    radius: radiusMeters,
    color: props.accent,
    fillColor: props.accent,
    fillOpacity: 0.14,
    opacity: 0.92,
    weight: 1.4,
  }).addTo(mapInstance);

  centerLayer = L.circleMarker(center, {
    radius: 7,
    color: '#f8fafc',
    fillColor: props.accent,
    fillOpacity: 1,
    opacity: 1,
    weight: 2,
  }).addTo(mapInstance);
}

onMounted(() => {
  if (!mapElement.value) {
    return;
  }

  mapInstance = L.map(mapElement.value, {
    attributionControl: false,
    boxZoom: false,
    doubleClickZoom: false,
    dragging: false,
    inertia: false,
    keyboard: false,
    scrollWheelZoom: false,
    tap: false,
    touchZoom: false,
    zoomControl: false,
  });

  L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
    maxZoom: 19,
    subdomains: 'abcd',
  }).addTo(mapInstance);

  updateMap();
  requestAnimationFrame(() => mapInstance?.invalidateSize(false));
});

watch(
  () => [props.city?.lat, props.city?.lng, props.city?.radiusMiles, props.accent],
  () => {
    updateMap();
  }
);

onBeforeUnmount(() => {
  if (mapInstance) {
    mapInstance.remove();
    mapInstance = null;
  }
});
</script>

<style scoped>
.coverage-mini-map {
  position: absolute;
  inset: 0;
  pointer-events: none;
}

.coverage-mini-map :deep(.leaflet-container) {
  width: 100%;
  height: 100%;
  filter: saturate(0.84) contrast(1.02);
}

.coverage-mini-map :deep(.leaflet-control-container),
.coverage-mini-map :deep(.leaflet-pane),
.coverage-mini-map :deep(.leaflet-top),
.coverage-mini-map :deep(.leaflet-bottom) {
  pointer-events: none;
}
</style>
