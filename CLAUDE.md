# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

BostonScope/PublicDataWatch is a Laravel 10 + Vue 3 civic data platform that aggregates, analyzes, and visualizes public data from Boston, Cambridge, Everett, and Chicago. It uses Inertia.js for SPA-like routing with server-side controllers.

## Development Commands

```bash
# Install dependencies
composer install
npm install

# Development
npm run dev          # Start Vite dev server
php artisan serve    # Start Laravel dev server

# Build for production
npm run build

# Database
php artisan migrate
php artisan migrate --database=person_crash_data  # Secondary DB

# Testing
php artisan test
./vendor/bin/phpunit tests/Feature/SomeTest.php  # Single test

# Queue processing (required for jobs)
php artisan queue:work
```

## Standalone Local Tooling

This repo also has local-first support workspaces that should be used instead of pushing everything into Laravel:

- `tools/analytics`
  - GA4 and Search Console access/reporting via `pdw-analytics`
  - documented current properties:
    - GA4: `properties/490826923` (`PublicDataWatch`)
    - Search Console: `sc-domain:publicdatawatch.com`
- `tools/exoskeleton`
  - founder action queue for `founder_review` and `founder_required` handoffs
- `city-generator`
  - LangGraph-based scaffolding tool for adding new cities from Socrata datasets

## Current Backend Ops State

As of March 25, 2026:

- Laravel scheduler entries are implemented in code for:
  - `app:run-admin-long-worker` which wraps `queue:work --stop-when-empty --queue=admin-long,default --timeout=7200 --tries=1`
  - `app:check-ingestion-dependencies`
  - `app:dispatch-daily-pipeline`
  - `app:evaluate-backend-health-alerts`
- Hostinger production is confirmed to use `php artisan schedule:run`
- the remaining backend-admin follow-up is mostly external-runtime confirmation, not missing Laravel code

## Data Pipeline Architecture

The data pipeline is orchestrated by `RunAllDataPipelineCommand` (`app:run-all-data-pipeline`) which runs stages sequentially:

### Pipeline Stages

1. **Boston Data Acquisition** - Downloads datasets from Boston Open Data Portal via scraper service
   - Command: `app:download-boston-dataset-via-scraper`
   - Config: `config/boston_datasets.php` defines resource IDs for 8 datasets

2. **Boston Data Seeding** - Processes CSVs into database tables
   - Seeders: `CrimeDataSeeder`, `ThreeOneOneSeeder`, `BuildingPermitsSeeder`, `PropertyViolationsSeeder`, `ConstructionOffHoursSeeder`, `FoodInspectionsSeeder`, `TrashSchedulesByAddressSeeder`

3. **Cambridge Data Acquisition**
   - Commands: `app:download-city-dataset`, `app:download-cambridge-logs`

4. **Cambridge Data Seeding**
   - Seeders: `NativeCambridgeBuildingPermitsSeeder`, `NativeCambridgeThreeOneOneSeeder`, `NativeCambridgeSanitaryInspectionsSeeder`, `NativeCambridgeHousingViolationsSeeder`, `CambridgeAddressesSeeder`, `CambridgeIntersectionsSeeder`, `CambridgeCrimeDataSeederMerge`, `CambridgePoliceLogSeeder`

5. **Everett Data Acquisition & Processing**
   - Commands: `app:download-everett-pdf-markdown`, `everett:process-data`, `app:generate-everett-csv`
   - Converts police PDF reports to structured data

6. **Everett Data Seeding** - `EverettCrimeDataSeeder`

7. **Chicago Data Seeding** - `ChicagoCrimeSeeder`, `ChicagoDataPointSeeder`

8. **San Francisco Data Seeding** - `SanFranciscoCrimeSeeder`, `SanFranciscoDataPointSeeder`

9. **Seattle Data Seeding** - `SeattleCrimeSeeder`, `SeattleDataPointSeeder`

10. **Montgomery County MD Data Seeding** - `MontgomeryCountyMdCrimeSeeder`, `MontgomeryCountyMdDataPointSeeder`

11. **Post-Seeding Aggregation**
   - `DataPointSeeder` - Aggregates all city data into unified `data_points` table for map display
   - `app:cache-metrics-data` - Caches statistics for dashboard

12. **Reporting** - `reports:send` dispatches location-based email reports

### Running Specific Stages

```bash
# Run all stages
php artisan app:run-all-data-pipeline

# Run specific stages
php artisan app:run-all-data-pipeline --stages="Boston Data Acquisition,Boston Data Seeding"

# Run specific seeders within a stage
php artisan app:run-all-data-pipeline --boston-seeders="CrimeDataSeeder,ThreeOneOneSeeder"

# Run specific datasets
php artisan app:run-all-data-pipeline --boston-datasets="crime-incident-reports,building-permits"
```

### Pipeline Logging

Logs are written to `storage/logs/pipeline_runs/{run_id}/` with per-command log files. View via Admin panel at `/admin/pipeline-file-logs`.

## Key Architecture Patterns

### Mappable Trait

All map-displayable models must use `App\Models\Concerns\Mappable` trait and implement:
- `getHumanName()` - Display name
- `getLatitudeField()` / `getLongitudeField()` - Coordinate field names
- `getDateField()` - Date field for filtering
- `getExternalIdName()` / `getExternalId()` - Unique identifier
- `getPopupConfig()` - Map popup display configuration
- `getIconClass()` / `getAlcivartechTypeForStyling()` - Map styling

### DataPoint Aggregation

The `DataPointSeeder` reads from all Mappable models and populates the `data_points` table with spatial point geometry. This powers the radial map queries in `GenericMapController`.

### Multi-Database Architecture

- Default MySQL connection: Main app data, users, locations
- `person_crash_data` connection: Traffic crash data (secondary DB)
- Pattern for new cities: Two databases per city - `{city}_db` (recent 6 months) and `{city}_data_db` (full historical)

### Seeder Pattern

Seeders follow a common pattern:
1. Find most recent downloaded CSV in `storage/app/datasets/`
2. Parse CSV with `League\Csv\Reader`
3. Batch upsert records (typically 500-1000 per batch)
4. Use address lookup for geocoding fallback (e.g., `CrimeDataSeeder` uses `TrashScheduleByAddress` for coordinate lookup)

## Configuration Files

- `config/boston_datasets.php` - Boston Open Data resource IDs
- `config/datasets.php` - Generic multi-city dataset configuration
- `config/everett_datasets.php` - Everett police data sources
- `config/statistical_metrics.php` - Analysis metric definitions
- `config/model_metadata_suggestions.php` - AI/GPT schema hints per model
- `config/services.php` - API keys (Google Places, Stripe, OpenAI, scraper service)

## Adding a New City

See `docs/# Adding a New City to BostonScope.md` for full guide. Key steps:
1. Create two database connections in `config/database.php`
2. Add dataset config to `config/datasets.php`
3. Create Model with Mappable trait
4. Create migrations for both databases
5. Create Seeder for ETL
6. Create DataPointSeeder for aggregation
7. Update `GenericMapController::getCityContext()`

## Analysis & Reporting

- `app:dispatch-statistical-analysis-jobs` - Statistical trend analysis
- `app:dispatch-yearly-count-comparison-jobs` - Year-over-year comparisons
- `app:dispatch-news-article-generation-jobs` - AI-generated news from analysis
- `app:analyze-statistical-anomalies` - Anomaly detection

Reports are stored in `Trend`, `YearlyCountComparison`, and `NewsArticle` models.

## Frontend Architecture

- Vue 3 pages in `resources/js/Pages/`
- Reusable components in `resources/js/Components/`
- Map components use Leaflet via `@vue-leaflet/vue-leaflet`
- Charts use ECharts via `vue-echarts`
- Inertia.js handles routing - no API endpoints needed for page data

## Analysis API Integration

This app dispatches statistical analysis jobs to the `open-data-statistics` FastAPI service (separate repo). The API URL is set via `ANALYSIS_API_URL` in `.env` and read as `config('services.analysis_api.url')`.

**Artisan commands that dispatch jobs:**
- `app:dispatch-statistical-analysis-jobs` — Stage 4 H3 anomaly analysis; updates `Trend` model
- `app:dispatch-yearly-count-comparison-jobs` — Stage 2 year-over-year comparison; updates `YearlyCountComparison` model
- `app:dispatch-historical-scoring-jobs` — Stage 6 historical scoring (no model, logged only)

**Results are consumed by:**
- `StatisticalAnalysisReportController` — reads Stage 4 results from S3
- `YearlyCountComparisonController` — fetches Stage 2 results live from the API
- `ScoringReportController` — reads Stage 5/6 scoring artifacts from S3 (cached)

See `docs/ANALYSIS_API_INTEGRATION.md` for full details on payloads, data flow, S3 layout, and failure modes.

See `docs/PAGES_AND_UX.md` for a complete inventory of all pages, their data sources, components, and UX improvement goals.
