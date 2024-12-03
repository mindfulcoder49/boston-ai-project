<template>
  <PageTemplate>
    <Head>
      <title>Crime Map</title>
    </Head>
    <CrimeMapComponent />
  </PageTemplate>
</template>

<script setup>
import CrimeMapComponent from '../Components/CrimeMapComponent.vue';
import PageTemplate from '@/Components/PageTemplate.vue';
import { Head } from '@inertiajs/vue3';
</script>

<style scoped>
#map {
  height: 70vh;
}
</style>
