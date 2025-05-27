<template>
  <div class="bg-gradient-to-r from-white to-sky-50 border border-sky-200 rounded-lg p-5 mb-6 shadow">
    <div class="flex flex-col md:flex-row md:justify-between md:items-center">
      <div>
        <h3 class="text-xl font-bold text-sky-900">Your Plan: {{ planLabel }}</h3>
        <p class="text-sm text-sky-700 mt-1">Here’s what you can see:</p>
      </div>
      <div v-if="promptAction" class="mt-4 md:mt-0 flex flex-wrap gap-2 items-center">
        <Link
          v-if="!isAuthenticated"
          :href="route('register')"
          class="inline-block bg-sky-600 text-white text-sm px-4 py-2 rounded-md hover:bg-sky-700"
        >Sign Up</Link>
        <a
          v-if="!isAuthenticated"
          :href="route('socialite.redirect', 'google') + '?redirect_to=' + route('map.index')"
          class="inline-flex items-center justify-center bg-white text-sky-700 border border-sky-300 text-sm px-4 py-2 rounded-md hover:bg-sky-50"
        >
          <img class="h-4 w-4 md:mr-2" src="https://upload.wikimedia.org/wikipedia/commons/c/c1/Google_%22G%22_logo.svg" alt="Google logo">
          <span class="hidden md:inline">Login with Google</span>
        </a>
        <Link
          v-if="!isAuthenticated"
          :href="route('login')"
          class="inline-block bg-slate-200 text-slate-800 text-sm px-4 py-2 rounded-md hover:bg-slate-300"
        >Log In</Link>
        <Link
          v-if="isAuthenticated && !isPro"
          :href="route('subscription.index')"
          class="inline-block bg-green-500 text-white text-sm px-4 py-2 rounded-md hover:bg-green-600"
        >Upgrade</Link>
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-5 text-sm text-sky-800">
      <div class="p-3 bg-white border rounded">
        <h4 class="font-semibold mb-1">Quick Map</h4>
        <p>{{ radialMapText }}</p>
      </div>
      <div class="p-3 bg-white border rounded">
        <h4 class="font-semibold mb-1">Full Map</h4>
        <p>{{ fullMapText }}</p>
      </div>
    </div>

    <p v-if="isPro" class="mt-4 text-green-700 font-medium">
      🎉 You have full historical access!
    </p>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { usePage, Link } from '@inertiajs/vue3'

const page = usePage()
const user = computed(() => page.props.auth?.user)
const isAuthenticated = computed(() => !!user.value)
const plan = computed(() => page.props.auth?.currentPlan?.name || (isAuthenticated.value ? 'Registered User' : 'Guest'))
const isPro = computed(() => plan.value === 'Pro Insights')

const planLabel = computed(() => {
  if (plan.value === 'Resident Awareness') return 'Resident Awareness'
  if (plan.value === 'Pro Insights') return 'Pro Insights'
  if (plan.value === 'Registered User') return 'Registered User'
  return 'Guest'
})

const fmt = (n, u) => {
  const now = new Date(), start = new Date(now)
  if (u === 'days') start.setDate(now.getDate() - n)
  else if (u === 'months') start.setMonth(now.getMonth() - n)
  const d1 = start.toLocaleDateString(), d2 = now.toLocaleDateString()
  return `${d1} – ${d2}`
}

const radialMapText = computed(() => {
  if (!isAuthenticated.value) return `Last 7 days of data (${fmt(7,'days')})`
  if (plan.value === 'Registered User') return `Last 14 days (${fmt(14,'days')})`
  if (plan.value === 'Resident Awareness') return `Last 21 days (${fmt(21,'days')})`
  if (isPro.value) return `Last 31 days (${fmt(31,'days')})`
  return 'Data access info unavailable'
})

const fullMapText = computed(() => {
  if (!isAuthenticated.value) return 'Log in to unlock more data.'
  if (plan.value === 'Registered User') return `Last 2 months (${fmt(2,'months')})`
  if (plan.value === 'Resident Awareness') return `Last 6 months (${fmt(6,'months')})`
  if (isPro.value) return 'All available history'
  return 'Data access info unavailable'
})

const promptAction = computed(() => !isPro.value)
</script>

<style scoped>
/* Tailwind overall; no extra styles needed */
</style>
