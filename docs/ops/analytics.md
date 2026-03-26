# Analytics

## Purpose

Establish a trustworthy measurement foundation so future recommendations are based on real user behavior rather than intuition or vanity signals.

## Current State

The app already has a Google Analytics integration via `vue-gtag`.

Current implementation found in the repo:
- GA is initialized in [resources/js/app.js](/home/briarmoss/Documents/boston-ai-project/resources/js/app.js)
- `tagId` comes from `VITE_GA_ID`
- shared analytics helpers and route gating live in [resources/js/Utils/analytics.js](/home/briarmoss/Documents/boston-ai-project/resources/js/Utils/analytics.js)
- shared Inertia page-view tracking lives in [resources/js/Components/PageTemplate.vue](/home/briarmoss/Documents/boston-ai-project/resources/js/Components/PageTemplate.vue)
- city-landing, explore-map, signup, and pricing instrumentation now live in page-specific Vue components
- banner click tracking still exists as a direct `vue-gtag` event in [resources/js/Components/FeaturedUserMapsBanner.vue](/home/briarmoss/Documents/boston-ai-project/resources/js/Components/FeaturedUserMapsBanner.vue)

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

### 3. The live GA property still has not been audited

The repo instrumentation is broader than the original baseline, but we still do not know from code alone:
- which events are actually arriving
- whether internal traffic is filtered
- whether multiple properties or environments exist
- whether GA recommended-event conventions are being used consistently in the property

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

## Event Taxonomy To Establish

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

1. Audit the GA property and confirm:
- property id
- environments tracked
- whether internal traffic filtering exists
- whether enhanced measurement is enabled

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

- access to the active Google Analytics property
- confirmation of whether multiple GA properties/environments exist
- confirmation of what, if anything, is already being reviewed manually

## Agent-Driven Work

- analytics audit
- event taxonomy definition
- dashboard specification
- instrumentation backlog
- low-risk frontend tracking additions

## Founder-Required Actions

- granting GA access or providing exports/screenshots
- confirming any analytics conventions that already matter to reporting

## Next Step

The next concrete task should be:
- review the live GA property and document what is actually flowing today

That review should populate:
- trusted events
- noisy/untrusted events
- missing funnel steps
- first analytics implementation tasks
