# Pages and UX Inventory

This document catalogs the current PublicDataWatch public surface, its route structure, and the intended UX role of each page.

## Navigation Structure

Current public navigation:

- `Home`
- `Crime Preview`
- `Cities`
- `Explore`
- `Pricing`
- `Help`

Authenticated account actions live outside the main public nav:

- `Saved Locations`
- `Saved Maps`
- `Report History`
- `Billing`
- `Profile`

Navigation and footer are generated from:

- `resources/js/Utils/publicNavigation.js`
- `resources/js/Components/PageTemplate.vue`
- `resources/js/Components/Footer.vue`

## Public Pages

### Home

- **Route**: `GET /`
- **Controller**: `HomeController::index()`
- **Vue Page**: `resources/js/Pages/Home.vue`
- **Purpose**: primary acquisition surface; leads with the address-first crime preview funnel and then introduces supported city pages and deeper explore tools
- **Data passed**: `cities[]`, `dataCategories[]`, `stats{totalRecords, cityCount, dataCategoryCount}`
- **Notes**:
  - city cards should point to city landing pages first
  - the hero should always make the first action obvious: search an address

### Crime Address Preview

- **Route**: `GET /crime-address`
- **Controller**: `CrimeAddressFunnelController::index()`
- **Vue Page**: `resources/js/Pages/CrimeAddress/Index.vue`
- **Primary APIs**:
  - `POST /api/crime-address/preview`
  - `POST /api/crime-address/context`
  - `POST /api/crime-address/coverage-request`
  - `POST /api/crime-address/trial/start` (auth)
- **Purpose**: acquisition and monetization wedge for users who want to know what crime is happening around one address
- **UX shape**:
  - address search first
  - unsupported-address branch with notify-me capture
  - supported preview with incidents first
  - score and trend context layered in for interpretation
  - CTA into free trial, then paid plans

### City Landing Pages

- **Routes**:
  - `/boston`
  - `/everett`
  - `/chicago`
  - `/san-francisco`
  - `/new-york`
  - `/montgomery-county-md`
  - `/seattle`
- **Controller**: `CityLandingController::show()`
- **Vue Page**: `resources/js/Pages/CityMapLite.vue`
- **Purpose**: search-facing regional entry points with city-specific positioning, dataset framing, and a lightweight map
- **Notes**:
  - Boston is the broadest multi-dataset city page
  - New York is 311-first, not crime-first
  - crime-enabled city pages should bridge naturally into `/crime-address`

### Radial Explore Map

- **Route**: `GET /map/{lat?}/{lng?}`
- **Controller**: route closure returning `RadialMap`
- **Vue Page**: `resources/js/Pages/RadialMap.vue`
- **Primary API**: `POST /api/map-data`
- **Purpose**: deeper address-centered exploration once the user needs more than the stripped-down preview
- **Notes**:
  - not the first public CTA anymore
  - still the fastest wide-area discovery tool in the product

### Full Data Map

- **Routes**:
  - `GET /data-map/{dataType}`
  - `GET /combined-map?types=...`
- **Controller**: `DataMapController`
- **Vue Pages**:
  - `resources/js/Pages/DataMap.vue`
  - `resources/js/Pages/CombinedDataMap.vue`
- **Purpose**: full map workflows for multi-neighborhood analysis, layered datasets, and power users

### Trends

- **Route**: `GET /trends`
- **Controller**: `TrendsController::index()`
- **Vue Page**: `resources/js/Pages/Trends/Index.vue`
- **Purpose**: browse region-level and model-level statistical findings

### Yearly Comparisons

- **Route**: `GET /yearly-comparisons`
- **Controller**: `YearlyCountComparisonController::index()`
- **Vue Page**: `resources/js/Pages/YearlyCountComparison/Index.vue`
- **Purpose**: compare current activity with prior years

### Neighborhood Scoring

- **Route**: `GET /scoring-reports`
- **Controller**: `ScoringReportController::index()`
- **Vue Page**: `resources/js/Pages/Reports/Scoring/Index.vue`
- **Purpose**: explore detailed scoring artifacts and deeper methodology-heavy scoring workflows

### Pricing

- **Route**: `GET /subscription`
- **Controller**: `SubscriptionController::index()`
- **Vue Page**: `resources/js/Pages/Subscription.vue`
- **Purpose**: convert trial and preview users into paid plans
- **Current framing**:
  - `$5/month` for the one-address recurring workflow
  - `$15/month` for broader full-tool access

### Help Center

- **Routes**:
  - `/help`
  - `/help/users`
  - `/help/municipalities`
  - `/help/researchers`
  - `/help/investors`
- **Vue Pages**:
  - `resources/js/Pages/Help/Index.vue`
  - `resources/js/Pages/Help/ForUsers.vue`
  - `resources/js/Pages/Help/ForMunicipalities.vue`
  - `resources/js/Pages/Help/ForResearchers.vue`
  - `resources/js/Pages/Help/ForInvestors.vue`
- **Purpose**: explain the product to different audiences without assuming they already understand the navigation or methodology

### About and Legal

- **Routes**:
  - `/about-us`
  - `/privacy-policy`
  - `/terms-of-use`
- **Vue Pages**:
  - `resources/js/Pages/Company/AboutUs.vue`
  - `resources/js/Pages/Legal/PrivacyPolicy.vue`
  - `resources/js/Pages/Legal/TermsOfUse.vue`

## Legacy And Secondary Surfaces

These still exist but are no longer the primary public wedge:

- `/crime-map`
- `/scatter`
- `/saved-maps`
- `/csvreports/map`
- public report viewers that are not part of the primary funnel

## UX Priorities

### 1. Keep the first action concrete

The public site should always make it easier to:

- enter an address
- understand the immediate answer
- decide whether deeper tools are worth opening

### 2. Make city pages genuinely different

City landing pages should not feel like one generic shell with a swapped city name. Each page should reflect:

- actual dataset mix
- actual use case
- actual region scope

### 3. Keep advanced tools one layer deeper

The public home, help, and footer should not force every visitor to choose between five analysis tools before they know whether the product is useful.

### 4. Explain scores in plain language

Scores only work when users can compare them against the city, nearby areas, and recent local activity. A lone number is not enough.

## Files Most Likely To Matter For Public IA Work

- `app/Http/Controllers/HomeController.php`
- `app/Http/Controllers/CrimeAddressFunnelController.php`
- `app/Http/Controllers/CityLandingController.php`
- `resources/js/Pages/Home.vue`
- `resources/js/Pages/CrimeAddress/Index.vue`
- `resources/js/Pages/CityMapLite.vue`
- `resources/js/Pages/Subscription.vue`
- `resources/js/Utils/publicNavigation.js`
- `resources/js/Components/PageTemplate.vue`
- `resources/js/Components/Footer.vue`
