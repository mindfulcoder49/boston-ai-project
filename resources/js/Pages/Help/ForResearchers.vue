<template>
    <PageTemplate>
        <Head title="For Researchers - PublicDataWatch" />

        <div class="bg-gray-50 py-12 sm:py-16">
            <div class="container mx-auto px-4 sm:px-6 lg:px-8">
                <div class="max-w-4xl mx-auto">
                    <!-- Breadcrumb -->
                    <nav class="mb-6 text-sm text-gray-500">
                        <Link :href="route('help.index')" class="hover:text-blue-600">Help Center</Link>
                        <span class="mx-2">/</span>
                        <span class="text-gray-900">For Researchers</span>
                    </nav>

                    <h1 class="text-4xl font-bold tracking-tight text-gray-900 mb-8">
                        For Researchers
                    </h1>

                    <div class="flex flex-col lg:flex-row gap-8">
                        <!-- Table of Contents -->
                        <aside class="lg:w-64 flex-shrink-0">
                            <div class="lg:sticky lg:top-8 bg-white rounded-lg shadow-md p-4 border border-gray-200">
                                <h2 class="font-semibold text-gray-900 mb-3">Contents</h2>
                                <nav class="space-y-1 text-sm">
                                    <a href="#data-sources" class="block text-blue-600 hover:text-blue-800 py-1">Data Sources & Provenance</a>
                                    <a href="#methodology" class="block text-blue-600 hover:text-blue-800 py-1">Statistical Methodology</a>
                                    <a href="#significance" class="block text-blue-600 hover:text-blue-800 py-1">Significance Testing</a>
                                    <a href="#geospatial" class="block text-blue-600 hover:text-blue-800 py-1">Geospatial Methods</a>
                                    <a href="#h3-naming" class="block text-blue-600 hover:text-blue-800 py-1 pl-3">↳ H3 Area Naming</a>
                                    <a href="#ai-integration" class="block text-blue-600 hover:text-blue-800 py-1">AI/LLM Integration</a>
                                    <a href="#pipeline" class="block text-blue-600 hover:text-blue-800 py-1">Data Pipeline Architecture</a>
                                    <a href="#citing" class="block text-blue-600 hover:text-blue-800 py-1">Accessing & Citing</a>
                                </nav>
                            </div>
                        </aside>

                        <!-- Main Content -->
                        <div class="flex-1 min-w-0">
                            <div class="bg-white rounded-lg shadow-md p-6 sm:p-8 border border-gray-200 prose prose-lg max-w-none">

                                <section id="data-sources">
                                    <h2>Data Sources & Provenance</h2>
                                    <p>PublicDataWatch ingests data from the following municipal open data portals:</p>

                                    <div class="overflow-x-auto">
                                        <table class="min-w-full text-sm">
                                            <thead>
                                                <tr>
                                                    <th class="text-left py-2 px-3">City</th>
                                                    <th class="text-left py-2 px-3">Source</th>
                                                    <th class="text-left py-2 px-3">Data Types</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr><td class="py-2 px-3">Boston, MA</td><td class="py-2 px-3">Analyze Boston</td><td class="py-2 px-3">8 datasets: crime, 311, building permits, property violations, food inspections, construction off-hours, trash schedules</td></tr>
                                                <tr><td class="py-2 px-3">Cambridge, MA</td><td class="py-2 px-3">Cambridge Open Data + Police Logs</td><td class="py-2 px-3">Crime, 311, building permits, housing violations, sanitary inspections</td></tr>
                                                <tr><td class="py-2 px-3">Everett, MA</td><td class="py-2 px-3">Police Dept. PDF Reports</td><td class="py-2 px-3">Crime (NLP-extracted from PDF)</td></tr>
                                                <tr><td class="py-2 px-3">Chicago, IL</td><td class="py-2 px-3">Chicago Data Portal</td><td class="py-2 px-3">Crime</td></tr>
                                                <tr><td class="py-2 px-3">San Francisco, CA</td><td class="py-2 px-3">DataSF</td><td class="py-2 px-3">Crime</td></tr>
                                                <tr><td class="py-2 px-3">Seattle, WA</td><td class="py-2 px-3">Seattle Open Data</td><td class="py-2 px-3">Crime</td></tr>
                                                <tr><td class="py-2 px-3">Montgomery County, MD</td><td class="py-2 px-3">dataMontgomery</td><td class="py-2 px-3">Crime</td></tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    <p class="mt-4">Data refresh pipelines run daily. Boston datasets are identified by specific resource IDs configured in the application. Cambridge police log data is scraped from PDF publications and converted to structured data using NLP processing.</p>
                                </section>

                                <hr class="my-8" />

                                <section id="methodology">
                                    <h2>Statistical Methodology</h2>

                                    <h3>Univariate Anomaly Detection (Stage 3)</h3>
                                    <p>The anomaly detection system constructs weekly incident count time series for each subgroup (e.g., "Larceny in Roxbury") and applies the following analysis:</p>
                                    <ul>
                                        <li><strong>Time series construction:</strong> Weekly incident counts per subgroup.</li>
                                        <li><strong>Model selection:</strong> The system tests for overdispersion in the count data. If overdispersion is detected (variance significantly exceeds the mean), a Negative Binomial model is used; otherwise, a Poisson model is fitted.</li>
                                        <li><strong>Anomaly p-value:</strong> Calculated using the survival function P(X &ge; observed count) of the fitted distribution.</li>
                                        <li><strong>Z-score:</strong> Computed as (observed - mean) / std for effect size quantification.</li>
                                        <li><strong>Trend detection:</strong> Linear regression on the most recent weeks of data. The regression slope and p-value indicate trend direction and significance.</li>
                                        <li><strong>Minimum data requirement:</strong> At least 8 weeks of historical data are required for analysis.</li>
                                        <li><strong>Noise reduction:</strong> Single-event anomalies in low-frequency series (where only 1 event was observed) are filtered out to reduce false positives.</li>
                                    </ul>

                                    <h3>H3 Spatial Analysis (Stage 4)</h3>
                                    <p>Spatial analysis uses Uber's H3 hexagonal hierarchical spatial index to divide cities into uniform cells:</p>
                                    <ul>
                                        <li><strong>Resolution:</strong> Configurable, typically resolution 8 (average hexagon edge length ~460m).</li>
                                        <li><strong>Method:</strong> The same statistical methods from Stage 3 (Poisson/NB model selection, anomaly detection, trend analysis) are applied independently to each H3 cell.</li>
                                        <li><strong>City-wide vs. localized:</strong> Results can be compared at the city level (Stage 3) and the neighborhood level (Stage 4) to distinguish localized anomalies from city-wide trends.</li>
                                        <li><strong>Processing:</strong> Multi-file chunked processing handles large datasets efficiently.</li>
                                    </ul>

                                    <h3>Yearly Count Comparison (Stage 2)</h3>
                                    <ul>
                                        <li><strong>Grouping:</strong> Data is grouped by a configurable column (e.g., offense description, neighborhood).</li>
                                        <li><strong>Temporal alignment:</strong> For incomplete current years, comparisons use the "to-date" method &mdash; only comparing data through the same calendar date across years, avoiding bias from comparing a partial year to a complete one.</li>
                                        <li><strong>Metrics:</strong> Year-over-year percentage change and absolute count differences.</li>
                                        <li><strong>Baseline comparison:</strong> Supports comparison against a specific baseline year (e.g., 2019 as a pre-pandemic baseline).</li>
                                    </ul>

                                    <h3>Neighborhood Scoring: Anomaly-Based (Stage 5)</h3>
                                    <ul>
                                        <li><strong>Input:</strong> Stage 4 H3 anomaly results.</li>
                                        <li><strong>Metric extraction:</strong> Configurable dot-notation paths extract specific values from analysis results.</li>
                                        <li><strong>Scoring formula:</strong> User-defined formulas evaluated via <code>asteval</code> (safe mathematical expression evaluation, no arbitrary code execution).</li>
                                        <li><strong>Aggregation:</strong> Weighted category scores are aggregated per H3 cell using sum or average methods.</li>
                                    </ul>

                                    <h3>Neighborhood Scoring: Historical (Stage 6)</h3>
                                    <ul>
                                        <li><strong>Input:</strong> Direct data ingestion (does not depend on Stage 4).</li>
                                        <li><strong>Method:</strong> Calculates average weekly incident count over a configurable period (default: 52 weeks).</li>
                                        <li><strong>Weighting:</strong> Incident categories receive configurable weights.</li>
                                        <li><strong>Explainability:</strong> Score composition breakdown shows the contribution of each category to the final score.</li>
                                    </ul>
                                </section>

                                <hr class="my-8" />

                                <section id="significance">
                                    <h2>Significance Testing & Interpretation</h2>
                                    <ul>
                                        <li><strong>P-value threshold:</strong> 0.05 (configurable).</li>
                                        <li><strong>Independence assumption:</strong> Each subgroup (category &times; location) is treated as an independent hypothesis test.</li>
                                        <li><strong>Multiple comparison correction:</strong> No Bonferroni or FDR correction is applied. This is intentional &mdash; the system is designed as a <em>screening tool</em> that maximizes sensitivity at the cost of some false positives. Users should treat flagged anomalies as candidates for further investigation, not definitive findings.</li>
                                        <li><strong>Trend significance:</strong> Assessed via the p-value of linear regression on the recent time window.</li>
                                        <li><strong>Anomaly significance:</strong> Assessed via the survival function of the fitted count distribution (Poisson or Negative Binomial).</li>
                                    </ul>
                                </section>

                                <hr class="my-8" />

                                <section id="geospatial">
                                    <h2>Geospatial Methods</h2>
                                    <ul>
                                        <li><strong>H3 hexagonal indexing:</strong> Resolutions 7-9 are typically used. Resolution 8 provides city-block-scale analysis; resolution 7 provides neighborhood-scale.</li>
                                        <li><strong>Radial queries:</strong> The radial map uses Haversine distance calculations for proximity searches.</li>
                                        <li><strong>Database spatial queries:</strong> <code>ST_Distance_Sphere</code> is used for server-side distance filtering in MySQL/MariaDB.</li>
                                        <li><strong>Point geometry:</strong> All data points are stored with spatial point geometry in the aggregated <code>data_points</code> table for efficient spatial indexing.</li>
                                        <li><strong>Coordinate validation:</strong> Sentinel values (0,0 coordinates, null values) are filtered during data ingestion.</li>
                                    </ul>
                                </section>

                                    <h3 id="h3-naming">H3 Area Naming: Reverse Geocoding Methodology</h3>
                                    <p>
                                        To give H3 cells human-readable labels, the platform performs reverse geocoding of each cell's centroid using the Google Geocoding API. The specific implementation is as follows:
                                    </p>

                                    <p><strong>Centroid computation.</strong> The H3 library's <code>cellToLatLng(h3Index)</code> function returns the geographic centroid of the cell — the arithmetic mean of its vertex coordinates projected onto the sphere. This is used as the query point for reverse geocoding. This is computed in the browser using the <code>h3-js</code> library rather than on the server, since PHP lacks a maintained H3 implementation.</p>

                                    <p><strong>API request.</strong> The centroid <code>(lat, lng)</code> is sent to <code>maps.googleapis.com/maps/api/geocode/json?latlng={lat},{lng}</code> with no result-type filter, so the full address component hierarchy is returned. The first result is used.</p>

                                    <p><strong>Component extraction by resolution.</strong> The appropriate name is selected from the returned address components using the following priority order, conditioned on resolution:</p>

                                    <div class="overflow-x-auto">
                                        <table class="min-w-full text-sm">
                                            <thead>
                                                <tr>
                                                    <th class="text-left py-2 px-3">Resolution</th>
                                                    <th class="text-left py-2 px-3">Approx. area</th>
                                                    <th class="text-left py-2 px-3">Component priority</th>
                                                    <th class="text-left py-2 px-3">Example output</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td class="py-2 px-3">5–6</td>
                                                    <td class="py-2 px-3">36–252 km²</td>
                                                    <td class="py-2 px-3"><code>locality</code> → <code>administrative_area_level_2</code> → <code>administrative_area_level_1</code></td>
                                                    <td class="py-2 px-3">"Boston" / "Suffolk County"</td>
                                                </tr>
                                                <tr>
                                                    <td class="py-2 px-3">7+</td>
                                                    <td class="py-2 px-3">&lt;5 km²</td>
                                                    <td class="py-2 px-3"><code>neighborhood</code> → <code>sublocality_level_1</code> → <code>sublocality</code>, appended with <code>locality</code></td>
                                                    <td class="py-2 px-3">"Beacon Hill, Boston"</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    <p><strong>Storage.</strong> Results are stored in the <code>h3_location_names</code> table keyed by <code>h3_index</code> (which is globally unique across all resolutions). Resolution is stored as a separate column for filtering. Names are served to the frontend as a shared Inertia prop (<code>h3LocationNames</code>) on every page load, cached server-side for one hour and invalidated on each geocoding run.</p>

                                    <p><strong>Known limitations and biases.</strong> Researchers should be aware of the following when interpreting area names:</p>
                                    <ul>
                                        <li><strong>Centroid boundary mismatch.</strong> H3 cells are not aligned to administrative boundaries. A cell whose centroid falls in neighborhood A may still include significant area from neighborhood B. This is more pronounced at resolutions 7–8 than at 9.</li>
                                        <li><strong>Google component availability.</strong> The <code>neighborhood</code> component is not always present in Google's response, particularly in suburban or rural areas. The fallback hierarchy above handles this gracefully, but may produce coarser names than expected.</li>
                                        <li><strong>Single-point sampling.</strong> Only the centroid is geocoded. A large hexagon spanning a park, a highway, and two neighborhoods will receive the name of whichever area contains the centroid alone.</li>
                                        <li><strong>Temporal stability.</strong> Google's administrative component data can change (boundary redraws, renaming). Names are geocoded once and not automatically re-verified unless the admin tool is re-run.</li>
                                        <li><strong>Coverage gaps.</strong> Hexagons are geocoded on-demand by the data team via the admin geocoding tool. Some hexagons may not yet have names; these display their raw <code>h3_index</code> as a fallback.</li>
                                    </ul>

                                <hr class="my-8" />

                                <section id="ai-integration">
                                    <h2>AI/LLM Integration</h2>
                                    <ul>
                                        <li><strong>News article generation:</strong> Statistical analysis findings are fed to LLMs to generate narrative articles summarizing trends and anomalies.</li>
                                        <li><strong>Location reports:</strong> Google Gemini generates streaming reports analyzing all data within a user-selected radius.</li>
                                        <li><strong>Chat-based queries:</strong> OpenAI GPT-4o-mini powers the interactive chat assistant for natural language data exploration.</li>
                                        <li><strong>NLP query translation:</strong> Natural language search queries are translated into structured database filters using LLM-powered parsing.</li>
                                        <li><strong>Multi-language generation:</strong> Reports can be generated in 15+ languages using the multilingual capabilities of the underlying LLMs.</li>
                                    </ul>
                                </section>

                                <hr class="my-8" />

                                <section id="pipeline">
                                    <h2>Data Pipeline Architecture</h2>
                                    <p>The data pipeline is orchestrated as an 8-stage sequential process:</p>
                                    <ol>
                                        <li><strong>Boston Data Acquisition</strong> &mdash; Downloads datasets from Analyze Boston via scraper service.</li>
                                        <li><strong>Boston Data Seeding</strong> &mdash; Processes CSVs into database tables using batch upsert (500-1000 records per batch).</li>
                                        <li><strong>Cambridge Data Acquisition</strong> &mdash; Downloads from Cambridge Open Data and police log PDFs.</li>
                                        <li><strong>Cambridge Data Seeding</strong> &mdash; Processes and geocodes Cambridge data.</li>
                                        <li><strong>Everett Data Acquisition & Processing</strong> &mdash; Downloads police PDFs, converts to structured data via NLP.</li>
                                        <li><strong>Everett Data Seeding</strong> &mdash; Loads processed Everett data.</li>
                                        <li><strong>Post-Seeding Aggregation</strong> &mdash; Populates the unified <code>data_points</code> table and caches metrics.</li>
                                        <li><strong>Reporting</strong> &mdash; Dispatches location-based email reports to subscribed users.</li>
                                    </ol>

                                    <h3>Multi-Database Architecture</h3>
                                    <p>Each city may have two databases: a primary database for recent data (approximately 6 months) and a historical database for full archival data. This architecture supports horizontal scaling as new cities are added.</p>

                                    <h3>City-Specific Acquisition</h3>
                                    <p>Different cities require different acquisition strategies:</p>
                                    <ul>
                                        <li><strong>API-based:</strong> Cities with REST APIs (Socrata-powered portals).</li>
                                        <li><strong>CSV download:</strong> Direct file download from data portals.</li>
                                        <li><strong>PDF scraping:</strong> Extraction from published PDF documents (e.g., Everett police reports).</li>
                                    </ul>
                                </section>

                                <hr class="my-8" />

                                <section id="citing">
                                    <h2>Accessing & Citing the Data</h2>

                                    <h3>Data Attribution</h3>
                                    <p>All data displayed on PublicDataWatch is derived from publicly available government open data portals. When using data or findings from PublicDataWatch in research, please cite both the original data source and the platform:</p>

                                    <div class="bg-gray-50 p-4 rounded-lg border text-sm font-mono">
                                        <p class="mb-2">Original data: [City Name] Open Data Portal, [Dataset Name], accessed via PublicDataWatch.</p>
                                        <p>Analysis: PublicDataWatch by AlcivarTech LLC, publicdatawatch.app</p>
                                    </div>

                                    <h3>Limitations & Caveats</h3>
                                    <ul>
                                        <li>Data reflects what is reported and recorded by municipal agencies &mdash; unreported incidents are not captured.</li>
                                        <li>Geocoding accuracy varies by source; some records use address-based geocoding as a fallback.</li>
                                        <li>No multiple comparison correction is applied &mdash; flagged anomalies should be treated as screening results.</li>
                                        <li>AI-generated reports and news articles are summaries, not primary analysis &mdash; always verify against the underlying data.</li>
                                        <li>Data freshness depends on the publishing schedule of the source municipality.</li>
                                        <li>Historical data availability varies by city and data type.</li>
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
.prose code {
    @apply bg-gray-100 px-1.5 py-0.5 rounded text-sm text-gray-800;
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
