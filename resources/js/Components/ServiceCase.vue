<template>
  <div
      v-if="data"
      class="p-4 bg-gray-100 rounded-lg shadow flex w-full"
      :class="{ 'w-1/2': hasPhoto }"
  >
      <div class="case-info">
          <h2 class="text-xl font-bold text-gray-800">311 Case</h2>
          <p class="text-gray-700 mb-4">
              <strong>Date:</strong> {{ new Date(data.date).toLocaleString() }}
          </p>
          <ul class="space-y-2">
              <li><strong>Case ID:</strong> {{ data.info.case_enquiry_id }}</li>
              <li><strong>Status:</strong> {{ data.info.case_status }}</li>
              <li><strong>Title:</strong> {{ data.info.case_title }}</li>
              <li><strong>Reason:</strong> {{ data.info.reason }}</li>
              <li><strong>Subject:</strong> {{ data.info.subject }}</li>
              <li><strong>Location:</strong> {{ data.info.location }}</li>
              <li><strong>Neighborhood:</strong> {{ data.info.neighborhood }}</li>
              <li><strong>Source:</strong> {{ data.info.source }}</li>
              <li><strong>Department:</strong> {{ data.info.department }}</li>
              <li><strong>Closure Date:</strong> {{ formatDate(data.info.closed_dt) }}</li>
          </ul>
      </div>

      <!-- Pass parsed images to the ImageCarousel component -->
      <OneImageCarousel
          v-if="hasPhoto"
          :dataPoints="parsedPhotos"
          @on-image-click="onImageClick"
      />
  </div>
</template>

<script setup>
import { computed, defineProps, defineEmits } from 'vue';
import OneImageCarousel from './OneImageCarousel.vue';

const props = defineProps({
  data: {
      type: Object,
      required: true,
  },
});

function formatDate(date) {
  return date ? new Date(date).toLocaleString() : 'N/A';
}

// Extract and parse photo URLs
const parsedPhotos = computed(() => {
  const photos = [];
  if (props.data.info?.closed_photo) {
      const closedPhotos = props.data.info.closed_photo.split('|');
      closedPhotos.forEach(photoUrl => {
          photos.push({
              info: {
                  closed_photo: photoUrl,
                  type: '311 Case',
              }
          })
      })
  }

  if (props.data.info?.submitted_photo) {
      const submittedPhotos = props.data.info.submitted_photo.split('|');
      submittedPhotos.forEach(photoUrl => {
          photos.push({
              info: {
                  submitted_photo: photoUrl,
                  type: '311 Case',
              }
          })
      })
  }
  return photos;
});

// Emit event when an image is clicked
const emit = defineEmits(['on-image-click']);
const onImageClick = (photo) => {
  emit('on-image-click', photo); // Emit the clicked photo
};

// Check if the case has a photo without an error if one of the fields is missing
const hasPhoto = computed(() => {
  return props.data.info?.closed_photo || props.data.info?.submitted_photo;
});
</script>

<style scoped></style>