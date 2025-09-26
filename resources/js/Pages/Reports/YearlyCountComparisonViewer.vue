<script setup>
import { ref, onMounted, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import PageTemplate from '@/Components/PageTemplate.vue';
// Chart.js imports are no longer needed
// import { Bar } from 'vue-chartjs';
// import { Chart as ChartJS, Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale } from 'chart.js';

// ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale);

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
// Chart-related refs are no longer needed
// const activeGroup = ref(null);
// const chartData = ref(null);
// const chartOptions = ref({ ... });

const comparisonData = computed(() => props.reportData?.results || []);
const allYears = computed(() => props.reportData?.all_years || []);
const parameters = computed(() => props.reportData?.parameters || {});
const toDateCutoff = computed(() => {
    if (!parameters.value.analysis_to_date_cutoff) return null;
    const d = new Date(parameters.value.analysis_to_date_cutoff);
    return d.toLocaleDateString(undefined, { month: 'long', day: 'numeric' });
});
const toDateCutoffShort = computed(() => {
    if (!parameters.value.analysis_to_date_cutoff) return null;
    const d = new Date(parameters.value.analysis_to_date_cutoff);
    return d.toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
});
const currentYear = computed(() => parameters.value.analysis_current_year);
const fullYears = computed(() => allYears.value.filter(year => year !== currentYear.value));

onMounted(() => {
    if (!props.reportData) {
        error.value = 'Failed to load report data. The analysis results might not be available.';
    }
});

// selectGroup and chart logic removed

const getChangeClass = (change) => {
    if (change > 0) return 'text-red-600';
    if (change < 0) return 'text-green-600';
    return 'text-gray-500';
};

const formatPercent = (value) => {
    if (value === null || value === undefined || isNaN(value)) return '--';
    const sign = value > 0 ? '+' : '';
    return `${sign}${value.toFixed(2)}% YoY`;
};

const formatCellData = (data) => {
    if (!data) return { count: '-', change: '--', changeClass: 'text-gray-500' };
    return {
        count: data.count.toLocaleString(),
        change: formatPercent(data.change_pct),
        changeClass: getChangeClass(data.change_pct),
    };
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
            <div v-else-if="reportData" class="space-y-8">
                <div class="text-center">
                    <h1 class="text-4xl font-bold text-gray-800">{{ reportTitle }}</h1>
                    <p class="text-lg text-gray-500 mt-2">Job ID: {{ jobId }}</p>
                </div>

                <div class="bg-white p-6 rounded-lg shadow-md">
                    <h3 class="text-2xl font-semibold mb-2">Analysis Parameters</h3>
                    <p class="text-gray-600">
                        Grouping by: <strong class="font-medium text-gray-800">{{ parameters.group_by_col }}</strong>.
                        Timestamp column: <strong class="font-medium text-gray-800">{{ parameters.timestamp_col }}</strong>.
                    </p>
                </div>

                <div v-if="comparisonData.length > 0">
                    <!-- To Date Comparison Table -->
                    <div class="bg-white p-6 rounded-lg shadow-md">
                        <h3 class="text-2xl font-semibold mb-2">Year-over-Year Comparison (To {{ toDateCutoffShort }})</h3>
                        <p class="text-gray-600 mb-4">
                            Comparing data up to <strong class="font-medium text-gray-800">{{ toDateCutoff }}</strong> for each year.
                            Current year is <strong class="font-medium text-gray-800">{{ currentYear }}</strong>.
                        </p>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Group</th>
                                        <th v-for="year in allYears" :key="year" scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ year }}</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="item in comparisonData" :key="item.group">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ item.group }}</td>
                                        <td v-for="year in allYears" :key="`${item.group}-${year}-todate`" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <div v-if="item.to_date[year]">
                                                <span class="font-semibold text-gray-800">{{ formatCellData(item.to_date[year]).count }}</span>
                                                <span :class="formatCellData(item.to_date[year]).changeClass" class="block text-xs">{{ formatCellData(item.to_date[year]).change }}</span>
                                            </div>
                                            <span v-else>-</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Full Year Comparison Table -->
                    <div class="bg-white p-6 rounded-lg shadow-md mt-8">
                        <h3 class="text-2xl font-semibold mb-2">Year-over-Year Comparison (Full Years)</h3>
                        <p class="text-gray-600 mb-4">Comparing complete calendar years. Current year ({{ currentYear }}) is excluded as it is incomplete.</p>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Group</th>
                                        <th v-for="year in fullYears" :key="year" scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ year }}</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="item in comparisonData" :key="item.group">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ item.group }}</td>
                                        <td v-for="year in fullYears" :key="`${item.group}-${year}-fullyear`" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <div v-if="item.full_year[year]">
                                                <span class="font-semibold text-gray-800">{{ formatCellData(item.full_year[year]).count }}</span>
                                                <span :class="formatCellData(item.full_year[year]).changeClass" class="block text-xs">{{ formatCellData(item.full_year[year]).change }}</span>
                                            </div>
                                            <span v-else>-</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
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
