<template>
    <div class="bg-white p-4 rounded-lg shadow flex flex-col md:flex-row items-center justify-between space-y-4 md:space-y-0 md:space-x-4">
        <div v-if="availableResolutions.length > 1" class="flex items-center space-x-4">
            <div>
                <label for="base-resolution" class="font-semibold text-sm text-gray-700">Base Resolution:</label>
                <select id="base-resolution" v-model="selectedBaseResolution" @change="onBaseResolutionChange" class="ml-2 p-2 border rounded-md text-sm">
                    <option v-for="res in availableResolutions" :key="res" :value="res">{{ res }}</option>
                </select>
            </div>
            <div>
                <label class="font-semibold text-sm text-gray-700">Click Mode:</label>
                <div class="inline-flex rounded-md shadow-sm ml-2" role="group">
                    <button
                        type="button"
                        @click="setMode('select')"
                        :class="['px-4 py-2 text-sm font-medium border rounded-l-lg', mode === 'select' ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-900 border-gray-200 hover:bg-gray-100']"
                    >
                        Select
                    </button>
                    <button
                        type="button"
                        @click="setMode('explode')"
                        :class="['px-4 py-2 text-sm font-medium border rounded-r-lg', mode === 'explode' ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-900 border-gray-200 hover:bg-gray-100']"
                    >
                        Explode
                    </button>
                </div>
            </div>
        </div>
        <div v-else class="text-sm text-gray-600">
            Only one resolution available for this report.
        </div>

        <div class="flex items-center space-x-2">
            <label for="color-steps" class="font-semibold text-sm text-gray-700">Color Steps:</label>
            <input type="range" id="color-steps" min="5" max="20" :value="colorSteps" @input="onColorStepsChange" class="w-32">
            <span class="text-sm font-mono">{{ colorSteps }}</span>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    reportGroup: {
        type: Array,
        required: true,
    },
    modelValue: { // For base resolution
        type: Number,
        required: true,
    },
    mode: { // For click mode
        type: String,
        required: true,
    },
    colorSteps: {
        type: Number,
        required: true,
    }
});

const emit = defineEmits(['update:modelValue', 'update:mode', 'update:colorSteps']);

const availableResolutions = computed(() => 
    [...new Set(props.reportGroup.map(r => r.resolution))].sort((a, b) => a - b)
);

const selectedBaseResolution = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value)
});

const onBaseResolutionChange = (event) => {
    emit('update:modelValue', parseInt(event.target.value, 10));
};

const setMode = (newMode) => {
    emit('update:mode', newMode);
};

const onColorStepsChange = (event) => {
    emit('update:colorSteps', parseInt(event.target.value, 10));
};
</script>
