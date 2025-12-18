<template>
    <PageTemplate>
        <Head title="About PublicDataWatch - Understanding Your City" />

        <div class="bg-gray-50 py-12 sm:py-16">
            <div class="container mx-auto px-4 sm:px-6 lg:px-8">
                <div class="max-w-3xl mx-auto text-center mb-12">
                    <h1 class="text-4xl font-bold tracking-tight text-gray-900 sm:text-5xl">
                        About PublicDataWatch
                    </h1>
                    <p class="mt-6 text-xl text-gray-600">
                        Empowering communities with data-driven insights into their cities.
                    </p>
                </div>

                <div class="bg-white p-6 sm:p-10 rounded-lg shadow-xl prose prose-lg lg:prose-xl max-w-none">
                    <section class="mb-10">
                        <h2>Our Mission with PublicDataWatch</h2>
                        <p>
                            At PublicDataWatch, we believe that access to clear, timely, and comprehensive city data is essential for an engaged and informed community. Our mission is to empower residents, businesses, community organizations, and researchers by transforming complex public data into easily understandable and actionable insights.
                        </p>
                        <p>
                            We aim to provide a transparent window into the daily operations and evolving landscape of cities, fostering a greater understanding of neighborhood dynamics, civic engagement, and urban development. Whether you're looking to stay informed about safety in your area, track local development, or understand service requests, PublicDataWatch is designed to be your go-to resource.
                        </p>
                    </section>

                    <section class="mb-10">
                        <h2>What is PublicDataWatch?</h2>
                        <p>
                            PublicDataWatch is an innovative web platform that aggregates, visualizes, and analyzes publicly available data from cities. Key features include:
                        </p>
                        <ul class="list-disc pl-5 space-y-1">
                            <li><strong>Interactive Mapping:</strong> Explore crime incidents, 311 service requests, building permits, and property violations on a user-friendly map centered on your location of interest.</li>
                            <li><strong>AI-Powered Summaries:</strong> Leveraging advanced AI (like Google's Gemini), PublicDataWatch provides concise summaries of events and trends, making complex data more accessible.</li>
                            <li><strong>Personalized Reports:</strong> Subscribers can receive automated email reports tailored to their saved locations and preferences, delivered in multiple languages.</li>
                            <li><strong>Advanced Data Tools:</strong> For power users, we offer specialized maps with natural language querying for crime data and detailed search capabilities for 311 cases.</li>
                        </ul>
                        <p>
                            We are continuously working to expand our data sources and enhance the features of PublicDataWatch to provide even greater value to our users.
                        </p>
                    </section>

                    <section class="mb-10">
                        <h2>About AlcivarTech LLC</h2>
                        <p>
                            AlcivarTech LLC is the technology company proudly behind PublicDataWatch. Founded with a passion for urban innovation and data transparency, our core mission is to develop intelligent solutions that address modern urban challenges and enhance the quality of city life.
                        </p>
                        <p>
                            We specialize in data aggregation, geospatial analysis, and the application of artificial intelligence to create meaningful tools for communities. At AlcivarTech, we believe in the power of technology to foster understanding, drive positive change, and build smarter, more responsive, and more livable cities for everyone.
                        </p>
                         <!-- Optional: Add a sentence about your location or founding story if relevant -->
                    </section>

                    <section class="mb-10">
                        <h2>Our Values</h2>
                        <p>
                            Our work at AlcivarTech and on PublicDataWatch is guided by a commitment to:
                        </p>
                        <ul class="list-disc pl-5 space-y-1">
                            <li><strong>Transparency:</strong> Making public data truly public and understandable.</li>
                            <li><strong>Accessibility:</strong> Designing tools that are easy to use for everyone, regardless of technical expertise.</li>
                            <li><strong>Innovation:</strong> Continuously exploring new technologies and methodologies to provide cutting-edge insights.</li>
                            <li><strong>Community Focus:</strong> Building tools that serve the needs of local residents and stakeholders.</li>
                            <li><strong>Data Integrity:</strong> Striving for accuracy and responsible handling of information.</li>
                        </ul>
                    </section>

                    <section>
                        <h2>Get in Touch</h2>
                        <p>
                            We're always eager to hear from our users, potential partners, or anyone interested in learning more about PublicDataWatch and AlcivarTech LLC.
                        </p>
                        <p>
                            For support, feedback, or general inquiries, please visit our <Link :href="route('help.contact')" class="text-blue-600 hover:text-blue-800 underline">Help & Contact Page</Link> or email us directly at <a href="mailto:help@publicdatawatch.com" class="text-blue-600 hover:text-blue-800 underline">help@publicdatawatch.com</a>.
                        </p>
                    </section>
                </div>
            </div>
        </div>
    </PageTemplate>
</template>

<script setup>
import PageTemplate from '@/Components/PageTemplate.vue';
import { Head, Link } from '@inertiajs/vue3';

// Assuming you have a named route 'help.contact' for your Help & Contact page
// If not, adjust the :href in the Link component accordingly.
</script>

<style scoped>
/* Custom prose styles if needed, Tailwind's @tailwindcss/typography plugin handles a lot. */
.prose h2 {
    @apply text-2xl sm:text-3xl font-semibold text-gray-800 mt-8 mb-4 border-b pb-2;
}
.prose h3 { /* If you decide to add H3s */
     @apply text-xl sm:text-2xl font-semibold text-gray-700 mt-6 mb-2;
}
.prose p {
    @apply mb-4 leading-relaxed text-gray-600;
}
.prose ul {
    @apply list-disc pl-6 mb-4 space-y-1 text-gray-600;
}
.prose strong {
    @apply font-semibold;
}
</style>