<template>
    <div class="bg-gradient-to-br from-gray-50 to-gray-100 p-4 sm:p-5 rounded-lg shadow-md flex items-center space-x-3 sm:space-x-4">
        <div v-if="icon" class="p-2 sm:p-3 bg-blue-500 text-white rounded-full flex-shrink-0">
            <span class="text-lg sm:text-xl">{{ getIconChar(icon) }}</span>
        </div>
        <div class="overflow-hidden">
            <h4 class="text-xs sm:text-sm font-medium text-gray-500 uppercase tracking-wider truncate">{{ title }}</h4>
            <p class="text-xl sm:text-2xl font-bold text-gray-800 truncate">{{ value }}</p>
        </div>
    </div>
</template>

<script setup>
defineProps({
    title: String,
    value: [String, Number],
    icon: String, // e.g., 'database', 'calendar-alt'
});

// Basic icon character mapping. For real icons, integrate an icon library.
const getIconChar = (iconName) => {
    const icons = {
        'database': '🗃️',
        'calendar-alt': '📅',
        'calendar-check': '🗓️',
        'calendar-day': '📆',
        'calendar-week': '📅',
        'calendar': '🗓',
        'crosshairs': '🎯',
        'hourglass-half': '⏳',
        'dollar-sign': '$',
        // Add more as needed
    };
    return icons[iconName] || '⭐';
};
</script>
