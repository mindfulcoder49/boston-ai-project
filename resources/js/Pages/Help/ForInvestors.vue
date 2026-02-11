<template>
    <PageTemplate>
        <Head title="For Investors - PublicDataWatch" />

        <div class="bg-gray-50 py-12 sm:py-16">
            <div class="container mx-auto px-4 sm:px-6 lg:px-8">
                <div class="max-w-4xl mx-auto">
                    <!-- Breadcrumb -->
                    <nav class="mb-6 text-sm text-gray-500">
                        <Link :href="route('help.index')" class="hover:text-blue-600">Help Center</Link>
                        <span class="mx-2">/</span>
                        <span class="text-gray-900">For Investors</span>
                    </nav>

                    <h1 class="text-4xl font-bold tracking-tight text-gray-900 mb-8">
                        For Investors
                    </h1>

                    <div class="flex flex-col lg:flex-row gap-8">
                        <!-- Table of Contents -->
                        <aside class="lg:w-64 flex-shrink-0">
                            <div class="lg:sticky lg:top-8 bg-white rounded-lg shadow-md p-4 border border-gray-200">
                                <h2 class="font-semibold text-gray-900 mb-3">Contents</h2>
                                <nav class="space-y-1 text-sm">
                                    <a href="#overview" class="block text-blue-600 hover:text-blue-800 py-1">Platform Overview</a>
                                    <a href="#market" class="block text-blue-600 hover:text-blue-800 py-1">Market Opportunity</a>
                                    <a href="#technology" class="block text-blue-600 hover:text-blue-800 py-1">Technology Stack</a>
                                    <a href="#business-model" class="block text-blue-600 hover:text-blue-800 py-1">Business Model</a>
                                    <a href="#capabilities" class="block text-blue-600 hover:text-blue-800 py-1">Product Capabilities</a>
                                    <a href="#scalability" class="block text-blue-600 hover:text-blue-800 py-1">Scalability & Growth</a>
                                    <a href="#coverage" class="block text-blue-600 hover:text-blue-800 py-1">Current Coverage</a>
                                    <a href="#advantages" class="block text-blue-600 hover:text-blue-800 py-1">Competitive Advantages</a>
                                    <a href="#contact" class="block text-blue-600 hover:text-blue-800 py-1">Contact</a>
                                </nav>
                            </div>
                        </aside>

                        <!-- Main Content -->
                        <div class="flex-1 min-w-0">
                            <div class="bg-white rounded-lg shadow-md p-6 sm:p-8 border border-gray-200 prose prose-lg max-w-none">

                                <section id="overview">
                                    <h2>Platform Overview</h2>
                                    <p>
                                        <strong>PublicDataWatch</strong> by AlcivarTech LLC is a civic data intelligence platform that aggregates, analyzes, and visualizes public data from multiple U.S. cities. The platform transforms raw municipal open data into actionable insights through interactive maps, automated statistical analysis, AI-generated reports, and neighborhood scoring.
                                    </p>
                                    <p>
                                        PublicDataWatch operates as a SaaS subscription platform, serving individual users, real estate professionals, researchers, and municipalities who need to understand neighborhood-level conditions across multiple data categories.
                                    </p>
                                </section>

                                <hr class="my-8" />

                                <section id="market">
                                    <h2>Market Opportunity</h2>
                                    <ul>
                                        <li><strong>Civic transparency & GovTech</strong> &mdash; Growing demand for tools that make government data accessible and actionable for citizens. The smart city market continues to expand as municipalities invest in open data programs.</li>
                                        <li><strong>Real estate due diligence</strong> &mdash; Buyers, renters, and investors need neighborhood-level data (crime, permits, violations) when evaluating properties. PublicDataWatch provides this in a unified, multi-category view.</li>
                                        <li><strong>Insurance risk assessment</strong> &mdash; Insurers benefit from granular, up-to-date neighborhood data for underwriting and risk modeling.</li>
                                        <li><strong>Community safety & livability</strong> &mdash; Residents, community organizations, and property managers use neighborhood scoring to assess and track area conditions over time.</li>
                                        <li><strong>Academic & policy research</strong> &mdash; Researchers need accessible platforms for analyzing urban data with documented statistical methodology.</li>
                                    </ul>
                                </section>

                                <hr class="my-8" />

                                <section id="technology">
                                    <h2>Technology Stack</h2>
                                    <div class="overflow-x-auto">
                                        <table class="min-w-full text-sm">
                                            <thead>
                                                <tr>
                                                    <th class="text-left py-2 px-3">Layer</th>
                                                    <th class="text-left py-2 px-3">Technology</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr><td class="py-2 px-3 font-medium">Backend</td><td class="py-2 px-3">Laravel 10 (PHP), Python (FastAPI + Celery for analysis pipeline)</td></tr>
                                                <tr><td class="py-2 px-3 font-medium">Frontend</td><td class="py-2 px-3">Vue 3, Inertia.js (SPA-like experience without a separate API layer)</td></tr>
                                                <tr><td class="py-2 px-3 font-medium">Database</td><td class="py-2 px-3">MySQL/MariaDB with multi-database architecture per city</td></tr>
                                                <tr><td class="py-2 px-3 font-medium">Mapping</td><td class="py-2 px-3">Leaflet.js with marker clustering</td></tr>
                                                <tr><td class="py-2 px-3 font-medium">AI/ML</td><td class="py-2 px-3">Google Gemini, OpenAI GPT-4o integration</td></tr>
                                                <tr><td class="py-2 px-3 font-medium">Geospatial</td><td class="py-2 px-3">Uber H3 hexagonal indexing</td></tr>
                                                <tr><td class="py-2 px-3 font-medium">Storage</td><td class="py-2 px-3">S3-compatible object storage</td></tr>
                                                <tr><td class="py-2 px-3 font-medium">Queue/Cache</td><td class="py-2 px-3">Redis for job queuing and caching</td></tr>
                                                <tr><td class="py-2 px-3 font-medium">Payments</td><td class="py-2 px-3">Stripe via Laravel Cashier</td></tr>
                                                <tr><td class="py-2 px-3 font-medium">Charts</td><td class="py-2 px-3">ECharts via vue-echarts</td></tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </section>

                                <hr class="my-8" />

                                <section id="business-model">
                                    <h2>Business Model</h2>
                                    <p>PublicDataWatch operates a freemium SaaS model with three tiers:</p>

                                    <div class="overflow-x-auto">
                                        <table class="min-w-full text-sm">
                                            <thead>
                                                <tr>
                                                    <th class="text-left py-2 px-3">Tier</th>
                                                    <th class="text-left py-2 px-3">Price</th>
                                                    <th class="text-left py-2 px-3">Key Features</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr><td class="py-2 px-3 font-medium">Free</td><td class="py-2 px-3">$0/mo</td><td class="py-2 px-3">2-week data window, 1 saved location, basic AI features</td></tr>
                                                <tr><td class="py-2 px-3 font-medium">Basic</td><td class="py-2 px-3">$5/mo</td><td class="py-2 px-3">6-month data window, 3 locations, daily AI reports</td></tr>
                                                <tr><td class="py-2 px-3 font-medium">Pro</td><td class="py-2 px-3">$15/mo</td><td class="py-2 px-3">Full historical data, 10 locations, advanced AI, CSV export, model choice</td></tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    <p class="mt-4">Additional revenue opportunities include municipal partnership licensing and enterprise/API access for real estate platforms and insurance providers.</p>
                                </section>

                                <hr class="my-8" />

                                <section id="capabilities">
                                    <h2>Product Capabilities</h2>
                                    <ul>
                                        <li><strong>Interactive radial map search</strong> &mdash; Explore data around any address with configurable radius, across 7+ cities.</li>
                                        <li><strong>8+ data categories</strong> &mdash; Crime, 311 service requests, building permits, property violations, food inspections, construction off-hours, traffic crashes, and more.</li>
                                        <li><strong>Automated statistical analysis</strong> &mdash; Trend detection (linear regression) and anomaly detection (Poisson/Negative Binomial models) run automatically on all data.</li>
                                        <li><strong>AI-generated reports</strong> &mdash; Streaming location reports in 15+ languages, powered by Google Gemini and OpenAI.</li>
                                        <li><strong>Neighborhood scoring</strong> &mdash; Composite safety and livability scores using H3 hexagonal spatial indexing with configurable weights.</li>
                                        <li><strong>Community map sharing</strong> &mdash; Users save and share map configurations, building a community knowledge base.</li>
                                        <li><strong>Year-over-year analytics</strong> &mdash; Automated comparison reports showing changes across years.</li>
                                        <li><strong>AI-generated news</strong> &mdash; Statistical findings converted to readable articles.</li>
                                        <li><strong>Natural language querying</strong> &mdash; Plain-English search translated to data filters via LLM.</li>
                                    </ul>
                                </section>

                                <hr class="my-8" />

                                <section id="scalability">
                                    <h2>Scalability & Growth Path</h2>
                                    <ul>
                                        <li><strong>Adding new cities:</strong> The platform has a documented, repeatable process for onboarding new cities. Any municipality with a CSV-based open data portal can be integrated.</li>
                                        <li><strong>Multi-database architecture:</strong> Each city uses dedicated database connections (recent data + historical archive), designed for horizontal scaling.</li>
                                        <li><strong>City-agnostic analysis engine:</strong> The statistical analysis pipeline works on any geo-temporal dataset regardless of source city, requiring only field mapping configuration.</li>
                                        <li><strong>Cloud-native storage:</strong> S3 abstraction layer supports deployment across cloud providers.</li>
                                        <li><strong>Pipeline architecture:</strong> The sequential stage-based pipeline can be parallelized as data volume grows.</li>
                                    </ul>
                                </section>

                                <hr class="my-8" />

                                <section id="coverage">
                                    <h2>Current Coverage</h2>
                                    <ul>
                                        <li><strong>7 cities:</strong> Boston, Cambridge, Everett, Chicago, San Francisco, Seattle, Montgomery County MD.</li>
                                        <li><strong>16+ data models</strong> spanning crime, civic services, permits, violations, and inspections.</li>
                                        <li><strong>Automated daily refresh</strong> of all data pipelines.</li>
                                        <li><strong>Multiple data acquisition strategies:</strong> REST API, CSV download, and PDF scraping to handle different municipal data publishing approaches.</li>
                                    </ul>
                                </section>

                                <hr class="my-8" />

                                <section id="advantages">
                                    <h2>Competitive Advantages</h2>
                                    <ul>
                                        <li><strong>Multi-city unified platform</strong> &mdash; Unlike city-specific tools, PublicDataWatch provides a single interface for data across multiple municipalities.</li>
                                        <li><strong>AI-powered insights</strong> &mdash; Goes beyond raw data display with automated statistical analysis and AI-generated narratives.</li>
                                        <li><strong>Statistical rigor</strong> &mdash; Published methodology (Poisson/NB model selection, significance testing, H3 spatial analysis) suitable for peer-review contexts.</li>
                                        <li><strong>Multi-language accessibility</strong> &mdash; AI reports in 15+ languages serve diverse communities without additional translation infrastructure.</li>
                                        <li><strong>Community engagement</strong> &mdash; Saved maps and sharing features create network effects as users contribute curated views.</li>
                                        <li><strong>Neighborhood scoring system</strong> &mdash; Proprietary composite scoring with configurable weights and multiple methodologies (historical + anomaly-based).</li>
                                        <li><strong>Documented onboarding process</strong> &mdash; New cities can be added systematically, reducing marginal cost of geographic expansion.</li>
                                    </ul>
                                </section>

                                <hr class="my-8" />

                                <section id="contact">
                                    <h2>Contact</h2>
                                    <p>
                                        For partnership and investment inquiries, please reach out through our <Link :href="route('help.contact')" class="text-blue-600 hover:text-blue-800">contact page</Link> or learn more <Link :href="route('about.us')" class="text-blue-600 hover:text-blue-800">about us</Link>.
                                    </p>
                                </section>
                            </div>
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

<style scoped>
.prose h2 {
    @apply text-2xl font-bold text-gray-900 mt-8 mb-4;
}
.prose h3 {
    @apply text-xl font-semibold text-gray-700 mt-6 mb-2;
}
.prose p {
    @apply mb-4 leading-relaxed text-gray-600;
}
.prose ul, .prose ol {
    @apply pl-6 mb-4 space-y-1 text-gray-600;
}
.prose ul {
    @apply list-disc;
}
.prose ol {
    @apply list-decimal;
}
.prose table {
    @apply border border-gray-200 rounded;
}
.prose th {
    @apply bg-gray-50 border-b border-gray-200 font-semibold text-gray-700;
}
.prose td {
    @apply border-b border-gray-100;
}
</style>
