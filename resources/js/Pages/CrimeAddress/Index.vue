<template>
  <PageTemplate>
    <Head>
      <title>Crime Around Your Address | PublicDataWatch</title>
      <meta
        name="description"
        content="Enter an address to see recent nearby crime, a readable local report, and city-level trend context."
      />
    </Head>

    <article class="-mx-4 sm:-mx-6 lg:-mx-8">
      <section class="relative overflow-hidden bg-gradient-to-br from-slate-950 via-slate-900 to-red-950 px-4 py-20 sm:px-6 lg:px-8">
        <div class="absolute inset-0 opacity-30" style="background: radial-gradient(circle at top left, rgba(248, 113, 113, 0.35), transparent 42%), radial-gradient(circle at bottom right, rgba(251, 191, 36, 0.18), transparent 40%);"></div>
        <div class="relative mx-auto max-w-4xl">
          <p class="mb-4 text-sm font-semibold uppercase tracking-[0.24em] text-red-200">Crime Address Preview</p>
          <h1 class="max-w-3xl text-4xl font-black tracking-tight text-white sm:text-5xl">
            Wondering what crime is happening around your address?
          </h1>
          <p class="mt-5 max-w-2xl text-lg leading-8 text-slate-300">
            Enter an address to see recent nearby crime, a readable summary, trend context, and neighborhood score context where available.
          </p>

          <div class="mt-10 rounded-[28px] border border-white/10 bg-white/95 p-4 shadow-2xl shadow-black/30 backdrop-blur">
            <div class="grid gap-3 lg:grid-cols-[1fr_auto]">
              <div class="relative">
                <GoogleAddressSearch
                  :initial-search-query="addressInput"
                  :language_codes="['en-US']"
                  @address-selected="handleAddressSelected"
                  @search-started="handleSearchStarted"
                />
              </div>
              <button
                type="button"
                class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-700 disabled:opacity-60"
                :disabled="geoLoading"
                @click="useCurrentLocation"
              >
                {{ geoLoading ? 'Locating…' : 'Use my location' }}
              </button>
            </div>
            <p class="mt-3 text-sm text-slate-500">
              Search runs the stripped-down crime preview, not the full map.
            </p>
            <p v-if="geoError" class="mt-2 text-sm text-red-600">{{ geoError }}</p>
          </div>
        </div>
      </section>

      <section class="mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
        <div v-if="isLoadingPreview" class="rounded-3xl border border-slate-200 bg-white p-10 text-center shadow-sm">
          <p class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-400">Loading Preview</p>
          <h2 class="mt-3 text-2xl font-bold text-slate-900">Checking coverage and building the local crime preview</h2>
        </div>

        <div v-else-if="unsupportedState" class="rounded-3xl border border-amber-200 bg-amber-50 p-8 shadow-sm">
          <p class="text-sm font-semibold uppercase tracking-[0.2em] text-amber-700">Coverage Request</p>
          <h2 class="mt-2 text-3xl font-bold text-slate-900">We do not serve your address yet.</h2>
          <p class="mt-3 max-w-2xl text-base leading-7 text-slate-700">
            We will look into adding your area and notify you if we do.
          </p>

          <form class="mt-6 grid gap-3 md:grid-cols-[1fr_320px_auto]" @submit.prevent="submitCoverageRequest">
            <input
              v-model="coverageForm.requested_address"
              type="text"
              class="rounded-2xl border border-amber-200 px-4 py-3 text-sm text-slate-900 shadow-sm"
              placeholder="Unsupported address"
            />
            <input
              v-model="coverageForm.email"
              type="email"
              class="rounded-2xl border border-amber-200 px-4 py-3 text-sm text-slate-900 shadow-sm"
              placeholder="Email for updates"
            />
            <button
              type="submit"
              class="rounded-2xl bg-amber-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-amber-700 disabled:opacity-60"
              :disabled="coverageSubmitting"
            >
              {{ coverageSubmitting ? 'Submitting…' : 'Notify me' }}
            </button>
          </form>

          <p v-if="coverageSuccess" class="mt-3 text-sm text-emerald-700">{{ coverageSuccess }}</p>
          <p v-if="coverageError" class="mt-3 text-sm text-red-600">{{ coverageError }}</p>
        </div>

        <div v-else-if="preview" class="space-y-8">
          <div class="grid gap-6 lg:grid-cols-[1.3fr_0.7fr]">
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
              <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                  <p class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-400">Preview</p>
                  <h2 class="mt-2 text-3xl font-bold text-slate-900">{{ preview.address }}</h2>
                  <p class="mt-2 text-sm text-slate-500">
                    {{ incidentSummaryLine }}
                  </p>
                </div>
                <div
                  class="min-w-[220px] rounded-3xl border border-slate-200 bg-slate-50 px-5 py-4 text-left"
                  data-testid="crime-address-neighborhood-score-card"
                >
                  <p class="text-xs uppercase tracking-[0.18em] text-slate-500">Address-Area Score</p>
                  <p
                    v-if="scoreValue !== null"
                    class="mt-2 text-3xl font-black text-slate-900"
                    data-testid="crime-address-neighborhood-score-value"
                  >
                    {{ Number(scoreValue).toFixed(1) }}
                  </p>
                  <p v-if="scoreSummary?.band?.label" class="mt-1 text-sm font-semibold text-slate-700">
                    {{ scoreSummary.band.label }}
                  </p>
                  <p class="mt-2 text-xs leading-5 text-slate-500">
                    Applies to the scored hexagon around this address, not an individual parcel boundary.
                  </p>
                  <p
                    v-if="scoreValue === null"
                    class="mt-1 text-sm text-slate-500"
                    data-testid="crime-address-neighborhood-score-unavailable"
                  >
                    {{ scoreLoading ? 'Loading…' : 'Not available' }}
                  </p>
                </div>
              </div>

              <div class="mt-6">
                <PreviewMap
                  :center="{ latitude: preview.latitude, longitude: preview.longitude }"
                  :incidents="preview.map_data.incidents"
                />
              </div>

              <div
                class="mt-6 rounded-3xl border border-slate-200 bg-slate-50 p-5"
                data-testid="crime-address-score-context"
              >
                <div class="flex flex-wrap items-start justify-between gap-4">
                  <div class="max-w-xl">
                    <p class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-500">How To Read This Score</p>
                    <h3 class="mt-2 text-xl font-bold text-slate-900">
                      {{ scoreSummary?.band?.label || 'Waiting for score context' }}
                    </h3>
                    <p class="mt-2 text-sm leading-7 text-slate-600">
                      {{ scoreSummary?.band?.description || scoreContextFallbackMessage }}
                    </p>
                  </div>
                  <div class="rounded-2xl bg-white px-4 py-3 text-sm text-slate-500">
                    <p class="font-semibold text-slate-900">{{ scoreSourceLabel }}</p>
                    <p class="mt-1">{{ scoreMethodologyText }}</p>
                  </div>
                </div>

                <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                  <div class="rounded-2xl bg-white p-4">
                    <p class="text-xs uppercase tracking-[0.16em] text-slate-400">City Percentile</p>
                    <p class="mt-2 text-2xl font-black text-slate-900">{{ scorePercentileLabel }}</p>
                    <p class="mt-1 text-sm text-slate-500">Relative concern within {{ preview.matched_city_name }}</p>
                  </div>
                  <div class="rounded-2xl bg-white p-4">
                    <p class="text-xs uppercase tracking-[0.16em] text-slate-400">City Median</p>
                    <p class="mt-2 text-2xl font-black text-slate-900">{{ distributionMedianLabel }}</p>
                    <p class="mt-1 text-sm text-slate-500">Middle scored area in this city or region</p>
                  </div>
                  <div class="rounded-2xl bg-white p-4">
                    <p class="text-xs uppercase tracking-[0.16em] text-slate-400">Nearby Median</p>
                    <p class="mt-2 text-2xl font-black text-slate-900">{{ nearbyMedianLabel }}</p>
                    <p class="mt-1 text-sm text-slate-500">{{ nearbyComparisonLabel }}</p>
                  </div>
                  <div class="rounded-2xl bg-white p-4">
                    <p class="text-xs uppercase tracking-[0.16em] text-slate-400">Scored Areas</p>
                    <p class="mt-2 text-2xl font-black text-slate-900">{{ scoreDistributionCountLabel }}</p>
                    <p class="mt-1 text-sm text-slate-500">Distribution sample behind this comparison</p>
                  </div>
                </div>

                <div class="mt-5 grid gap-5 lg:grid-cols-[1fr_auto] lg:items-start">
                  <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-500">Top Score Drivers</p>
                    <ul v-if="scoreTopDrivers.length" class="mt-3 space-y-3">
                      <li
                        v-for="driver in scoreTopDrivers"
                        :key="driver.label"
                        class="flex items-center justify-between rounded-2xl bg-white px-4 py-3"
                      >
                        <div>
                          <p class="text-sm font-semibold text-slate-900">{{ driver.label }}</p>
                          <p class="text-xs text-slate-500">{{ driver.share_percent !== null ? `${driver.share_percent}% of weighted score` : 'Driver contribution' }}</p>
                        </div>
                        <p class="text-sm font-bold text-slate-700">{{ Number(driver.weighted_score).toFixed(1) }}</p>
                      </li>
                    </ul>
                    <p v-else class="mt-3 text-sm leading-6 text-slate-600">
                      The score loaded, but detailed driver composition was not available for this area.
                    </p>
                  </div>

                  <div class="grid gap-3 sm:min-w-[220px]">
                    <Link
                      v-if="cityLandingHref"
                      :href="cityLandingHref"
                      class="inline-flex items-center justify-center rounded-2xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-white"
                    >
                      Open city page
                    </Link>
                    <Link
                      :href="route('data-map.combined')"
                      class="inline-flex items-center justify-center rounded-2xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-white"
                    >
                      Open full data map
                    </Link>
                    <Link
                      :href="trendViewerHref"
                      class="inline-flex items-center justify-center rounded-2xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-white"
                    >
                      Browse trends
                    </Link>
                    <Link
                      :href="scoreViewerHref"
                      class="inline-flex items-center justify-center rounded-2xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-white"
                    >
                      Open scoring tools
                    </Link>
                  </div>
                </div>
              </div>
            </div>

            <aside class="space-y-4">
              <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-400">What stands out</p>
                <ul v-if="hasIncidents" class="mt-4 space-y-3">
                  <li
                    v-for="category in preview.incident_summary.top_categories"
                    :key="category.category"
                    class="flex items-center justify-between rounded-2xl bg-slate-50 px-4 py-3"
                  >
                    <span class="text-sm font-medium text-slate-700">{{ category.category }}</span>
                    <span class="text-sm font-bold text-slate-900">{{ category.count }}</span>
                  </li>
                </ul>
                <p v-else class="mt-4 text-sm leading-6 text-slate-600">
                  No incident categories stand out because no recent incidents were found in this preview radius.
                </p>
              </div>

              <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-400">Next Step</p>
                <h3 class="mt-2 text-xl font-bold text-slate-900">{{ nextStepTitle }}</h3>
                <p class="mt-3 text-sm leading-6 text-slate-600">
                  {{ nextStepBody }}
                </p>
                <div v-if="!isAuthenticated" class="mt-5 flex flex-wrap gap-3">
                  <Link
                    :href="registerHref"
                    @click="trackPreviewSignupStarted('register')"
                    class="inline-flex items-center rounded-2xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-700"
                  >
                    Create free account
                  </Link>
                  <Link
                    :href="loginHref"
                    @click="trackPreviewSignupStarted('login')"
                    class="inline-flex items-center rounded-2xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                  >
                    Log in
                  </Link>
                </div>
                <div v-else-if="isPaidPlan" class="mt-5 flex flex-wrap gap-3">
                  <Link
                    :href="route('map.index')"
                    class="inline-flex items-center rounded-2xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-700"
                  >
                    Open full map
                  </Link>
                  <Link
                    :href="pricingHref"
                    class="inline-flex items-center rounded-2xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                  >
                    Compare plans
                  </Link>
                </div>
                <div v-else-if="hasExpiredTrial" class="mt-5 space-y-3">
                  <div class="grid gap-3">
                    <button
                      type="button"
                      class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-700"
                      @click="selectPlan('basic')"
                    >
                      Keep daily reports for $5/month
                    </button>
                    <button
                      type="button"
                      class="inline-flex items-center justify-center rounded-2xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                      @click="selectPlan('pro')"
                    >
                      Unlock full access for $15/month
                    </button>
                  </div>
                  <Link
                    :href="pricingHref"
                    class="inline-flex items-center text-sm font-semibold text-slate-500 transition hover:text-slate-700"
                  >
                    Compare plan details
                  </Link>
                </div>
                <div v-else class="mt-5">
                  <button
                    type="button"
                    class="inline-flex items-center rounded-2xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-700 disabled:opacity-60"
                    :disabled="trialSubmitting"
                    @click="startTrial"
                  >
                    {{ trialSubmitting ? 'Saving…' : hasActiveTrial ? 'Use this address for my daily trial reports' : 'Start 7-day free trial' }}
                  </button>
                  <p v-if="trialMessage" class="mt-3 text-sm text-emerald-700">{{ trialMessage }}</p>
                  <p v-if="trialError" class="mt-3 text-sm text-red-600">{{ trialError }}</p>
                </div>
              </div>
            </aside>
          </div>

          <div class="grid gap-6 lg:grid-cols-[1.1fr_0.9fr]">
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
              <p class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-400">Address Report</p>
              <div class="mt-4 space-y-5">
                <section
                  v-for="section in reportSections"
                  :key="section.title"
                  class="rounded-2xl bg-slate-50 p-4"
                >
                  <h3 class="text-lg font-bold text-slate-900">{{ section.title }}</h3>
                  <p class="mt-2 text-sm leading-7 text-slate-700">{{ section.body }}</p>
                </section>
              </div>
            </div>

            <div class="space-y-6">
              <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-400">Recent Incidents</p>
                <div v-if="hasIncidents" class="mt-4 space-y-4">
                  <article
                    v-for="incident in preview.incident_summary.recent_incidents"
                    :key="`${incident.date}-${incident.category}-${incident.location_label}`"
                    class="border-b border-slate-100 pb-4 last:border-b-0 last:pb-0"
                  >
                    <h3 class="text-sm font-bold text-slate-900">{{ incident.category }}</h3>
                    <p class="mt-1 text-xs uppercase tracking-[0.16em] text-slate-400">{{ formatDate(incident.date) }}</p>
                    <p v-if="incident.description" class="mt-2 text-sm text-slate-700">{{ incident.description }}</p>
                    <p v-if="incident.location_label" class="mt-1 text-sm text-slate-500">{{ incident.location_label }}</p>
                  </article>
                </div>
                <p v-else class="mt-4 text-sm leading-6 text-slate-600">
                  No recent incidents were found within this preview radius.
                </p>
              </div>

              <div
                v-if="preview.trend_context?.summary?.status === 'ok'"
                class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm"
                data-testid="crime-address-trend-context"
              >
                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-400">City Trend Context</p>
                <div class="mt-4 grid grid-cols-3 gap-3">
                  <div class="rounded-2xl bg-slate-50 p-4">
                    <p class="text-xs uppercase tracking-[0.16em] text-slate-400">Findings</p>
                    <p class="mt-2 text-2xl font-black text-slate-900">{{ preview.trend_context.summary.total_findings }}</p>
                  </div>
                  <div class="rounded-2xl bg-slate-50 p-4">
                    <p class="text-xs uppercase tracking-[0.16em] text-slate-400">Anomalies</p>
                    <p class="mt-2 text-2xl font-black text-slate-900">{{ preview.trend_context.summary.anomaly_count }}</p>
                  </div>
                  <div class="rounded-2xl bg-slate-50 p-4">
                    <p class="text-xs uppercase tracking-[0.16em] text-slate-400">Trend Signals</p>
                    <p class="mt-2 text-2xl font-black text-slate-900">{{ preview.trend_context.summary.trend_count }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div v-else class="rounded-3xl border border-slate-200 bg-white p-10 text-center shadow-sm">
          <p class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-400">Start Here</p>
          <h2 class="mt-3 text-2xl font-bold text-slate-900">Enter an address to generate the crime preview</h2>
          <p class="mt-3 text-base leading-7 text-slate-600">
            If the address is supported, this page shows a recent crime map, a readable report, trend context, and neighborhood score context where available.
          </p>
        </div>
      </section>
    </article>
  </PageTemplate>
</template>

<script setup>
import axios from 'axios';
import * as h3 from 'h3-js';
import { computed, onMounted, ref, watch } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import GoogleAddressSearch from '@/Components/GoogleAddressSearch.vue';
import PageTemplate from '@/Components/PageTemplate.vue';
import PreviewMap from '@/Components/CrimeAddress/PreviewMap.vue';
import { trackAnalyticsEvent, trackOncePerSession } from '@/Utils/analytics';

const props = defineProps({
  initialAddress: {
    type: String,
    default: '',
  },
  initialLatitude: {
    type: Number,
    default: null,
  },
  initialLongitude: {
    type: Number,
    default: null,
  },
});

const page = usePage();
const addressInput = ref(props.initialAddress ?? '');
const preview = ref(null);
const unsupportedState = ref(null);
const scoreContext = ref(null);
const isLoadingPreview = ref(false);
const scoreLoading = ref(false);
const geoLoading = ref(false);
const geoError = ref(null);
const coverageSubmitting = ref(false);
const coverageSuccess = ref('');
const coverageError = ref('');
const trialSubmitting = ref(false);
const trialMessage = ref('');
const trialError = ref('');
const trialState = ref({
  active: Boolean(page.props.auth?.user?.has_crime_address_trial),
  used: Boolean(page.props.auth?.user?.has_used_crime_address_trial),
  endsAt: page.props.auth?.user?.crime_address_trial_ends_at ?? null,
});
const coverageForm = ref({
  requested_address: props.initialAddress ?? '',
  email: page.props.auth?.user?.email ?? '',
});
const isAuthenticated = computed(() => Boolean(page.props.auth?.user));
const currentPlanTier = computed(() => page.props.auth?.currentPlan?.tier ?? page.props.auth?.user?.current_plan_details?.tier ?? 'guest');
const isPaidPlan = computed(() => ['basic', 'pro'].includes(currentPlanTier.value));
const hasActiveTrial = computed(() => !isPaidPlan.value && trialState.value.active);
const hasExpiredTrial = computed(() => !isPaidPlan.value && currentPlanTier.value === 'free' && trialState.value.used && !trialState.value.active);
const hasIncidents = computed(() => (preview.value?.incident_summary?.total_incidents ?? 0) > 0);
const incidentSummaryLine = computed(() => {
  if (!preview.value) {
    return '';
  }

  const radius = Number(preview.value.radius ?? 0.25).toFixed(2);
  const incidentCount = Number(preview.value.incident_summary?.total_incidents ?? 0);

  if (incidentCount === 0) {
    return `${preview.value.matched_city_name} • No recent incidents found within ${radius} miles`;
  }

  return `${preview.value.matched_city_name} • ${incidentCount} recent incidents within ${radius} miles`;
});
const nextStepTitle = computed(() => {
  if (!isAuthenticated.value) {
    return 'Sign up to get this report in your email every day';
  }

  if (isPaidPlan.value) {
    return currentPlanTier.value === 'pro'
      ? 'Your Pro plan already unlocks the full workflow'
      : 'Your paid plan already supports recurring reports';
  }

  if (hasActiveTrial.value) {
    return 'Your daily report trial is active';
  }

  if (hasExpiredTrial.value) {
    return 'Your free trial has ended';
  }

  return 'Start your 7-day free trial';
});
const nextStepBody = computed(() => {
  if (!isAuthenticated.value) {
    return 'Create a free account first, then start the 7-day no-card trial for daily email reports on this address.';
  }

  if (isPaidPlan.value) {
    return currentPlanTier.value === 'pro'
      ? 'Use the full map, trend context, and neighborhood score tooling across multiple neighborhoods from your existing plan.'
      : 'Keep using your one-address daily report, or upgrade if you need the full map, trend context, and neighborhood scores across multiple neighborhoods.';
  }

  if (hasActiveTrial.value) {
    return `Your daily email report trial is active${trialState.value.endsAt ? ` through ${formatDate(trialState.value.endsAt)}` : ''}. Use this button again if you want to swap the tracked address.`;
  }

  if (hasExpiredTrial.value) {
    return 'Choose $5/month to keep the one-address daily report going, or $15/month for the full map, trends, neighborhood scores, and broader professional use.';
  }

  return 'Start a 7-day free trial for one address, then keep the daily report going on a paid plan if it proves useful.';
});
const currentUrl = computed(() => {
  if (typeof window === 'undefined') {
    return route('crime-address.index');
  }

  return window.location.pathname + window.location.search;
});
const registerHref = computed(() => `${route('register')}?redirect_to=${encodeURIComponent(currentUrl.value)}`);
const loginHref = computed(() => `${route('login')}?redirect_to=${encodeURIComponent(currentUrl.value)}`);
const pricingHref = computed(() => route('subscription.index', {
  source: 'crime-address',
  recommended: hasExpiredTrial.value ? 'basic' : undefined,
}));
const scoreSummary = computed(() => scoreContext.value?.score_context ?? null);
const scoreValue = computed(() => {
  const summaryScore = scoreSummary.value?.score;
  if (summaryScore !== undefined && summaryScore !== null) {
    return Number(summaryScore);
  }

  const detailsScore = scoreContext.value?.score_details?.score;
  return detailsScore !== undefined ? Number(detailsScore) : null;
});
const scoreTopDrivers = computed(() => scoreSummary.value?.top_drivers ?? []);
const scorePercentileLabel = computed(() => {
  if (scoreSummary.value?.percentile === undefined || scoreSummary.value?.percentile === null) {
    return scoreLoading.value ? 'Loading…' : 'Unavailable';
  }

  return formatOrdinal(Math.round(Number(scoreSummary.value.percentile)));
});
const distributionMedianLabel = computed(() => {
  const median = scoreSummary.value?.distribution?.median;
  return median !== undefined && median !== null ? Number(median).toFixed(1) : 'Unavailable';
});
const nearbyMedianLabel = computed(() => {
  const median = scoreSummary.value?.nearby_peers?.median;
  return median !== undefined && median !== null ? Number(median).toFixed(1) : 'No nearby peer score';
});
const nearbyComparisonLabel = computed(() => {
  if (!scoreSummary.value?.nearby_peers?.available) {
    return 'No adjacent scored cells available for comparison';
  }

  const delta = Number(scoreSummary.value.nearby_peers?.current_vs_median ?? 0);
  if (delta === 0) {
    return 'This address-area sits right on the nearby median';
  }

  return delta > 0
    ? `${delta.toFixed(1)} above nearby median`
    : `${Math.abs(delta).toFixed(1)} below nearby median`;
});
const scoreDistributionCountLabel = computed(() => {
  const count = scoreSummary.value?.distribution?.count;
  return count !== undefined && count !== null ? String(count) : 'Unavailable';
});
const scoreSourceLabel = computed(() => {
  const source = scoreSummary.value?.methodology?.source;
  if (source === 'stage4_fallback') {
    return 'Preview score estimate';
  }

  if (source === 'stage6_artifact') {
    return 'Historical neighborhood score';
  }

  return scoreLoading.value ? 'Loading score source…' : 'Score source unavailable';
});
const scoreMethodologyText = computed(() => {
  if (!scoreSummary.value?.methodology) {
    return scoreLoading.value
      ? 'Building city and nearby comparisons for this address.'
      : 'This score will load with city and nearby comparison context when available.';
  }

  const weeks = scoreSummary.value.methodology.analysis_period_weeks;
  const resolution = scoreSummary.value.methodology.resolution;

  if (weeks) {
    return `Based on a ${weeks}-week scoring window at H3 resolution ${resolution}.`;
  }

  return `Built from the scored H3 cell around this address at resolution ${resolution}.`;
});
const scoreContextFallbackMessage = computed(() => {
  if (scoreLoading.value) {
    return 'Building city-level and nearby-area comparison context for this address now.';
  }

  return 'A single score without nearby and city context is not useful. When scoring is available, this section explains how the address-area compares locally.';
});
const cityLandingHref = computed(() => {
  const routeNameByCity = {
    boston: 'city.landing.boston',
    everett: 'city.landing.everett',
    chicago: 'city.landing.chicago',
    san_francisco: 'city.landing.san_francisco',
    seattle: 'city.landing.seattle',
    montgomery_county_md: 'city.landing.montgomery_county_md',
  };

  const routeName = routeNameByCity[preview.value?.matched_city_key];
  return routeName ? route(routeName) : null;
});
const trendViewerHref = computed(() => {
  if (preview.value?.trend_context?.job_id) {
    return route('reports.statistical-analysis.show', { jobId: preview.value.trend_context.job_id });
  }

  return route('trends.index');
});
const scoreViewerHref = computed(() => {
  if (preview.value?.score_report?.job_id && preview.value?.score_report?.artifact_name) {
    return route('scoring-reports.show', {
      jobId: preview.value.score_report.job_id,
      artifactName: preview.value.score_report.artifact_name,
    });
  }

  return route('scoring-reports.index');
});

const reportSections = computed(() => {
  const sections = [...(preview.value?.preview_report ?? [])];

  if (scoreValue.value !== null) {
    const topDrivers = scoreTopDrivers.value
      .slice(0, 3)
      .map((entry) => `${entry.label} (${Number(entry.weighted_score ?? 0).toFixed(1)})`)
      .join(', ');
    const percentileText = scoreSummary.value?.percentile !== undefined
      ? ` It sits around the ${formatOrdinal(Math.round(Number(scoreSummary.value.percentile)))} percentile of scored areas in ${preview.value?.matched_city_name}.`
      : '';

    sections.push({
      title: 'Neighborhood score',
      body: topDrivers
        ? `This address-area currently scores ${Number(scoreValue.value).toFixed(1)} and is labeled ${scoreSummary.value?.band?.label?.toLowerCase() ?? 'with local score context'} in ${preview.value?.matched_city_name}. The strongest contributors are ${topDrivers}.${percentileText}`
        : `This address-area currently scores ${Number(scoreValue.value).toFixed(1)}.${percentileText}`,
    });
  }

  const analysisDetails = scoreContext.value?.analysis_details ?? [];
  if (analysisDetails.length > 0) {
    const secondaryGroups = [...new Set(analysisDetails.map((item) => item.secondary_group).filter(Boolean))].slice(0, 4);
    sections.push({
      title: 'Address-specific trend signals',
      body: secondaryGroups.length
        ? `The latest neighborhood analysis flags activity in ${secondaryGroups.join(', ')} around this address.`
        : 'The latest neighborhood analysis returned address-specific trend context for this location.',
    });
  }

  return sections;
});

async function handleAddressSelected(location) {
  if (!location?.lat || !location?.lng) {
    return;
  }

  addressInput.value = location.address;
  coverageForm.value.requested_address = location.address;
  coverageSuccess.value = '';
  coverageError.value = '';
  updateUrl(location.address, location.lat, location.lng);
  await loadPreview(location.address, location.lat, location.lng);
}

function handleSearchStarted() {
  preview.value = null;
  unsupportedState.value = null;
  scoreContext.value = null;
  coverageSuccess.value = '';
  coverageError.value = '';
  trialMessage.value = '';
  trialError.value = '';
}

async function loadPreview(address, latitude, longitude) {
  isLoadingPreview.value = true;
  preview.value = null;
  unsupportedState.value = null;
  scoreContext.value = null;

  trackAnalyticsEvent('crime_address_address_submitted', {
    pageType: 'crime_address',
    params: {
      page_path: '/crime-address',
      address_present: true,
    },
  });

  try {
    const response = await axios.post(route('crime-address.preview'), {
      address,
      latitude,
      longitude,
    });

    if (response.data.supported === false) {
      unsupportedState.value = response.data;
      coverageForm.value.requested_address = response.data.serviceability?.normalized_address ?? address;
      trackAnalyticsEvent('crime_address_address_unsupported', {
        pageType: 'crime_address',
        params: {
          page_path: '/crime-address',
          city: response.data.serviceability?.nearest_city_key,
        },
      });
      return;
    }

    preview.value = response.data;
    trackAnalyticsEvent('crime_address_preview_rendered', {
      pageType: 'crime_address',
      city: response.data.matched_city_key,
      params: {
        page_path: '/crime-address',
        incident_count: response.data.incident_summary?.total_incidents,
      },
    });

    await loadScoreContext();
  } catch (error) {
    unsupportedState.value = {
      supported: false,
      message: 'We could not generate the preview for that address.',
    };
  } finally {
    isLoadingPreview.value = false;
  }
}

async function loadScoreContext() {
  if (!preview.value?.score_report?.resolution) {
    return;
  }

  scoreLoading.value = true;

  try {
    const h3Index = h3.latLngToCell(
      preview.value.latitude,
      preview.value.longitude,
      preview.value.score_report.resolution,
    );

    const payload = {
      h3_index: h3Index,
      comparison_h3_indices: h3.gridDisk(h3Index, 1).filter((index) => index !== h3Index),
    };

    if (preview.value.score_report.job_id && preview.value.score_report.artifact_name) {
      payload.job_id = preview.value.score_report.job_id;
      payload.artifact_name = preview.value.score_report.artifact_name;
    } else if (preview.value.score_report.source === 'stage4_fallback') {
      payload.model_class = preview.value.score_report.model_class ?? preview.value.crime_model_class;
      payload.source_job_id = preview.value.score_report.source_job_id ?? preview.value.score_report.job_id;
      payload.column_name = preview.value.score_report.column_name;
    }

    const response = await axios.post(route('scoring-reports.score-for-location'), payload);

    scoreContext.value = response.data;
  } catch (error) {
    scoreContext.value = null;
  } finally {
    scoreLoading.value = false;
  }
}

async function submitCoverageRequest() {
  coverageSubmitting.value = true;
  coverageSuccess.value = '';
  coverageError.value = '';

  try {
    const response = await axios.post(route('crime-address.coverage-request.store'), {
      requested_address: coverageForm.value.requested_address,
      normalized_address: unsupportedState.value?.serviceability?.normalized_address ?? coverageForm.value.requested_address,
      latitude: unsupportedState.value?.serviceability?.latitude ?? props.initialLatitude,
      longitude: unsupportedState.value?.serviceability?.longitude ?? props.initialLongitude,
      email: coverageForm.value.email,
      source_page: '/crime-address',
    });

    coverageSuccess.value = response.data.message;
    trackAnalyticsEvent('crime_address_coverage_request_submitted', {
      pageType: 'crime_address',
      params: {
        page_path: '/crime-address',
      },
    });
  } catch (error) {
    coverageError.value = 'We could not save your request. Please try again.';
  } finally {
    coverageSubmitting.value = false;
  }
}

async function startTrial() {
  trialSubmitting.value = true;
  trialMessage.value = '';
  trialError.value = '';

  try {
    const response = await axios.post(route('crime-address.trial.start'), {
      address: preview.value.address,
      latitude: preview.value.latitude,
      longitude: preview.value.longitude,
    });

    trialMessage.value = response.data.message;
    trialState.value = {
      active: true,
      used: true,
      endsAt: response.data.trial_ends_at ?? trialState.value.endsAt,
    };
    trackAnalyticsEvent('crime_address_trial_started', {
      pageType: 'crime_address',
      city: preview.value.matched_city_key,
      params: {
        page_path: '/crime-address',
      },
    });
  } catch (error) {
    trialError.value = error.response?.data?.message ?? 'We could not start the free trial.';
    if (error.response?.status === 409) {
      trialState.value = {
        active: false,
        used: true,
        endsAt: trialState.value.endsAt,
      };
    }
  } finally {
    trialSubmitting.value = false;
  }
}

async function reverseGeocodeLocation(latitude, longitude) {
  const response = await axios.post(route('google-places.reverse-geocode'), {
    latitude,
    longitude,
  });

  return response.data.address;
}

function trackPreviewSignupStarted(method) {
  trackAnalyticsEvent('crime_address_preview_signup_started', {
    pageType: 'crime_address',
    city: preview.value?.matched_city_key,
    params: {
      page_path: '/crime-address',
      method,
    },
  });
}

function selectPlan(plan) {
  trackAnalyticsEvent('crime_address_plan_selected', {
    pageType: 'crime_address',
    city: preview.value?.matched_city_key,
    isAuthenticated: isAuthenticated.value,
    params: {
      page_path: '/crime-address',
      plan_name: plan,
    },
  });

  if (isAuthenticated.value) {
    window.location.href = route('subscribe.checkout', { plan, source: 'crime-address' });
    return;
  }

  window.location.href = route('subscription.index', {
    source: 'crime-address',
    recommended: plan,
  });
}

function useCurrentLocation() {
  if (!navigator.geolocation) {
    geoError.value = 'Geolocation is not supported by your browser.';
    return;
  }

  geoLoading.value = true;
  geoError.value = null;
  handleSearchStarted();

  navigator.geolocation.getCurrentPosition(
    async (position) => {
      const latitude = Number(position.coords.latitude.toFixed(6));
      const longitude = Number(position.coords.longitude.toFixed(6));

      try {
        const address = await reverseGeocodeLocation(latitude, longitude);
        geoLoading.value = false;
        addressInput.value = address;
        coverageForm.value.requested_address = address;
        updateUrl(address, latitude, longitude);
        await loadPreview(address, latitude, longitude);
      } catch (error) {
        geoLoading.value = false;
        geoError.value = 'We found your coordinates, but could not resolve a street address. Please search your address instead.';
      }
    },
    (error) => {
      geoLoading.value = false;
      geoError.value = error.code === 1
        ? 'Location access denied. Please allow location access in your browser settings.'
        : 'Unable to determine your location right now.';
    },
    { enableHighAccuracy: true, timeout: 10000 },
  );
}

function updateUrl(address, latitude, longitude) {
  const params = new URLSearchParams({
    address,
    lat: String(latitude),
    lng: String(longitude),
  });

  window.history.replaceState({}, '', `${route('crime-address.index')}?${params.toString()}`);
}

function formatDate(value) {
  if (!value) {
    return 'Date unavailable';
  }

  const parsed = new Date(value);
  if (Number.isNaN(parsed.getTime())) {
    return value;
  }

  return parsed.toLocaleDateString(undefined, {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  });
}

function formatOrdinal(value) {
  const absValue = Math.abs(Number(value));
  const lastTwo = absValue % 100;

  if (lastTwo >= 11 && lastTwo <= 13) {
    return `${absValue}th`;
  }

  switch (absValue % 10) {
    case 1:
      return `${absValue}st`;
    case 2:
      return `${absValue}nd`;
    case 3:
      return `${absValue}rd`;
    default:
      return `${absValue}th`;
  }
}

onMounted(async () => {
  if (props.initialAddress && props.initialLatitude && props.initialLongitude) {
    await loadPreview(props.initialAddress, props.initialLatitude, props.initialLongitude);
  }
});

watch(hasExpiredTrial, (expired) => {
  if (!expired || !page.props.auth?.user?.id) {
    return;
  }

  trackOncePerSession(
    `crime-address-trial-expired:${page.props.auth.user.id}`,
    'crime_address_trial_expired',
    {
      pageType: 'crime_address',
      isAuthenticated: true,
      params: {
        page_path: '/crime-address',
      },
    },
  );
}, { immediate: true });
</script>
