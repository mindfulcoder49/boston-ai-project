<template>
    <PageTemplate>
        <Head title="User Guide - PublicDataWatch" />

        <div class="bg-gray-50 py-12 sm:py-16">
            <div class="container mx-auto px-4 sm:px-6 lg:px-8">
                <div class="max-w-4xl mx-auto">
                    <!-- Breadcrumb -->
                    <nav class="mb-6 text-sm text-gray-500">
                        <Link :href="route('help.index')" class="hover:text-blue-600">Help Center</Link>
                        <span class="mx-2">/</span>
                        <span class="text-gray-900">User Guide</span>
                    </nav>

                    <h1 class="text-4xl font-bold tracking-tight text-gray-900 mb-8">
                        User Guide
                    </h1>

                    <div class="flex flex-col lg:flex-row gap-8">
                        <!-- Table of Contents (sidebar) -->
                        <aside class="lg:w-64 flex-shrink-0">
                            <div class="lg:sticky lg:top-8 bg-white rounded-lg shadow-md p-4 border border-gray-200">
                                <h2 class="font-semibold text-gray-900 mb-3">Contents</h2>
                                <nav class="space-y-1 text-sm">
                                    <a href="#getting-started" class="block text-blue-600 hover:text-blue-800 py-1">Getting Started</a>
                                    <a href="#crime-preview" class="block text-blue-600 hover:text-blue-800 py-1">Crime Preview</a>
                                    <a href="#full-data-map" class="block text-blue-600 hover:text-blue-800 py-1">Explore Tools</a>
                                    <a href="#ai-assistant" class="block text-blue-600 hover:text-blue-800 py-1">AI Assistant</a>
                                    <a href="#data-types" class="block text-blue-600 hover:text-blue-800 py-1">Data Types Explained</a>
                                    <a href="#cities" class="block text-blue-600 hover:text-blue-800 py-1">Cities & Coverage</a>
                                    <a href="#saved-maps" class="block text-blue-600 hover:text-blue-800 py-1">Saved Maps & Community</a>
                                    <a href="#reports" class="block text-blue-600 hover:text-blue-800 py-1">Reports & Analysis</a>
                                    <a href="#statistical-reports" class="block text-blue-600 hover:text-blue-800 py-1">Statistical Reports</a>
                                    <a href="#area-names" class="block text-blue-600 hover:text-blue-800 py-1">Area Names on Maps</a>
                                    <a href="#data-metrics" class="block text-blue-600 hover:text-blue-800 py-1">Data Metrics Dashboard</a>
                                    <a href="#account" class="block text-blue-600 hover:text-blue-800 py-1">Account Management</a>
                                    <a href="#subscriptions" class="block text-blue-600 hover:text-blue-800 py-1">Subscription Plans</a>
                                </nav>
                            </div>
                        </aside>

                        <!-- Main Content -->
                        <div class="flex-1 min-w-0">
                            <div class="bg-white rounded-lg shadow-md p-6 sm:p-8 border border-gray-200 prose prose-lg max-w-none">

                                <!-- Getting Started -->
                                <section id="getting-started">
                                    <h2>Getting Started</h2>

                                    <h3>Creating an Account</h3>
                                    <p>
                                        You can create a PublicDataWatch account in two ways:
                                    </p>
                                    <ul>
                                        <li><strong>Email registration</strong> &mdash; Click "Create Account" or follow a signup prompt from the crime preview or pricing page.</li>
                                        <li><strong>Google login</strong> &mdash; Click the Google icon to sign in with your Google account. An account will be created automatically on your first login.</li>
                                    </ul>

                                    <h3>Understanding Subscription Tiers</h3>
                                    <p>PublicDataWatch currently has a free registered state, a no-card preview-to-trial path, and two paid plans:</p>
                                    <div class="overflow-x-auto">
                                        <table class="min-w-full text-sm">
                                            <thead>
                                                <tr>
                                                    <th class="text-left py-2 px-3">Feature</th>
                                                    <th class="text-left py-2 px-3">Free / Trial</th>
                                                    <th class="text-left py-2 px-3">Basic ($5/mo)</th>
                                                    <th class="text-left py-2 px-3">Pro ($15/mo)</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr><td class="py-2 px-3">Best use</td><td class="py-2 px-3">Free preview and account access</td><td class="py-2 px-3">Keep one address on a daily report</td><td class="py-2 px-3">Full map, trends, scores, and broader work</td></tr>
                                                <tr><td class="py-2 px-3">Saved locations</td><td class="py-2 px-3">1</td><td class="py-2 px-3">3</td><td class="py-2 px-3">10</td></tr>
                                                <tr><td class="py-2 px-3">Recurring report workflow</td><td class="py-2 px-3">Trial / limited</td><td class="py-2 px-3">1 address</td><td class="py-2 px-3">Broader professional use</td></tr>
                                                <tr><td class="py-2 px-3">Full map, trends, scores</td><td class="py-2 px-3">Preview and limited browsing</td><td class="py-2 px-3">Limited</td><td class="py-2 px-3">Yes</td></tr>
                                                <tr><td class="py-2 px-3">CSV export</td><td class="py-2 px-3">No</td><td class="py-2 px-3">No</td><td class="py-2 px-3">Yes</td></tr>
                                                <tr><td class="py-2 px-3">AI model choice</td><td class="py-2 px-3">Default</td><td class="py-2 px-3">Default</td><td class="py-2 px-3">Multiple models</td></tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    <h3>Navigating the Platform</h3>
                                    <p>
                                        The navigation bar now leads with the public funnel first: <strong>Home</strong>, <strong>Crime Preview</strong>, <strong>Cities</strong>, <strong>Explore</strong>, <strong>Pricing</strong>, and <strong>Help</strong>. Advanced map, trend, score, and metrics workflows live under <strong>Explore</strong>. Saved locations, saved maps, billing, profile, and report history live in the authenticated account area. On mobile, open the hamburger menu to see the same grouped structure.
                                    </p>
                                </section>

                                <hr class="my-8" />

                                <!-- Crime Preview -->
                                <section id="crime-preview">
                                    <h2>Crime Preview</h2>
                                    <p>
                                        The homepage now starts with the crime-address preview funnel rather than dropping every user directly into the radial map. This is the fastest way to answer the normal person question: what is happening around this address right now?
                                    </p>
                                    <h3>Searching An Address</h3>
                                    <p>Use the address search bar or “Use my location” to run the preview. If the address is supported, PublicDataWatch builds a lightweight results page. If it is unsupported, you can ask to be notified when coverage expands.</p>

                                    <h3>What Loads First</h3>
                                    <p>Incident-level information loads first. Trend and score context are layered in to help you interpret the area without making the first screen feel like a technical dashboard.</p>

                                    <h3>Understanding The Preview</h3>
                                    <ul>
                                        <li><strong>Recent nearby incidents</strong> &mdash; What happened nearby recently.</li>
                                        <li><strong>Readable summary</strong> &mdash; A plain-language explanation of what stands out.</li>
                                        <li><strong>Trend context</strong> &mdash; Whether the surrounding city or region is showing meaningful shifts.</li>
                                        <li><strong>Score context</strong> &mdash; A simplified area score with city-relative and nearby-relative framing.</li>
                                    </ul>

                                    <h3>Next Step</h3>
                                    <p>If the preview proves useful, you can create an account and move into the daily-report workflow or open the deeper map and analysis tools.</p>
                                </section>

                                <hr class="my-8" />

                                <!-- Full Data Map -->
                                <section id="full-data-map">
                                    <h2>Explore Tools</h2>
                                    <h3>Single Data Type View</h3>
                                    <p>Access individual data type maps to see all records of a specific type (e.g., all crime incidents, all building permits) across the entire city.</p>

                                    <h3>Combined Data Map</h3>
                                    <p>The <Link :href="route('data-map.combined')" class="text-blue-600 hover:text-blue-800">Combined Map</Link> overlays multiple data types on a single map, letting you see relationships between different categories.</p>

                                    <h3>Natural Language Querying</h3>
                                    <p>Use the NLP filter to describe what you're looking for in plain language (e.g., "show me robberies near downtown from last month"). The system translates your query into data filters automatically.</p>

                                    <h3>Cluster Radius</h3>
                                    <p>Adjust the cluster radius to control how aggressively nearby markers are grouped together.</p>

                                    <h3>Saving Map Configurations</h3>
                                    <p>Save your current map view, filters, and settings as a named map that you can return to later or share with the community.</p>
                                </section>

                                <hr class="my-8" />

                                <!-- AI Assistant -->
                                <section id="ai-assistant">
                                    <h2>AI Assistant</h2>
                                    <h3>Asking Questions About Your Data</h3>
                                    <p>The AI chat assistant can answer questions about the data currently displayed on your map. Ask about patterns, comparisons, or specific incidents.</p>

                                    <h3>Generating Location Reports</h3>
                                    <p>Generate deeper reports for a location once the preview is not enough. These reports analyze the records around the selected area and provide longer summaries, patterns, and context.</p>

                                    <h3>Choosing AI Models</h3>
                                    <p>Pro subscribers can choose between different AI models (OpenAI GPT and Google Gemini) depending on their preference for response style.</p>

                                    <h3>Multi-Language Reports</h3>
                                    <p>Reports can be generated in 15+ languages including Spanish, Portuguese, Chinese, French, Arabic, and more &mdash; making city data accessible to diverse communities.</p>

                                    <h3>Tips for Best Results</h3>
                                    <ul>
                                        <li>Be specific about location and time frame in your questions.</li>
                                        <li>Use suggested prompts as starting points.</li>
                                        <li>The AI works best with data already loaded on the map &mdash; load your area first, then ask questions.</li>
                                    </ul>
                                </section>

                                <hr class="my-8" />

                                <!-- Data Types -->
                                <section id="data-types">
                                    <h2>Data Types Explained</h2>

                                    <h3>Crime Incident Reports</h3>
                                    <p>Official police-reported crime incidents. Fields include offense description, offense group (e.g., Larceny, Assault), shooting indicator, date/time, and location. Data comes from city police department records.</p>

                                    <h3>311 Service Cases</h3>
                                    <p>Citizen-reported service requests (potholes, graffiti, noise complaints, etc.). Each case has a lifecycle: open, in progress, closed. PublicDataWatch supports live status polling to check current case status in real time.</p>

                                    <h3>Building Permits</h3>
                                    <p>Applications for construction and renovation work. Includes work type (new construction, renovation, demolition), permit status (issued, pending, denied), and estimated project valuation.</p>

                                    <h3>Property Violations</h3>
                                    <p>Code enforcement violations on properties. Includes violation type, status codes (open, closed, in compliance), and property details.</p>

                                    <h3>Construction Off-Hours</h3>
                                    <p>Special permits for construction work outside normal hours (evenings, weekends, holidays). Shows who has been approved for after-hours work in your area.</p>

                                    <h3>Food Inspections</h3>
                                    <p>Restaurant and food establishment inspection results. Includes inspection outcome (pass, fail, needs re-inspection), violation levels, and license information.</p>

                                    <h3>Traffic Crash Data</h3>
                                    <p>Motor vehicle crash reports including crash severity, vehicle types involved, pedestrian/cyclist involvement, and contributing factors.</p>
                                </section>

                                <hr class="my-8" />

                                <!-- Cities -->
                                <section id="cities">
                                    <h2>Cities & Coverage</h2>
                                    <div class="overflow-x-auto">
                                        <table class="min-w-full text-sm">
                                            <thead>
                                                <tr>
                                                    <th class="text-left py-2 px-3">City</th>
                                                    <th class="text-left py-2 px-3">Data Types Available</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr><td class="py-2 px-3 font-medium">Boston, MA</td><td class="py-2 px-3">Crime, 311, Building Permits, Property Violations, Food Inspections, Construction Off-Hours, Car Crashes</td></tr>
                                                <tr><td class="py-2 px-3 font-medium">Cambridge, MA</td><td class="py-2 px-3">Crime, 311, Building Permits, Housing Violations, Sanitary Inspections</td></tr>
                                                <tr><td class="py-2 px-3 font-medium">Everett, MA</td><td class="py-2 px-3">Crime (from police PDF reports)</td></tr>
                                                <tr><td class="py-2 px-3 font-medium">Chicago, IL</td><td class="py-2 px-3">Crime</td></tr>
                                                <tr><td class="py-2 px-3 font-medium">San Francisco, CA</td><td class="py-2 px-3">Crime</td></tr>
                                                <tr><td class="py-2 px-3 font-medium">New York, NY</td><td class="py-2 px-3">311 / service requests</td></tr>
                                                <tr><td class="py-2 px-3 font-medium">Seattle, WA</td><td class="py-2 px-3">Crime</td></tr>
                                                <tr><td class="py-2 px-3 font-medium">Montgomery County, MD</td><td class="py-2 px-3">Crime</td></tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <p class="mt-4">City pages are intentionally different from one another. Boston is the broadest multi-dataset surface, several regions are crime-first, and New York is currently 311-first. If your city has an open data portal, <Link :href="route('help.contact')" class="text-blue-600 hover:text-blue-800">let us know</Link>.</p>
                                </section>

                                <hr class="my-8" />

                                <!-- Saved Maps -->
                                <section id="saved-maps">
                                    <h2>Saved Maps & Community</h2>
                                    <h3>Saving Your Current Map View</h3>
                                    <p>Click the save button on any map view to capture your current location, zoom level, active filters, and data selections. Give your map a name and optional description.</p>

                                    <h3>Public vs. Private Maps</h3>
                                    <p>Choose to make your saved map public (visible to all users) or keep it private (only you can access it). Public maps may be featured by administrators.</p>

                                    <h3>Browsing Community Maps</h3>
                                    <p>Visit <Link :href="route('saved-maps.index')" class="text-blue-600 hover:text-blue-800">User Maps</Link> to browse maps created and shared by other users. Find interesting views of your city or discover data in areas you haven't explored.</p>

                                    <h3>Creator Tier Badges</h3>
                                    <p>Each shared map shows the creator's subscription tier, which indicates the depth of data available when the map was created (2 weeks for free, 6 months for basic, all time for pro).</p>
                                </section>

                                <hr class="my-8" />

                                <!-- Reports -->
                                <section id="reports">
                                    <h2>Reports & Analysis</h2>
                                    <h3>AI-Generated Location Reports</h3>
                                    <p>Location reports provide a comprehensive analysis of all public data within your selected area. They include summaries of crime trends, service request patterns, permit activity, and more. Reports are generated in real time using AI streaming.</p>

                                    <h3>Report History</h3>
                                    <p>All your generated reports are saved in <Link :href="route('reports.index')" class="text-blue-600 hover:text-blue-800">Report History</Link> (requires login). You can review past reports and track changes over time.</p>

                                    <h3>Downloading Reports</h3>
                                    <p>Pro subscribers can download reports for offline use and sharing.</p>
                                </section>

                                <hr class="my-8" />

                                <!-- Statistical Reports -->
                                <section id="statistical-reports">
                                    <h2>Statistical Reports</h2>

                                    <h3>Trends & Anomaly Detection</h3>
                                    <p>The <Link :href="route('trends.index')" class="text-blue-600 hover:text-blue-800">Trends</Link> page shows statistically significant changes in data patterns. The system uses time-series analysis to detect unusual spikes, drops, or sustained trends in incident counts across different categories and neighborhoods.</p>

                                    <h3>Yearly Count Comparisons</h3>
                                    <p><Link :href="route('yearly-comparisons.index')" class="text-blue-600 hover:text-blue-800">Yearly Comparisons</Link> show how this year's data compares to previous years. See which categories are increasing or decreasing year-over-year with percentage changes.</p>

                                    <h3>Neighborhood Scoring</h3>
                                    <p><Link :href="route('scoring-reports.index')" class="text-blue-600 hover:text-blue-800">Neighborhood Scores</Link> provide composite area scores using H3 hexagonal indexing. On the crime preview page, those scores are intentionally simplified and shown with city-relative and nearby-relative context. The full scoring tools let you drill down by resolution and inspect the deeper methodology.</p>

                                    <h3>AI-Generated News</h3>
                                    <p>News articles are automatically generated from statistical analysis findings, turning data trends into readable narratives.</p>
                                </section>

                                <hr class="my-8" />

                                <!-- Area Names -->
                                <section id="area-names">
                                    <h2>Area Names on Maps</h2>
                                    <p>
                                        Throughout the hotspot map, statistical reports, and neighborhood scoring pages, geographic areas are displayed using human-readable names like <strong>"Beacon Hill, Boston"</strong> or <strong>"Logan Square, Chicago"</strong> rather than raw technical identifiers.
                                    </p>

                                    <h3>Where These Names Come From</h3>
                                    <p>
                                        The platform divides cities into a grid of uniform hexagonal cells (see "Statistical Reports" above for an explanation of H3 indexing). To give each hexagon a readable name, we take the <strong>center point</strong> of that hexagon and look up what neighborhood or area that coordinate falls in using Google's location data. The result &mdash; for example "South End, Boston" or "Wicker Park, Chicago" &mdash; is stored and shown wherever that hexagon appears.
                                    </p>

                                    <h3>What the Name Represents</h3>
                                    <p>
                                        The name reflects the area that contains the <em>center</em> of the hexagon, not necessarily every part of it. Because hexagons don't line up with neighborhood boundaries, a hexagon near a boundary might be labeled with one neighborhood even though part of it technically falls in an adjacent one. Think of the name as a useful approximation of where the hotspot is, not a precise administrative designation.
                                    </p>
                                    <p>
                                        The level of detail in the name also depends on the resolution of the analysis:
                                    </p>
                                    <ul>
                                        <li><strong>Large hexagons (resolution 5–6, covering several square miles)</strong> &mdash; Named at the city or district level, e.g. "Boston" or "Suffolk County."</li>
                                        <li><strong>Medium hexagons (resolution 7, covering about 2 square miles)</strong> &mdash; Named at the neighborhood level, e.g. "Roxbury, Boston."</li>
                                        <li><strong>Small hexagons (resolution 8–9, covering a few city blocks)</strong> &mdash; Named at the sub-neighborhood level, e.g. "Beacon Hill, Boston."</li>
                                    </ul>

                                    <h3>What If a Hexagon Shows a Technical ID?</h3>
                                    <p>
                                        If you see an identifier like <code class="font-mono text-sm">8928a3b3fffffff</code> instead of a place name, that hexagon hasn't been geocoded yet. Names are added in batches by the data team and grow over time as more areas are covered. The technical ID is always shown in small print beneath the name for reference.
                                    </p>
                                </section>

                                <hr class="my-8" />

                                <!-- Data Metrics -->
                                <section id="data-metrics">
                                    <h2>Data Metrics Dashboard</h2>
                                    <p>The <Link :href="route('data.metrics')" class="text-blue-600 hover:text-blue-800">Data Metrics</Link> page provides an overview of the platform's data holdings: total record counts per data type, date ranges covered, and data freshness indicators showing when each dataset was last updated.</p>
                                </section>

                                <hr class="my-8" />

                                <!-- Account -->
                                <section id="account">
                                    <h2>Account Management</h2>
                                    <ul>
                                        <li><strong>Edit profile</strong> &mdash; Update your name and email from the Profile page.</li>
                                        <li><strong>Change password</strong> &mdash; Set a new password from Profile (email accounts only; Google login users manage passwords through Google).</li>
                                        <li><strong>Manage subscription</strong> &mdash; Access the Stripe billing portal to update payment methods, view invoices, or cancel your plan.</li>
                                        <li><strong>Redeem codes</strong> &mdash; Enter subscription codes on the Profile page to activate gifted or promotional plans.</li>
                                        <li><strong>Delete account</strong> &mdash; Permanently delete your account and all associated data from the Profile page.</li>
                                    </ul>
                                </section>

                                <hr class="my-8" />

                                <!-- Subscriptions -->
                                <section id="subscriptions">
                                    <h2>Subscription Plans</h2>
                                    <p>Visit the <Link :href="route('subscription.index')" class="text-blue-600 hover:text-blue-800">Subscription</Link> page to view full plan details and subscribe.</p>

                                    <h3>Registered / Trial State</h3>
                                    <p>Create an account for free, then start a no-card trial from the crime preview workflow when you want recurring daily reports for one address.</p>

                                    <h3>Basic ($5/month)</h3>
                                    <p>Best for keeping one address on a recurring daily-report workflow, with deeper everyday local usage than the free state.</p>

                                    <h3>Pro ($15/month)</h3>
                                    <p>Best for reporters, agents, property operators, and other power users who need the full map, trends, neighborhood scores, exports, and broader multi-neighborhood work.</p>

                                    <h3>Managing Your Subscription</h3>
                                    <p>You can upgrade, downgrade, or cancel at any time through the Stripe billing portal. Changes take effect at the start of your next billing cycle.</p>
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
.prose ul {
    @apply list-disc pl-6 mb-4 space-y-1 text-gray-600;
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
