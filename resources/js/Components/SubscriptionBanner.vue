<template>
    <div v-if="!isAuthenticated || (isAuthenticated && !isSubscribed)"
         class="bg-gradient-to-r from-blue-900 to-green-700 text-white p-6 shadow-xl my-6 flex flex-col md:flex-row items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold mb-2">
          {{ translations.LabelsByLanguageCode[getSingleLanguageCode]?.bannerTitle || 'Enhance Your Experience!' }}
        </h2>
        <p class="mb-4 md:mb-0" v-if="!isAuthenticated">
          {{ translations.LabelsByLanguageCode[getSingleLanguageCode]?.bannerDescriptionLoggedOut || 'Log in or register to access advanced free features like recent data on the full map and Food Inspection results. Subscribe for even more!' }}
        </p>
        <p class="mb-4 md:mb-0" v-else-if="isAuthenticated && !isSubscribed">
          {{ translations.LabelsByLanguageCode[getSingleLanguageCode]?.bannerDescriptionLoggedInNotSubscribed || 'You have access to great free features! Subscribe to unlock premium capabilities like extended data history, detailed reports, and more saved locations.' }}
        </p>
      </div>
      <div class="mt-4 md:mt-0 md:ml-6 text-center">
        <template v-if="isAuthenticated && !isSubscribed">
          <Link :href="route('subscription.index')"
                class="px-6 py-3 bg-white text-blue-600 font-semibold rounded-md shadow-md hover:bg-gray-100 transition-colors whitespace-nowrap">
            {{ translations.LabelsByLanguageCode[getSingleLanguageCode]?.bannerButtonViewPlans || 'View Plans & Subscribe' }}
          </Link>
        </template>
        <template v-else-if="!isAuthenticated">
          <div class="flex flex-col space-y-2 items-center">
            <a :href="route('socialite.redirect', 'google') + '?redirect_to=' + route('map.index')"
               class="flex items-center justify-center w-full px-6 py-3 border border-transparent rounded-md shadow-sm text-base font-medium text-blue-600 bg-white hover:bg-gray-50">
              <img class="h-5 w-5 mr-2" src="https://upload.wikimedia.org/wikipedia/commons/c/c1/Google_%22G%22_logo.svg" alt="Google logo">
              {{ translations.LabelsByLanguageCode[getSingleLanguageCode]?.bannerRegisterWithGoogleButton || 'Login with Google' }}
            </a>
            <Link :href="route('register') + '?redirect_to=' + route('map.index')" class="text-sm text-gray-100 hover:text-white hover:underline">
              {{ translations.LabelsByLanguageCode[getSingleLanguageCode]?.bannerRegisterManuallyLink || 'Or register manually' }}
            </Link>
             <Link :href="route('subscription.index')" class="mt-2 text-sm text-gray-100 hover:text-white hover:underline">
              {{ translations.LabelsByLanguageCode[getSingleLanguageCode]?.bannerOrViewPlansLink || 'Or view subscription plans' }}
            </Link>
          </div>
        </template>
      </div>
    </div>
    <div v-else-if="isAuthenticated && isSubscribed"
         class="bg-gradient-to-r from-green-500 to-teal-600 text-white p-6  shadow-xl my-6 flex flex-col md:flex-row items-center justify-between">
      <div>
          <h2 class="text-2xl font-bold mb-2">
              {{ translations.LabelsByLanguageCode[getSingleLanguageCode]?.subscribedBannerTitle || 'You\'re All Set!' }}
          </h2>
          <p class="mb-4 md:mb-0">
              {{ translations.LabelsByLanguageCode[getSingleLanguageCode]?.subscribedBannerDescription || 'Thank you for being a subscriber. Manage your subscription or explore features.' }}
          </p>
      </div>
      <Link :href="route('billing')"
          class="mt-4 md:mt-0 md:ml-6 px-6 py-3 bg-white text-green-600 font-semibold  shadow-md hover:bg-gray-100 transition-colors whitespace-nowrap">
          {{ translations.LabelsByLanguageCode[getSingleLanguageCode]?.manageSubscriptionButton || 'Manage Subscription' }}
      </Link>
    </div>
  </template>
  
  <script setup>
  import { Link, usePage } from '@inertiajs/vue3';
  import { computed, inject, ref } from 'vue';
  
  const page = usePage();
  const isAuthenticated = computed(() => !!page.props.auth.user);
  const isSubscribed = computed(() => {
      // This logic needs to be robust. Ideally, pass subscription status as a prop from Laravel.
      // For now, a simple check. You'll likely enhance this.
      // Ensure auth and user objects exist before accessing properties
      return page.props.auth && page.props.auth.user && page.props.auth.user.is_subscribed;
  });
  
  
  const translations = inject('translations');
  const language_codes = ref(['en-US']); // Or get from a global store/user preferences
  
  const getSingleLanguageCode = computed(() => {
    return (translations.LabelsByLanguageCode && translations.LabelsByLanguageCode[language_codes.value[0]]) ? language_codes.value[0] : 'en-US';
  });
  
  // Add to your translations provider:
  /*
  translations.LabelsByLanguageCode['en-US'] = {
    ...translations.LabelsByLanguageCode['en-US'],
    bannerTitle: 'Enhance Your Experience!',
    bannerDescriptionLoggedOut: 'Log in or register to access advanced free features like recent data on the full map and Food Inspection results. Subscribe for even more!',
    bannerDescriptionLoggedInNotSubscribed: 'You have access to great free features! Subscribe to unlock premium capabilities like extended data history, detailed reports, and more saved locations.',
    bannerButtonViewPlans: 'View Plans & Subscribe',
    bannerRegisterWithGoogleButton: 'Login with Google', // Was: 'Register with Google to Subscribe'
    bannerRegisterManuallyLink: 'Or register manually',
    bannerOrViewPlansLink: 'Or view subscription plans',
    subscribedBannerTitle: 'You\'re All Set!',
    subscribedBannerDescription: 'Thank you for being a subscriber. Manage your subscription or explore features.',
    manageSubscriptionButton: 'Manage Subscription',
  };
  */
  </script>
  
  <style scoped>
  /* Styles for the banner if needed */
  </style>