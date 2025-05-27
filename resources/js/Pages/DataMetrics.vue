<template>
    <PageTemplate>
        <Head title="Data Metrics & Coverage" />

        <div class="container mx-auto px-2 sm:px-4 py-8">
            <h1 class="text-3xl sm:text-4xl font-bold text-center text-gray-800 mb-3 sm:mb-4">Our Data Universe</h1>
            <p class="text-center text-gray-600 mb-1 sm:mb-2 px-4">
                Explore the depth and breadth of data available on our platform.
            </p>
            <p class="text-center text-sm text-gray-500 mb-8 sm:mb-10">
                Last updated: {{ new Date(lastUpdated).toLocaleString() }}
            </p>

            <div v-if="metricsData && metricsData.length > 0" class="space-y-10 sm:space-y-12">
                <div v-for="(data, index) in metricsData" :key="index"
                    class="bg-white p-4 sm:p-6 rounded-xl shadow-xl hover:shadow-2xl transition-shadow duration-300">
                    <h2 class="text-2xl sm:text-3xl font-semibold text-blue-700 mb-4 sm:mb-6 border-b-2 border-blue-200 pb-2">
                        {{ data.modelName }}
                    </h2>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6 mb-6">
                        <MetricCard title="Total Records" :value="formatNumber(data.totalRecords)" icon="database" />
                        <MetricCard v-if="data.minDate && data.minDate !== 'Error'" title="Oldest Record" :value="formatDate(data.minDate)" icon="calendar-alt" />
                        <MetricCard v-if="data.maxDate && data.maxDate !== 'Error'" title="Newest Record" :value="formatDate(data.maxDate)" icon="calendar-check" />
                        <MetricCard v-if="data.recordsLast30Days !== undefined" title="Last 30 Days" :value="formatNumber(data.recordsLast30Days)" icon="calendar-day" />
                        <MetricCard v-if="data.recordsLast90Days !== undefined" title="Last 90 Days" :value="formatNumber(data.recordsLast90Days)" icon="calendar-week" />
                        <MetricCard v-if="data.recordsLast1Year !== undefined" title="Last Year" :value="formatNumber(data.recordsLast1Year)" icon="calendar" />
                    </div>

                    <!-- Specific Metrics -->
                    <div v-if="hasSpecificMetrics(data)" class="mt-6 sm:mt-8 pt-4 sm:pt-6 border-t border-gray-200">
                        <h3 class="text-xl sm:text-2xl font-medium text-gray-700 mb-3 sm:mb-4">Specific Insights:</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
                            <!-- Crime Data Specifics -->
                            <div v-if="data.modelName === 'Crime Data'" class="space-y-4 sm:space-y-6">
                                <BarChartCard title="Top Offense Groups" :chart-data="formatChartData(data.offenseGroupDistribution, 'offense_description', 'total')" />
                                <MetricCard title="Shooting Incidents Reported" :value="formatNumber(data.shootingIncidents)" icon="crosshairs" />
                            </div>

                            <!-- 311 Cases Specifics -->
                            <div v-if="data.modelName === 'Three One One Case'" class="space-y-4 sm:space-y-6">
                                <BarChartCard title="Case Status Distribution" :chart-data="formatChartData(data.caseStatusDistribution, 'case_status', 'total')" />
                                <MetricCard v-if="data.averageClosureTimeHours" title="Avg. Closure Time" :value="`${data.averageClosureTimeHours} hours`" icon="hourglass-half" />
                            </div>

                            <!-- Food Inspections Specifics -->
                            <div v-if="data.modelName === 'Food Inspection'" class="space-y-4 sm:space-y-6">
                                <BarChartCard title="Inspection Results" :chart-data="formatChartData(data.resultDistribution, 'result', 'total')" />
                                <BarChartCard title="Violation Levels" :chart-data="formatChartData(data.violationLevelDistribution, 'viol_level', 'total')" />
                            </div>

                            <!-- Property Violations Specifics -->
                            <div v-if="data.modelName === 'Property Violation'" class="space-y-4 sm:space-y-6">
                                <BarChartCard title="Violation Status" :chart-data="formatChartData(data.statusDistribution, 'status', 'total')" />
                                <ListCard title="Top Violation Types" :items="formatListItems(data.topViolationCodes, 'code', 'description', 'total')" />
                            </div>

                            <!-- Building Permits Specifics -->
                            <div v-if="data.modelName === 'Building Permit'" class="space-y-4 sm:space-y-6">
                                <BarChartCard title="Work Types" :chart-data="formatChartData(data.workTypeDistribution, 'worktype', 'total')" />
                                <BarChartCard title="Permit Status" :chart-data="formatChartData(data.permitStatusDistribution, 'status', 'total')" />
                                <MetricCard title="Total Declared Valuation" :value="formatCurrency(data.totalDeclaredValuation)" icon="dollar-sign" />
                            </div>
                        </div>
                    </div>
                     <div v-else class="mt-6 sm:mt-8 pt-4 sm:pt-6 border-t border-gray-200">
                        <p class="text-gray-500 italic">No specific insights available for this data type yet.</p>
                    </div>
                </div>
            </div>
            <div v-else class="text-center py-10">
                <p class="text-xl text-gray-500">No metrics data available at the moment. Please check back later.</p>
            </div>
        </div>
    </PageTemplate>
</template>

<script setup>
import PageTemplate from '@/Components/PageTemplate.vue';
import { Head } from '@inertiajs/vue3';
import { defineAsyncComponent, computed } from 'vue';

const MetricCard = defineAsyncComponent(() => import('@/Components/Metrics/MetricCard.vue'));
const BarChartCard = defineAsyncComponent(() => import('@/Components/Metrics/BarChartCard.vue'));
const ListCard = defineAsyncComponent(() => import('@/Components/Metrics/ListCard.vue'));


const props = defineProps({
    metricsData: Array,
    lastUpdated: String,
});

const formatNumber = (num) => {
    if (num === null || num === undefined) return 'N/A';
    return num.toLocaleString();
};

const formatCurrency = (num) => {
    if (num === null || num === undefined) return 'N/A';
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(num);
};

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString(undefined, { year: 'numeric', month: 'long', day: 'numeric' });
};

const formatChartData = (dataArray, labelKey, valueKey) => {
    if (!dataArray || dataArray.length === 0) return { labels: [], datasets: [{ data: [] }] };
    const backgroundColors = [
        '#4A90E2', '#50E3C2', '#F5A623', '#BD10E0', '#9013FE', 
        '#4A4A4A', '#D0021B', '#F8E71C', '#7ED321', '#B8E986',
        '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF',
        '#FF9F40', '#C9CBCF', '#77DD77', '#FFB347', '#836953'
    ];
    return {
        labels: dataArray.map(item => item[labelKey] || 'Unknown'),
        datasets: [{
            label: 'Count',
            backgroundColor: dataArray.map((_, i) => backgroundColors[i % backgroundColors.length]), // Cycle through colors
            data: dataArray.map(item => item[valueKey])
        }]
    };
};

const formatListItems = (dataArray, primaryKey, secondaryKey, valueKey) => {
    if (!dataArray || dataArray.length === 0) return [];
    return dataArray.map(item => ({
        primary: item[primaryKey] || 'Unknown',
        secondary: item[secondaryKey] || '',
        value: formatNumber(item[valueKey])
    }));
};

const hasSpecificMetrics = (data) => {
    return (data.modelName === 'Crime Data' && (data.offenseGroupDistribution || data.shootingIncidents !== undefined)) ||
           (data.modelName === 'Three One One Case' && (data.caseStatusDistribution || data.averageClosureTimeHours !== undefined)) ||
           (data.modelName === 'Food Inspection' && (data.resultDistribution || data.violationLevelDistribution)) ||
           (data.modelName === 'Property Violation' && (data.statusDistribution || data.topViolationCodes)) ||
           (data.modelName === 'Building Permit' && (data.workTypeDistribution || data.permitStatusDistribution || data.totalDeclaredValuation !== undefined));
};

</script>

<style scoped>
/* Additional styling if needed */
</style>
