# Growth And Monetization

## Purpose

Turn PublicDataWatch into a product that can earn money without relying on vague “more traffic” goals or founder thrash.

The current wedge is:

- one address
- one immediate answer
- one recurring paid outcome

## Current Monetization Shape

PublicDataWatch should currently be thought of in two layers:

1. **Acquisition layer**
   - homepage
   - crime-address preview
   - city landing pages

2. **Paid expansion layer**
   - recurring one-address reports
   - full map
   - trend reports
   - neighborhood scoring
   - broader professional workflows

## Current Best-Fit Funnel

Primary funnel:

1. user enters an address
2. PublicDataWatch checks whether the address is supported
3. if supported, the user sees:
   - recent nearby incidents
   - readable summary
   - trend context
   - score context
4. user signs up to keep getting that report
5. user starts a no-card trial
6. after trial:
   - `$5/month` keeps the one-address report workflow going
   - `$15/month` unlocks the broader toolset

Unsupported addresses should not dead-end. They should capture:

- requested address
- email
- intent to be notified when coverage expands

## Role Of City Landing Pages

City landing pages are not the primary monetization CTA, but they matter because they:

- serve as search-facing entry points
- teach users what is available in each region
- bridge city-specific intent into the address preview
- reduce confusion about uneven dataset coverage

City pages should route toward:

- `/crime-address` when the city is crime-preview eligible
- full-map workflows when the user wants broader exploration

## Current Pricing Story

### Registered user / free state

- free account
- limited saved-location access
- can enter the public funnel without paying

### Trial state

- no-card trial initiated from the crime-address flow
- tied to the one-address recurring-report workflow
- should feel like a real product outcome, not a fake demo

### `$5/month`

Best framed as:

- one-address daily awareness
- for residents, renters, buyers, and people tracking one place

### `$15/month`

Best framed as:

- full map
- trend context
- neighborhood scores
- multi-neighborhood or professional use

## Metrics That Matter

### Acquisition

- sessions to `/`
- sessions to `/crime-address`
- sessions to city landing pages
- search traffic to city landing pages

### Activation

- `crime_address_address_submitted`
- `crime_address_preview_rendered`
- `crime_address_address_unsupported`
- `crime_address_coverage_request_submitted`
- city page to preview clickthrough

### Trial

- `crime_address_preview_signup_started`
- `signup_completed`
- `crime_address_trial_started`

### Monetization

- `crime_address_plan_selected`
- `report_plan_selected`
- `checkout_started`
- `checkout_completed`

## Operating Model

Agent:

- proposes funnel changes
- drafts landing-page and pricing copy
- implements low-risk funnel work
- adds analytics and tests
- defines experiments

Founder:

- approves major messaging shifts
- approves pricing changes
- approves Stripe-side billing changes
- approves externally visible launch messaging

## Experiment Rules

- one hypothesis per experiment
- one primary success metric
- do not change homepage, pricing, and offer logic all at once unless the experiment explicitly requires it
- keep rollback easy
- add measurement before launch when practical

## Recommended Near-Term Growth Loop

1. improve city landing clarity
2. improve city-page to preview handoff
3. improve preview-to-signup conversion
4. improve trial-to-paid conversion
5. improve unsupported-area capture and follow-up

## Current Safe Agent-Driven Work

- homepage and city-page messaging
- CTA routing into the crime-address funnel
- analytics instrumentation and QA
- trial/paywall UX tuning
- SEO copy improvements for city pages and help pages

## Founder-Review Work

- major public messaging shifts
- pricing or packaging changes
- plan naming changes
- any offer that changes what `$5` or `$15` means

## Founder-Required Work

- Stripe dashboard changes
- public channel posting
- external partnerships or outreach

## Near-Term Deliverables

- clearer homepage and city-page handoffs into the preview funnel
- cleaner pricing-language alignment with the current `$5` / `$15` split
- better city landing pages as search-facing entry points
- cleaner trial and conversion measurement
- implemented crime-address funnel plan:
  [2026-03-27-crime-address-funnel-implementation-plan.md](./2026-03-27-crime-address-funnel-implementation-plan.md)
