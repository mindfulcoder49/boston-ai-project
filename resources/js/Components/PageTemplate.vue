<template>
  <div class="flex min-h-screen flex-col bg-slate-50">
    <nav class="sticky top-0 z-40 border-b border-slate-200/80 bg-white/90 backdrop-blur">
      <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between py-3">
          <div class="flex items-center gap-4 lg:gap-8">
            <Link :href="route('home')" class="shrink-0">
              <ApplicationLogo class="block h-10 w-auto fill-current text-slate-900 sm:h-11" />
            </Link>

            <div class="hidden items-center gap-5 md:flex">
              <template v-for="item in navigation.primary" :key="item.label">
                <Dropdown
                  v-if="item.kind === 'dropdown'"
                  align="left"
                  width="48"
                  content-classes="py-2 bg-white"
                >
                  <template #trigger>
                    <button
                      type="button"
                      class="inline-flex items-center gap-1 border-b-2 px-1 py-2 text-sm font-medium transition"
                      :class="navTriggerClasses(isNavItemActive(item))"
                    >
                      {{ item.label }}
                      <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m19 9-7 7-7-7" />
                      </svg>
                    </button>
                  </template>

                  <template #content>
                    <div class="w-64 p-2">
                      <Link
                        v-for="link in item.items"
                        :key="`${item.label}-${link.label}`"
                        :href="link.href"
                        class="block rounded-xl px-3 py-2 text-sm text-slate-600 transition hover:bg-slate-50 hover:text-slate-900"
                      >
                        {{ link.label }}
                      </Link>
                    </div>
                  </template>
                </Dropdown>

                <NavLink
                  v-else
                  :href="item.href"
                  :active="isNavItemActive(item)"
                >
                  {{ item.label }}
                </NavLink>
              </template>
            </div>
          </div>

          <div class="hidden items-center gap-3 md:flex">
            <span
              class="hidden rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] lg:inline-flex"
              :class="planBadgeClasses"
            >
              {{ currentPlanName }}
            </span>

            <template v-if="isAuthenticated">
              <Link
                :href="route('locations.index')"
                class="inline-flex items-center rounded-2xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
              >
                Saved locations
              </Link>

              <Dropdown align="right" width="48">
                <template #trigger>
                  <span class="inline-flex rounded-md">
                    <button
                      type="button"
                      class="inline-flex items-center rounded-2xl border border-transparent bg-white px-3 py-2 text-sm font-medium text-slate-600 transition hover:text-slate-900 focus:outline-none"
                    >
                      <img v-if="avatarUrl" :src="avatarUrl" alt="User Avatar" class="mr-2 h-8 w-8 rounded-full">
                      <span
                        v-else-if="userName"
                        class="mr-2 inline-flex h-8 w-8 items-center justify-center rounded-full bg-slate-200 text-xs font-semibold text-slate-700"
                      >
                        {{ userName.substring(0, 2).toUpperCase() }}
                      </span>
                      {{ userName }}
                      <svg class="ml-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                      </svg>
                    </button>
                  </span>
                </template>

                <template #content>
                  <DropdownLink :href="route('reports.index')">Report history</DropdownLink>
                  <DropdownLink :href="route('billing')">Billing</DropdownLink>
                  <DropdownLink :href="route('profile.edit')">Profile</DropdownLink>
                  <button
                    type="button"
                    class="block w-full px-4 py-2 text-left text-sm leading-5 text-gray-700 transition duration-150 ease-in-out hover:bg-gray-100 focus:bg-gray-100 focus:outline-none"
                    @click="logoutUser"
                  >
                    Log Out
                  </button>
                </template>
              </Dropdown>
            </template>

            <template v-else>
              <Link
                :href="route('crime-address.index')"
                class="inline-flex items-center rounded-2xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-700"
              >
                Start free preview
              </Link>
              <Link
                :href="route('login')"
                class="inline-flex items-center rounded-2xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
              >
                Log in
              </Link>
            </template>
          </div>

          <div class="flex items-center md:hidden">
            <button
              type="button"
              :aria-label="showingNavigationDropdown ? 'Close navigation menu' : 'Open navigation menu'"
              class="inline-flex items-center justify-center rounded-xl p-2 text-slate-500 transition hover:bg-slate-100 hover:text-slate-700"
              @click="showingNavigationDropdown = !showingNavigationDropdown"
            >
              <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                <path :class="{ hidden: showingNavigationDropdown, 'inline-flex': !showingNavigationDropdown }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                <path :class="{ hidden: !showingNavigationDropdown, 'inline-flex': showingNavigationDropdown }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>
      </div>

      <div
        v-if="showingNavigationDropdown"
        data-testid="mobile-nav-panel"
        class="max-h-[calc(100vh-4.5rem)] overflow-y-auto border-t border-slate-200 bg-white overscroll-contain md:hidden"
      >
        <div class="space-y-1 px-4 py-4">
          <template v-for="item in navigation.primary" :key="`mobile-${item.label}`">
            <ResponsiveNavLink
              v-if="!item.kind"
              :href="item.href"
              :active="isNavItemActive(item)"
            >
              {{ item.label }}
            </ResponsiveNavLink>

            <div v-else class="rounded-2xl border border-slate-200 px-3 py-3">
              <p class="px-1 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">{{ item.label }}</p>
              <div class="mt-2 space-y-1">
                <ResponsiveNavLink
                  v-for="link in item.items"
                  :key="`mobile-${item.label}-${link.label}`"
                  :href="link.href"
                  :active="false"
                >
                  {{ link.label }}
                </ResponsiveNavLink>
              </div>
            </div>
          </template>
        </div>

        <div class="border-t border-slate-200 px-4 py-4">
          <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Account</p>
          <div class="mt-3 space-y-1">
            <template v-if="isAuthenticated">
              <ResponsiveNavLink :href="route('locations.index')" :active="route().current('locations.*')">Saved Locations</ResponsiveNavLink>
              <ResponsiveNavLink :href="route('saved-maps.index')" :active="route().current('saved-maps.index')">Saved Maps</ResponsiveNavLink>
              <ResponsiveNavLink :href="route('reports.index')" :active="route().current('reports.index') || route().current('reports.show')">Report History</ResponsiveNavLink>
              <ResponsiveNavLink :href="route('billing')" :active="route().current('billing')">Billing</ResponsiveNavLink>
              <ResponsiveNavLink :href="route('profile.edit')" :active="route().current('profile.edit')">Profile</ResponsiveNavLink>
              <button
                type="button"
                class="block w-full rounded-xl px-3 py-2 text-left text-base font-medium text-gray-600 transition hover:bg-gray-50 hover:text-gray-800"
                @click="logoutUser"
              >
                Log Out
              </button>
            </template>
            <template v-else>
              <ResponsiveNavLink :href="route('crime-address.index')" :active="route().current('crime-address.index')">Start Free Preview</ResponsiveNavLink>
              <ResponsiveNavLink :href="route('login')" :active="route().current('login')">Log In</ResponsiveNavLink>
              <ResponsiveNavLink :href="route('register')" :active="route().current('register')">Create Account</ResponsiveNavLink>
            </template>
          </div>
        </div>
      </div>
    </nav>

    <main class="mx-auto w-full max-w-7xl flex-grow px-4 py-8 sm:px-6 lg:px-8">
      <slot></slot>
    </main>

    <Footer />
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import Footer from '@/Components/Footer.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { buildPublicNavigation } from '@/Utils/publicNavigation';
import { trackAnalyticsEvent, trackPageView } from '@/Utils/analytics';

const page = usePage();

const isAuthenticated = computed(() => Boolean(page.props.auth?.user));
const userName = computed(() => page.props.auth?.user?.name || '');
const avatarUrl = computed(() => page.props.auth?.user?.avatar_url || null);
const currentPlanName = computed(() => page.props.auth?.currentPlan?.name || 'Guest');
const navigation = computed(() => buildPublicNavigation(route, isAuthenticated.value));
const showingNavigationDropdown = ref(false);

const planBadgeClasses = computed(() => ({
  'bg-slate-100 text-slate-700': currentPlanName.value === 'Guest',
  'bg-blue-100 text-blue-800': currentPlanName.value === 'Registered User',
  'bg-emerald-100 text-emerald-800': currentPlanName.value === 'Resident Awareness',
  'bg-violet-100 text-violet-800': currentPlanName.value === 'Pro Insights',
}));

function navTriggerClasses(active) {
  return active
    ? 'border-indigo-400 text-slate-900'
    : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700';
}

function isNavItemActive(item) {
  return (item.routeNames ?? []).some((name) => route().current(name));
}

async function logoutUser() {
  router.post(route('logout'), {}, {
    onSuccess: () => {
      trackAnalyticsEvent('logout', {
        pageType: 'account',
        isAuthenticated: true,
        params: {
          method: 'inertia',
        },
      });
    },
  });
}

onMounted(() => {
  trackPageView({
    isAuthenticated: isAuthenticated.value,
    params: {
      page_location: window.location.href,
      page_path: window.location.pathname,
    },
  });
});

watch(() => page.url, (newUrl, oldUrl) => {
  showingNavigationDropdown.value = false;

  if (newUrl !== oldUrl) {
    trackPageView({
      isAuthenticated: isAuthenticated.value,
      params: {
        page_location: window.location.origin + newUrl,
        page_path: newUrl,
      },
    });
  }
});
</script>
