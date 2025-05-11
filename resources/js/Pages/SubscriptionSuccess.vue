<script setup>
import { Link } from '@inertiajs/vue3'; // Or '@inertiajs/inertia-vue3'
// defineProps({
//   sessionId: String, // If you chose to pass it as a prop in the route
// });
// You could potentially fetch more session details here if needed,
// but remember the webhook is the definitive source for subscription status.
</script>

<template>
  <div>
    <h1>Subscription Process Complete!</h1>
    <p>Your payment was successful via Stripe Checkout. Your subscription status is being updated. This may take a moment as we receive confirmation from Stripe (via webhook!).</p>
    <p>You can check your profile page soon to see your active subscription.</p>
    <p><Link :href="route('map.index')">Go to Dashboard</Link></p> <!-- Or link back home -->
  </div>
</template>