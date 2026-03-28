# Analytics

## Purpose

Keep measurement trustworthy enough that product, SEO, and monetization decisions can be based on actual behavior.

## Current Confirmed State

Live GA4 access is available through [tools/analytics](../../tools/analytics/README.md).

Current default live property:

- `properties/490826923`
- display name: `PublicDataWatch`
- timezone: `America/New_York`

Current implementation anchors:

- GA bootstrap: `resources/js/app.js`
- shared tracking helpers: `resources/js/Utils/analytics.js`
- shared page-view tracking: `resources/js/Components/PageTemplate.vue`

## Recent Interpretation Rule

When instrumentation changed recently:

1. find the production deploy time first
2. validate GA4 in a narrow post-change window, usually the last `24` to `72` hours
3. only then use broader `14` to `30` day windows for context

Do not use a mixed historical window as proof that a newly added event is broken.

## Current Event Families

### Shared page events

- `page_view`
- `city_page_view`
- `explore_map_view`
- `pricing_page_view`

### Shared interaction events

- `use_my_location_clicked`
- `address_search_started`
- `address_search_completed`
- `map_marker_selected`
- `translation_requested`
- `translation_completed`
- `translation_failed`
- `explore_map_clicked`
- `logout`

### Crime-address funnel events

- `crime_address_address_submitted`
- `crime_address_address_unsupported`
- `crime_address_preview_rendered`
- `crime_address_coverage_request_submitted`
- `crime_address_preview_signup_started`
- `crime_address_trial_started`
- `crime_address_plan_selected`

### Account and checkout events

- `signup_started`
- `signup_completed`
- `report_plan_selected`
- `checkout_started`
- `checkout_completed`
- `subscription_canceled`

## Current Normalization Rules

The analytics helper now normalizes:

- `page_path` so query-string variants do not fragment one page into many rows
- `page_location` so only meaningful marketing params remain

Allowed location params currently include:

- `utm_source`
- `utm_medium`
- `utm_campaign`
- `utm_term`
- `utm_content`
- `utm_id`
- `gclid`
- `fbclid`
- `msclkid`

## Current Exclusions

Analytics is currently excluded for:

- `/admin`
- `/reports/statistical-analysis`
- `/scoring-reports`
- `/csvreports`

This should remain an explicit policy choice, not accidental drift.

## Priority Questions Analytics Should Answer

### Acquisition

- which landing pages bring users in
- which city pages actually earn traffic
- which sources drive users into the preview funnel

### Activation

- how often users submit an address
- how often they get a supported preview
- how often they hit unsupported coverage
- whether city pages hand off into the preview funnel

### Monetization

- how often preview users start signup
- how often signup completes
- how often trials start
- how often plan selection and checkout happen

### Stability

- did a deploy break key events
- did a page-path or routing change fragment reporting again

## Current Problems

### 1. Internal traffic is still an interpretation risk

Operator QA and Playwright production checks can pollute the live property if internal filtering is not handled cleanly.

### 2. Report-surface policy is still unresolved

Some report surfaces are excluded while other public artifact-like surfaces can still be measured or indexed differently. This should stay deliberate.

### 3. Funnel reporting still needs a standing KPI layer

The code has the events, but the weekly reporting habit still needs to be consistent.

## Weekly KPI Baseline

### Acquisition

- sessions by landing page
- sessions by source / medium
- sessions by city page

### Activation

- address submitted
- preview rendered
- unsupported coverage rate
- city page to preview clickthrough

### Trial and monetization

- signup started
- signup completed
- trial started
- plan selected
- checkout started
- checkout completed

## Safe Agent-Driven Work

- post-deploy GA4 validation
- event taxonomy documentation
- page-path normalization improvements
- dashboard and KPI definition work
- low-risk instrumentation additions

## Founder-Review Work

- internal-traffic filtering policy
- long-term separation strategy for production vs operator traffic if the property setup changes

## Near-Term Deliverables

- keep event docs aligned with actual code in `resources/js/Utils/analytics.js`
- validate the address-preview funnel in narrow post-deploy windows first
- keep city-page, preview, and checkout metrics readable after routing changes
