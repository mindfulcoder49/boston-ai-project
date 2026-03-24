<template>
    <PageTemplate>
        <Head>
            <title>PublicDataWatch — Civic Data Intelligence for Your City</title>
            <meta name="description" content="PublicDataWatch aggregates crime data, 311 service requests, building permits, property violations, food inspections, and more across Boston, Cambridge, Everett, Chicago, San Francisco, Seattle, and Montgomery County MD. Explore interactive maps, AI-powered trend analysis, neighborhood scoring, and yearly comparisons." />
        </Head>

        <article class="home-page -mx-4 sm:-mx-6 lg:-mx-8">
            <!-- Hero Section -->
            <section class="relative py-20 md:py-28 overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-br from-slate-950 via-blue-950 to-indigo-950"></div>
                <div class="absolute inset-0 opacity-40" style="background: radial-gradient(ellipse at 20% 10%, rgba(56, 189, 248, 0.4) 0%, transparent 45%), radial-gradient(ellipse at 80% 80%, rgba(129, 140, 248, 0.3) 0%, transparent 45%);"></div>
                <!-- Subtle grid pattern -->
                <div class="absolute inset-0 opacity-[0.04]" style="background-image: url('data:image/svg+xml,<svg width=&quot;40&quot; height=&quot;40&quot; xmlns=&quot;http://www.w3.org/2000/svg&quot;><path d=&quot;M0 0h40v40H0z&quot; fill=&quot;none&quot; stroke=&quot;white&quot; stroke-width=&quot;0.5&quot;/></svg>');"></div>

                <div class="relative z-10 text-center px-4 max-w-4xl mx-auto">
                    <h1 class="text-4xl md:text-5xl lg:text-6xl font-extrabold text-white mb-6 tracking-tight leading-tight">
                        Public Data Intelligence
                        <span class="block text-transparent bg-clip-text bg-gradient-to-r from-cyan-300 via-blue-300 to-indigo-300">for Your City</span>
                    </h1>
                    <p class="text-base md:text-lg text-slate-300 max-w-2xl mx-auto mb-10 leading-relaxed">
                        Crime reports, 311 requests, building permits, property violations, and more
                        from {{ stats.cityCount }} cities — unified with AI-powered trend analysis,
                        neighborhood scoring, and interactive maps.
                    </p>

                    <!-- Address Search Bar -->
                    <div class="max-w-2xl mx-auto mb-10">
                        <p class="text-sm text-slate-400 mb-3 font-medium">Search an address to explore nearby data on the Radial Map</p>
                        <div class="hero-search-wrapper rounded-2xl p-2 flex flex-col sm:flex-row gap-2">
                            <div class="flex-1 relative">
                                <GoogleAddressSearch
                                    @address-selected="handleAddressSelected"
                                    :language_codes="['en-US']"
                                    placeholder_text="Enter your address..."
                                />
                            </div>
                            <button
                                @click="useCurrentLocation"
                                :disabled="geoLoading"
                                class="flex items-center justify-center gap-2 px-5 py-3 bg-blue-500 hover:bg-blue-400 text-white text-sm font-semibold rounded-xl transition-all whitespace-nowrap disabled:opacity-50 shadow-lg shadow-blue-500/25"
                            >
                                <svg v-if="!geoLoading" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 2a7 7 0 00-7 7c0 5.25 7 13 7 13s7-7.75 7-13a7 7 0 00-7-7z"/><circle cx="12" cy="9" r="2.5"/></svg>
                                <svg v-else class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                {{ geoLoading ? 'Locating...' : 'Use My Location' }}
                            </button>
                        </div>
                        <p v-if="geoError" class="text-red-400 text-sm mt-3">{{ geoError }}</p>
                    </div>

                    <div class="flex flex-col sm:flex-row justify-center gap-3">
                        <Link :href="route('data-map.combined')" class="inline-flex items-center justify-center gap-2 px-7 py-3.5 bg-white text-slate-900 font-bold rounded-xl hover:bg-slate-100 transition-all shadow-xl shadow-black/20 text-sm">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l5.447 2.724A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                            Full Data Map
                        </Link>
                        <Link :href="route('subscription.index')" class="inline-flex items-center justify-center gap-2 px-7 py-3.5 text-white font-bold rounded-xl border border-white/25 hover:bg-white/10 transition-all backdrop-blur-sm text-sm">
                            View Plans
                        </Link>
                    </div>
                </div>
            </section>

            <div class="px-4 sm:px-6 lg:px-8">
                <!-- Stats Bar -->
                <section class="py-10">
                    <div class="grid grid-cols-3 gap-4 md:gap-8">
                        <div class="glass-card rounded-2xl p-6 text-center">
                            <div class="text-3xl md:text-4xl font-extrabold text-slate-900">{{ stats.cityCount }}</div>
                            <div class="text-sm text-slate-500 mt-1 font-medium">Cities &amp; Regions</div>
                        </div>
                        <div class="glass-card rounded-2xl p-6 text-center">
                            <div class="text-3xl md:text-4xl font-extrabold text-slate-900">{{ stats.dataCategoryCount }}</div>
                            <div class="text-sm text-slate-500 mt-1 font-medium">Data Categories</div>
                        </div>
                        <div class="glass-card rounded-2xl p-6 text-center">
                            <div class="text-3xl md:text-4xl font-extrabold text-slate-900">{{ formattedTotalRecords }}</div>
                            <div class="text-sm text-slate-500 mt-1 font-medium">Total Records</div>
                        </div>
                    </div>
                </section>

                <!-- Cities We Cover -->
                <section class="py-10">
                    <h2 class="text-2xl md:text-3xl font-bold text-slate-900 mb-2 text-center">Cities We Cover</h2>
                    <p class="text-slate-500 text-center mb-8 max-w-lg mx-auto">Open data from across the country. Click a city to open its landing page and explore local datasets.</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                        <Link
                            v-for="city in cities"
                            :key="city.name"
                            :href="city.primaryUrl"
                            class="glass-card group rounded-2xl p-5 block hover:scale-[1.02] transition-all duration-200"
                        >
                            <div class="flex items-center gap-3 mb-3">
                                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white flex-shrink-0 shadow-lg shadow-blue-500/20">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                </div>
                                <div>
                                    <h3 class="font-bold text-slate-900 group-hover:text-blue-600 transition-colors">{{ city.name }}</h3>
                                    <p class="text-xs text-slate-400">{{ city.dataTypeCount }} data {{ city.dataTypeCount === 1 ? 'type' : 'types' }}</p>
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-1.5">
                                <span
                                    v-for="dataType in city.dataTypes"
                                    :key="dataType"
                                    class="text-xs px-2.5 py-0.5 rounded-full font-medium"
                                    :class="categoryBadgeClass(dataType)"
                                >
                                    {{ dataType }}
                                </span>
                            </div>
                        </Link>
                    </div>
                </section>

                <!-- Platform Features -->
                <section class="py-10">
                    <h2 class="text-2xl md:text-3xl font-bold text-slate-900 mb-2 text-center">Platform Features</h2>
                    <p class="text-slate-500 text-center mb-8 max-w-lg mx-auto">Powerful tools to explore, analyze, and understand the public data shaping your community.</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                        <Link
                            v-for="feature in features"
                            :key="feature.title"
                            :href="feature.route"
                            class="glass-card group rounded-2xl p-5 block hover:scale-[1.02] transition-all duration-200"
                        >
                            <div class="w-11 h-11 rounded-xl mb-4 flex items-center justify-center text-white shadow-lg" :class="feature.iconBg" :style="feature.iconShadow">
                                <component :is="feature.iconComponent" />
                            </div>
                            <h3 class="font-bold text-slate-900 mb-1.5 group-hover:text-blue-600 transition-colors">{{ feature.title }}</h3>
                            <p class="text-sm text-slate-500 leading-relaxed">{{ feature.description }}</p>
                        </Link>
                    </div>
                </section>

                <!-- Help Center / Audiences -->
                <section class="py-10">
                    <h2 class="text-2xl md:text-3xl font-bold text-slate-900 mb-2 text-center">Who Is This For?</h2>
                    <p class="text-slate-500 text-center mb-8 max-w-lg mx-auto">PublicDataWatch serves diverse audiences. Find the guide tailored to your needs.</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                        <Link
                            v-for="audience in audiences"
                            :key="audience.title"
                            :href="audience.route"
                            class="glass-card group rounded-2xl p-5 block hover:scale-[1.02] transition-all duration-200"
                        >
                            <div class="w-11 h-11 rounded-xl mb-4 flex items-center justify-center text-white shadow-lg" :class="audience.iconBg" :style="audience.iconShadow">
                                <component :is="audience.iconComponent" />
                            </div>
                            <h3 class="font-bold text-slate-900 mb-1.5 group-hover:text-blue-600 transition-colors">{{ audience.title }}</h3>
                            <p class="text-sm text-slate-500 leading-relaxed">{{ audience.description }}</p>
                        </Link>
                    </div>
                </section>

                <!-- Data Categories -->
                <section class="py-10">
                    <h2 class="text-2xl md:text-3xl font-bold text-slate-900 mb-2 text-center">Data Categories</h2>
                    <p class="text-slate-500 text-center mb-8 max-w-lg mx-auto">We ingest and normalize diverse public datasets. New data types appear automatically as cities expand.</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                        <div
                            v-for="category in dataCategories"
                            :key="category.name"
                            class="glass-card rounded-2xl p-5"
                        >
                            <div class="flex items-center gap-3 mb-2">
                                <div class="w-3 h-3 rounded-full shadow-sm" :class="categoryDotColor(category.name)"></div>
                                <h3 class="font-bold text-slate-900">{{ category.name }}</h3>
                            </div>
                            <p class="text-sm text-slate-500 leading-relaxed">{{ category.description }}</p>
                        </div>
                    </div>
                </section>
            </div>

            <!-- Bottom CTA -->
            <section class="relative py-16 overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-br from-slate-950 via-blue-950 to-indigo-950"></div>
                <div class="absolute inset-0 opacity-30" style="background: radial-gradient(ellipse at 60% 50%, rgba(56, 189, 248, 0.35) 0%, transparent 55%);"></div>
                <div class="relative z-10 text-center px-4">
                    <h2 class="text-2xl md:text-3xl font-bold text-white mb-3">Ready to explore your neighborhood?</h2>
                    <p class="text-slate-400 mb-8 max-w-xl mx-auto">
                        Dive into interactive maps, discover trends, and stay informed with data-driven insights.
                    </p>
                    <div class="flex flex-wrap justify-center gap-3">
                        <Link :href="route('map.index')" class="inline-flex items-center gap-2 px-6 py-3 bg-white text-slate-900 font-bold rounded-xl hover:bg-slate-100 transition-all text-sm shadow-xl shadow-black/20">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M22 12h-4M6 12H2M12 6V2M12 22v-4"/><circle cx="12" cy="12" r="3"/></svg>
                            Radial Map
                        </Link>
                        <Link :href="route('register')" class="inline-flex items-center gap-2 px-6 py-3 text-white font-semibold rounded-xl border border-white/25 hover:bg-white/10 transition-all text-sm backdrop-blur-sm">
                            Create Account
                        </Link>
                        <Link :href="route('subscription.index')" class="inline-flex items-center gap-2 px-6 py-3 text-white font-semibold rounded-xl border border-white/25 hover:bg-white/10 transition-all text-sm backdrop-blur-sm">
                            Pricing
                        </Link>
                        <Link :href="route('help.index')" class="inline-flex items-center gap-2 px-6 py-3 text-white font-semibold rounded-xl border border-white/25 hover:bg-white/10 transition-all text-sm backdrop-blur-sm">
                            Help Center
                        </Link>
                    </div>
                </div>
            </section>
        </article>
    </PageTemplate>
</template>

<script setup>
import { ref, computed, h } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import PageTemplate from '@/Components/PageTemplate.vue';
import GoogleAddressSearch from '@/Components/GoogleAddressSearch.vue';

const props = defineProps({
    cities: Array,
    dataCategories: Array,
    stats: Object,
});

// --- Address search & geolocation ---
const geoLoading = ref(false);
const geoError = ref(null);

function handleAddressSelected(coordinates) {
    if (coordinates && coordinates.lat && coordinates.lng) {
        router.visit(route('map.index', { lat: coordinates.lat, lng: coordinates.lng }));
    }
}

function useCurrentLocation() {
    if (!navigator.geolocation) {
        geoError.value = 'Geolocation is not supported by your browser.';
        return;
    }
    geoLoading.value = true;
    geoError.value = null;
    navigator.geolocation.getCurrentPosition(
        (position) => {
            geoLoading.value = false;
            router.visit(route('map.index', {
                lat: position.coords.latitude.toFixed(6),
                lng: position.coords.longitude.toFixed(6),
            }));
        },
        (error) => {
            geoLoading.value = false;
            geoError.value = error.code === 1
                ? 'Location access denied. Please allow location access in your browser settings.'
                : 'Unable to determine your location. Please try searching an address instead.';
        },
        { enableHighAccuracy: true, timeout: 10000 }
    );
}

// --- Formatting ---
const formattedTotalRecords = computed(() => {
    const total = props.stats.totalRecords;
    if (total >= 1_000_000) {
        return (total / 1_000_000).toFixed(1).replace(/\.0$/, '') + 'M+';
    }
    if (total >= 1_000) {
        return Math.floor(total / 1_000).toLocaleString() + 'K+';
    }
    return total.toLocaleString();
});

// --- Category colors ---
const dotColors = {
    'Crime': 'bg-red-500',
    '311 Case': 'bg-amber-500',
    'Building Permit': 'bg-blue-500',
    'Property Violation': 'bg-orange-500',
    'Food Inspection': 'bg-emerald-500',
    'Construction Off Hour': 'bg-violet-500',
    'Car Crash': 'bg-rose-600',
};
const badgeClasses = {
    'Crime': 'bg-red-50 text-red-700',
    '311 Case': 'bg-amber-50 text-amber-700',
    'Building Permit': 'bg-blue-50 text-blue-700',
    'Property Violation': 'bg-orange-50 text-orange-700',
    'Food Inspection': 'bg-emerald-50 text-emerald-700',
    'Construction Off Hour': 'bg-violet-50 text-violet-700',
    'Car Crash': 'bg-rose-50 text-rose-700',
};
function categoryDotColor(name) {
    return dotColors[name] || 'bg-slate-400';
}
function categoryBadgeClass(name) {
    return badgeClasses[name] || 'bg-slate-100 text-slate-600';
}

// --- SVG Icon Components (render functions, no deps needed) ---
const IconRadialMap = { render: () => h('svg', { class: 'w-5 h-5', fill: 'none', stroke: 'currentColor', 'stroke-width': '2', viewBox: '0 0 24 24' }, [h('circle', { cx: '12', cy: '12', r: '10' }), h('path', { d: 'M22 12h-4M6 12H2M12 6V2M12 22v-4' }), h('circle', { cx: '12', cy: '12', r: '3' })]) };
const IconFullMap = { render: () => h('svg', { class: 'w-5 h-5', fill: 'none', stroke: 'currentColor', 'stroke-width': '2', viewBox: '0 0 24 24' }, [h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l5.447 2.724A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7' })]) };
const IconAI = { render: () => h('svg', { class: 'w-5 h-5', fill: 'none', stroke: 'currentColor', 'stroke-width': '2', viewBox: '0 0 24 24' }, [h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456z' })]) };
const IconTrends = { render: () => h('svg', { class: 'w-5 h-5', fill: 'none', stroke: 'currentColor', 'stroke-width': '2', viewBox: '0 0 24 24' }, [h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M2 20h20M5 17l4-6 4 3 6-9' })]) };
const IconYearly = { render: () => h('svg', { class: 'w-5 h-5', fill: 'none', stroke: 'currentColor', 'stroke-width': '2', viewBox: '0 0 24 24' }, [h('rect', { x: '3', y: '4', width: '18', height: '18', rx: '2' }), h('path', { d: 'M16 2v4M8 2v4M3 10h18' })]) };
const IconScore = { render: () => h('svg', { class: 'w-5 h-5', fill: 'none', stroke: 'currentColor', 'stroke-width': '2', viewBox: '0 0 24 24' }, [h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z' })]) };
const IconCommunity = { render: () => h('svg', { class: 'w-5 h-5', fill: 'none', stroke: 'currentColor', 'stroke-width': '2', viewBox: '0 0 24 24' }, [h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z' })]) };
const IconMetrics = { render: () => h('svg', { class: 'w-5 h-5', fill: 'none', stroke: 'currentColor', 'stroke-width': '2', viewBox: '0 0 24 24' }, [h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M3 3v18h18M7 16v-3m4 3v-7m4 7v-5m4 5V8' })]) };

// Audience icons
const IconUser = { render: () => h('svg', { class: 'w-5 h-5', fill: 'none', stroke: 'currentColor', 'stroke-width': '2', viewBox: '0 0 24 24' }, [h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z' })]) };
const IconMunicipality = { render: () => h('svg', { class: 'w-5 h-5', fill: 'none', stroke: 'currentColor', 'stroke-width': '2', viewBox: '0 0 24 24' }, [h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0012 9.75c-2.551 0-5.056.2-7.5.582V21' })]) };
const IconResearcher = { render: () => h('svg', { class: 'w-5 h-5', fill: 'none', stroke: 'currentColor', 'stroke-width': '2', viewBox: '0 0 24 24' }, [h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M4.26 10.147a60.438 60.438 0 00-.491 6.347A48.62 48.62 0 0112 20.904a48.62 48.62 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.636 50.636 0 00-2.658-.813A59.906 59.906 0 0112 3.493a59.903 59.903 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0112 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.443m-7.007 11.55A5.981 5.981 0 006.75 15.75v-1.5' })]) };
const IconInvestor = { render: () => h('svg', { class: 'w-5 h-5', fill: 'none', stroke: 'currentColor', 'stroke-width': '2', viewBox: '0 0 24 24' }, [h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z' })]) };

const features = [
    {
        title: 'Radial Map',
        description: 'Search any address and see all nearby public data within a customizable radius.',
        route: route('map.index'),
        iconBg: 'bg-gradient-to-br from-cyan-500 to-blue-600',
        iconShadow: 'box-shadow: 0 4px 14px rgba(6, 182, 212, 0.35)',
        iconComponent: IconRadialMap,
    },
    {
        title: 'Full Data Map',
        description: 'View entire datasets plotted on an interactive city-wide map with filtering.',
        route: route('data-map.combined'),
        iconBg: 'bg-gradient-to-br from-blue-500 to-indigo-600',
        iconShadow: 'box-shadow: 0 4px 14px rgba(59, 130, 246, 0.35)',
        iconComponent: IconFullMap,
    },
    {
        title: 'AI Reports',
        description: 'Generate AI-powered location reports that analyze patterns and provide insights.',
        route: route('subscription.index'),
        iconBg: 'bg-gradient-to-br from-violet-500 to-purple-600',
        iconShadow: 'box-shadow: 0 4px 14px rgba(139, 92, 246, 0.35)',
        iconComponent: IconAI,
    },
    {
        title: 'Trend Analysis',
        description: 'Statistical trend reports showing how data categories change over time.',
        route: route('trends.index'),
        iconBg: 'bg-gradient-to-br from-emerald-500 to-teal-600',
        iconShadow: 'box-shadow: 0 4px 14px rgba(16, 185, 129, 0.35)',
        iconComponent: IconTrends,
    },
    {
        title: 'Yearly Comparisons',
        description: 'Compare year-over-year data to spot emerging patterns and shifts.',
        route: route('yearly-comparisons.index'),
        iconBg: 'bg-gradient-to-br from-amber-500 to-orange-600',
        iconShadow: 'box-shadow: 0 4px 14px rgba(245, 158, 11, 0.35)',
        iconComponent: IconYearly,
    },
    {
        title: 'Neighborhood Scores',
        description: 'Composite scoring of neighborhoods based on multiple data dimensions.',
        route: route('scoring-reports.index'),
        iconBg: 'bg-gradient-to-br from-yellow-400 to-amber-500',
        iconShadow: 'box-shadow: 0 4px 14px rgba(250, 204, 21, 0.35)',
        iconComponent: IconScore,
    },
    {
        title: 'Community Maps',
        description: 'Save and share custom map views with the community.',
        route: route('saved-maps.index'),
        iconBg: 'bg-gradient-to-br from-pink-500 to-rose-600',
        iconShadow: 'box-shadow: 0 4px 14px rgba(236, 72, 153, 0.35)',
        iconComponent: IconCommunity,
    },
    {
        title: 'Data Metrics',
        description: 'Detailed statistics and freshness information for every data source.',
        route: route('data.metrics'),
        iconBg: 'bg-gradient-to-br from-slate-500 to-slate-700',
        iconShadow: 'box-shadow: 0 4px 14px rgba(100, 116, 139, 0.35)',
        iconComponent: IconMetrics,
    },
];

const audiences = [
    {
        title: 'End Users',
        description: 'Learn how to use interactive maps, AI reports, and all platform features to understand your neighborhood.',
        route: route('help.users'),
        iconBg: 'bg-gradient-to-br from-blue-500 to-blue-700',
        iconShadow: 'box-shadow: 0 4px 14px rgba(59, 130, 246, 0.35)',
        iconComponent: IconUser,
    },
    {
        title: 'Municipalities',
        description: 'See how PublicDataWatch helps your city with data transparency and civic engagement.',
        route: route('help.municipalities'),
        iconBg: 'bg-gradient-to-br from-emerald-500 to-emerald-700',
        iconShadow: 'box-shadow: 0 4px 14px rgba(16, 185, 129, 0.35)',
        iconComponent: IconMunicipality,
    },
    {
        title: 'Researchers',
        description: 'Access multi-city datasets, trend analysis, and statistical tools for academic and policy research.',
        route: route('help.researchers'),
        iconBg: 'bg-gradient-to-br from-indigo-500 to-indigo-700',
        iconShadow: 'box-shadow: 0 4px 14px rgba(99, 102, 241, 0.35)',
        iconComponent: IconResearcher,
    },
    {
        title: 'Investors',
        description: 'Evaluate neighborhoods with composite scoring, crime trends, and development activity data.',
        route: route('help.investors'),
        iconBg: 'bg-gradient-to-br from-amber-500 to-amber-700',
        iconShadow: 'box-shadow: 0 4px 14px rgba(245, 158, 11, 0.35)',
        iconComponent: IconInvestor,
    },
];
</script>

<style scoped>
.glass-card {
    background: rgba(255, 255, 255, 0.55);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.7);
    box-shadow:
        0 8px 32px rgba(0, 0, 0, 0.06),
        0 1px 2px rgba(0, 0, 0, 0.04),
        inset 0 1px 0 rgba(255, 255, 255, 0.8);
}
.glass-card:hover {
    background: rgba(255, 255, 255, 0.75);
    box-shadow:
        0 12px 40px rgba(0, 0, 0, 0.1),
        0 2px 4px rgba(0, 0, 0, 0.06),
        inset 0 1px 0 rgba(255, 255, 255, 0.9);
}

.hero-search-wrapper {
    background: rgba(255, 255, 255, 0.07);
    backdrop-filter: blur(24px);
    -webkit-backdrop-filter: blur(24px);
    border: 1px solid rgba(255, 255, 255, 0.15);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2), inset 0 1px 0 rgba(255, 255, 255, 0.1);
}

/* Override GoogleAddressSearch input for the hero context */
.hero-search-wrapper :deep(input) {
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    color: white;
    border-radius: 0.75rem;
    padding: 0.75rem 1rem;
    font-size: 0.9375rem;
    width: 100%;
    transition: all 0.2s;
}
.hero-search-wrapper :deep(input::placeholder) {
    color: rgba(255, 255, 255, 0.45);
}
.hero-search-wrapper :deep(input:focus) {
    outline: none;
    border-color: rgba(255, 255, 255, 0.4);
    background: rgba(255, 255, 255, 0.15);
    box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.2);
}
.hero-search-wrapper :deep(ul) {
    background: #0f172a;
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 0.75rem;
    margin-top: 0.375rem;
    overflow: hidden;
}
.hero-search-wrapper :deep(li) {
    color: #e2e8f0;
    padding: 0.625rem 1rem;
}
.hero-search-wrapper :deep(li:hover) {
    background: rgba(255, 255, 255, 0.08);
}
</style>
