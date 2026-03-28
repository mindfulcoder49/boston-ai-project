# 2026-03-27 Crime-Address Funnel Implementation Plan

## Purpose

This is the execution plan for the next monetization workstream:

- a crime-first, address-first acquisition funnel
- a 7-day no-card daily-report trial
- a low-friction upgrade path from free trial to recurring paid reports
- a real unsupported-area capture flow with later notification support
- a testing-first rollout so the funnel can be changed safely

It translates the product direction into:

- workstreams
- file-level implementation targets
- rollout order
- exact test targets
- acceptance criteria

## Product Definition

Primary user promise:

- `Wondering what crime is happening around your address? Enter here to find out.`

Desired user flow:

1. User enters an address.
2. If unsupported, show:
   - `We do not serve your address yet. We will look into adding your area and notify you if we do.`
3. If supported, show a lightweight preview page with:
   - recent explore map
   - nearby crime incidents
   - one readable report below the map
   - incident context, trend context, and neighborhood score context in the same report
4. Ask the user to sign up to get this report by email every day.
5. Start a 7-day no-card trial after signup.
6. After trial expiry:
   - `$5/month` keeps the one-address recurring report
   - `$15/month` unlocks full trends, neighborhood scores, full data map, and broader multi-neighborhood/professional use

## Repo Findings That Change The Plan

The obvious surfaces are not the whole implementation surface.

Important current behavior:

- [GenericMapController.php](/home/briarmoss/Documents/boston-ai-project/app/Http/Controllers/GenericMapController.php) chooses the nearest configured city rather than explicitly rejecting unsupported addresses.
- [AiAssistantController.php](/home/briarmoss/Documents/boston-ai-project/app/Http/Controllers/AiAssistantController.php) can stream an on-demand location report, but it does not assemble the exact preview payload needed for this funnel.
- [ScoringReportController.php](/home/briarmoss/Documents/boston-ai-project/app/Http/Controllers/ScoringReportController.php) already supports score lookup for an address-derived H3 cell.
- [DispatchLocationReports.php](/home/briarmoss/Documents/boston-ai-project/app/Console/Commands/DispatchLocationReports.php) skips free users entirely, so there is no current trial path.
- [SendLocationReportEmail.php](/home/briarmoss/Documents/boston-ai-project/app/Jobs/SendLocationReportEmail.php) and [SendLocationReportEmailNoAI.php](/home/briarmoss/Documents/boston-ai-project/app/Jobs/SendLocationReportEmailNoAI.php) are both dispatched for recurring reports today, which risks duplicate user-facing emails and duplicate `reports` rows.
- [AuthenticatedSessionController.php](/home/briarmoss/Documents/boston-ai-project/app/Http/Controllers/Auth/AuthenticatedSessionController.php), [RegisteredUserController.php](/home/briarmoss/Documents/boston-ai-project/app/Http/Controllers/Auth/RegisteredUserController.php), [SocialLoginController.php](/home/briarmoss/Documents/boston-ai-project/app/Http/Controllers/Auth/SocialLoginController.php), [Login.vue](/home/briarmoss/Documents/boston-ai-project/resources/js/Pages/Auth/Login.vue), and [Register.vue](/home/briarmoss/Documents/boston-ai-project/resources/js/Pages/Auth/Register.vue) do not currently preserve a reliable funnel redirect path.
- [EmailController.php](/home/briarmoss/Documents/boston-ai-project/app/Http/Controllers/EmailController.php) is only a generic feedback path. It is not enough to support the promise `we will notify you if we do`.
- Current automated coverage is thin:
  - only a few PHPUnit tests exist
  - there is no browser test harness
  - only [UserFactory.php](/home/briarmoss/Documents/boston-ai-project/database/factories/UserFactory.php) exists

## Working Principles

- prefer a dedicated funnel over trying to mutate the full radial-map experience into a marketing page
- prefer one canonical user-facing recurring report path over today’s duplicate AI and no-AI mail dispatch
- keep the unsupported-area promise honest by storing coverage requests in the app
- add automated tests before the new flow is made broadly visible
- ship behind a feature flag first
- keep the initial trial model narrow and reversible

## Recommended Execution Order

1. Test harness and fixture support
2. Serviceability and unsupported-area capture
3. Funnel routes, controller, and lightweight preview page
4. Preview report assembler
5. Auth continuity and signup return path
6. Trial state and access rules
7. Recurring report dispatch refactor
8. Paywall transitions and analytics
9. Browser tests and staged rollout

Reason:

- the serviceability decision controls both the supported and unsupported branches
- the preview report shape must exist before signup and trial logic can be validated
- auth continuity must be fixed before trial conversion can be trusted
- recurring delivery should be refactored only after trial rules are explicit
- analytics should be added to the stabilized flow, not to a moving target

## Workstream 1: Test Harness And Fixture Support

### Goal

Create the minimum test infrastructure required to change the funnel safely.

### Files To Add

- `database/factories/LocationFactory.php`
- `database/factories/ReportFactory.php`
- `database/factories/CoverageRequestFactory.php`
- `tests/Feature/CrimeAddressFunnel/`
- `tests/Unit/Services/`
- frontend test config:
  - `playwright.config.*`
  - `tests/e2e/`
  - related `package.json` script additions

### Files To Change

- [package.json](/home/briarmoss/Documents/boston-ai-project/package.json)
- [composer.json](/home/briarmoss/Documents/boston-ai-project/composer.json) only if a PHP-side helper is needed

### Implementation Steps

1. Add model factories for `Location`, `Report`, and the new `CoverageRequest`.
2. Add fixture helpers for:
   - supported address lookup results
   - unsupported address lookup results
   - map preview payloads
   - scoring summary payloads
   - trend summary payloads
3. Add Playwright as the browser-level smoke suite for the funnel.
4. Add scripts for:
   - backend test suite
   - e2e smoke suite

### Exact Test Targets

New PHPUnit files:

- `tests/Unit/Services/AddressServiceabilityServiceTest.php`
- `tests/Unit/Services/CrimeAddressPreviewBuilderTest.php`
- `tests/Unit/Services/ReportAccessServiceTest.php`
- `tests/Feature/CrimeAddressFunnel/PreviewFlowTest.php`
- `tests/Feature/CrimeAddressFunnel/CoverageRequestTest.php`
- `tests/Feature/CrimeAddressFunnel/TrialLifecycleTest.php`
- `tests/Feature/Commands/DispatchLocationReportsTest.php`

New browser tests:

- `tests/e2e/crime-address-funnel.spec.ts`

### Acceptance Criteria

- backend factories exist for all new funnel models
- PHPUnit can create supported and unsupported funnel states without manual fixture setup
- a browser runner exists and can execute one end-to-end funnel smoke test locally

## Workstream 2: Serviceability And Unsupported-Area Capture

### Goal

Replace nearest-city fallback with a real supported-or-unsupported decision and store unsupported-area requests durably.

### Files To Add

- `app/Services/AddressServiceabilityService.php`
- `app/Http/Controllers/CoverageRequestController.php`
- `app/Models/CoverageRequest.php`
- `database/migrations/2026_03_27_000001_create_coverage_requests_table.php`

### Files To Change

- [config/cities.php](/home/briarmoss/Documents/boston-ai-project/config/cities.php)
- [routes/web.php](/home/briarmoss/Documents/boston-ai-project/routes/web.php)
- [EmailController.php](/home/briarmoss/Documents/boston-ai-project/app/Http/Controllers/EmailController.php) only if the new request flow also emits an operator email

### Recommended Design

Coverage decision should be explicit, not proximity-only.

The service should return:

- `supported: true|false`
- `matched_city_key`
- `matched_city_name`
- `reason`
- normalized address data
- lat/lng

The unsupported flow should store:

- requested address
- normalized address
- latitude
- longitude
- email
- user_id if authenticated
- source page
- status
- notes

### Exact Tests

- `AddressServiceabilityServiceTest`
  - supported address in Boston returns `supported=true`
  - supported address in a crime-only city returns `supported=true`
  - address outside configured coverage returns `supported=false`
  - service does not silently coerce unsupported coordinates to nearest city

- `CoverageRequestTest`
  - unsupported preview submission stores a `coverage_requests` row
  - authenticated request stores `user_id`
  - duplicate requests for the same email/address are handled according to the chosen policy

### Acceptance Criteria

- unsupported addresses no longer fall through to nearest-city preview data
- unsupported users see the exact notify-me promise
- coverage requests are stored in-app and queryable later

## Workstream 3: Funnel Routes, Controller, And Lightweight Preview Page

### Goal

Create a dedicated crime-address funnel instead of overloading the full radial map.

### Files To Add

- `app/Http/Controllers/CrimeAddressFunnelController.php`
- `resources/js/Pages/CrimeAddress/Index.vue`
- `resources/js/Pages/CrimeAddress/Preview.vue`
- `resources/js/Components/CrimeAddress/`

### Files To Change

- [routes/web.php](/home/briarmoss/Documents/boston-ai-project/routes/web.php)
- [Home.vue](/home/briarmoss/Documents/boston-ai-project/resources/js/Pages/Home.vue)
- [PageTemplate.vue](/home/briarmoss/Documents/boston-ai-project/resources/js/Components/PageTemplate.vue) only if nav exposure is needed

### Recommended Design

Add a dedicated public route for the funnel, for example:

- `GET /crime-address`
- `POST /api/crime-address/preview`
- `POST /api/crime-address/coverage-request`

The preview page should include:

- address header
- simple recent map
- key recent incidents
- one readable report
- signup CTA

It should not include:

- full radial-map controls
- value filters
- generic chat UI
- full statistics panels

### Exact Tests

- `PreviewFlowTest`
  - index page renders
  - supported address returns preview payload
  - unsupported address returns unsupported state
  - preview response includes the expected sections for a supported address

- Playwright:
  - user enters unsupported address and sees notify-me form
  - user enters supported address and sees preview page

### Acceptance Criteria

- there is a dedicated public route for the funnel
- the preview experience is materially simpler than the full radial map
- supported and unsupported flows are both reachable from the new entry point

## Workstream 4: Preview Report Assembler

### Goal

Assemble the exact preview payload the funnel needs:

- recent nearby crime
- trend context
- neighborhood score context

### Files To Add

- `app/Services/CrimeAddressPreviewBuilder.php`
- `app/Services/CrimeAddressTrendSummaryService.php` if trend extraction needs its own layer

### Files To Change

- [AiAssistantController.php](/home/briarmoss/Documents/boston-ai-project/app/Http/Controllers/AiAssistantController.php)
- [ScoringReportController.php](/home/briarmoss/Documents/boston-ai-project/app/Http/Controllers/ScoringReportController.php) only if a lighter-weight summary helper is exposed
- [TrendsController.php](/home/briarmoss/Documents/boston-ai-project/app/Http/Controllers/TrendsController.php) only if a lighter-weight summary helper is exposed

### Recommended Design

Do not make the preview page orchestrate multiple unrelated frontend fetches.

Instead:

1. controller calls `CrimeAddressPreviewBuilder`
2. builder gets map data
3. builder resolves score context
4. builder resolves trend context
5. builder formats one compact preview DTO

The builder should be callable from both:

- the anonymous preview route
- the recurring report job path later

### Exact Tests

- `CrimeAddressPreviewBuilderTest`
  - map incidents are included
  - score context is included when available
  - trend context is included when available
  - missing score/trend data degrades gracefully
  - preview stays crime-first even when other datasets exist

### Acceptance Criteria

- preview payload can be assembled without the full radial-map page
- score/trend context is visible in the same preview response
- preview behavior is deterministic under tests

## Workstream 5: Auth Continuity And Signup Return Path

### Goal

Preserve funnel context across manual signup, login, and social auth.

### Files To Change

- [AuthenticatedSessionController.php](/home/briarmoss/Documents/boston-ai-project/app/Http/Controllers/Auth/AuthenticatedSessionController.php)
- [RegisteredUserController.php](/home/briarmoss/Documents/boston-ai-project/app/Http/Controllers/Auth/RegisteredUserController.php)
- [SocialLoginController.php](/home/briarmoss/Documents/boston-ai-project/app/Http/Controllers/Auth/SocialLoginController.php)
- [Login.vue](/home/briarmoss/Documents/boston-ai-project/resources/js/Pages/Auth/Login.vue)
- [Register.vue](/home/briarmoss/Documents/boston-ai-project/resources/js/Pages/Auth/Register.vue)

### Recommended Design

Use one canonical redirect-intent mechanism for:

- login
- manual signup
- social auth

The user should return to the address preview state they came from.

### Exact Tests

- `PreviewFlowTest`
  - manual signup from preview returns to preview success state
  - login from preview returns to preview success state
  - social login start path preserves funnel intent in the redirect URL or session

- Playwright:
  - signup CTA from preview does not dump the user to `/`

### Acceptance Criteria

- all auth paths preserve funnel intent
- signup completion lands the user in the funnel, not on the generic homepage or map

## Workstream 6: Trial State And Access Rules

### Goal

Add a narrow, explicit 7-day no-card trial for the recurring one-address report.

### Files To Add

- `app/Services/ReportAccessService.php`
- migration for trial fields on `users` or a dedicated trial table

### Files To Change

- [User.php](/home/briarmoss/Documents/boston-ai-project/app/Models/User.php)
- [HandleInertiaRequests.php](/home/briarmoss/Documents/boston-ai-project/app/Http/Middleware/HandleInertiaRequests.php)
- [AppServiceProvider.php](/home/briarmoss/Documents/boston-ai-project/app/Providers/AppServiceProvider.php) if shared auth state is consolidated
- [LocationController.php](/home/briarmoss/Documents/boston-ai-project/app/Http/Controllers/LocationController.php)
- [ProfileController.php](/home/briarmoss/Documents/boston-ai-project/app/Http/Controllers/ProfileController.php) if profile needs to display trial state

### Recommended Design

Keep v1 narrow:

- one trial per user
- one primary trial address
- 7 days from activation
- no anti-abuse hardening yet beyond ordinary account creation

Suggested user fields:

- `report_trial_started_at`
- `report_trial_ends_at`
- `report_trial_location_id`
- `report_trial_status`

### Exact Tests

- `ReportAccessServiceTest`
  - new preview signup starts an active 7-day trial
  - active trial permits one recurring report location
  - expired trial loses recurring-report entitlement
  - basic tier keeps one recurring report
  - pro tier keeps expanded access

- `TrialLifecycleTest`
  - signup from preview creates or attaches the trial address
  - revisiting during active trial shows trial-active state
  - revisiting after expiry shows paywall state

### Acceptance Criteria

- the app can explicitly represent trial state without Stripe
- trial state is available to backend eligibility rules and frontend rendering

## Workstream 7: Recurring Report Dispatch Refactor

### Goal

Make recurring report delivery consistent with the new trial and plan rules and remove duplicate user-facing report dispatch.

### Files To Change

- [DispatchLocationReports.php](/home/briarmoss/Documents/boston-ai-project/app/Console/Commands/DispatchLocationReports.php)
- [SendLocationReportEmail.php](/home/briarmoss/Documents/boston-ai-project/app/Jobs/SendLocationReportEmail.php)
- [SendLocationReportEmailNoAI.php](/home/briarmoss/Documents/boston-ai-project/app/Jobs/SendLocationReportEmailNoAI.php)
- [LocationController.php](/home/briarmoss/Documents/boston-ai-project/app/Http/Controllers/LocationController.php)
- [ReportController.php](/home/briarmoss/Documents/boston-ai-project/app/Http/Controllers/ReportController.php)
- [Report.php](/home/briarmoss/Documents/boston-ai-project/app/Models/Report.php)

### Recommended Design

Move recurring eligibility out of the command body and into `ReportAccessService`.

For user-facing recurring reports:

- choose one canonical mail/report job
- stop sending duplicate raw and AI reports to the same user by default

The raw job can be:

- retired
- made admin-only
- or preserved only as a debug/ops path

### Exact Tests

- `DispatchLocationReportsTest`
  - active trial user receives one recurring report dispatch
  - expired trial user receives none
  - basic user receives one
  - pro user receives the broader eligible set
  - command does not dispatch both AI and raw jobs for the same user-facing report path

- mail/queue tests
  - `Mail::fake()` asserts one message
  - `Queue::fake()` asserts one canonical report job

### Acceptance Criteria

- recurring dispatch understands trial users
- expired users are cut off cleanly
- duplicate user-facing report sends are removed

## Workstream 8: Paywall, Pricing Surface, And Analytics

### Goal

Show the correct transition from free trial to paid recurring report and measure the funnel cleanly.

### Files To Change

- [SubscriptionController.php](/home/briarmoss/Documents/boston-ai-project/app/Http/Controllers/SubscriptionController.php)
- [Subscription.vue](/home/briarmoss/Documents/boston-ai-project/resources/js/Pages/Subscription.vue)
- [analytics.js](/home/briarmoss/Documents/boston-ai-project/resources/js/Utils/analytics.js)
- the new funnel page/components

### Recommended Design

Paywall language should match the actual jobs:

- `$5/month`: keep getting the one-address recurring report
- `$15/month`: full access to trends, scores, full map, and broader multi-neighborhood/pro use

Analytics should add:

- `crime_address_submitted`
- `crime_address_supported`
- `crime_address_unsupported`
- `coverage_request_submitted`
- `crime_preview_rendered`
- `preview_signup_started`
- `preview_signup_completed`
- `report_trial_started`
- `report_trial_expired`
- `report_plan_selected`

### Exact Tests

- feature tests for paywall state by user status
- analytics unit assertions if analytics helpers are extracted or wrapped
- Playwright:
  - active trial sees trial CTA/state
  - expired trial sees paywall with `$5` and `$15` paths

### Acceptance Criteria

- pricing language matches the actual recurring-report behavior
- funnel analytics distinguish supported, unsupported, signup, trial, and conversion steps

## Workstream 9: Browser Tests And Staged Rollout

### Goal

Roll out the funnel behind a feature flag and verify the live path safely.

### Files To Add

- feature flag config if needed
- Playwright test data helpers

### Files To Change

- whichever route or homepage CTA exposes the funnel

### Rollout Steps

1. Implement behind a flag or hidden route.
2. Run backend test suite.
3. Run browser smoke suite.
4. Validate locally through Sail.
5. Deploy behind the flag.
6. Verify production supported and unsupported paths manually.
7. Expose homepage CTA only after verification.

### Exact Test Commands

Local backend:

```bash
./vendor/bin/sail artisan test
```

Targeted backend:

```bash
./vendor/bin/sail artisan test --filter=CrimeAddressFunnel
./vendor/bin/sail artisan test --filter=DispatchLocationReportsTest
```

Fallback without Sail:

```bash
./vendor/bin/phpunit
```

Browser:

```bash
npx playwright test tests/e2e/crime-address-funnel.spec.ts
```

### Acceptance Criteria

- all targeted backend tests pass
- browser smoke passes
- production manual QA passes for:
  - one supported address
  - one unsupported address
  - one active trial user
  - one expired trial user
  - one `$5` subscriber
  - one `$15` subscriber

## Suggested Initial File Creation / Change Order

This is the recommended implementation order for the actual coding session:

1. `database/migrations/*create_coverage_requests_table.php`
2. `app/Models/CoverageRequest.php`
3. `database/factories/CoverageRequestFactory.php`
4. `database/factories/LocationFactory.php`
5. `database/factories/ReportFactory.php`
6. `app/Services/AddressServiceabilityService.php`
7. `tests/Unit/Services/AddressServiceabilityServiceTest.php`
8. `app/Http/Controllers/CoverageRequestController.php`
9. `tests/Feature/CrimeAddressFunnel/CoverageRequestTest.php`
10. `app/Services/CrimeAddressPreviewBuilder.php`
11. `tests/Unit/Services/CrimeAddressPreviewBuilderTest.php`
12. `app/Http/Controllers/CrimeAddressFunnelController.php`
13. `resources/js/Pages/CrimeAddress/Index.vue`
14. `resources/js/Pages/CrimeAddress/Preview.vue`
15. `tests/Feature/CrimeAddressFunnel/PreviewFlowTest.php`
16. auth redirect fixes in login/register/social controllers and pages
17. `tests/Feature/CrimeAddressFunnel/PreviewFlowTest.php` additions for auth continuity
18. trial-state migration and `ReportAccessService`
19. `tests/Unit/Services/ReportAccessServiceTest.php`
20. `tests/Feature/CrimeAddressFunnel/TrialLifecycleTest.php`
21. recurring report refactor in command/job files
22. `tests/Feature/Commands/DispatchLocationReportsTest.php`
23. analytics additions
24. Playwright harness and `tests/e2e/crime-address-funnel.spec.ts`

## Founder Review / Approval Split

### Safe Agent-Driven Work

- test harness
- serviceability implementation
- coverage request storage
- funnel routes and preview page
- preview report assembly
- auth redirect fixes
- trial-state implementation
- recurring report dispatch refactor
- analytics instrumentation
- browser smoke setup

### Founder-Review Work

- confirm the unsupported-area copy
- confirm the 7-day no-card trial rule
- confirm the `$5` and `$15` positioning
- confirm whether the raw recurring report path should be retired or preserved as an internal-only path

### Founder-Required Work

- public launch timing
- any external announcement copy

## Current Founder Queue Item

The current founder-review handoff for this workstream is:

- `task_1b82f5778553` in `tools/exoskeleton`

It should be updated rather than replaced if the funnel definition changes again.

## Work Log

- `2026-03-27 23:10:46 EDT`
  - founder requested an elapsed-time capture during implementation
  - user-reported active work duration at this checkpoint: `4h 44m 35s`
- `2026-03-27 23:18:12 EDT`
  - implementation stop timestamp for this pass
  - elapsed duration based on the earlier founder timer checkpoint: `4h 52m 01s`
