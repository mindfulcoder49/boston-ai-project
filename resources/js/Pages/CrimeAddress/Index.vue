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
                  show_submit_button
                  submit_button_label="Search address"
                  submit_button_class="crime-address-search-submit"
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
          <p v-if="coverageForm.requested_address" class="mt-3 text-sm text-amber-900/80">
            Requested address: <span class="font-semibold">{{ coverageForm.requested_address }}</span>
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
              {{ coverageSubmitting ? 'Submitting…' : 'Notify me if coverage expands' }}
            </button>
          </form>
          <p class="mt-3 text-sm leading-6 text-slate-600">
            We will only use your email for coverage updates about this area.
          </p>

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
                  <p class="text-xs uppercase tracking-[0.18em] text-slate-500">Area Score</p>
                  <p
                    v-if="scoreValue !== null"
                    class="mt-2 text-3xl font-black text-slate-900"
                    data-testid="crime-address-neighborhood-score-value"
                  >
                    {{ Number(scoreValue).toFixed(1) }}
                  </p>
                  <p v-if="scoreValue !== null" class="mt-1 text-sm font-semibold text-slate-700">
                    {{ scoreHeadline }}
                  </p>
                  <p class="mt-2 text-xs leading-5 text-slate-500">
                    Covers the area around this address, not one exact building.
                  </p>
                  <p
                    v-if="scoreValue === null"
                    class="mt-1 text-sm text-slate-500"
                    data-testid="crime-address-neighborhood-score-unavailable"
                  >
                    {{ scoreAvailabilityLabel }}
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
                    <p class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-500">What This Score Suggests</p>
                    <h3 class="mt-2 text-xl font-bold text-slate-900">
                      {{ scoreHeadline }}
                    </h3>
                    <p class="mt-2 text-sm leading-7 text-slate-600">
                      {{ scoreContextSummary }}
                    </p>
                  </div>
                  <div class="rounded-2xl bg-white px-4 py-3 text-sm text-slate-500">
                    <p class="font-semibold text-slate-900">{{ scoreSourceLabel }}</p>
                    <p class="mt-1">{{ scoreMethodologyText }}</p>
                  </div>
                </div>

                <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                  <div class="rounded-2xl bg-white p-4">
                    <p class="text-xs uppercase tracking-[0.16em] text-slate-400">Compared With The City</p>
                    <p class="mt-2 text-xl font-black text-slate-900">{{ scoreCityComparisonLabel }}</p>
                    <p class="mt-1 text-sm text-slate-500">How this area looks next to the rest of {{ preview.matched_city_name }}</p>
                  </div>
                  <div class="rounded-2xl bg-white p-4">
                    <p class="text-xs uppercase tracking-[0.16em] text-slate-400">Compared With Nearby Areas</p>
                    <p class="mt-2 text-xl font-black text-slate-900">{{ scoreNearbyComparisonLabel }}</p>
                    <p class="mt-1 text-sm text-slate-500">How it compares with the surrounding blocks</p>
                  </div>
                  <div class="rounded-2xl bg-white p-4">
                    <p class="text-xs uppercase tracking-[0.16em] text-slate-400">History Window</p>
                    <p class="mt-2 text-xl font-black text-slate-900">{{ scoreHistoryWindowLabel }}</p>
                    <p class="mt-1 text-sm text-slate-500">How much history is behind this score</p>
                  </div>
                </div>

                <div class="mt-5 grid gap-5 lg:grid-cols-[1fr_auto] lg:items-start">
                  <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-500">What Is Pushing This Score</p>
                    <ul v-if="scoreTopDrivers.length" class="mt-3 space-y-3">
                      <li
                        v-for="driver in scoreTopDrivers"
                        :key="driver.label"
                        class="flex items-center justify-between rounded-2xl bg-white px-4 py-3"
                        >
                        <div>
                          <p class="text-sm font-semibold text-slate-900">{{ driver.label }}</p>
                          <p class="text-xs text-slate-500">{{ driver.share_percent !== null ? `${driver.share_percent}% of this score` : 'Driver contribution' }}</p>
                        </div>
                        <p class="text-sm font-bold text-slate-700">{{ Number(driver.weighted_score).toFixed(1) }}</p>
                      </li>
                    </ul>
                    <p v-else class="mt-3 text-sm leading-6 text-slate-600">
                      The score loaded, but a driver breakdown was not available for this area yet.
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
                v-if="preview.trend_context?.summary?.status === 'ok' || deferredContextLoading"
                class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm"
                data-testid="crime-address-trend-context"
              >
                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-400">City Trend Context</p>
                <h3 class="mt-2 text-xl font-bold text-slate-900">{{ trendHeadline }}</h3>
                <p class="mt-3 text-sm leading-6 text-slate-600">{{ trendBody }}</p>
                <div v-if="preview.trend_context?.summary?.status === 'ok'" class="mt-4 grid gap-3 sm:grid-cols-3">
                  <div class="rounded-2xl bg-slate-50 p-4">
                    <p class="text-xs uppercase tracking-[0.16em] text-slate-400">Recent Spikes</p>
                    <p class="mt-2 text-2xl font-black text-slate-900">{{ preview.trend_context.summary.anomaly_count }}</p>
                  </div>
                  <div class="rounded-2xl bg-slate-50 p-4">
                    <p class="text-xs uppercase tracking-[0.16em] text-slate-400">Longer Patterns</p>
                    <p class="mt-2 text-2xl font-black text-slate-900">{{ preview.trend_context.summary.trend_count }}</p>
                  </div>
                  <div class="rounded-2xl bg-slate-50 p-4">
                    <p class="text-xs uppercase tracking-[0.16em] text-slate-400">Main Categories</p>
                    <p class="mt-2 text-base font-black text-slate-900">{{ trendTopCategoriesLabel }}</p>
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
import { PUBLIC_CITY_ROUTE_NAME_BY_KEY } from '@/Utils/publicNavigation';

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
const deferredContextLoading = ref(false);
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
const scoreAvailabilityLabel = computed(() => {
  if (deferredContextLoading.value || scoreLoading.value) {
    return 'Checking how this area compares…';
  }

  return 'No area score available yet';
});
const scoreHeadline = computed(() => {
  const percentile = Number(scoreSummary.value?.percentile ?? NaN);

  if (Number.isNaN(percentile)) {
    return deferredContextLoading.value || scoreLoading.value
      ? 'Checking whether this area feels quieter, typical, or busier'
      : 'Area score unavailable';
  }

  if (percentile >= 75) {
    return 'Higher activity than most areas in this city';
  }

  if (percentile <= 25) {
    return 'Lower activity than most areas in this city';
  }

  return 'About typical for this city';
});
const scoreCityComparisonLabel = computed(() => {
  const percentile = Number(scoreSummary.value?.percentile ?? NaN);
  const city = preview.value?.matched_city_name ?? 'this city';

  if (Number.isNaN(percentile)) {
    return deferredContextLoading.value || scoreLoading.value ? 'Comparing with the city…' : 'City comparison unavailable';
  }

  if (percentile >= 75) {
    return `Higher than most of ${city}`;
  }

  if (percentile >= 60) {
    return `Higher than many parts of ${city}`;
  }

  if (percentile <= 25) {
    return `Lower than most of ${city}`;
  }

  if (percentile <= 40) {
    return `Lower than many parts of ${city}`;
  }

  return `Around the middle of ${city}`;
});
const scoreNearbyComparisonLabel = computed(() => {
  if (!scoreSummary.value?.nearby_peers?.available) {
    return deferredContextLoading.value || scoreLoading.value
      ? 'Checking nearby areas…'
      : 'Nearby comparison unavailable';
  }

  const delta = Number(scoreSummary.value.nearby_peers?.current_vs_median ?? 0);
  if (Math.abs(delta) < 2) {
    return 'About the same as nearby areas';
  }

  if (delta > 0) {
    return delta >= 5 ? 'Clearly higher than nearby areas' : 'A little higher than nearby areas';
  }

  return delta <= -5 ? 'Clearly lower than nearby areas' : 'A little lower than nearby areas';
});
const scoreHistoryWindowLabel = computed(() => {
  const weeks = scoreSummary.value?.methodology?.analysis_period_weeks;
  if (weeks) {
    return `About the last ${weeks} weeks`;
  }

  return deferredContextLoading.value || scoreLoading.value ? 'Loading history window…' : 'History window unavailable';
});
const scoreSourceLabel = computed(() => {
  const source = scoreSummary.value?.methodology?.source;
  if (source === 'stage4_fallback') {
    return 'Preview area score';
  }

  if (source === 'stage6_artifact') {
    return 'Historical area score';
  }

  return deferredContextLoading.value || scoreLoading.value ? 'Loading score source…' : 'Score source unavailable';
});
const scoreMethodologyText = computed(() => {
  if (!scoreSummary.value?.methodology) {
    return deferredContextLoading.value || scoreLoading.value
      ? 'Comparing this address with the rest of the city and nearby areas.'
      : 'This score covers the area around the address, not one exact parcel.';
  }

  const weeks = scoreSummary.value.methodology.analysis_period_weeks;

  if (weeks) {
    return `Built from roughly ${weeks} weeks of history in the area around this address.`;
  }

  return 'Built from the surrounding area around this address, not one exact building.';
});
const scoreContextFallbackMessage = computed(() => {
  if (deferredContextLoading.value || scoreLoading.value) {
    return 'We are comparing this area with the rest of the city and the nearby blocks now.';
  }

  return 'When an area score is available, this section explains in plain language whether this address looks quieter, typical, or busier than the rest of the city and nearby areas.';
});
const scoreContextSummary = computed(() => {
  if (scoreValue.value === null) {
    return scoreContextFallbackMessage.value;
  }

  return `${scoreCityComparisonLabel.value}. ${scoreNearbyComparisonLabel.value}.`;
});
const trendSummary = computed(() => preview.value?.trend_context?.summary ?? null);
const trendTopCategoriesLabel = computed(() => {
  const categories = trendSummary.value?.top_categories ?? [];
  return categories.length ? categories.slice(0, 3).join(', ') : 'No specific categories called out';
});
const trendHeadline = computed(() => {
  const city = preview.value?.matched_city_name ?? 'This city';

  if (deferredContextLoading.value && (!trendSummary.value || trendSummary.value.status !== 'ok')) {
    return `Checking ${city.toLowerCase()} trend context`;
  }

  if (!trendSummary.value || trendSummary.value.status !== 'ok') {
    return 'City trend context unavailable';
  }

  if ((trendSummary.value.anomaly_count ?? 0) > 0 && (trendSummary.value.trend_count ?? 0) > 0) {
    return `${city} is showing some unusual crime patterns right now`;
  }

  if ((trendSummary.value.anomaly_count ?? 0) > 0) {
    return `${city} has a few recent spikes worth watching`;
  }

  if ((trendSummary.value.trend_count ?? 0) > 0) {
    return `${city} has a few longer-running patterns worth watching`;
  }

  return `${city} does not show strong citywide crime signals right now`;
});
const trendBody = computed(() => {
  if (!trendSummary.value || trendSummary.value.status !== 'ok') {
    return deferredContextLoading.value
      ? 'Adding citywide trend context to this preview now.'
      : 'No citywide trend summary is available for this address yet.';
  }

  return trendTopCategoriesLabel.value !== 'No specific categories called out'
    ? `The strongest recent categories are ${trendTopCategoriesLabel.value}.`
    : 'The citywide trend summary did not call out a specific category mix.';
});
const cityLandingHref = computed(() => {
  const routeName = PUBLIC_CITY_ROUTE_NAME_BY_KEY[preview.value?.matched_city_key];
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
      .map((entry) => entry.label)
      .join(', ');

    sections.push({
      title: 'What the area score suggests',
      body: topDrivers
        ? `This address currently scores ${Number(scoreValue.value).toFixed(1)}. ${scoreCityComparisonLabel.value}. ${scoreNearbyComparisonLabel.value}. The biggest factors behind the score are ${topDrivers}.`
        : `This address currently scores ${Number(scoreValue.value).toFixed(1)}. ${scoreCityComparisonLabel.value}. ${scoreNearbyComparisonLabel.value}.`,
    });
  }

  const analysisDetails = scoreContext.value?.analysis_details ?? [];
  if (analysisDetails.length > 0) {
    const secondaryGroups = [...new Set(analysisDetails.map((item) => item.secondary_group).filter(Boolean))].slice(0, 4);
    sections.push({
      title: 'Address-specific pattern check',
      body: secondaryGroups.length
        ? `The latest address-area analysis flags recent activity in ${secondaryGroups.join(', ')} around this location.`
        : 'The latest address-area analysis returned additional pattern context for this location.',
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
  deferredContextLoading.value = false;
  scoreLoading.value = false;
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
  deferredContextLoading.value = false;
  scoreLoading.value = false;

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

    deferredContextLoading.value = true;
    void loadDeferredContext(response.data.address, response.data.latitude, response.data.longitude);
  } catch (error) {
    unsupportedState.value = {
      supported: false,
      message: 'We could not generate the preview for that address.',
    };
  } finally {
    isLoadingPreview.value = false;
  }
}

async function loadDeferredContext(address, latitude, longitude) {
  try {
    const response = await axios.post(route('crime-address.context'), {
      address,
      latitude,
      longitude,
    });

    if (response.data.supported === false || !preview.value) {
      return;
    }

    preview.value = {
      ...preview.value,
      ...response.data,
      preview_report: [
        ...(preview.value.preview_report ?? []),
        ...(response.data.preview_report ?? []),
      ],
    };

    if (preview.value?.score_report?.resolution) {
      void loadScoreContext();
    }
  } catch (error) {
    // Leave the incident preview visible even if deferred context fails.
  } finally {
    deferredContextLoading.value = false;
  }
}

async function loadScoreContext() {
  if (!preview.value?.score_report?.resolution) {
    scoreLoading.value = false;
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

<style scoped>
.crime-address-search-submit {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 1rem;
  border: 1px solid #0f172a;
  background: #0f172a;
  color: white;
  padding: 0.85rem 1rem;
  font-size: 0.95rem;
  font-weight: 700;
  transition: background-color 0.15s ease, opacity 0.15s ease;
}

.crime-address-search-submit:hover:enabled {
  background: #334155;
}

.crime-address-search-submit:disabled {
  opacity: 0.55;
  cursor: not-allowed;
}
</style>
