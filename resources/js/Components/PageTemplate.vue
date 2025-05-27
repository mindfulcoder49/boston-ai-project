<template>
  <div class="flex flex-col min-h-screen">
    <nav class="bg-white border-b border-gray-100 w-full">
        <!-- Primary Navigation Menu -->
        <div class="w-auto m-5">
            <div class="flex justify-between h-24 sm:h-16">
                <div class="flex">
                    <!-- Logo -->
                    <div class="shrink-0 flex items-center">
                        <Link :href="route('map.index')">
                            <ApplicationLogo class="block h-16 w-auto fill-current text-gray-800" />
                        </Link>
                    </div>

                    <!-- Navigation Links -->
                    <div class="hidden space-x-8 sm:-my-px sm:ml-10 sm:flex">
                        <NavLink :href="route('map.index')" :active="route().current('map.index')">
                            Home
                        </NavLink>
                        <NavLink :href="route('data-map.combined')" :active="route().current('data-map.combined')">
                            Full Data Map
                        </NavLink>
                        <NavLink v-if="isAuthenticated" :href="route('reports.index')" :active="route().current('reports.index') || route().current('reports.show')">
                            Report History
                        </NavLink>
                        <NavLink :href="route('subscription.index')" :active="route().current('subscription.index')">
                            Subscription
                        </NavLink>
                        <NavLink :href="route('data.metrics')" :active="route().current('data.metrics')">
                            Data Metrics
                        </NavLink>
                    </div>
                </div>

                <div class="hidden md:flex md:items-center md:ml-6">
                    <!-- Subscription Status -->
                    <div class="mr-4">
                        <span class="text-sm font-medium px-2.5 py-0.5 rounded-full"
                              :class="{
                                  'bg-gray-100 text-gray-800': currentPlanName === 'Guest',
                                  'bg-blue-100 text-blue-800': currentPlanName === 'Registered User',
                                  'bg-green-100 text-green-800': currentPlanName === 'Resident Awareness',
                                  'bg-purple-100 text-purple-800': currentPlanName === 'Pro Insights',
                                  'bg-yellow-100 text-yellow-800': !['Guest', 'Registered User', 'Resident Awareness', 'Pro Insights'].includes(currentPlanName) && isAuthenticated
                              }">
                            {{ currentPlanName }}
                        </span>
                    </div>

                    <!-- Authenticated User Dropdown -->
                    <div v-if="isAuthenticated" class="ml-3 relative">
                        <Dropdown align="right" width="48">
                            <template #trigger>
                                <span class="inline-flex rounded-md">
                                    <button
                                        type="button"
                                        class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150"
                                    >
                                        <img v-if="avatarUrl" :src="avatarUrl" alt="User Avatar" class="h-8 w-8 rounded-full mr-2 -ml-1">
                                        <span v-else-if="userName" class="inline-flex items-center justify-center h-8 w-8 rounded-full bg-gray-200 text-gray-600 text-xs font-semibold mr-2 -ml-1">
                                            {{ userName.substring(0, 2).toUpperCase() }}
                                        </span>
                                        {{ userName }}
                                        <svg class="ml-2 -mr-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </span>
                            </template>
                            <template #content>
                                <DropdownLink :href="route('profile.edit')"> Profile </DropdownLink>
                                <DropdownLink @click="logoutUser"> Log Out </DropdownLink>
                            </template>
                        </Dropdown>
                    </div>
                    <!-- Guest User Links -->
                    <div v-else class="flex items-center ml-3 relative">
                        <a :href="route('socialite.redirect', 'google') + '?redirect_to=' + route('map.index')"
                           class="p-2 mr-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-full focus:outline-none focus:bg-gray-100"
                           title="Login or Register with Google">
                            <img class="h-5 w-5" src="https://upload.wikimedia.org/wikipedia/commons/c/c1/Google_%22G%22_logo.svg" alt="Google logo">
                        </a>
                        <Dropdown align="right" width="48">
                            <template #trigger>
                                <span class="inline-flex rounded-md">
                                    <button
                                        type="button"
                                        class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150"
                                    >
                                        Login/Register
                                        <svg class="ml-2 -mr-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </span>
                            </template>
                            <template #content>
                                <DropdownLink :href="route('login')"> Login </DropdownLink>
                                <DropdownLink :href="route('register')"> Register </DropdownLink>
                            </template>
                        </Dropdown>
                    </div>
                </div>

                <!-- Hamburger -->
                <div class="-mr-2 flex items-center md:hidden">
                    <button @click="showingNavigationDropdown = !showingNavigationDropdown" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                        <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                            <path :class="{ hidden: showingNavigationDropdown, 'inline-flex': !showingNavigationDropdown }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            <path :class="{ hidden: !showingNavigationDropdown, 'inline-flex': showingNavigationDropdown }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Responsive Navigation Menu -->
        <div :class="{ block: showingNavigationDropdown, hidden: !showingNavigationDropdown }" class="md:hidden">
            <div class="pt-2 pb-3 space-y-1">
                <ResponsiveNavLink :href="route('map.index')" :active="route().current('map.index')"> Home </ResponsiveNavLink>
                <ResponsiveNavLink :href="route('data-map.combined')" :active="route().current('data-map.combined')"> Full Data Map </ResponsiveNavLink>
                <ResponsiveNavLink v-if="isAuthenticated" :href="route('reports.index')" :active="route().current('reports.index') || route().current('reports.show')"> Report History </ResponsiveNavLink>
                <ResponsiveNavLink :href="route('subscription.index')" :active="route().current('subscription.index')"> Subscription </ResponsiveNavLink>
                <ResponsiveNavLink :href="route('data.metrics')" :active="route().current('data.metrics')"> Data Metrics </ResponsiveNavLink>
            </div>

            <!-- Responsive Settings Options -->
            <div class="pt-4 pb-1 border-t border-gray-200">
                <div class="px-4">
                    <div class="font-medium text-base text-gray-800">
                        <span class="text-xs font-medium px-2 py-0.5 rounded-full mr-2"
                              :class="{
                                  'bg-gray-100 text-gray-800': currentPlanName === 'Guest',
                                  'bg-blue-100 text-blue-800': currentPlanName === 'Registered User',
                                  'bg-green-100 text-green-800': currentPlanName === 'Resident Awareness',
                                  'bg-purple-100 text-purple-800': currentPlanName === 'Pro Insights',
                                  'bg-yellow-100 text-yellow-800': !['Guest', 'Registered User', 'Resident Awareness', 'Pro Insights'].includes(currentPlanName) && isAuthenticated
                              }">
                            {{ currentPlanName }}
                        </span>
                    </div>
                    <div v-if="isAuthenticated" class="mt-1">
                        <div class="font-medium text-base text-gray-800 flex items-center">
                            <img v-if="avatarUrl" :src="avatarUrl" alt="User Avatar" class="h-8 w-8 rounded-full mr-2">
                             <span v-else-if="userName" class="inline-flex items-center justify-center h-8 w-8 rounded-full bg-gray-200 text-gray-600 text-xs font-semibold mr-2">
                                {{ userName.substring(0, 2).toUpperCase() }}
                            </span>
                            {{ userName }}
                        </div>
                        <div v-if="userEmail" class="font-medium text-sm text-gray-500">{{ userEmail }}</div>
                    </div>
                </div>


                <div class="mt-3 space-y-1">
                    <template v-if="isAuthenticated">
                        <ResponsiveNavLink :href="route('profile.edit')"> Profile </ResponsiveNavLink>
                        <ResponsiveNavLink @click="logoutUser" as="button"> Log Out </ResponsiveNavLink>
                    </template>
                    <template v-else>
                        <ResponsiveNavLink :href="route('login')" :active="route().current('login')"> Login </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('register')" :active="route().current('register')"> Register </ResponsiveNavLink>
                         <ResponsiveNavLink :href="route('socialite.redirect', 'google')">
                            <img class="h-4 w-4 inline mr-1" src="https://upload.wikimedia.org/wikipedia/commons/c/c1/Google_%22G%22_logo.svg" alt="Google logo">
                            Login with Google
                        </ResponsiveNavLink>
                    </template>
                </div>
            </div>
            
            <!-- Footer Links for Responsive -->
            <div class="pt-4 pb-1 border-t border-gray-200">
                 <div class="mt-3 space-y-1">
                    <ResponsiveNavLink :href="route('about.us')" :active="route().current('about.us')"> About Us </ResponsiveNavLink>
                    <ResponsiveNavLink :href="route('help.contact')" :active="route().current('help.contact')"> Help/Contact </ResponsiveNavLink>
                    <ResponsiveNavLink :href="route('privacy.policy')" :active="route().current('privacy.policy')"> Privacy Policy </ResponsiveNavLink>
                    <ResponsiveNavLink :href="route('terms.of.use')" :active="route().current('terms.of.use')"> Terms of Use </ResponsiveNavLink>
                 </div>
            </div>
        </div>
    </nav> <!-- End of nav -->

    <main class="flex-grow container mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <DataVisibilityBanner /> 
      <slot></slot>
    </main>

    <Footer /> <!-- Add the new Footer component here -->

  </div> <!-- End of root flex container -->
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, getCurrentInstance } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { Link, router, usePage } from '@inertiajs/vue3'; 
import axios from 'axios';
import Footer from '@/Components/Footer.vue'; 
import DataVisibilityBanner from '@/Components/DataVisibilityBanner.vue'; 
import * as VueGtagModule from 'vue-gtag'; // Import entire module as namespace

let gtagClickEvent = () => {}; // No-op function

if (import.meta.env.VITE_GA_ID) {
    try {
        if (VueGtagModule && typeof VueGtagModule.event === 'function') {
            gtagClickEvent = VueGtagModule.event;
        } else {
            console.warn('VueGtagModule.event is not a function. Trying fallback for click tracking.');
            const instance = getCurrentInstance();
            if (instance && instance.appContext.config.globalProperties.$gtag && typeof instance.appContext.config.globalProperties.$gtag.event === 'function') {
                console.warn('Falling back to global $gtag.event for click tracking.');
                gtagClickEvent = instance.appContext.config.globalProperties.$gtag.event;
            } else {
                console.error('Failed to obtain gtag event function for click tracking.');
            }
        }
    } catch (e) {
        console.error('Error accessing VueGtagModule.event:', e, 'Click tracking might not work.');
        const instance = getCurrentInstance();
        if (instance && instance.appContext.config.globalProperties.$gtag && typeof instance.appContext.config.globalProperties.$gtag.event === 'function') {
            console.warn('Falling back to global $gtag.event due to error.');
            gtagClickEvent = instance.appContext.config.globalProperties.$gtag.event;
        } else {
             console.error('Fallback to $gtag.event also failed for click tracking.');
        }
    }
}

const $page = usePage();

// ... existing computed properties (isAuthenticated, userName, etc.) ...
const isAuthenticated = computed(() => !!$page.props.auth?.user);
const userName = computed(() => $page.props.auth?.user?.name || '');
const userEmail = computed(() => $page.props.auth?.user?.email || '');
const avatarUrl = computed(() => $page.props.auth?.user?.avatar_url || null);
const currentPlanName = computed(() => $page.props.auth?.currentPlan?.name || 'Guest');

const showingNavigationDropdown = ref(false);

async function logoutUser() {
  try {
      await axios.post(route('logout'));
  } catch (error) {
      console.error("Logout failed:", error);
      // Handle logout error, e.g., show a notification
  } finally {
      window.location = '/'; // Or router.visit('/', { replace: true })
  }
}

const handleGlobalClick = (e) => {
  if (import.meta.env.VITE_GA_ID && e.target && typeof gtagClickEvent === 'function' && gtagClickEvent !== (() => {})) { // Ensure it's not the no-op
    let eventLabel = e.target.innerText || e.target.ariaLabel || e.target.alt || e.target.id || e.target.tagName;
    if (eventLabel && eventLabel.length > 100) { // GA label limit
        eventLabel = eventLabel.substring(0, 97) + '...';
    }
    gtagClickEvent('click', { // Use the event function from useGtag
      event_category: 'interaction',
      event_label: eventLabel || 'unlabeled_element',
      element_classes: e.target.className || '',
      element_id: e.target.id || '',
      element_tag_name: e.target.tagName || '',
    });
  }
};

onMounted(() => {
  if (import.meta.env.VITE_GA_ID) {
    document.addEventListener('click', handleGlobalClick);
    // Initial page_view is handled by vue-gtag config and router.on('finish') in app.js
  }
});

onBeforeUnmount(() => {
  if (import.meta.env.VITE_GA_ID) {
    document.removeEventListener('click', handleGlobalClick);
  }
});
</script>
