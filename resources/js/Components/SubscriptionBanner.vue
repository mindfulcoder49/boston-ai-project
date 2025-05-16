<template>
    <div v-if="!isSubscribed"
         class="bg-gradient-to-r from-blue-900 to-green-700 text-white p-6 shadow-xl my-6 flex flex-col md:flex-row items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold mb-2">
          {{ translations.LabelsByLanguageCode[getSingleLanguageCode]?.bannerTitle || 'Unlock Full Potential!' }}
        </h2>
        <p class="mb-4 md:mb-0">
          {{ translations.LabelsByLanguageCode[getSingleLanguageCode]?.bannerDescription || 'Subscribe to access premium features like detailed reports, advanced maps, and more saved locations.' }}
        </p>
      </div>
      <div class="mt-4 md:mt-0 md:ml-6 text-center">
        <template v-if="isAuthenticated">
          <Link :href="route('subscription.index')"
                class="px-6 py-3 bg-white text-blue-600 font-semibold rounded-md shadow-md hover:bg-gray-100 transition-colors whitespace-nowrap">
            {{ translations.LabelsByLanguageCode[getSingleLanguageCode]?.bannerButton || 'View Plans & Subscribe' }}
          </Link>
        </template>
        <template v-else>
          <div class="flex flex-col space-y-2 items-center">
            <a :href="route('socialite.redirect', 'google') + '?redirect_to=' + route('subscription.index')"
               class="flex items-center justify-center w-full px-6 py-3 border border-transparent rounded-md shadow-sm text-base font-medium text-blue-600 bg-white hover:bg-gray-50">
              <img class="h-5 w-5 mr-2" src="https://upload.wikimedia.org/wikipedia/commons/c/c1/Google_%22G%22_logo.svg" alt="Google logo">
              {{ translations.LabelsByLanguageCode[getSingleLanguageCode]?.bannerRegisterWithGoogleButton || 'Login with Google to Subscribe' }}
            </a>
            <Link :href="route('register') + '?redirect_to=' + route('subscription.index')" class="text-sm text-gray-100 hover:text-white hover:underline">
              {{ translations.LabelsByLanguageCode[getSingleLanguageCode]?.bannerRegisterManuallyLink || 'Or register manually' }}
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
      return page.props.auth.user && page.props.auth.user.is_subscribed; // Assuming you add `is_subscribed` to user props
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
    bannerTitle: 'Unlock Full Potential!',
    bannerDescription: 'Subscribe to access premium features like detailed reports, advanced maps, and more saved locations.',
    bannerButton: 'View Plans & Subscribe',
    subscribedBannerTitle: 'You\'re All Set!',
    subscribedBannerDescription: 'Thank you for being a subscriber. Manage your subscription or explore features.',
    manageSubscriptionButton: 'Manage Subscription',
    bannerRegisterWithGoogleButton: 'Register with Google to Subscribe',
    bannerRegisterManuallyLink: 'Or register manually',
  };
  */
  </script>
  
  <style scoped>
  /* Styles for the banner if needed */
  </style>