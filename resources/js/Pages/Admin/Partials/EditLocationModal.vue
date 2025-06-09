<template>
  <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" @click.self="closeModal">
    <div class="bg-white p-6 rounded-lg shadow-xl w-full max-w-xl max-h-[90vh] overflow-y-auto">
      <h2 class="text-xl font-semibold mb-6">Edit Location: {{ form.name || (location_data && location_data.name) }}</h2>
      <form @submit.prevent="submitForm" class="space-y-4">
        <div>
          <label for="loc_name" class="block text-sm font-medium text-gray-700">Name</label>
          <input type="text" id="loc_name" v-model="form.name" class="mt-1 block w-full input-field" required>
          <div v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</div>
        </div>

        <div>
          <label for="loc_address" class="block text-sm font-medium text-gray-700">Address</label>
          <textarea id="loc_address" v-model="form.address" rows="2" class="mt-1 block w-full input-field" required></textarea>
          <div v-if="form.errors.address" class="text-red-500 text-xs mt-1">{{ form.errors.address }}</div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label for="loc_latitude" class="block text-sm font-medium text-gray-700">Latitude</label>
            <input type="number" step="any" id="loc_latitude" v-model.number="form.latitude" class="mt-1 block w-full input-field" required>
            <div v-if="form.errors.latitude" class="text-red-500 text-xs mt-1">{{ form.errors.latitude }}</div>
          </div>
          <div>
            <label for="loc_longitude" class="block text-sm font-medium text-gray-700">Longitude</label>
            <input type="number" step="any" id="loc_longitude" v-model.number="form.longitude" class="mt-1 block w-full input-field" required>
            <div v-if="form.errors.longitude" class="text-red-500 text-xs mt-1">{{ form.errors.longitude }}</div>
          </div>
        </div>
        
        <div>
          <label for="loc_user_id" class="block text-sm font-medium text-gray-700">User</label>
          <select id="loc_user_id" v-model="form.user_id" class="mt-1 block w-full input-field" required>
            <option disabled :value="null">Select a user</option>
            <option v-for="user in usersForSelect" :key="user.id" :value="user.id">{{ user.display_name }}</option>
          </select>
          <div v-if="form.errors.user_id" class="text-red-500 text-xs mt-1">{{ form.errors.user_id }}</div>
        </div>

        <div>
          <label for="loc_language" class="block text-sm font-medium text-gray-700">Language (e.g., en, es)</label>
          <input type="text" id="loc_language" v-model="form.language" class="mt-1 block w-full input-field" maxlength="10">
          <div v-if="form.errors.language" class="text-red-500 text-xs mt-1">{{ form.errors.language }}</div>
        </div>

        <div>
          <label for="loc_notes" class="block text-sm font-medium text-gray-700">Notes</label>
          <textarea id="loc_notes" v-model="form.notes" rows="3" class="mt-1 block w-full input-field"></textarea>
          <div v-if="form.errors.notes" class="text-red-500 text-xs mt-1">{{ form.errors.notes }}</div>
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
import { watch } from 'vue';

const props = defineProps({
  show: Boolean,
  location_data: Object,
  usersForSelect: Array, // Array of {id: number, display_name: string}
});

const emit = defineEmits(['close']);

const form = useForm({
  name: '',
  address: '',
  latitude: 0,
  longitude: 0,
  notes: '',
  user_id: null,
  language: '', // Added language
});

watch(() => props.location_data, (newLocationData) => {
  form.clearErrors();
  form.reset(); // Reset form to defaults first

  if (newLocationData) {
    form.name = newLocationData.name || '';
    form.address = newLocationData.address || '';
    form.latitude = newLocationData.latitude || 0;
    form.longitude = newLocationData.longitude || 0;
    form.notes = newLocationData.notes || '';
    form.user_id = newLocationData.user_id || null;
    form.language = newLocationData.language || ''; // Added language
  }
}, { immediate: true, deep: true });


const submitForm = () => {
  if (!props.location_data) return;
  form.put(route('admin.locations.update', props.location_data.id), {
    preserveScroll: true,
    onSuccess: () => {
      closeModal();
    },
    onError: (errors) => {
      console.error("Error updating location:", errors);
    },
  });
};

const closeModal = () => {
  emit('close');
  // form.reset(); // Already handled by watcher when props.location_data changes
  // form.clearErrors(); // Also handled by watcher
};
</script>
<style scoped>
.input-field {
  border: 1px solid #d1d5db;
  border-radius: 0.375rem;
  padding: 0.5rem 0.75rem;
  box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
}
.input-field:focus {
  outline: 2px solid transparent;
  outline-offset: 2px;
  border-color: #6366f1;
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.5);
}
.btn-primary {
    background-color: #4f46e5; color: white; padding: 0.5rem 1rem; border-radius: 0.375rem;
}
.btn-primary:hover {
    background-color: #4338ca;
}
.btn-secondary {
    background-color: #e5e7eb; color: #374151; border: 1px solid #d1d5db; padding: 0.5rem 1rem; border-radius: 0.375rem;
}
.btn-secondary:hover {
    background-color: #d1d5db;
}
</style>
