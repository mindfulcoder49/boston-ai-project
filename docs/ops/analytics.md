# Analytics

## Purpose

Establish a trustworthy measurement foundation so future recommendations are based on real user behavior rather than intuition or vanity signals.

## Current State

The app already has a Google Analytics integration via `vue-gtag`.

Current implementation found in the repo:
- GA is initialized in [resources/js/app.js](/home/briarmoss/Documents/boston-ai-project/resources/js/app.js)
- `tagId` comes from `VITE_GA_ID`
- shared page-view and click tracking currently live in [resources/js/Components/PageTemplate.vue](/home/briarmoss/Documents/boston-ai-project/resources/js/Components/PageTemplate.vue)
- banner click tracking exists in [resources/js/Components/FeaturedUserMapsBanner.vue](/home/briarmoss/Documents/boston-ai-project/resources/js/Components/FeaturedUserMapsBanner.vue)

## What Is Currently Tracked

From code inspection, the current analytics baseline includes:

1. Page views
- initial `page_view` on mount in `PageTemplate.vue`
- subsequent `page_view` events on Inertia route change

2. Logout
- `logout` event on successful logout in `PageTemplate.vue`

3. Global click tracking
- a generic `click_event` listener attached to the whole document in `PageTemplate.vue`
- labels are inferred from button text, link text, title, or alt text

4. Featured banner clicks
- a `click` event with category `engagement` from `FeaturedUserMapsBanner.vue`

## Current Problems

The existing setup is useful as a baseline, but not yet strong enough for product decisions.

### 1. Too much generic click noise

The global document click listener in `PageTemplate.vue` likely creates a large amount of low-signal event traffic.

Problems:
- hard to interpret
- event labels may be inconsistent
- many clicks are not meaningful funnel steps
- difficult to build reliable dashboards from generic click labels

### 2. Missing product-specific funnel events

There is no evidence yet of dedicated tracking for key user actions such as:
- `/everett` page visit as a city-page-specific event
- geolocation button click
- address search start
- address search success
- marker click on city pages
- translation request
- translation success/failure
- explore-map handoff click
- signup started
- checkout started
- checkout completed

### 3. No clear event taxonomy

There is no documented analytics spec that defines:
- canonical event names
- required parameters
- user properties
- when to use GA recommended events versus custom events

### 4. No documented dashboard or KPI layer

The codebase does not currently document:
- weekly KPI definitions
- funnel definitions
- source/medium reporting conventions
- city/page segmentation standards

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

2. Reduce reliance on generic click tracking
- decide whether to keep, narrow, or remove the document-wide `click_event`

3. Add explicit product events to the Everett flow
- `city_page_view`
- `use_my_location_clicked`
- `address_search_completed`
- `map_marker_selected`
- `translation_requested`
- `translation_completed`
- `explore_map_clicked`

4. Define a single event naming convention
- documented in this file or a dedicated analytics spec

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

