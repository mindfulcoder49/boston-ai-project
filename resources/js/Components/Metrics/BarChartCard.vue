<template>
    <div class="bg-white p-3 sm:p-4 rounded-lg shadow">
        <h5 class="text-base sm:text-md font-semibold text-gray-700 mb-2 sm:mb-3 text-center sm:text-left">{{ title }}</h5>
        <div v-if="chartData && chartData.labels && chartData.labels.length > 0" class="chart-container relative h-64 sm:h-72 md:h-80">
            <Bar :data="chartData" :options="chartOptions" /> 
        </div>
        <div v-else class="text-center py-10">
            <p class="text-gray-500">No data available for this chart.</p>
        </div>
    </div>
</template>

<script setup>
 import { Bar } from 'vue-chartjs';
 import { Chart as ChartJS, Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale } from 'chart.js';
 ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale);

defineProps({
    title: String,
    chartData: Object,
});

 const chartOptions = {
     responsive: true,
     maintainAspectRatio: false, // Set to false to allow custom height via CSS on parent
     plugins: { // Moved legend to plugins
        legend: {
            display: true, // Keep legend if desired, or set to false
            position: 'top',
        },
        tooltip: {
            mode: 'index',
            intersect: false,
        },
     },
    scales: {
        x: {
            grid: {
                display: false, // Hide x-axis grid lines for a cleaner look
            }
        },
        y: {
            grid: {
                borderDash: [2, 4], // Dashed y-axis grid lines
            },
            beginAtZero: true
        }
    }
 };
</script>

<style scoped>
.chart-container {
  /* Ensure the container itself can be sized, maintainAspectRatio:false on chart helps */
  /* Height is set via Tailwind classes in the template for responsiveness */
}
</style>
