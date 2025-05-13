<template>
    <footer class="bg-gray-800 text-gray-300 py-8 mt-auto">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div>
                    <h3 class="text-lg font-semibold text-white mb-3">Cityscope</h3>
                    <p class="text-sm">
                        Providing insights into your urban environment.
                        <!-- Short tagline or description -->
                    </p>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-white mb-3">Quick Links</h3>
                    <ul class="space-y-2">
                        <li>
                            <Link :href="route('map.index')" class="hover:text-white transition-colors duration-200">Home</Link>
                        </li>
                        <li>
                            <Link :href="route('subscription.index')" class="hover:text-white transition-colors duration-200">Subscription</Link>
                        </li>
                         <li>
                            <Link :href="route('crime-map')" class="hover:text-white transition-colors duration-200">Boston Crime Map</Link>
                        </li>
                        <li>
                            <Link :href="route('cases.indexnofilter')" class="hover:text-white transition-colors duration-200">Boston 311 Case Map</Link>
                        </li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-white mb-3">Legal & Support</h3>
                    <ul class="space-y-2">
                        <li>
                            <Link :href="route('about.us')" class="hover:text-white transition-colors duration-200">About Us</Link>
                        </li>
                        <li>
                            <Link :href="route('help.contact')" class="hover:text-white transition-colors duration-200">Help/Contact</Link>
                        </li>
                        <li>
                            <Link :href="route('privacy.policy')" class="hover:text-white transition-colors duration-200">Privacy Policy</Link>
                        </li>
                        <li>
                            <Link :href="route('terms.of.use')" class="hover:text-white transition-colors duration-200">Terms of Use</Link>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="mt-8 border-t border-gray-700 pt-6 text-center text-sm">
                <p>&copy; {{ new Date().getFullYear() }} AlcivarTech LLC. All rights reserved.</p>
                <p class="mt-1">Cityscope - Know Your City.</p>
            </div>
        </div>
    </footer>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<style scoped>
/* Additional specific styles can be added here if Tailwind classes are not sufficient */

p {
    margin: 0;
    color: #d1d5db; /* Tailwind gray-300 */
}
</style>
