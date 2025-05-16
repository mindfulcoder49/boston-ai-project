<template>
    <PageTemplate>
      <Head title="Subscription Plans" />
  
      <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold text-center text-gray-800 mb-10">
          {{ translations.LabelsByLanguageCode[getSingleLanguageCode]?.subscriptionPageTitle || 'Choose Your Plan' }}
        </h1>
  
        <!-- Success Message -->
        <div v-if="status === 'success'" class="mb-8 p-4 bg-green-100 border border-green-400 text-green-700 rounded-md shadow-md">
          <h2 class="text-xl font-semibold">
            {{ translations.LabelsByLanguageCode[getSingleLanguageCode]?.subscriptionSuccessTitle || 'Subscription Successful!' }}
          </h2>
          <p>
            {{ translations.LabelsByLanguageCode[getSingleLanguageCode]?.subscriptionSuccessMessage || 'Thank you for subscribing! Your access has been updated. You can manage your subscription anytime from the billing portal.' }}
          </p>
          <p v-if="sessionId" class="text-sm mt-2">
            {{ translations.LabelsByLanguageCode[getSingleLanguageCode]?.sessionId || 'Session ID' }}: {{ sessionId }}
          </p>
          <Link :href="route('map.index')" class="mt-4 inline-block px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600">
            {{ translations.LabelsByLanguageCode[getSingleLanguageCode]?.goToDashboard || 'Go to Dashboard' }}
          </Link>
        </div>
  
        <!-- Cancel Message -->
        <div v-if="status === 'cancel'" class="mb-8 p-4 bg-red-100 border border-red-400 text-red-700 rounded-md shadow-md">
          <h2 class="text-xl font-semibold">
             {{ translations.LabelsByLanguageCode[getSingleLanguageCode]?.subscriptionCancelledTitle || 'Subscription Canceled' }}
          </h2>
          <p>
            {{ translations.LabelsByLanguageCode[getSingleLanguageCode]?.subscriptionCancelledMessage || 'Your subscription process was canceled. You can choose a plan below or return to the dashboard.' }}
          </p>
           <Link :href="route('map.index')" class="mt-4 inline-block px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600">
            {{ translations.LabelsByLanguageCode[getSingleLanguageCode]?.goToDashboard || 'Go to Dashboard' }}
          </Link>
        </div>
  
        <div class="grid md:grid-cols-2 gap-8">
          <!-- Basic Plan -->
          <div class="border p-6 rounded-lg shadow-lg flex flex-col bg-white" :class="{'ring-2 ring-blue-500': currentPlan === 'basic'}">
            <h2 class="text-2xl font-semibold text-gray-700">
              {{ translations.LabelsByLanguageCode[getSingleLanguageCode]?.basicPlanTitle || 'Resident Awareness' }}
            </h2>
            <p class="text-3xl font-bold my-4 text-blue-600">$5 <span class="text-sm font-normal text-gray-500">/month</span></p>
            <p class="text-gray-600 mb-6">
              {{ translations.LabelsByLanguageCode[getSingleLanguageCode]?.basicPlanDescription || 'Stay informed about what\'s happening in your neighborhood.' }}
            </p>
            <ul class="space-y-2 text-gray-600 mb-6 flex-grow">
              <li v-for="feature in basicFeatures" :key="feature.id" class="flex items-center">
                <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                {{ feature.text }}
              </li>
            </ul>
            <div class="mt-auto">
              <button
                v-if="isAuthenticated && currentPlan !== 'basic'"
                @click="goToRoute(route('subscribe.checkout', { plan: 'basic' }))"
                class="w-full px-6 py-3 text-white bg-blue-500 rounded-md shadow-lg hover:bg-blue-600 transition-colors">
                {{ translations.LabelsByLanguageCode[getSingleLanguageCode]?.subscribeButton || 'Subscribe' }}
              </button>
              <div v-else-if="!isAuthenticated" class="flex flex-col space-y-2 items-center">
                 <a :href="route('socialite.redirect', 'google') + '?redirect_to=' + route('subscribe.checkout', { plan: 'basic' })"
                   class="flex items-center justify-center w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                   <img class="h-5 w-5 mr-2" src="https://upload.wikimedia.org/wikipedia/commons/c/c1/Google_%22G%22_logo.svg" alt="Google logo">
                   {{ translations.LabelsByLanguageCode[getSingleLanguageCode]?.registerWithGoogleToSubscribeButton || 'Login with Google to Subscribe' }}
                 </a>
                 <Link :href="route('register') + '?redirect_to=' + route('subscribe.checkout', { plan: 'basic' })" class="text-sm text-blue-600 hover:underline">
                   {{ translations.LabelsByLanguageCode[getSingleLanguageCode]?.registerManuallyToSubscribeLink || 'Or register manually to subscribe' }}
                 </Link>
              </div>
              <div v-else-if="currentPlan === 'basic'" class="w-full px-6 py-3 text-center text-blue-600 font-semibold bg-blue-100 rounded-md">
                {{ translations.LabelsByLanguageCode[getSingleLanguageCode]?.currentPlanButton || 'Current Plan' }}
              </div>
            </div>
          </div>
  
          <!-- Pro Plan -->
          <div class="border p-6 rounded-lg shadow-lg flex flex-col bg-white relative" :class="{'ring-2 ring-purple-500': currentPlan === 'pro'}">
            <div class="absolute top-0 right-0 bg-purple-500 text-white text-xs font-semibold px-3 py-1 rounded-bl-lg rounded-tr-lg">
               {{ translations.LabelsByLanguageCode[getSingleLanguageCode]?.bestValueBadge || 'Best Value' }}
            </div>
            <h2 class="text-2xl font-semibold text-gray-700">
              {{ translations.LabelsByLanguageCode[getSingleLanguageCode]?.proPlanTitle || 'Pro Insights' }}
            </h2>
            <p class="text-3xl font-bold my-4 text-purple-600">$15 <span class="text-sm font-normal text-gray-500">/month</span></p>
            <p class="text-gray-600 mb-6">
              {{ translations.LabelsByLanguageCode[getSingleLanguageCode]?.proPlanDescription || 'Unlock deeper insights and advanced tools for power users.' }}
            </p>
            <ul class="space-y-2 text-gray-600 mb-6 flex-grow">
               <li v-for="feature in proFeatures" :key="feature.id" class="flex items-center">
                <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                {{ feature.text }}
              </li>
            </ul>
            <div class="mt-auto">
              <button
                v-if="isAuthenticated && currentPlan !== 'pro'"
                @click="goToRoute(route('subscribe.checkout', { plan: 'pro' }))"
                class="w-full px-6 py-3 text-white bg-purple-500 rounded-md shadow-lg hover:bg-purple-600 transition-colors">
                {{ translations.LabelsByLanguageCode[getSingleLanguageCode]?.subscribeButton || 'Subscribe' }}
              </button>
              <div v-else-if="!isAuthenticated" class="flex flex-col space-y-2 items-center">
                 <a :href="route('socialite.redirect', 'google') + '?redirect_to=' + route('subscribe.checkout', { plan: 'pro' })"
                   class="flex items-center justify-center w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                   <img class="h-5 w-5 mr-2" src="https://upload.wikimedia.org/wikipedia/commons/c/c1/Google_%22G%22_logo.svg" alt="Google logo">
                   {{ translations.LabelsByLanguageCode[getSingleLanguageCode]?.registerWithGoogleToSubscribeButton || 'Login with Google to Subscribe' }}
                 </a>
                 <Link :href="route('register') + '?redirect_to=' + route('subscribe.checkout', { plan: 'pro' })" class="text-sm text-purple-600 hover:underline">
                   {{ translations.LabelsByLanguageCode[getSingleLanguageCode]?.registerManuallyToSubscribeLink || 'Or register manually to subscribe' }}
                 </Link>
              </div>
              <div v-else-if="currentPlan === 'pro'" class="w-full px-6 py-3 text-center text-purple-600 font-semibold bg-purple-100 rounded-md">
                {{ translations.LabelsByLanguageCode[getSingleLanguageCode]?.currentPlanButton || 'Current Plan' }}
              </div>
            </div>
          </div>
        </div>
  
        <div class="text-center mt-12" v-if="isAuthenticated && currentPlan">
          <p class="text-gray-600 mb-2">
            {{ translations.LabelsByLanguageCode[getSingleLanguageCode]?.manageSubscriptionPrompt || 'Need to manage your subscription or payment details?' }}
          </p>
          <button @click="goToRoute(route('billing'))" class="px-6 py-3 text-white bg-gray-700 rounded-md shadow-lg hover:bg-gray-800 transition-colors">
            {{ translations.LabelsByLanguageCode[getSingleLanguageCode]?.goToBillingPortalButton || 'Go to Billing Portal' }}
          </button>
        </div>
      </div>
    </PageTemplate>
  </template>
  
  <script setup>
  import PageTemplate from '@/Components/PageTemplate.vue';
  import { Head, Link, usePage } from '@inertiajs/vue3';
  import { computed, inject, ref } from 'vue';
  
  const props = defineProps({
    status: String, // 'success', 'cancel', or null
    sessionId: String,
    currentPlan: String, // 'basic', 'pro', or null
    isAuthenticated: Boolean,
  });
  
  const translations = inject('translations');
  const language_codes = ref(['en-US']); // Assuming this might come from user settings or a global store later
  
  const getSingleLanguageCode = computed(() => {
    // This is a simplified version. In a real app, you'd likely get this from a store or user preferences.
    // For now, if translations exist for en-US, use it. Otherwise, fallback.
    return (translations.LabelsByLanguageCode && translations.LabelsByLanguageCode[language_codes.value[0]]) ? language_codes.value[0] : 'en-US';
  });
  
  const goToRoute = (targetRoute) => {
    // No need to check isAuthenticated here anymore for the primary button click,
    // as the template logic now separates authenticated and unauthenticated actions.
    // However, if called from other places, the check might still be relevant.
    // For direct checkout, it's assumed user is authenticated by this point.
    window.location.href = targetRoute;
  };
  
  // Define features for each plan - these should be translatable
  const basicFeatures = computed(() => [
    { id:1, text: translations.FeatureTranslations[getSingleLanguageCode.value]?.radialMapAccess || 'Radial Map Access' },
    { id:2, text: translations.FeatureTranslations[getSingleLanguageCode.value]?.saveOneLocation || 'Save 1 Favorite Location' },
    { id:3, text: translations.FeatureTranslations[getSingleLanguageCode.value]?.dailyWeeklyReportsOne || 'Daily/Weekly Reports (1 Location)' },
    { id:4, text: translations.FeatureTranslations[getSingleLanguageCode.value]?.basicAIAssistant || 'Basic AI Assistant' },
  ]);
  
  const proFeatures = computed(() => [
    { id:1, text: translations.FeatureTranslations[getSingleLanguageCode.value]?.allBasicFeatures || 'All Basic Plan Features' },
    { id:2, text: translations.FeatureTranslations[getSingleLanguageCode.value]?.crime311Maps || 'Interactive Crime & 311 Maps' },
    { id:3, text: translations.FeatureTranslations[getSingleLanguageCode.value]?.saveFiveLocations || 'Save 5 Favorite Locations' },
    { id:4, text: translations.FeatureTranslations[getSingleLanguageCode.value]?.reportsAllLocations || 'Reports for All Saved Locations' },
    { id:5, text: translations.FeatureTranslations[getSingleLanguageCode.value]?.advancedAIAssistant || 'Advanced AI Assistant' },
    { id:6, text: translations.FeatureTranslations[getSingleLanguageCode.value]?.csvExport || 'CSV Data Export' },
  ]);
  
  // Example: Add these to your main translations provider (e.g., app.js or a dedicated translations file)
  
  translations.LabelsByLanguageCode['en-US'] = {
    ...translations.LabelsByLanguageCode['en-US'], // keep existing
    subscriptionPageTitle: 'Choose Your Plan',
    subscriptionSuccessTitle: 'Subscription Successful!',
    subscriptionSuccessMessage: 'Thank you for subscribing! Your access has been updated. You can manage your subscription anytime from the billing portal.',
    sessionId: 'Session ID',
    goToDashboard: 'Go to Dashboard',
    subscriptionCancelledTitle: 'Subscription Canceled',
    subscriptionCancelledMessage: 'Your subscription process was canceled. You can choose a plan below or return to the dashboard.',
    basicPlanTitle: 'Resident Awareness',
    basicPlanDescription: 'Stay informed about what\'s happening in your neighborhood.',
    proPlanTitle: 'Pro Insights',
    proPlanDescription: 'Unlock deeper insights and advanced tools for power users.',
    bestValueBadge: 'Best Value',
    subscribeButton: 'Subscribe',
    currentPlanButton: 'Current Plan',
    manageSubscriptionPrompt: 'Need to manage your subscription or payment details?',
    goToBillingPortalButton: 'Go to Billing Portal',
    registerWithGoogleToSubscribeButton: 'Login with Google to Subscribe',
    registerManuallyToSubscribeLink: 'Or register manually to subscribe',
  };
  
  translations.FeatureTranslations = {
    'en-US': {
      radialMapAccess: 'Radial Map Access',
      saveOneLocation: 'Save 1 Favorite Location',
      dailyWeeklyReportsOne: 'Daily/Weekly Reports (1 Location)',
      basicAIAssistant: 'Basic AI Assistant',
      allBasicFeatures: 'All Basic Plan Features',
      crime311Maps: 'Interactive Crime & 311 Maps',
      saveFiveLocations: 'Save 5 Favorite Locations',
      reportsAllLocations: 'Reports for All Saved Locations',
      advancedAIAssistant: 'Advanced AI Assistant',
      csvExport: 'CSV Data Export',
    },
    'es-MX': { // Example for Spanish
      radialMapAccess: 'Acceso al Mapa Radial',
      // ... other features
    }
  };
  
  
  </script>
  
  <style scoped>
  /* Add any specific styles if needed, Tailwind should cover most */
  </style>