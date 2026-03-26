# Analytics

## Purpose

Establish a trustworthy measurement foundation so future recommendations are based on real user behavior rather than intuition or vanity signals.

## Current State

The app already has a Google Analytics integration via `vue-gtag`, and documented local GA4 access is available through the standalone [tools/analytics](../../tools/analytics/README.md) workspace.

Confirmed live GA4 status as of March 24, 2026:
- local GA4 access was verified with `pdw-analytics smoke-test`
- current property: `properties/490826923`
- current property display name: `PublicDataWatch`
- property timezone: `America/New_York`
- current confirmed web stream measurement id: `G-KH7YW40E1H`
- the analytics service account and `alex.g.alcivar49@gmail.com` both have Admin access

Current implementation found in the repo:
- GA is initialized in [resources/js/app.js](/home/briarmoss/Documents/boston-ai-project/resources/js/app.js)
- `tagId` comes from `VITE_GA_ID`
- shared analytics helpers and route gating live in [resources/js/Utils/analytics.js](/home/briarmoss/Documents/boston-ai-project/resources/js/Utils/analytics.js)
- shared Inertia page-view tracking lives in [resources/js/Components/PageTemplate.vue](/home/briarmoss/Documents/boston-ai-project/resources/js/Components/PageTemplate.vue)
- city-landing, explore-map, signup, and pricing instrumentation now live in page-specific Vue components
- banner click tracking still exists as a direct `vue-gtag` event in [resources/js/Components/FeaturedUserMapsBanner.vue](/home/briarmoss/Documents/boston-ai-project/resources/js/Components/FeaturedUserMapsBanner.vue)

## Validation Rule For Recent Analytics Changes

When analytics code or GA4 admin settings changed recently, do not treat a 14 to 30 day report as the primary truth for current implementation behavior.

Required interpretation rule:
- first identify the production deploy or change window
- first validate event delivery in a narrow post-change window, usually the last 24 to 72 hours in the GA4 property timezone
- only after that use a broader 14 to 30 day report for context, and label it as mixed historical data if it spans pre-change traffic

Why this matters:
- older windows can still be dominated by legacy events such as historical `click_event` traffic
- newly added events can appear absent simply because the updated frontend had not yet been deployed or had not yet received enough traffic
- a broad report can create a false negative about the current instrumentation if it mixes pre-change and post-change behavior

Minimum post-change validation checklist:
1. verify the production deploy date or time before interpreting GA4
2. run an `eventName` report over the post-change window and check the specific new events you expect
3. run a `pagePath` or landing-page report over the same window so you know whether the relevant surfaces actually had traffic
4. only call an event missing if the relevant page had traffic in the post-change window and the event still did not appear
5. keep broader 14 to 30 day reports as trend context, not proof of the current implementation state

## What Is Currently Tracked

From code inspection, the current analytics baseline includes:

1. Shared page and context events
- initial `page_view` on mount in `PageTemplate.vue`
- subsequent `page_view` events on Inertia route change
- `city_page_view` on city landing pages
- `explore_map_view` on the radial explore map
- `pricing_page_view` on the subscription page

2. Product interaction events
- `use_my_location_clicked`
- `address_search_started`
- `address_search_completed`
- `map_marker_selected`
- `translation_requested`
- `translation_completed`
- `translation_failed`
- `explore_map_clicked`

3. Account and billing events
- `signup_started`
- `signup_completed`
- `checkout_started`
- `checkout_completed`
- `subscription_canceled`
- `logout`

4. Edge and legacy instrumentation
- featured banner clicks emit a direct `click` event from `FeaturedUserMapsBanner.vue`

## Current Problems

The existing setup is useful as a baseline, but not yet strong enough for product decisions.

### 1. `page_view` can fragment by query string

Initial loads use `window.location.pathname`, but Inertia route changes use `$page.url` directly for `page_path`.

Problems:
- one logical page can split into multiple GA rows
- `session_id`, `types`, and similar query params can create noisy page variants
- weekly landing-page reporting becomes harder to normalize

### 2. Instrumentation is inconsistent at the edges

Known inconsistencies:
- `FeaturedUserMapsBanner.vue` bypasses `trackAnalyticsEvent`
- those banner events miss shared route gating and common parameters
- report-route exclusions are inconsistent
- `/reports/yearly-comparison/{jobId}` remains tracked while similar report surfaces are excluded

### 3. The live GA property has been audited once, but hygiene issues remain

The March 24, 2026 audit verified live property access and baseline settings, but the remaining risks are still material:
- admin and artifact URLs are still showing up in the property
- internal traffic filtering and environment separation still need an explicit operating decision
- the first audit window does not yet prove that the newer explicit product events are arriving cleanly after deployment and real traffic

### 4. No documented dashboard or KPI layer

The codebase does not currently document:
- weekly KPI definitions
- funnel definitions
- source/medium reporting conventions
- city/page segmentation standards

### 5. Documentation drift created false negatives in the ops playbook

This file previously described:
- a document-wide generic click tracker in `PageTemplate.vue`
- missing funnel events for city search, map interaction, signup, and checkout

That description was outdated. The repo now has explicit city/search/signup/checkout instrumentation, so this playbook must stay aligned with:
- [resources/js/Utils/analytics.js](/home/briarmoss/Documents/boston-ai-project/resources/js/Utils/analytics.js)
- [resources/js/Pages/CityMapLite.vue](/home/briarmoss/Documents/boston-ai-project/resources/js/Pages/CityMapLite.vue)
- [resources/js/Pages/RadialMap.vue](/home/briarmoss/Documents/boston-ai-project/resources/js/Pages/RadialMap.vue)
- [resources/js/Pages/Subscription.vue](/home/briarmoss/Documents/boston-ai-project/resources/js/Pages/Subscription.vue)

### 6. Mixed historical GA4 windows can misstate the current implementation

Recent instrumentation changes must be evaluated in a post-deploy window first.

Known failure mode:
- a 14 to 30 day report can still show strong legacy `click_event` history even after the current frontend removed the old document-wide listener
- a new event such as `city_page_view` or `explore_map_view` can look absent if the report window includes mostly pre-deploy traffic
- ops reviews can accidentally report an analytics regression when the real issue is only that the wrong time window was used

## Desired End State

Analytics should support weekly decision-making across:
- acquisition
- activation
- retention
- monetization
- operational confidence

The analytics layer should answer:
- which landing pages bring qualified traffic
- which city pages create interaction
- which CTAs move users deeper into the product
- which sources lead to signup or paid conversion
- which UX changes improve behavior without breaking flows

## Canonical Event Taxonomy

### Page and context events
- `city_page_view`
- `explore_map_view`
- `pricing_page_view`
- `checkout_page_view`

### Interaction events
- `use_my_location_clicked`
- `address_search_started`
- `address_search_completed`
- `map_marker_selected`
- `translation_requested`
- `translation_completed`
- `translation_failed`
- `explore_map_clicked`

### Account and billing events
- `signup_started`
- `signup_completed`
- `checkout_started`
- `checkout_completed`
- `subscription_canceled`

### Common event parameters
- `city`
- `page_type`
- `language_code`
- `device_type`
- `is_authenticated`
- `traffic_source`
- `campaign`

## Priority KPIs

These should become the weekly reporting baseline:

### Acquisition
- sessions by landing page
- sessions by source / medium
- sessions by city page

### Activation
- city page to meaningful interaction rate
- geolocation clickthrough rate
- address search completion rate
- marker selection rate
- explore-map clickthrough rate

### Monetization
- signup start rate
- signup completion rate
- checkout start rate
- checkout completion rate

### Stability
- event delivery sanity checks
- broken funnel alerts
- traffic anomaly review

## Recommended Analytics Loop

### Daily
- check for obvious traffic anomalies
- check for broken key events after deploys
- when analytics changed recently, use a post-change window first and state the exact window used

### Weekly
- review top landing pages
- review top city pages
- review interaction rates on city pages
- review signup and checkout funnel
- identify one instrumentation issue and one optimization issue

### Monthly
- review source quality
- review content/channel contribution
- revise KPI and dashboard design if needed

## Immediate Backlog

1. Run a fresh live GA4 follow-up review through `tools/analytics` and confirm:
- in a post-change window first, then in a broader context window second
- which explicit product events are now arriving after deployment
- whether internal traffic filtering is effective
- whether production, admin, and local/staging traffic should remain in one property
- whether the current stream and enhanced-measurement settings are still the intended baseline

2. Normalize `page_view` paths
- strip or whitelist query-string params before sending `page_path`
- decide which query params, if any, are analytically meaningful

3. Move banner clicks onto the shared analytics wrapper
- route all banner events through `trackAnalyticsEvent`
- ensure route gating and common params are applied consistently

4. Align exclusions for public report surfaces
- decide whether report viewers should be tracked at all
- keep exclusion behavior consistent across similar report routes

5. Create a weekly dashboard definition
- traffic
- city-page engagement
- explore-map handoff
- signup
- checkout

## Inputs Required From Founder

- decision on whether production, admin, and any local or staging traffic should stay in one GA4 property
- confirmation of what, if anything, is already being reviewed manually
- confirmation of any reporting conventions that should govern event naming or dashboard definitions

## Agent-Driven Work

- analytics audit
- event taxonomy definition
- dashboard specification
- instrumentation backlog
- low-risk frontend tracking additions

## Founder-Required Actions

- confirming the intended GA4 property and filter strategy
- approving GA4 UI-side changes if the next step requires filter, stream, or property-setting changes

## Next Step

The next concrete task should be:
- run a fresh live GA4 audit through `tools/analytics` and compare the property data against the event taxonomy now in code

That review should populate:
- trusted live events
- still-missing or noisy events
- internal-traffic contamination status
- first analytics implementation or GA-admin cleanup tasks

That review must:
- state the exact post-change GA4 date window used for event-delivery validation
- separate post-change delivery validation from broader historical trend context
