<template>
  <div ref="mapElement" class="h-[360px] w-full rounded-2xl border border-slate-200 shadow-sm"></div>
</template>

<script setup>
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
import * as L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import markerIcon from 'leaflet/dist/images/marker-icon.png';
import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png';
import markerShadow from 'leaflet/dist/images/marker-shadow.png';

delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({ iconRetinaUrl: markerIcon2x, iconUrl: markerIcon, shadowUrl: markerShadow });

const props = defineProps({
  center: {
    type: Object,
    required: true,
  },
  incidents: {
    type: Array,
    default: () => [],
  },
});

const mapElement = ref(null);
let mapInstance = null;
let markerLayer = null;

function drawMap() {
  if (!mapElement.value) {
    return;
  }

  if (!mapInstance) {
    mapInstance = L.map(mapElement.value, {
      zoomControl: true,
      scrollWheelZoom: false,
    }).setView([props.center.latitude, props.center.longitude], 14);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; OpenStreetMap contributors',
    }).addTo(mapInstance);
  } else {
    mapInstance.setView([props.center.latitude, props.center.longitude], mapInstance.getZoom());
  }

  if (markerLayer) {
    markerLayer.remove();
  }

  markerLayer = L.layerGroup();

  L.circle([props.center.latitude, props.center.longitude], {
    radius: 402.336,
    color: '#0f172a',
    fillColor: '#0f172a',
    fillOpacity: 0.08,
    weight: 1,
  }).addTo(markerLayer);

  L.marker([props.center.latitude, props.center.longitude])
    .bindPopup('Selected address')
    .addTo(markerLayer);

  props.incidents.forEach((incident) => {
    if (!incident.latitude || !incident.longitude) {
      return;
    }

    const marker = L.circleMarker([incident.latitude, incident.longitude], {
      radius: 6,
      color: '#b91c1c',
      fillColor: '#ef4444',
      fillOpacity: 0.75,
      weight: 1,
    });

    marker.bindPopup(`
      <strong>${incident.category ?? 'Crime incident'}</strong><br>
      ${incident.date ?? ''}<br>
      ${incident.description ?? incident.location_label ?? ''}
    `);
    marker.addTo(markerLayer);
  });

  markerLayer.addTo(mapInstance);
}

onMounted(() => {
  drawMap();
});

watch(() => [props.center, props.incidents], () => {
  drawMap();
}, { deep: true });

onBeforeUnmount(() => {
  if (mapInstance) {
    mapInstance.remove();
  }
});
</script>
