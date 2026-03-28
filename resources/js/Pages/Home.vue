<template>
  <PageTemplate>
    <Head>
      <title>PublicDataWatch | Know What Crime Is Happening Around Your Address</title>
      <meta
        name="description"
        content="Search an address to see recent nearby crime, readable local context, neighborhood score framing, and the daily-report workflow. Then explore the full map, trends, and scoring tools across supported regions."
      />
    </Head>

    <article class="-mx-4 sm:-mx-6 lg:-mx-8">
      <section class="relative overflow-hidden px-4 py-20 sm:px-6 lg:px-8 lg:py-24">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(34,197,94,0.22),_transparent_30%),radial-gradient(circle_at_bottom_right,_rgba(59,130,246,0.28),_transparent_36%),linear-gradient(135deg,_#020617,_#0f172a_45%,_#082f49)]"></div>
        <div class="absolute inset-0 opacity-[0.06]" style="background-image: linear-gradient(rgba(255,255,255,0.7) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.7) 1px, transparent 1px); background-size: 36px 36px;"></div>

        <div class="relative mx-auto grid max-w-7xl gap-14 lg:grid-cols-[1.15fr_0.85fr] lg:items-center">
          <div>
            <p class="text-sm font-semibold uppercase tracking-[0.28em] text-cyan-300">Address-First Civic Data</p>
            <h1 class="mt-6 max-w-4xl text-4xl font-black tracking-tight text-white sm:text-5xl lg:text-6xl">
              Know what crime is happening around your address before you commit to a place.
            </h1>
            <p class="mt-6 max-w-2xl text-lg leading-8 text-slate-300">
              PublicDataWatch starts with the simple question regular people actually have: what is happening near this address right now? Then it expands into trends, scoring, and full-map workflows for people who need deeper context.
            </p>

            <div class="mt-10 max-w-3xl rounded-[30px] border border-white/10 bg-white/10 p-4 shadow-2xl shadow-slate-950/40 backdrop-blur-xl">
              <p class="mb-3 text-sm font-medium text-cyan-100">Try the crime preview first</p>
              <div class="grid gap-3 lg:grid-cols-[1fr_auto]">
                <div class="hero-search-wrapper rounded-2xl p-2">
                  <GoogleAddressSearch
                    @address-selected="handleAddressSelected"
                    :language_codes="['en-US']"
                    placeholder_text="Enter your address..."
                  />
                </div>
                <button
                  type="button"
                  class="inline-flex items-center justify-center gap-2 rounded-2xl bg-cyan-400 px-5 py-3 text-sm font-semibold text-slate-950 transition hover:bg-cyan-300 disabled:opacity-60"
                  :disabled="geoLoading"
                  @click="useCurrentLocation"
                >
                  <svg v-if="!geoLoading" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 2a7 7 0 00-7 7c0 5.25 7 13 7 13s7-7.75 7-13a7 7 0 00-7-7z" />
                    <circle cx="12" cy="9" r="2.5" />
                  </svg>
                  <svg v-else class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                  </svg>
                  {{ geoLoading ? 'Locating…' : 'Use my location' }}
                </button>
              </div>
              <p class="mt-3 text-sm text-slate-300">
                If the address is supported, you will see a lightweight local preview first. If not, you can ask to be notified when coverage expands.
              </p>
              <p v-if="geoError" class="mt-2 text-sm text-rose-200">{{ geoError }}</p>
            </div>

            <div class="mt-8 flex flex-wrap gap-3">
              <Link
                :href="route('crime-address.index')"
                class="inline-flex items-center rounded-2xl bg-white px-5 py-3 text-sm font-semibold text-slate-950 transition hover:bg-slate-100"
              >
                Open crime preview
              </Link>
              <Link
                :href="`${route('home')}#cities`"
                class="inline-flex items-center rounded-2xl border border-white/15 px-5 py-3 text-sm font-semibold text-white transition hover:bg-white/5"
              >
                See supported regions
              </Link>
              <Link
                :href="`${route('home')}#explore-tools`"
                class="inline-flex items-center rounded-2xl border border-white/15 px-5 py-3 text-sm font-semibold text-white transition hover:bg-white/5"
              >
                Browse advanced tools
              </Link>
            </div>
          </div>

          <div class="grid gap-4">
            <div class="rounded-[28px] border border-white/10 bg-white/10 p-6 shadow-2xl shadow-slate-950/30 backdrop-blur-xl">
              <p class="text-sm font-semibold uppercase tracking-[0.18em] text-cyan-200">What the first preview should answer</p>
              <ul class="mt-5 space-y-4 text-sm leading-7 text-slate-200">
                <li v-for="promise in previewPromises" :key="promise.title" class="rounded-2xl border border-white/10 bg-slate-950/30 p-4">
                  <p class="font-semibold text-white">{{ promise.title }}</p>
                  <p class="mt-1 text-slate-300">{{ promise.body }}</p>
                </li>
              </ul>
            </div>

            <div class="grid grid-cols-3 gap-4">
              <div class="rounded-3xl border border-white/10 bg-white/10 p-5 text-center shadow-xl shadow-slate-950/20 backdrop-blur">
                <p class="text-3xl font-black text-white">{{ stats.cityCount }}</p>
                <p class="mt-1 text-xs uppercase tracking-[0.16em] text-slate-300">Regions</p>
              </div>
              <div class="rounded-3xl border border-white/10 bg-white/10 p-5 text-center shadow-xl shadow-slate-950/20 backdrop-blur">
                <p class="text-3xl font-black text-white">{{ stats.dataCategoryCount }}</p>
                <p class="mt-1 text-xs uppercase tracking-[0.16em] text-slate-300">Data types</p>
              </div>
              <div class="rounded-3xl border border-white/10 bg-white/10 p-5 text-center shadow-xl shadow-slate-950/20 backdrop-blur">
                <p class="text-3xl font-black text-white">{{ formattedTotalRecords }}</p>
                <p class="mt-1 text-xs uppercase tracking-[0.16em] text-slate-300">Records</p>
              </div>
            </div>
          </div>
        </div>
      </section>

      <div class="px-4 sm:px-6 lg:px-8">
        <section id="how-it-works" class="py-14">
          <div class="mx-auto max-w-6xl">
            <div class="max-w-2xl">
              <p class="text-sm font-semibold uppercase tracking-[0.22em] text-cyan-700">How It Works</p>
              <h2 class="mt-3 text-3xl font-black tracking-tight text-slate-900">Lead with one address. Expand only if the user wants more.</h2>
            </div>
            <div class="mt-8 grid gap-5 lg:grid-cols-3">
              <article
                v-for="step in workflowSteps"
                :key="step.title"
                class="rounded-[28px] border border-slate-200 bg-white p-6 shadow-sm"
              >
                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-400">{{ step.step }}</p>
                <h3 class="mt-3 text-xl font-bold text-slate-900">{{ step.title }}</h3>
                <p class="mt-3 text-sm leading-7 text-slate-600">{{ step.body }}</p>
              </article>
            </div>
          </div>
        </section>

        <section class="py-14">
          <div class="mx-auto max-w-6xl rounded-[34px] border border-slate-200 bg-white p-8 shadow-sm lg:p-10">
            <div class="grid gap-8 lg:grid-cols-[0.95fr_1.05fr]">
              <div>
                <p class="text-sm font-semibold uppercase tracking-[0.22em] text-cyan-700">What The Preview Includes</p>
                <h2 class="mt-3 text-3xl font-black tracking-tight text-slate-900">The score only makes sense when it sits next to incidents and trends.</h2>
                <p class="mt-4 text-base leading-8 text-slate-600">
                  The address preview is designed to answer one question clearly, then give the user just enough context to interpret it without dropping them into the full tool stack.
                </p>
              </div>
              <div class="grid gap-4 sm:grid-cols-3">
                <article
                  v-for="panel in previewPanels"
                  :key="panel.title"
                  class="rounded-3xl border border-slate-200 bg-slate-50 p-5"
                >
                  <h3 class="text-lg font-bold text-slate-900">{{ panel.title }}</h3>
                  <p class="mt-3 text-sm leading-7 text-slate-600">{{ panel.body }}</p>
                </article>
              </div>
            </div>
          </div>
        </section>

        <section id="cities" class="py-14">
          <div class="mx-auto max-w-6xl">
            <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
              <div>
                <p class="text-sm font-semibold uppercase tracking-[0.22em] text-cyan-700">Coverage</p>
                <h2 class="mt-3 text-3xl font-black tracking-tight text-slate-900">Supported cities and regions</h2>
                <p class="mt-3 max-w-2xl text-base leading-8 text-slate-600">
                  Coverage is not uniform across every region. The homepage should make that clear before the user ever hits an unsupported address.
                </p>
              </div>
              <Link
                :href="route('crime-address.index')"
                class="inline-flex items-center rounded-2xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
              >
                Search an address now
              </Link>
            </div>

            <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
              <Link
                v-for="city in cities"
                :key="city.name"
                :href="city.primaryUrl"
                class="group rounded-[28px] border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-cyan-200 hover:shadow-lg"
              >
                <div class="flex items-center justify-between gap-3">
                  <div>
                    <h3 class="text-lg font-bold text-slate-900 transition group-hover:text-cyan-700">{{ city.name }}</h3>
                    <p class="mt-1 text-xs uppercase tracking-[0.16em] text-slate-400">{{ city.dataTypeCount }} data {{ city.dataTypeCount === 1 ? 'type' : 'types' }}</p>
                  </div>
                  <div class="rounded-2xl bg-slate-100 px-3 py-2 text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">
                    {{ city.dataTypes.includes('Crime') ? 'Crime' : 'Data' }}
                  </div>
                </div>

                <div class="mt-4 flex flex-wrap gap-2">
                  <span
                    v-for="dataType in city.dataTypes"
                    :key="`${city.name}-${dataType}`"
                    class="rounded-full px-2.5 py-1 text-xs font-medium"
                    :class="categoryBadgeClass(dataType)"
                  >
                    {{ dataType }}
                  </span>
                </div>
              </Link>
            </div>
          </div>
        </section>

        <section id="explore-tools" class="py-14">
          <div class="mx-auto max-w-6xl">
            <div class="max-w-2xl">
              <p class="text-sm font-semibold uppercase tracking-[0.22em] text-cyan-700">Explore Tools</p>
              <h2 class="mt-3 text-3xl font-black tracking-tight text-slate-900">Advanced workflows belong under one explore layer, not in the first user decision.</h2>
              <p class="mt-3 text-base leading-8 text-slate-600">
                These tools still matter. They just should not compete with the address preview for attention on the first screen.
              </p>
            </div>

            <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
              <Link
                v-for="tool in professionalTools"
                :key="tool.title"
                :href="tool.route"
                class="group rounded-[28px] border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:border-cyan-200 hover:shadow-lg"
              >
                <div class="inline-flex rounded-2xl p-3 text-white shadow-lg" :class="tool.iconBg" :style="tool.iconShadow">
                  <component :is="tool.iconComponent" />
                </div>
                <h3 class="mt-5 text-xl font-bold text-slate-900 transition group-hover:text-cyan-700">{{ tool.title }}</h3>
                <p class="mt-3 text-sm leading-7 text-slate-600">{{ tool.description }}</p>
              </Link>
            </div>
          </div>
        </section>

        <section class="py-14">
          <div class="mx-auto max-w-6xl rounded-[34px] border border-slate-200 bg-slate-950 px-8 py-10 text-white shadow-sm lg:px-10">
            <div class="grid gap-8 lg:grid-cols-[1fr_auto] lg:items-center">
              <div>
                <p class="text-sm font-semibold uppercase tracking-[0.22em] text-cyan-300">Next Step</p>
                <h2 class="mt-3 text-3xl font-black tracking-tight">Start with the free address preview. Use the deeper tools after the preview earns it.</h2>
                <p class="mt-4 max-w-2xl text-base leading-8 text-slate-300">
                  That is the cleanest path for residents, and it still leaves room for reporters, agents, and operators who need the broader map, trend, and scoring stack.
                </p>
              </div>
              <div class="flex flex-wrap gap-3">
                <Link
                  :href="route('crime-address.index')"
                  class="inline-flex items-center rounded-2xl bg-cyan-400 px-5 py-3 text-sm font-semibold text-slate-950 transition hover:bg-cyan-300"
                >
                  Start crime preview
                </Link>
                <Link
                  :href="route('subscription.index')"
                  class="inline-flex items-center rounded-2xl border border-white/15 px-5 py-3 text-sm font-semibold text-white transition hover:bg-white/5"
                >
                  Compare plans
                </Link>
              </div>
            </div>
          </div>
        </section>
      </div>
    </article>
  </PageTemplate>
</template>

<script setup>
import axios from 'axios';
import { computed, h, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import GoogleAddressSearch from '@/Components/GoogleAddressSearch.vue';
import PageTemplate from '@/Components/PageTemplate.vue';

const props = defineProps({
  cities: Array,
  dataCategories: Array,
  stats: Object,
});

const geoLoading = ref(false);
const geoError = ref(null);

async function reverseGeocodeLocation(latitude, longitude) {
  const response = await axios.post(route('google-places.reverse-geocode'), {
    latitude,
    longitude,
  });

  return response.data.address;
}

function openPreview(address, latitude, longitude) {
  router.visit(route('crime-address.index', {
    address,
    lat: latitude,
    lng: longitude,
  }));
}

function handleAddressSelected(location) {
  if (!location?.lat || !location?.lng) {
    return;
  }

  openPreview(location.address, location.lat, location.lng);
}

function useCurrentLocation() {
  if (!navigator.geolocation) {
    geoError.value = 'Geolocation is not supported by your browser.';
    return;
  }

  geoLoading.value = true;
  geoError.value = null;

  navigator.geolocation.getCurrentPosition(
    async (position) => {
      const latitude = Number(position.coords.latitude.toFixed(6));
      const longitude = Number(position.coords.longitude.toFixed(6));

      try {
        const address = await reverseGeocodeLocation(latitude, longitude);
        geoLoading.value = false;
        openPreview(address, latitude, longitude);
      } catch (error) {
        geoLoading.value = false;
        geoError.value = 'We found your coordinates, but could not resolve a street address. Please search your address instead.';
      }
    },
    (error) => {
      geoLoading.value = false;
      geoError.value = error.code === 1
        ? 'Location access denied. Please allow location access in your browser settings.'
        : 'Unable to determine your location. Please try searching an address instead.';
    },
    { enableHighAccuracy: true, timeout: 10000 },
  );
}

const formattedTotalRecords = computed(() => {
  const total = props.stats.totalRecords;
  if (total >= 1_000_000) {
    return `${(total / 1_000_000).toFixed(1).replace(/\.0$/, '')}M+`;
  }
  if (total >= 1_000) {
    return `${Math.floor(total / 1_000).toLocaleString()}K+`;
  }
  return total.toLocaleString();
});

const badgeClasses = {
  Crime: 'bg-red-50 text-red-700',
  '311 Case': 'bg-amber-50 text-amber-700',
  'Building Permit': 'bg-blue-50 text-blue-700',
  'Property Violation': 'bg-orange-50 text-orange-700',
  'Food Inspection': 'bg-emerald-50 text-emerald-700',
  'Construction Off Hour': 'bg-violet-50 text-violet-700',
  'Car Crash': 'bg-rose-50 text-rose-700',
};

function categoryBadgeClass(name) {
  return badgeClasses[name] || 'bg-slate-100 text-slate-600';
}

const previewPromises = [
  {
    title: 'Recent nearby crime',
    body: 'Show what has happened near the address recently instead of asking the user to learn the full product first.',
  },
  {
    title: 'Readable context',
    body: 'Summarize what stands out, what is common nearby, and what the trends suggest without overwhelming the user.',
  },
  {
    title: 'Score framing that makes sense',
    body: 'Explain how the surrounding scored area compares with the city and nearby cells so the number means something.',
  },
];

const workflowSteps = [
  {
    step: 'Step 1',
    title: 'Search an address',
    body: 'The first action should always be a concrete address lookup, not a tour of maps, metrics, and reports.',
  },
  {
    step: 'Step 2',
    title: 'Read the local preview',
    body: 'Show nearby incidents, trend context, and score framing in one stripped-down view that answers the user’s question quickly.',
  },
  {
    step: 'Step 3',
    title: 'Decide if daily reports are worth it',
    body: 'After the preview proves useful, offer the email-report workflow and the broader paid toolset.',
  },
];

const previewPanels = [
  {
    title: 'Incidents',
    body: 'Recent incidents and category patterns around the address, with a light map and readable summaries.',
  },
  {
    title: 'Trend context',
    body: 'City or region-level findings that show whether the address sits inside a place with active statistical signals.',
  },
  {
    title: 'Score context',
    body: 'A city-relative and nearby-relative view of the score, not just a bare number with no explanation.',
  },
];

const IconFullMap = { render: () => h('svg', { class: 'h-5 w-5', fill: 'none', stroke: 'currentColor', 'stroke-width': '2', viewBox: '0 0 24 24' }, [h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l5.447 2.724A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7' })]) };
const IconTrends = { render: () => h('svg', { class: 'h-5 w-5', fill: 'none', stroke: 'currentColor', 'stroke-width': '2', viewBox: '0 0 24 24' }, [h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M2 20h20M5 17l4-6 4 3 6-9' })]) };
const IconScore = { render: () => h('svg', { class: 'h-5 w-5', fill: 'none', stroke: 'currentColor', 'stroke-width': '2', viewBox: '0 0 24 24' }, [h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z' })]) };
const IconYearly = { render: () => h('svg', { class: 'h-5 w-5', fill: 'none', stroke: 'currentColor', 'stroke-width': '2', viewBox: '0 0 24 24' }, [h('rect', { x: '3', y: '4', width: '18', height: '18', rx: '2' }), h('path', { d: 'M16 2v4M8 2v4M3 10h18' })]) };
const IconMetrics = { render: () => h('svg', { class: 'h-5 w-5', fill: 'none', stroke: 'currentColor', 'stroke-width': '2', viewBox: '0 0 24 24' }, [h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M3 3v18h18M7 16v-3m4 3v-7m4 7v-5m4 5V8' })]) };

const professionalTools = [
  {
    title: 'Full data map',
    description: 'Explore broader spatial context across the city once a single address preview is not enough.',
    route: route('data-map.combined'),
    iconBg: 'bg-gradient-to-br from-blue-500 to-cyan-500',
    iconShadow: 'box-shadow: 0 10px 28px rgba(59, 130, 246, 0.28)',
    iconComponent: IconFullMap,
  },
  {
    title: 'Trend reports',
    description: 'Review the statistical findings that explain why certain parts of a city are changing.',
    route: route('trends.index'),
    iconBg: 'bg-gradient-to-br from-emerald-500 to-teal-600',
    iconShadow: 'box-shadow: 0 10px 28px rgba(16, 185, 129, 0.28)',
    iconComponent: IconTrends,
  },
  {
    title: 'Neighborhood scores',
    description: 'Compare scored H3 areas directly when you need more than the preview’s local context block.',
    route: route('scoring-reports.index'),
    iconBg: 'bg-gradient-to-br from-amber-400 to-orange-500',
    iconShadow: 'box-shadow: 0 10px 28px rgba(245, 158, 11, 0.28)',
    iconComponent: IconScore,
  },
  {
    title: 'Yearly comparisons',
    description: 'See whether activity is shifting against prior years before you make a decision or publish a story.',
    route: route('yearly-comparisons.index'),
    iconBg: 'bg-gradient-to-br from-violet-500 to-fuchsia-600',
    iconShadow: 'box-shadow: 0 10px 28px rgba(139, 92, 246, 0.28)',
    iconComponent: IconYearly,
  },
  {
    title: 'Data metrics',
    description: 'Check how broad and fresh the underlying public datasets are in each region.',
    route: route('data.metrics'),
    iconBg: 'bg-gradient-to-br from-slate-500 to-slate-700',
    iconShadow: 'box-shadow: 0 10px 28px rgba(71, 85, 105, 0.28)',
    iconComponent: IconMetrics,
  },
];
</script>

<style scoped>
.hero-search-wrapper {
  background: rgba(255, 255, 255, 0.06);
  backdrop-filter: blur(24px);
  -webkit-backdrop-filter: blur(24px);
  border: 1px solid rgba(255, 255, 255, 0.16);
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.08);
}

.hero-search-wrapper :deep(input) {
  width: 100%;
  border-radius: 0.95rem;
  border: 1px solid rgba(255, 255, 255, 0.18);
  background: rgba(255, 255, 255, 0.08);
  color: white;
  padding: 0.85rem 1rem;
  font-size: 0.95rem;
}

.hero-search-wrapper :deep(input::placeholder) {
  color: rgba(226, 232, 240, 0.6);
}

.hero-search-wrapper :deep(input:focus) {
  outline: none;
  border-color: rgba(34, 211, 238, 0.6);
  box-shadow: 0 0 0 3px rgba(34, 211, 238, 0.18);
}

.hero-search-wrapper :deep(ul) {
  margin-top: 0.35rem;
  overflow: hidden;
  border-radius: 0.95rem;
  border: 1px solid rgba(255, 255, 255, 0.14);
  background: #0f172a;
}

.hero-search-wrapper :deep(li) {
  padding: 0.7rem 1rem;
  color: #e2e8f0;
}

.hero-search-wrapper :deep(li:hover) {
  background: rgba(255, 255, 255, 0.08);
}
</style>
