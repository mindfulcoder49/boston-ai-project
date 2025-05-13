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
                        <NavLink :href="route('crime-map')" :active="route().current('crime-map')">
                            Boston Crime Map
                        </NavLink>
                        <NavLink :href="route('cases.indexnofilter')" :active="route().current('cases.indexnofilter') || route().current('scatter')">
                            Boston 311 Case Map
                        </NavLink>
                        <NavLink :href="route('subscription.index')" :active="route().current('subscription.index')">
                            Subscription
                        </NavLink>
                    </div>
                </div>

                <div class="hidden sm:flex sm:items-center sm:ml-6">
                    <!-- Authenticated User Dropdown -->
                    <div v-if="isAuthenticated" class="ml-3 relative">
                        <Dropdown align="right" width="48">
                            <template #trigger>
                                <span class="inline-flex rounded-md">
                                    <button
                                        type="button"
                                        class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150"
                                    >
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
                    <div v-else class="ml-3 relative">
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
                <div class="-mr-2 flex items-center sm:hidden">
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
        <div :class="{ block: showingNavigationDropdown, hidden: !showingNavigationDropdown }" class="sm:hidden">
            <div class="pt-2 pb-3 space-y-1">
                <ResponsiveNavLink :href="route('map.index')" :active="route().current('map.index')"> Home </ResponsiveNavLink>
                <ResponsiveNavLink :href="route('crime-map')" :active="route().current('crime-map')"> Boston Crime Map </ResponsiveNavLink>
                <ResponsiveNavLink :href="route('cases.indexnofilter')" :active="route().current('cases.indexnofilter') || route().current('scatter')"> Boston 311 Case Map </ResponsiveNavLink>
                <ResponsiveNavLink :href="route('subscription.index')" :active="route().current('subscription.index')"> Subscription </ResponsiveNavLink>
            </div>

            <!-- Responsive Settings Options -->
            <div class="pt-4 pb-1 border-t border-gray-200">
                <div v-if="isAuthenticated" class="px-4">
                    <div class="font-medium text-base text-gray-800">{{ userName }}</div>
                    <div v-if="userEmail" class="font-medium text-sm text-gray-500">{{ userEmail }}</div>
                </div>

                <div class="mt-3 space-y-1">
                    <template v-if="isAuthenticated">
                        <ResponsiveNavLink :href="route('profile.edit')"> Profile </ResponsiveNavLink>
                        <ResponsiveNavLink @click="logoutUser" as="button"> Log Out </ResponsiveNavLink>
                    </template>
                    <template v-else>
                        <ResponsiveNavLink :href="route('login')" :active="route().current('login')"> Login </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('register')" :active="route().current('register')"> Register </ResponsiveNavLink>
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

    <main class="flex-grow">
      <slot></slot>
    </main>

    <Footer /> <!-- Add the new Footer component here -->

  </div> <!-- End of root flex container -->
</template>

<script setup>
import { ref } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { Link, router, usePage } from '@inertiajs/vue3'; 
import axios from 'axios';
import Footer from '@/Components/Footer.vue'; // Import the new Footer component

const $page = usePage();

const isAuthenticated = $page.props.auth.user !== null;
const userName = $page.props.auth.user ? $page.props.auth.user.name : '';
const userEmail = $page.props.auth.user ? $page.props.auth.user.email : '';

const showingNavigationDropdown = ref(false);

async function logoutUser() {
  try {
      await axios.post(route('logout'));
      window.location = '/'; // Or router.visit('/', { replace: true })
  } catch (error) {
      console.error("Logout failed:", error);
      // Handle logout error, e.g., show a notification
  }
}
</script>
