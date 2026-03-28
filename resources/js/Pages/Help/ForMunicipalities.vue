<template>
    <PageTemplate>
        <Head title="For Municipalities - PublicDataWatch" />

        <div class="bg-gray-50 py-12 sm:py-16">
            <div class="container mx-auto px-4 sm:px-6 lg:px-8">
                <div class="max-w-4xl mx-auto">
                    <!-- Breadcrumb -->
                    <nav class="mb-6 text-sm text-gray-500">
                        <Link :href="route('help.index')" class="hover:text-blue-600">Help Center</Link>
                        <span class="mx-2">/</span>
                        <span class="text-gray-900">For Municipalities</span>
                    </nav>

                    <h1 class="text-4xl font-bold tracking-tight text-gray-900 mb-8">
                        For Municipalities
                    </h1>

                    <div class="flex flex-col lg:flex-row gap-8">
                        <!-- Table of Contents -->
                        <aside class="lg:w-64 flex-shrink-0">
                            <div class="lg:sticky lg:top-8 bg-white rounded-lg shadow-md p-4 border border-gray-200">
                                <h2 class="font-semibold text-gray-900 mb-3">Contents</h2>
                                <nav class="space-y-1 text-sm">
                                    <a href="#overview" class="block text-blue-600 hover:text-blue-800 py-1">Platform Overview</a>
                                    <a href="#data-usage" class="block text-blue-600 hover:text-blue-800 py-1">How Your Data is Used</a>
                                    <a href="#benefits" class="block text-blue-600 hover:text-blue-800 py-1">Benefits</a>
                                    <a href="#adding-city" class="block text-blue-600 hover:text-blue-800 py-1">Adding Your City</a>
                                    <a href="#analysis" class="block text-blue-600 hover:text-blue-800 py-1">Statistical Analysis</a>
                                    <a href="#scoring" class="block text-blue-600 hover:text-blue-800 py-1">Neighborhood Scoring</a>
                                    <a href="#use-cases" class="block text-blue-600 hover:text-blue-800 py-1">Use Cases</a>
                                </nav>
                            </div>
                        </aside>

                        <!-- Main Content -->
                        <div class="flex-1 min-w-0">
                            <div class="bg-white rounded-lg shadow-md p-6 sm:p-8 border border-gray-200 prose prose-lg max-w-none">

                                <section id="overview">
                                    <h2>Platform Overview for City Officials</h2>
                                    <p>
                                        PublicDataWatch is a civic data intelligence platform that takes the public data your city already publishes through open data portals and makes it accessible, visual, and actionable for residents, researchers, and decision-makers.
                                    </p>
                                    <p>
                                        Rather than requiring residents to navigate complex data portals or download raw CSV files, PublicDataWatch starts with an address-first public preview and then expands into city landing pages, interactive maps, automated statistical analysis, AI-generated reports, and neighborhood scoring.
                                    </p>
                                    <p>
                                        PublicDataWatch currently covers <strong>8 cities and regions</strong> across the United States: Boston, Cambridge, Everett, Chicago, San Francisco, New York, Montgomery County, MD, and Seattle. Coverage is dataset-specific by region: Boston is the broadest multi-dataset area, several cities are crime-first, and New York is currently 311-first.
                                    </p>
                                </section>

                                <hr class="my-8" />

                                <section id="data-usage">
                                    <h2>How Your City's Data is Used</h2>

                                    <h3>Open Data Portal Integration</h3>
                                    <p>
                                        PublicDataWatch connects to municipal open data portals (Analyze Boston, Cambridge Open Data, DataSF, etc.) and downloads public datasets on a regular schedule. We do not require any special API access or data sharing agreements &mdash; we use the same publicly available data that any resident can access.
                                    </p>

                                    <h3>Data Pipeline</h3>
                                    <p>Our automated pipeline handles the full data lifecycle:</p>
                                    <ol>
                                        <li><strong>Acquisition</strong> &mdash; Downloads datasets from your open data portal.</li>
                                        <li><strong>Processing</strong> &mdash; Cleans, normalizes, and geocodes records.</li>
                                        <li><strong>Aggregation</strong> &mdash; Creates unified data points for cross-category analysis.</li>
                                        <li><strong>Analysis</strong> &mdash; Runs statistical trend and anomaly detection.</li>
                                        <li><strong>Visualization</strong> &mdash; Presents data through city landing pages, the crime preview funnel, and deeper map workflows.</li>
                                    </ol>

                                    <h3>Refresh Frequency</h3>
                                    <p>Data pipelines run daily to keep information current. The <Link :href="route('data.metrics')" class="text-blue-600 hover:text-blue-800">Data Metrics</Link> page shows the freshness of each dataset.</p>

                                    <h3>Privacy Considerations</h3>
                                    <p>
                                        PublicDataWatch only uses data that is already publicly available through official government open data portals. We do not collect, store, or display personally identifiable information (PII). All data displayed has already been anonymized or de-identified by the publishing municipality.
                                    </p>
                                </section>

                                <hr class="my-8" />

                                <section id="benefits">
                                    <h2>Benefits for Municipalities</h2>

                                    <h3>Citizen Engagement</h3>
                                    <p>Residents can start with one address instead of a raw portal export. That lowers the barrier to exploring neighborhood conditions and makes public data more usable for non-technical audiences.</p>

                                    <h3>Transparency</h3>
                                    <p>PublicDataWatch transforms raw data files into accessible visualizations that non-technical audiences can understand, increasing the reach and impact of your open data program.</p>

                                    <h3>Automated Trend Detection</h3>
                                    <p>Statistical analysis runs automatically on your data, surfacing significant trends (increasing crime categories, 311 response times, permit activity changes) that might otherwise go unnoticed.</p>

                                    <h3>Neighborhood Scoring</h3>
                                    <p>Composite scores help identify areas that may need additional resources or attention, based on objective data rather than perception.</p>

                                    <h3>Multi-Language Access</h3>
                                    <p>AI reports can be generated in 15+ languages, helping you serve linguistically diverse communities without additional translation costs.</p>

                                    <h3>Reduced Public Records Burden</h3>
                                    <p>When residents can easily find and explore city data themselves, it can reduce the volume of FOIA requests and public records inquiries.</p>
                                </section>

                                <hr class="my-8" />

                                <section id="adding-city">
                                    <h2>Adding Your City to PublicDataWatch</h2>

                                    <h3>Supported Data Formats</h3>
                                    <p>We work with CSV datasets from open data portals. Most major open data platforms (Socrata, CKAN, ArcGIS Hub) are compatible.</p>

                                    <h3>Required Data Fields</h3>
                                    <p>At minimum, each dataset needs:</p>
                                    <ul>
                                        <li><strong>Timestamps</strong> &mdash; When events occurred (date/time fields).</li>
                                        <li><strong>Coordinates</strong> &mdash; Latitude and longitude for mapping (or addresses for geocoding).</li>
                                        <li><strong>Categorization</strong> &mdash; Fields that classify the data (offense type, request category, permit type, etc.).</li>
                                    </ul>

                                    <h3>Data Types We Can Ingest</h3>
                                    <ul>
                                        <li>Crime incident reports</li>
                                        <li>311 / service requests</li>
                                        <li>Building and construction permits</li>
                                        <li>Code violations (property, housing, sanitary)</li>
                                        <li>Food establishment inspections</li>
                                        <li>Traffic crash data</li>
                                    </ul>

                                    <h3>Integration Process</h3>
                                    <ol>
                                        <li>Share your open data portal URL and available datasets.</li>
                                        <li>We map your data fields to our standard schema.</li>
                                        <li>We build acquisition and processing pipelines specific to your data.</li>
                                        <li>Your city goes live on the platform with full map, analysis, and reporting capabilities.</li>
                                    </ol>

                                    <h3>Get in Touch</h3>
                                    <p>
                                        Interested in adding your city? <Link :href="route('help.contact')" class="text-blue-600 hover:text-blue-800">Contact us</Link> to start the conversation.
                                    </p>
                                </section>

                                <hr class="my-8" />

                                <section id="analysis">
                                    <h2>Statistical Analysis Capabilities</h2>
                                    <ul>
                                        <li><strong>Trend detection</strong> &mdash; Identifies statistically significant upward or downward trends in incident counts by neighborhood and category.</li>
                                        <li><strong>Anomaly detection</strong> &mdash; Flags unusual spikes in specific areas that deviate from expected patterns.</li>
                                        <li><strong>Year-over-year comparisons</strong> &mdash; Shows how current-year data compares to the same period in previous years, with percentage changes.</li>
                                        <li><strong>Neighborhood scoring</strong> &mdash; Generates composite safety and livability scores at configurable geographic resolutions.</li>
                                        <li><strong>AI-generated narratives</strong> &mdash; Converts statistical findings into readable reports and news articles that can be shared with the public.</li>
                                    </ul>
                                    <p>For full methodology details, see our <Link href="/help/researchers" class="text-blue-600 hover:text-blue-800">Researchers guide</Link>.</p>
                                </section>

                                <hr class="my-8" />

                                <section id="scoring">
                                    <h2>Neighborhood Scoring System</h2>

                                    <h3>H3 Hexagonal Indexing</h3>
                                    <p>PublicDataWatch uses Uber's H3 hexagonal hierarchical spatial index to divide cities into uniform hexagonal cells. Unlike neighborhood boundaries (which vary in size and shape), H3 cells provide consistent, comparable geographic units.</p>

                                    <h3>Score Composition</h3>
                                    <p>Scores are calculated from weighted combinations of data categories. Each category (crime, violations, service requests, etc.) contributes to a composite score based on configurable weights.</p>

                                    <h3>Customizable Weights</h3>
                                    <p>Category weights can be adjusted to reflect your city's priorities. For example, you might weight violent crime more heavily than property crime, or emphasize code violations for a livability-focused score.</p>

                                    <h3>Multiple Methodologies</h3>
                                    <ul>
                                        <li><strong>Historical scoring</strong> &mdash; Based on average weekly incident counts over a configurable period (default: 52 weeks).</li>
                                        <li><strong>Anomaly-based scoring</strong> &mdash; Based on statistical anomaly detection results, highlighting areas with unusual activity.</li>
                                    </ul>

                                    <h3>Drill-Down Capability</h3>
                                    <p>View scores at different H3 resolution levels to analyze data at varying geographic scales, from large neighborhoods down to individual blocks.</p>
                                </section>

                                <hr class="my-8" />

                                <section id="use-cases">
                                    <h2>Use Cases</h2>
                                    <ul>
                                        <li><strong>City council presentations</strong> &mdash; Pull data-backed neighborhood reports with maps and statistics for council meetings.</li>
                                        <li><strong>Resource allocation</strong> &mdash; Use trend analysis and scoring to identify areas that need additional policing, code enforcement, or services.</li>
                                        <li><strong>Community meetings</strong> &mdash; Share interactive map views with residents to facilitate data-driven discussions about neighborhood issues.</li>
                                        <li><strong>Performance tracking</strong> &mdash; Use year-over-year comparisons to measure the impact of policy changes and resource deployments.</li>
                                        <li><strong>Emergency response planning</strong> &mdash; Analyze spatial patterns in crime and incident data to inform response strategies.</li>
                                        <li><strong>Public reporting</strong> &mdash; Use AI-generated reports and news articles as starting points for public communications about neighborhood conditions.</li>
                                    </ul>
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
</style>
