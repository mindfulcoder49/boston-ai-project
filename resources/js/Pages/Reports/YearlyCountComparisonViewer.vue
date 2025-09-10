<script setup>
import { ref, onMounted, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import PageTemplate from '@/Components/PageTemplate.vue';
import { Bar } from 'vue-chartjs';
import { Chart as ChartJS, Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale } from 'chart.js';

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale);

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

const error = ref(null);
const activeGroup = ref(null);
const chartData = ref(null);
const chartOptions = ref({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'top',
        },
        title: {
            display: true,
            text: 'Yearly Counts'
        }
    }
});

const comparisonData = computed(() => props.reportData?.results || []);
const baselineYear = computed(() => props.reportData?.parameters?.baseline_year);
const years = computed(() => {
    if (comparisonData.value.length === 0) return [];
    const yearKeys = Object.keys(comparisonData.value[0]?.yearly_counts || {});
    return yearKeys.sort();
});

onMounted(() => {
    if (!props.reportData) {
        error.value = 'Failed to load report data. The analysis results might not be available.';
    } else if (comparisonData.value.length > 0) {
        // Select the first group by default
        selectGroup(comparisonData.value[0]);
    }
});

const selectGroup = (group) => {
    activeGroup.value = group;
    const labels = years.value;
    const data = labels.map(year => group.yearly_counts[year] || 0);

    chartData.value = {
        labels,
        datasets: [{
            label: `Count for ${group.group}`,
            backgroundColor: '#4f46e5',
            data,
        }]
    };
};

const getChangeClass = (change) => {
    if (change > 0) return 'text-red-600';
    if (change < 0) return 'text-green-600';
    return 'text-gray-500';
};

const formatPercent = (value) => {
    if (value === null || value === undefined || isNaN(value)) return 'N/A';
    return `${value.toFixed(2)}%`;
};
</script>

<template>
    <PageTemplate>
        <Head :title="reportTitle" />
        <div class="container mx-auto p-4 md:p-8">
            <div v-if="error" class="text-center py-20 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                <strong class="font-bold">Error!</strong>
                <span class="block sm:inline">{{ error }}</span>
            </div>
            <div v-else-if="reportData" class="space-y-12">
                <h1 class="text-4xl font-bold text-center text-gray-800">{{ reportTitle }}</h1>
                <h2 class="text-lg text-center text-gray-500">Job ID: {{ jobId }}</h2>

                <div v-if="comparisonData.length > 0" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Data Table -->
                    <div class="lg:col-span-2 bg-white p-6 rounded-lg shadow-md">
                        <h3 class="text-2xl font-semibold mb-4">Comparison Data</h3>
                        <div class="overflow-x-auto max-h-[70vh]">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50 sticky top-0">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Group</th>
                                        <th v-for="year in years" :key="year" scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ year }}</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">YoY Change</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="item in comparisonData" :key="item.group" @click="selectGroup(item)" class="hover:bg-gray-100 cursor-pointer" :class="{'bg-indigo-100': activeGroup && activeGroup.group === item.group}">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ item.group }}</td>
                                        <td v-for="year in years" :key="`${item.group}-${year}`" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ item.yearly_counts[year] || 0 }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <div v-for="(change, period) in item.yoy_change" :key="period">
                                                <span class="font-semibold">{{ period.replace('_vs_', ' vs ') }}: </span>
                                                <span :class="getChangeClass(change)">{{ formatPercent(change) }}</span>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Chart -->
                    <div class="lg:col-span-1 bg-white p-6 rounded-lg shadow-md">
                         <h3 class="text-2xl font-semibold mb-4">Yearly Trend</h3>
                         <div v-if="activeGroup" class="h-[60vh]">
                            <h4 class="text-lg font-medium text-center mb-4">{{ activeGroup.group }}</h4>
                            <Bar v-if="chartData" :data="chartData" :options="chartOptions" />
                         </div>
                         <div v-else class="flex items-center justify-center h-full text-gray-500">
                             <p>Select a group from the table to view the chart.</p>
                         </div>
                    </div>
                </div>

                <div v-else class="text-center py-6 text-gray-500">
                    <p>No comparison data was found in the analysis results.</p>
                </div>
            </div>
        </div>
    </PageTemplate>
</template>
