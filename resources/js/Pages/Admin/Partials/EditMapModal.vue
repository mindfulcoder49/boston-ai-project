<template>
  <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" @click.self="closeModal">
    <div class="bg-white p-6 rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto"> <!-- Increased max-w -->
      <h2 class="text-xl font-semibold mb-6">Edit Map: {{ form.name || (map_data && map_data.name) }}</h2>
      <form @submit.prevent="submitForm" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label for="map_name" class="block text-sm font-medium text-gray-700">Name</label>
            <input type="text" id="map_name" v-model="form.name" class="mt-1 block w-full" required>
            <div v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</div>
          </div>
          <div>
            <label for="map_slug" class="block text-sm font-medium text-gray-700">Slug</label>
            <input type="text" id="map_slug" v-model="form.slug" class="mt-1 block w-full">
            <div v-if="form.errors.slug" class="text-red-500 text-xs mt-1">{{ form.errors.slug }}</div>
          </div>
        </div>

        <div>
          <label for="map_description" class="block text-sm font-medium text-gray-700">Description</label>
          <textarea id="map_description" v-model="form.description" rows="3" class="mt-1 block w-full"></textarea>
          <div v-if="form.errors.description" class="text-red-500 text-xs mt-1">{{ form.errors.description }}</div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label for="map_latitude" class="block text-sm font-medium text-gray-700">Latitude</label>
            <input type="number" step="any" id="map_latitude" v-model.number="form.latitude" class="mt-1 block w-full" required>
            <div v-if="form.errors.latitude" class="text-red-500 text-xs mt-1">{{ form.errors.latitude }}</div>
          </div>
          <div>
            <label for="map_longitude" class="block text-sm font-medium text-gray-700">Longitude</label>
            <input type="number" step="any" id="map_longitude" v-model.number="form.longitude" class="mt-1 block w-full" required>
            <div v-if="form.errors.longitude" class="text-red-500 text-xs mt-1">{{ form.errors.longitude }}</div>
          </div>
          <div>
            <label for="map_zoom_level" class="block text-sm font-medium text-gray-700">Zoom Level</label>
            <input type="number" id="map_zoom_level" v-model.number="form.zoom_level" class="mt-1 block w-full" required>
            <div v-if="form.errors.zoom_level" class="text-red-500 text-xs mt-1">{{ form.errors.zoom_level }}</div>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label for="map_creator_display_name" class="block text-sm font-medium text-gray-700">Creator Display Name</label>
                <input type="text" id="map_creator_display_name" v-model="form.creator_display_name" class="mt-1 block w-full">
                <div v-if="form.errors.creator_display_name" class="text-red-500 text-xs mt-1">{{ form.errors.creator_display_name }}</div>
            </div>
            <div>
                <label for="map_view_count" class="block text-sm font-medium text-gray-700">View Count</label>
                <input type="number" id="map_view_count" v-model.number="form.view_count" class="mt-1 block w-full" required>
                <div v-if="form.errors.view_count" class="text-red-500 text-xs mt-1">{{ form.errors.view_count }}</div>
            </div>
        </div>

        <!-- Status Toggles -->
        <div class="space-y-3 pt-2">
            <h3 class="text-sm font-medium text-gray-700">Status Flags</h3>
            <div class="flex items-center">
                <input id="is_public" v-model="form.is_public" type="checkbox" class="h-4 w-4">
                <label for="is_public" class="ml-2 block text-sm text-gray-900">Public</label>
            </div>
            <div class="flex items-center">
                <input id="is_approved" v-model="form.is_approved" :disabled="!form.is_public" type="checkbox" class="h-4 w-4">
                <label for="is_approved" class="ml-2 block text-sm text-gray-900">Approved</label>
            </div>
            <div class="flex items-center">
                <input id="is_featured" v-model="form.is_featured" :disabled="!form.is_public || !form.is_approved" type="checkbox" class="h-4 w-4">
                <label for="is_featured" class="ml-2 block text-sm text-gray-900">Featured</label>
            </div>
        </div>
        
        <!-- JSON Fields -->
        <div>
          <label for="map_filters" class="block text-sm font-medium text-gray-700">Filters JSON</label>
          <textarea id="map_filters" v-model="filters_string" rows="3" class="mt-1 block w-full font-mono text-xs" placeholder="Enter valid JSON or leave empty"></textarea>
          <div v-if="form.errors.filters" class="text-red-500 text-xs mt-1">{{ form.errors.filters }}</div>
        </div>
        <div>
          <label for="map_settings" class="block text-sm font-medium text-gray-700">Map Settings JSON</label>
          <textarea id="map_settings" v-model="map_settings_string" rows="3" class="mt-1 block w-full font-mono text-xs" placeholder="Enter valid JSON or leave empty"></textarea>
          <div v-if="form.errors.map_settings" class="text-red-500 text-xs mt-1">{{ form.errors.map_settings }}</div>
        </div>
        <div>
          <label for="map_configurable_filter_fields" class="block text-sm font-medium text-gray-700">Configurable Filter Fields JSON</label>
          <textarea id="map_configurable_filter_fields" v-model="configurable_filter_fields_string" rows="3" class="mt-1 block w-full font-mono text-xs" placeholder="Enter valid JSON or leave empty"></textarea>
          <div v-if="form.errors.configurable_filter_fields" class="text-red-500 text-xs mt-1">{{ form.errors.configurable_filter_fields }}</div>
        </div>


        <div class="mt-8 flex justify-end space-x-3">
          <button type="button" @click="closeModal" class="px-4 py-2 btn-secondary">Cancel</button>
          <button type="submit" :disabled="form.processing" class="px-4 py-2 btn-primary disabled:opacity-50">
            {{ form.processing ? 'Saving...' : 'Save Changes' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import { watch, ref, nextTick } from 'vue';

const props = defineProps({
  show: Boolean,
  map_data: Object,
});

const emit = defineEmits(['close']);

const form = useForm({
  name: '',
  description: '',
  creator_display_name: '',
  is_public: false,
  is_approved: false,
  is_featured: false,
  latitude: 0,
  longitude: 0,
  zoom_level: 10,
  slug: '',
  view_count: 0,
  filters: '',       // Will hold the JSON string for submission
  map_settings: '',  // Will hold the JSON string for submission
  configurable_filter_fields: '', // Will hold the JSON string for submission
});

// Refs for textarea v-model bindings
const filters_string = ref('');
const map_settings_string = ref('');
const configurable_filter_fields_string = ref('');


watch(() => props.map_data, (newMapData) => {
  form.clearErrors();
  form.reset(); // Reset form to defaults first

  if (newMapData) {
    // Assign primitive values directly to the form
    form.name = newMapData.name || '';
    form.description = newMapData.description || '';
    form.creator_display_name = newMapData.creator_display_name || '';
    form.is_public = newMapData.is_public || false;
    form.is_approved = newMapData.is_approved || false;
    form.is_featured = newMapData.is_featured || false;
    form.latitude = newMapData.latitude || 0;
    form.longitude = newMapData.longitude || 0;
    form.zoom_level = newMapData.zoom_level || 10;
    form.slug = newMapData.slug || '';
    form.view_count = newMapData.view_count || 0;

    // Update the string refs for JSON textareas
    // These refs are v-model bound to the textareas
    filters_string.value = newMapData.filters ? JSON.stringify(newMapData.filters, null, 2) : '';
    map_settings_string.value = newMapData.map_settings ? JSON.stringify(newMapData.map_settings, null, 2) : '';
    configurable_filter_fields_string.value = newMapData.configurable_filter_fields ? JSON.stringify(newMapData.configurable_filter_fields, null, 2) : '';

  } else {
    // If newMapData is null (e.g. modal closed and re-opened without data), clear string refs
    filters_string.value = '';
    map_settings_string.value = '';
    configurable_filter_fields_string.value = '';
  }
}, { immediate: true, deep: true });


watch(() => form.is_public, (isPublic) => {
    if (!isPublic) {
        form.is_approved = false;
        form.is_featured = false;
    }
});
watch(() => form.is_approved, (isApproved) => {
    if (!isApproved) {
        form.is_featured = false;
    }
});


const submitForm = () => {
  if (!props.map_data) return;

  // Assign the current values from textareas (which are bound to string refs) to the form object for submission
  form.filters = filters_string.value;
  form.map_settings = map_settings_string.value;
  form.configurable_filter_fields = configurable_filter_fields_string.value;
  
  // Clear specific JSON errors if string is empty, as nullable allows empty
  if (!filters_string.value.trim()) form.clearErrors('filters');
  if (!map_settings_string.value.trim()) form.clearErrors('map_settings');
  if (!configurable_filter_fields_string.value.trim()) form.clearErrors('configurable_filter_fields');


  form.put(route('admin.maps.update', props.map_data.id), {
    preserveScroll: true,
    onSuccess: () => {
      closeModal();
    },
    onError: (errors) => {
      console.error("Error updating map:", errors);
    },
  });
};

const closeModal = () => {
  emit('close');
  // form.reset(); // Already called in watcher when props.map_data changes or becomes null
  // form.clearErrors(); // Also handled by watcher
};

</script>

<style scoped>
/* Add if not using @tailwindcss/forms */
input[type="text"], input[type="number"], input[type="email"], textarea, select {
  border: 1px solid #d1d5db; /* Tailwind gray-300 */
  border-radius: 0.375rem; /* Tailwind rounded-md */
  box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); /* Tailwind shadow-sm */
}
input[type="text"]:focus, input[type="number"]:focus, input[type="email"]:focus, textarea:focus, select:focus {
  outline: 2px solid transparent;
  outline-offset: 2px;
  border-color: #6366f1; /* Tailwind indigo-500 */
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.5); /* Tailwind ring-indigo-500 */
}
.btn-primary {
    background-color: #4f46e5; color: white;
}
.btn-primary:hover {
    background-color: #4338ca;
}
.btn-secondary {
    background-color: #e5e7eb; color: #374151; border: 1px solid #d1d5db;
}
.btn-secondary:hover {
    background-color: #d1d5db;
}
</style>
