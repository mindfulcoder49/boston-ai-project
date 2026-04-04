<template>
  <PageTemplate>
    <Head>
      <title>PublicDataWatch | Choose Your City Before You Search</title>
      <meta
        name="description"
        content="PublicDataWatch only covers a small set of city and regional pages. Choose the place we support first, then search an address with the right local datasets."
      />
    </Head>

    <article class="-mx-4 sm:-mx-6 lg:-mx-8">
      <section class="relative overflow-hidden px-4 py-20 sm:px-6 lg:px-8 lg:py-24">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(34,197,94,0.22),_transparent_30%),radial-gradient(circle_at_bottom_right,_rgba(59,130,246,0.28),_transparent_36%),linear-gradient(135deg,_#020617,_#0f172a_45%,_#082f49)]"></div>
        <div class="absolute inset-0 opacity-[0.06]" style="background-image: linear-gradient(rgba(255,255,255,0.7) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.7) 1px, transparent 1px); background-size: 36px 36px;"></div>

        <div class="relative mx-auto grid max-w-7xl gap-14 lg:grid-cols-[1.05fr_0.95fr] lg:items-center">
          <div>
            <p class="text-sm font-semibold uppercase tracking-[0.28em] text-cyan-300">Transparent Coverage</p>
            <h1 class="mt-6 max-w-4xl text-4xl font-black tracking-tight text-white sm:text-5xl lg:text-6xl">
              Choose your city before you search an address.
            </h1>
            <p class="mt-6 max-w-2xl text-lg leading-8 text-slate-300">
              PublicDataWatch only covers a small set of city and regional pages. Start with the page that matches your area so the address search, map layers, and reports line up with what we actually publish there.
            </p>

            <div
              class="mt-10 max-w-3xl rounded-[30px] border border-white/10 bg-white/10 p-5 shadow-2xl shadow-slate-950/40 backdrop-blur-xl"
              data-testid="home-trust-proof"
            >
              <p class="text-xs font-semibold uppercase tracking-[0.18em] text-cyan-100">Built from official public records</p>
              <p class="mt-3 text-sm leading-7 text-slate-200">
                PublicDataWatch currently publishes {{ stats.cityCount }} city and regional pages with {{ formattedTotalRecords }} records across crime, 311, permits, inspections, violations, and crash data. Some pages are crime-focused. Others include broader civic data.
              </p>
              <div class="mt-4 flex flex-wrap gap-3 text-sm">
                <Link
                  :href="route('data.metrics')"
                  class="inline-flex items-center font-semibold text-cyan-100 transition hover:text-white"
                >
                  See data freshness and coverage
                </Link>
                <Link
                  :href="`${route('home')}#cities`"
                  class="inline-flex items-center font-semibold text-slate-300 transition hover:text-white"
                >
                  See every supported city page
                </Link>
              </div>
            </div>

            <div class="mt-8 flex flex-wrap gap-3">
              <Link
                :href="`${route('home')}#cities`"
                class="inline-flex items-center rounded-2xl bg-white px-5 py-3 text-sm font-semibold text-slate-950 transition hover:bg-slate-100"
              >
                Choose a city page
              </Link>
              <Link
                :href="route('crime-address.index')"
                class="inline-flex items-center rounded-2xl border border-white/15 px-5 py-3 text-sm font-semibold text-white transition hover:bg-white/5"
              >
                Open the direct address preview
              </Link>
              <Link
                :href="`${route('home')}#explore-tools`"
                class="inline-flex items-center rounded-2xl border border-white/15 px-5 py-3 text-sm font-semibold text-white transition hover:bg-white/5"
              >
                Browse advanced tools
              </Link>
            </div>
          </div>

          <div
            class="rounded-[30px] border border-white/10 bg-white/10 p-6 shadow-2xl shadow-slate-950/30 backdrop-blur-xl"
            data-testid="home-city-picker"
          >
            <div class="flex items-start justify-between gap-4">
              <div>
                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-cyan-200">Choose Your City</p>
                <h2 class="mt-3 text-2xl font-black tracking-tight text-white">Start with the page that matches your area.</h2>
              </div>
              <div class="rounded-2xl bg-slate-950/35 px-3 py-2 text-xs font-semibold uppercase tracking-[0.16em] text-slate-300">
                {{ stats.cityCount }} pages
              </div>
            </div>

            <div class="mt-6 grid gap-3 sm:grid-cols-2">
              <Link
                v-for="city in cities"
                :key="city.key"
                :href="city.landingUrl || city.primaryUrl"
                class="group rounded-[24px] border border-white/10 bg-slate-950/30 p-4 transition hover:-translate-y-0.5 hover:border-cyan-200/50 hover:bg-slate-950/45"
              >
                <div class="flex items-start justify-between gap-3">
                  <div>
                    <h3 class="text-base font-bold text-white transition group-hover:text-cyan-200">{{ city.locationLabel }}</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-300">{{ city.coverageNote }}</p>
                  </div>
                  <div class="rounded-2xl bg-cyan-400/10 px-3 py-2 text-[11px] font-semibold uppercase tracking-[0.16em] text-cyan-100">
                    {{ coverageFocusLabel(city) }}
                  </div>
                </div>
                <div class="mt-4 flex flex-wrap gap-2">
                  <span
                    v-for="dataType in city.dataTypes.slice(0, 3)"
                    :key="`${city.key}-${dataType}`"
                    class="rounded-full px-2.5 py-1 text-xs font-medium"
                    :class="categoryBadgeClass(dataType)"
                  >
                    {{ dataType }}
                  </span>
                  <span
                    v-if="city.dataTypes.length > 3"
                    class="rounded-full bg-white/10 px-2.5 py-1 text-xs font-medium text-slate-200"
                  >
                    +{{ city.dataTypes.length - 3 }} more
                  </span>
                </div>
              </Link>
            </div>
          </div>
        </div>
      </section>

      <div class="px-4 sm:px-6 lg:px-8">
        <section id="how-it-works" class="py-14">
          <div class="mx-auto max-w-6xl">
            <div class="max-w-2xl">
              <p class="text-sm font-semibold uppercase tracking-[0.22em] text-cyan-700">How It Works</p>
              <h2 class="mt-3 text-3xl font-black tracking-tight text-slate-900">Start with the right city. Then get specific.</h2>
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
                <p class="text-sm font-semibold uppercase tracking-[0.22em] text-cyan-700">Why Lead With City Pages</p>
                <h2 class="mt-3 text-3xl font-black tracking-tight text-slate-900">The front door should match the coverage we actually have.</h2>
                <p class="mt-4 text-base leading-8 text-slate-600">
                  The right city page tells the truth about what datasets exist there before anyone wastes time on a dead-end address lookup. Once you pick the right place, the address flow and deeper tools make much more sense.
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
                <h2 class="mt-3 text-3xl font-black tracking-tight text-slate-900">Supported city and regional pages</h2>
                <p class="mt-3 max-w-2xl text-base leading-8 text-slate-600">
                  This is the front-door list. If a place is not here, the homepage should not imply we fully support it yet.
                </p>
              </div>
              <Link
                :href="route('data.metrics')"
                class="inline-flex items-center rounded-2xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
              >
                Review freshness and dataset breadth
              </Link>
            </div>

            <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
              <Link
                v-for="city in cities"
                :key="city.key"
                :href="city.landingUrl || city.primaryUrl"
                class="group rounded-[28px] border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-cyan-200 hover:shadow-lg"
              >
                <div class="flex items-start justify-between gap-3">
                  <div>
                    <h3 class="text-lg font-bold text-slate-900 transition group-hover:text-cyan-700">{{ city.locationLabel }}</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-600">{{ city.coverageNote }}</p>
                    <p class="mt-3 text-xs uppercase tracking-[0.16em] text-slate-400">{{ city.dataTypeCount }} data {{ city.dataTypeCount === 1 ? 'type' : 'types' }}</p>
                  </div>
                  <div class="rounded-2xl bg-slate-100 px-3 py-2 text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">
                    {{ coverageFocusLabel(city) }}
                  </div>
                </div>

                <div class="mt-4 flex flex-wrap gap-2">
                  <span
                    v-for="dataType in city.dataTypes"
                    :key="`${city.key}-${dataType}`"
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
              <h2 class="mt-3 text-3xl font-black tracking-tight text-slate-900">Go deeper after the city page tells you it is relevant.</h2>
              <p class="mt-3 text-base leading-8 text-slate-600">
                Use the full map, historical comparisons, and scoring tools when you need broader city context, multiple neighborhoods, or a reporting workflow beyond one address.
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
                <h2 class="mt-3 text-3xl font-black tracking-tight">Already know the exact address and city?</h2>
                <p class="mt-4 max-w-2xl text-base leading-8 text-slate-300">
                  The city page is the honest starting point. If you already know the supported city and exact address, the direct preview is still available. If the first scan helps, keep daily reports or unlock the fuller map workflow.
                </p>
              </div>
              <div class="flex flex-wrap gap-3">
                <Link
                  :href="route('crime-address.index')"
                  class="inline-flex items-center rounded-2xl bg-cyan-400 px-5 py-3 text-sm font-semibold text-slate-950 transition hover:bg-cyan-300"
                >
                  Open direct address preview
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
import { computed, h } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import PageTemplate from '@/Components/PageTemplate.vue';

const props = defineProps({
  cities: Array,
  dataCategories: Array,
  stats: Object,
});

const formattedTotalRecords = computed(() => {
  const total = Number.isFinite(Number(props.stats?.totalRecords)) ? Number(props.stats.totalRecords) : 0;
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

function coverageFocusLabel(city) {
  if (city.dataTypes.includes('Crime') && city.dataTypes.length > 1) {
    return 'Crime + city data';
  }

  if (city.dataTypes.includes('Crime')) {
    return 'Crime focus';
  }

  if (city.dataTypes.includes('311 Case')) {
    return '311 focus';
  }

  return 'City data';
}

const workflowSteps = [
  {
    step: 'Step 1',
    title: 'Choose the city page',
    body: 'Start with the supported city or regional page so the product promise matches the data that actually exists there.',
  },
  {
    step: 'Step 2',
    title: 'Search the address there',
    body: 'Use the city-specific address search to get the right map layers, dataset mix, and local framing for that place.',
  },
  {
    step: 'Step 3',
    title: 'Go deeper only if it earns it',
    body: 'Move into maps, trends, scoring, or daily reports once the first read tells you the area is worth deeper review.',
  },
];

const previewPanels = [
  {
    title: 'Honest coverage first',
    body: 'The homepage should say exactly which city and regional pages exist instead of pretending every address is equally supported.',
  },
  {
    title: 'The right data mix',
    body: 'Boston is not the same product surface as New York or Everett. The city page tells you whether you are getting crime, 311, or a broader civic-data mix.',
  },
  {
    title: 'Cleaner decisions',
    body: 'When the city page is right, the later address preview, full map, and reports all feel more trustworthy and easier to interpret.',
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
    description: 'Explore broader spatial context across the city once the city page confirms the dataset mix you need.',
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
    description: 'Compare scored H3 areas directly when you need more than an address-centered first pass.',
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
    description: 'Check how broad and fresh the underlying public datasets are in each supported place.',
    route: route('data.metrics'),
    iconBg: 'bg-gradient-to-br from-slate-500 to-slate-700',
    iconShadow: 'box-shadow: 0 10px 28px rgba(71, 85, 105, 0.28)',
    iconComponent: IconMetrics,
  },
];
</script>
