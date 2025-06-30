<template>
  <div v-if="showBanner && (featuredMaps.length > 0 || !$page.props.auth.user)" class="bg-gradient-to-r from-blue-600 to-blue-700 text-white p-4 rounded-lg shadow-lg my-6">
    <div class="container mx-auto">
      <div v-if="featuredMaps.length > 0">
        <h3 class="text-xl font-semibold mb-2">Featured Community Maps!</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 text-sm">
          <div v-for="map in featuredMaps" :key="map.id" class="bg-blue-800 p-3 rounded-md hover:bg-blue-900 transition-colors">
            <Link :href="route('saved-maps.view', map.id)" class="block">
              <h4 class="font-semibold text-white truncate">{{ map.name }}</h4>
              <p v-if="map.description" class="text-xs text-blue-200 truncate">{{ map.description }}</p>
              <p class="text-xs text-blue-300 mt-1">
                By: {{ map.creator_display_name || map.user?.name || 'Community Member' }}
                 <span v-if="map.creator_tier_display_name" 
                      :class="['ml-1 px-1 py-0.25 rounded-full text-xs', getTierBadgeClass(map.creator_tier_name, false)]">
                  {{ map.creator_tier_display_name }}
                </span>
              </p>
            </Link>
          </div>
        </div>
         <div class="mt-4 text-center flex justify-center items-center flex-wrap gap-3">
            <Link
              :href="route('saved-maps.index')"
              class="inline-block px-5 py-2 bg-white text-blue-700 font-semibold rounded-md hover:bg-blue-50 transition-colors duration-150 ease-in-out shadow-sm text-sm"
              @click="trackBannerClick('view_all_public_maps')"
            >
              View All Public Maps
            </Link>
            <Link
              :href="route('reports.map.index')"
              class="inline-block px-5 py-2 bg-white text-blue-700 font-semibold rounded-md hover:bg-blue-50 transition-colors duration-150 ease-in-out shadow-sm text-sm"
              @click="trackBannerClick('view_report_maps')"
            >
              View Report Maps
            </Link>
          </div>
      </div>
      <div v-else-if="!$page.props.auth.user" class="text-center">
        <h3 class="text-xl font-semibold">Discover Community & Report Maps!</h3>
        <p class="text-sm mt-1 mb-3 text-blue-100">Explore maps created by our community or view our curated data reports.</p>
        <div class="flex justify-center items-center flex-wrap gap-3">
          <Link
            :href="route('saved-maps.index')"
            class="px-6 py-2 bg-white text-blue-700 font-semibold rounded-md hover:bg-blue-50 transition-colors duration-150 ease-in-out shadow-md"
             @click="trackBannerClick('guest_view_public_maps')"
          >
            Explore Public Maps
          </Link>
          <Link
            :href="route('reports.map.index')"
            class="px-6 py-2 bg-white text-blue-700 font-semibold rounded-md hover:bg-blue-50 transition-colors duration-150 ease-in-out shadow-md"
             @click="trackBannerClick('guest_view_report_maps')"
          >
            View Report Maps
          </Link>
        </div>
      </div>
      <!-- If logged in and no featured maps, banner can be hidden by v-if condition -->
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { event } from 'vue-gtag';

const page = usePage();
const featuredMaps = computed(() => page.props.featuredMaps || []);

// Banner is shown if there are featured maps OR if the user is a guest (to prompt exploration)
const showBanner = computed(() => featuredMaps.value.length > 0 || !page.props.auth?.user);

const trackBannerClick = (label) => {
  event('click', {
    event_category: 'engagement',
    event_label: label || 'featured_user_maps_banner_click',
    value: 1
  });
};

const getTierBadgeClass = (tierName, isLightText = false) => {
  const tierLower = tierName?.toLowerCase() || '';
  // Adjusted for better visibility on dark banner background if needed
  // These color combinations (e.g., text-purple-50 on bg-purple-400) are high contrast.
  if (tierLower.includes('pro')) return isLightText ? 'bg-purple-400 text-purple-50' : 'bg-purple-100 text-purple-800';
  if (tierLower.includes('basic')) return isLightText ? 'bg-green-400 text-green-50' : 'bg-green-100 text-green-800';
  if (tierLower.includes('free')) return isLightText ? 'bg-blue-400 text-blue-50' : 'bg-blue-100 text-blue-800';
  return isLightText ? 'bg-gray-600 text-gray-50' : 'bg-gray-100 text-gray-800';
};

</script>

<style scoped>
/* Add any specific styles for the banner here */
</style>
