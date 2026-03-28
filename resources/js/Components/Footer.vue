<template>
  <footer class="mt-auto border-t border-slate-200 bg-slate-950 text-slate-300">
    <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
      <div class="grid gap-10 lg:grid-cols-[1.2fr_2fr]">
        <div class="max-w-sm">
          <p class="text-sm font-semibold uppercase tracking-[0.22em] text-cyan-300">PublicDataWatch</p>
          <h2 class="mt-4 text-2xl font-black tracking-tight text-white">
            Crime around your address first. Deeper civic-data tools when you need them.
          </h2>
          <p class="mt-4 text-sm leading-7 text-slate-400">
            Search an address, read the local preview, and decide whether you want daily reports or the full map and scoring workflow.
          </p>
          <div class="mt-6 flex flex-wrap gap-3">
            <Link
              :href="route('crime-address.index')"
              class="inline-flex items-center rounded-2xl bg-cyan-400 px-4 py-3 text-sm font-semibold text-slate-950 transition hover:bg-cyan-300"
            >
              Start crime preview
            </Link>
            <Link
              :href="route('subscription.index')"
              class="inline-flex items-center rounded-2xl border border-white/15 px-4 py-3 text-sm font-semibold text-white transition hover:bg-white/5"
            >
              View pricing
            </Link>
          </div>
        </div>

        <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-5">
          <section v-for="section in footerSections" :key="section.title">
            <h3 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-200">{{ section.title }}</h3>
            <ul class="mt-4 space-y-3">
              <li v-for="link in section.links" :key="`${section.title}-${link.label}`">
                <Link :href="link.href" class="text-sm text-slate-400 transition hover:text-white">
                  {{ link.label }}
                </Link>
              </li>
            </ul>
          </section>
        </div>
      </div>

      <div class="mt-10 flex flex-col gap-2 border-t border-white/10 pt-6 text-sm text-slate-500 sm:flex-row sm:items-center sm:justify-between">
        <p>&copy; {{ new Date().getFullYear() }} AlcivarTech LLC. All rights reserved.</p>
        <p>PublicDataWatch helps people understand what is happening around an address before they commit to a place.</p>
      </div>
    </div>
  </footer>
</template>

<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { buildPublicNavigation } from '@/Utils/publicNavigation';

const page = usePage();
const footerSections = computed(() => buildPublicNavigation(route, Boolean(page.props.auth?.user)).footerSections);
</script>
