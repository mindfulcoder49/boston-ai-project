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
                        Helping people understand what is happening around an address before they commit to a place.
                    </p>
                </div>

                <div class="bg-white p-6 sm:p-10 rounded-lg shadow-xl prose prose-lg lg:prose-xl max-w-none">
                    <section class="mb-10">
                        <h2>Our Mission with PublicDataWatch</h2>
                        <p>
                            At PublicDataWatch, we believe public data is only useful when regular people can actually act on it. Our mission is to turn complex civic records into clear, practical neighborhood context for residents, businesses, reporters, researchers, and local decision-makers.
                        </p>
                        <p>
                            The product starts with one address and one practical question: what is happening around this place right now? From there, users can move into city pages, deeper maps, trend reports, neighborhood scoring, and recurring reports when they need more context.
                        </p>
                    </section>

                    <section class="mb-10">
                        <h2>What is PublicDataWatch?</h2>
                        <p>
                            PublicDataWatch is a civic-data platform built around address-first local awareness. Key features include:
                        </p>
                        <ul class="list-disc pl-5 space-y-1">
                            <li><strong>Crime preview around one address:</strong> A stripped-down first answer with incidents, readable summary, and supporting context.</li>
                            <li><strong>City and region landing pages:</strong> Region-specific pages that reflect what data is actually available in Boston, Everett, Chicago, San Francisco, New York, Montgomery County, Seattle, and beyond.</li>
                            <li><strong>Interactive maps and analysis tools:</strong> Full map workflows, trends, yearly comparisons, and neighborhood scoring for deeper investigation.</li>
                            <li><strong>Recurring reports:</strong> Ongoing report workflows for people who want repeated updates instead of one-off browsing.</li>
                        </ul>
                        <p>
                            We are continuously working to expand regional coverage, improve clarity, and make local public data easier to use without pretending the data says more than it does.
                        </p>
                    </section>

                    <section class="mb-10">
                        <h2>About AlcivarTech LLC</h2>
                        <p>
                            AlcivarTech LLC is the technology company behind PublicDataWatch. Its work focuses on making complex civic and geospatial information easier to understand and easier to act on.
                        </p>
                        <p>
                            We specialize in data aggregation, geospatial analysis, and the careful use of AI to build tools that make local public information more legible to non-technical users.
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
