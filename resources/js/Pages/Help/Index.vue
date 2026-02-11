<template>
    <PageTemplate>
        <Head title="Help Center - PublicDataWatch" />

        <div class="bg-gray-50 py-12 sm:py-16">
            <div class="container mx-auto px-4 sm:px-6 lg:px-8">
                <div class="max-w-4xl mx-auto">
                    <!-- Hero -->
                    <div class="text-center mb-12">
                        <h1 class="text-4xl font-bold tracking-tight text-gray-900 mb-4">
                            PublicDataWatch Help Center
                        </h1>
                        <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                            PublicDataWatch aggregates, analyzes, and visualizes public data from multiple cities to help you understand your urban environment. Find the guide that's right for you below.
                        </p>
                    </div>

                    <!-- Audience Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-12">
                        <!-- End Users -->
                        <Link href="/help/users" class="block bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow p-6 border border-gray-200">
                            <div class="flex items-start">
                                <div class="flex-shrink-0 bg-blue-100 rounded-lg p-3">
                                    <svg class="h-6 w-6 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <h2 class="text-xl font-semibold text-gray-900">End Users</h2>
                                    <p class="mt-1 text-gray-600">Learn how to use the interactive maps, AI reports, and all platform features.</p>
                                </div>
                            </div>
                        </Link>

                        <!-- Municipalities -->
                        <Link href="/help/municipalities" class="block bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow p-6 border border-gray-200">
                            <div class="flex items-start">
                                <div class="flex-shrink-0 bg-green-100 rounded-lg p-3">
                                    <svg class="h-6 w-6 text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0 0 12 9.75c-2.551 0-5.056.2-7.5.582V21" />
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <h2 class="text-xl font-semibold text-gray-900">Municipalities</h2>
                                    <p class="mt-1 text-gray-600">How PublicDataWatch can help your city with data transparency and civic engagement.</p>
                                </div>
                            </div>
                        </Link>

                        <!-- Researchers -->
                        <Link href="/help/researchers" class="block bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow p-6 border border-gray-200">
                            <div class="flex items-start">
                                <div class="flex-shrink-0 bg-purple-100 rounded-lg p-3">
                                    <svg class="h-6 w-6 text-purple-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.104v5.714a2.25 2.25 0 0 1-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 0 1 4.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0 1 12 15a9.065 9.065 0 0 0-6.23.693L5 14.5m14.8.8 1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0 1 12 21c-2.773 0-5.491-.235-8.135-.687-1.718-.293-2.3-2.379-1.067-3.61L5 14.5" />
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <h2 class="text-xl font-semibold text-gray-900">Researchers</h2>
                                    <p class="mt-1 text-gray-600">Statistical methodology, data sources, and analysis capabilities.</p>
                                </div>
                            </div>
                        </Link>

                        <!-- Investors -->
                        <Link href="/help/investors" class="block bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow p-6 border border-gray-200">
                            <div class="flex items-start">
                                <div class="flex-shrink-0 bg-yellow-100 rounded-lg p-3">
                                    <svg class="h-6 w-6 text-yellow-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <h2 class="text-xl font-semibold text-gray-900">Investors</h2>
                                    <p class="mt-1 text-gray-600">Platform overview, technology, market opportunity, and business model.</p>
                                </div>
                            </div>
                        </Link>
                    </div>

                    <!-- Quick Links -->
                    <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">Quick Links</h2>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <Link :href="route('help.contact')" class="flex items-center text-blue-600 hover:text-blue-800">
                                <svg class="h-5 w-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                                </svg>
                                Feedback & Support
                            </Link>
                            <Link :href="route('subscription.index')" class="flex items-center text-blue-600 hover:text-blue-800">
                                <svg class="h-5 w-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z" />
                                </svg>
                                Pricing & Plans
                            </Link>
                            <Link :href="route('about.us')" class="flex items-center text-blue-600 hover:text-blue-800">
                                <svg class="h-5 w-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                                </svg>
                                About Us
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </PageTemplate>
</template>

<script setup>
import PageTemplate from '@/Components/PageTemplate.vue';
import { Head, Link } from '@inertiajs/vue3';
</script>
