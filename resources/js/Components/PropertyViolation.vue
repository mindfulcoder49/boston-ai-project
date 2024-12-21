<template>
    <div v-if="data" class="p-4 bg-gray-100 rounded-lg shadow">
        <h2 class="text-xl font-bold text-gray-800">Property Violation</h2>
        <p class="text-gray-700 mb-4">
            <strong>Date:</strong> {{ new Date(data.date).toLocaleString() }}
        </p>
        <ul class="space-y-2">
            <li><strong>Case Number:</strong> {{ data.info.case_no }}</li>
             <li><strong>Case Definition Key:</strong> {{ data.info.ap_case_defn_key }}</li>
            <li><strong>Status:</strong> {{ data.info.status }}</li>
            <li><strong>Code:</strong> {{ data.info.code }}</li>
             <li><strong>Value:</strong> {{ data.info.value }}</li>
            <li><strong>Description:</strong> {{ data.info.description }}</li>
            <li><strong>Violation Street Number:</strong> {{ data.info.violation_stno }}</li>
            <li><strong>Violation Street High:</strong> {{ data.info.violation_sthigh }}</li>
            <li><strong>Violation Street:</strong> {{ data.info.violation_street }}</li>
            <li><strong>Violation Suffix:</strong> {{ data.info.violation_suffix }}</li>
             <li><strong>Violation City:</strong> {{ data.info.violation_city }}</li>
            <li><strong>Violation State:</strong> {{ data.info.violation_state }}</li>
             <li><strong>Violation Zip:</strong> {{ data.info.violation_zip }}</li>
             <li><strong>Ward:</strong> {{ data.info.ward }}</li>
            <li><strong>Contact Address 1:</strong> {{ data.info.contact_addr1 }}</li>
             <li><strong>Contact Address 2:</strong> {{ data.info.contact_addr2 }}</li>
            <li><strong>Contact City:</strong> {{ data.info.contact_city }}</li>
             <li><strong>Contact State:</strong> {{ data.info.contact_state }}</li>
            <li><strong>Contact Zip:</strong> {{ data.info.contact_zip }}</li>
              <li><strong>Sam ID:</strong> {{ data.info.sam_id }}</li>
        </ul>
    </div>
</template>

<script setup>
defineProps({
    data: {
        type: Object,
        required: true,
    },
});
</script>