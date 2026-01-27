<template>
  <div class="json-tree">
    <ul class="list-none pl-5">
      <li v-for="(value, key) in data" :key="key">
        <span @click="toggleCollapse(key)" class="cursor-pointer font-semibold">
          {{ key }}:
          <template v-if="isObjectOrArray(value)">
            <span class="text-blue-500">
              [{{ collapsed[key] ? '...' : (Array.isArray(value) ? `Array(${value.length})` : 'Object') }}]
            </span>
          </template>
          <template v-else>
            <span class="text-gray-700 ml-1">{{ formatValue(value) }}</span>
          </template>
        </span>
        <div v-if="!collapsed[key] && isObjectOrArray(value)" class="ml-5">
          <JsonTree :data="value" />
        </div>
      </li>
    </ul>
  </div>
</template>

<script setup>
import { ref, defineProps } from 'vue';

const props = defineProps({
  data: {
    type: [Object, Array],
    required: true
  }
});

// Initialize all keys as collapsed by default.
const collapsed = ref(
  Object.keys(props.data).reduce((acc, key) => {
    acc[key] = true;
    return acc;
  }, {})
);

const isObjectOrArray = (value) => {
  return typeof value === 'object' && value !== null;
};

const formatValue = (value) => {
  if (typeof value === 'string') {
    return `"${value}"`;
  }
  return value;
};

const toggleCollapse = (key) => {
  collapsed.value[key] = !collapsed.value[key];
};
</script>
