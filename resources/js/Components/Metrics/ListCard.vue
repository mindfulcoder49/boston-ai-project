<template>
    <div class="bg-white p-3 sm:p-4 rounded-lg shadow">
        <h5 class="text-base sm:text-md font-semibold text-gray-700 mb-2 sm:mb-3 text-center sm:text-left">{{ title }}</h5>
        <ul v-if="items && items.length > 0" class="space-y-2">
            <li v-for="(item, index) in items" :key="index" 
                class="flex flex-col sm:flex-row justify-between sm:items-center text-sm p-2 sm:p-3 bg-gray-50 hover:bg-gray-100 rounded transition-colors duration-150">
                <div class="flex-grow mb-1 sm:mb-0 sm:mr-2 overflow-hidden">
                    <span class="font-medium text-gray-800 block truncate" :title="item.primary + (item.secondary ? ' (' + item.secondary + ')' : '')">
                        {{ item.primary }}
                        <span v-if="item.secondary" class="text-gray-600 text-xs sm:text-sm">({{ item.secondary }})</span>
                    </span>
                </div>
                <span class="font-semibold text-blue-600 flex-shrink-0">{{ item.value }}</span>
            </li>
        </ul>
        <div v-else class="text-center py-5">
            <p class="text-gray-500">No items to display.</p>
        </div>
    </div>
</template>

<script setup>
defineProps({
    title: String,
    items: Array, // Expected format: [{ primary: '', secondary: '', value: '' }]
});
</script>
