<script setup>
import { Link } from '@inertiajs/vue3'; // Or '@inertiajs/inertia-vue3'
</script>

<template>
  <div>
    <h1>Subscription Canceled</h1>
    <p>You have canceled the subscription process on Stripe's page.</p>
    <p><Link :href="route('map.index')">Go to Dashboard</Link></p> <!-- Or link back home -->
  </div>
</template>