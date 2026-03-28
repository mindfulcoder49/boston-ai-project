# CLAUDE.md

This file gives implementation context for coding agents working in this repository.

## Project Overview

PublicDataWatch is a Laravel 10 + Vue 3 civic-data platform with an address-first public funnel.

Current public product shape:

- homepage at `/` leads with the crime-address preview
- `/crime-address` is the primary acquisition and monetization funnel
- city and region landing pages live at `/{city-slug}`
- deeper tools live under Explore: radial map, full data map, trends, yearly comparisons, and scoring
- pricing lives at `/subscription`

Current supported cities and regions:

- Boston
- Cambridge
- Everett
- Chicago
- San Francisco
- New York
- Montgomery County, MD
- Seattle

Coverage is dataset-specific. Boston is the broadest multi-dataset area. Several other regions are crime-first. New York is currently 311-first.

## Preferred Commands

Install:

```bash
composer install
npm install
```

Local development:

```bash
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate
npm run dev
```

When Sail is not in use:

```bash
php artisan migrate
php artisan serve
npm run dev
```

Testing:

```bash
./vendor/bin/sail test
npx playwright test
npm run build
```

## Production Deploy

Production deploys run on Hostinger through:

```bash
ssh <host-alias-from-ssh-config> '~/publicdatawatchdeploy.sh'
```

The deploy script currently:

- fetches and hard-resets to `origin/main`
- runs Composer install
- runs `npm run build`
- copies `public/build` into `public_html/build`
- runs `php artisan route:cache`

Read the exact SSH alias, user, and port from local `~/.ssh/config` rather than hard-coding them in repo docs.

## Standalone Local Tooling

- `tools/analytics`
  - GA4 and Search Console access/reporting
  - default live GA4 property: `properties/490826923`
  - default Search Console property: `sc-domain:publicdatawatch.com`
- `tools/exoskeleton`
  - founder action queue for `founder_review` and `founder_required` handoffs
- `city-generator`
  - scaffolding for onboarding additional cities

## Core Public Pages

- `resources/js/Pages/Home.vue`
  - address-first homepage
- `resources/js/Pages/CrimeAddress/Index.vue`
  - crime preview funnel
- `resources/js/Pages/CityMapLite.vue`
  - city landing page shell
- `resources/js/Pages/RadialMap.vue`
  - radial explore workflow
- `resources/js/Pages/CombinedDataMap.vue`
  - full multi-dataset map
- `resources/js/Pages/Subscription.vue`
  - plan selection and checkout framing

Navigation and footer are driven from:

- `resources/js/Utils/publicNavigation.js`
- `resources/js/Components/PageTemplate.vue`
- `resources/js/Components/Footer.vue`

## Analytics And SEO

Shared analytics helpers live in:

- `resources/js/Utils/analytics.js`

Current notable event families:

- `page_view`
- `city_page_view`
- `explore_map_view`
- `pricing_page_view`
- `crime_address_*`
- `signup_*`
- `checkout_*`

Shared metadata behavior is driven by:

- `app/Support/SeoMetadata.php`
- `app/Http/Controllers/SitemapController.php`

## Data Pipeline Architecture

The daily pipeline is orchestrated by:

- `app/Console/Commands/RunAllDataPipelineCommand.php`

High-level stages:

1. Boston acquisition
2. Boston seeding
3. Cambridge acquisition
4. Cambridge seeding
5. Everett acquisition and processing
6. Everett seeding
7. Chicago seeding
8. San Francisco seeding
9. Seattle seeding
10. Montgomery County, MD seeding
11. Post-seeding aggregation and report dispatch

Operational summaries and schedules are configured through:

- `app/Support/AdminPipelineConfig.php`
- `config/backend_admin.php`

## Key Architecture Patterns

### Mappable models

All map-displayable models must use `App\Models\Concerns\Mappable` and provide the static methods the trait expects for coordinates, dates, popups, and styling.

### Aggregated point layer

`DataPointSeeder` populates the aggregated point table that powers the radial map and some shared spatial queries.

### Crime-address funnel

The acquisition funnel depends on:

- `app/Http/Controllers/CrimeAddressFunnelController.php`
- `app/Services/AddressServiceabilityService.php`
- `app/Services/CrimeAddressPreviewBuilder.php`
- `app/Services/ScoreContextBuilder.php`
- `app/Services/ReportAccessService.php`

### City landing pages

City routes are generated from `config('cities.cities')` and handled by:

- `app/Http/Controllers/CityLandingController.php`
- `resources/js/Pages/CityMapLite.vue`

## Key Config Files

- `config/cities.php`
- `config/datasets.php`
- `config/boston_datasets.php`
- `config/everett_datasets.php`
- `config/backend_admin.php`
- `config/services.php`
- `config/statistical_metrics.php`
- `config/model_metadata_suggestions.php`

## Adding A New City

See:

- `docs/ADDING_A_CITY.md`
- `docs/CHICAGO_INTEGRATION_GUIDE.md`
- `city-generator/README.md`

In practice, new-city work usually means:

1. add city config and serviceability rules
2. add database connections and migrations
3. add model + seeder + datapoint aggregation
4. add homepage/city-landing metadata
5. add tests and production verification

## Analysis API Integration

This app dispatches external statistical jobs to the `open-data-statistics` FastAPI service.

Relevant commands:

- `app:dispatch-statistical-analysis-jobs`
- `app:dispatch-yearly-count-comparison-jobs`
- `app:dispatch-historical-scoring-jobs`
- `app:dispatch-news-article-generation-jobs`

See:

- `docs/ANALYSIS_API_INTEGRATION.md`

## Reference Docs

- `docs/PAGES_AND_UX.md`
- `docs/ops/OPERATING_SYSTEM.md`
- `docs/ops/analytics.md`
- `docs/ops/seo.md`
- `docs/ops/growth-monetization.md`
- `docs/ops/social-distribution.md`
