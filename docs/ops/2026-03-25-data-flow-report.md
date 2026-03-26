# 2026-03-25 Data Flow Report

## Purpose

This report maps the current production-relevant data flow for PublicDataWatch before any operational changes are made.

It is based on:
- code and config scanned on 2026-03-25
- current command surfaces in the Laravel app
- the separate `sysadmin/` DNS updater
- founder notes about what actually runs on Hostinger versus the development machine

The goal is to make the real system legible before changing scheduling, logging, queue policy, or analysis cadence.

## Executive Summary

- The production-critical freshness loop is `app:run-all-data-pipeline`.
- That loop is not currently scheduled inside Laravel. `php artisan schedule:list` returns no scheduled tasks.
- The only currently stated Hostinger cron is a queue listener:
  - `*/5 * * * * /usr/bin/php /home/u353344964/domains/publicdatawatch.com/bostonApp/artisan queue:listen --timeout=500 --tries=3`
- The queue listener keeps background jobs moving, but it does not itself trigger the daily ingestion pipeline.
- Daily ingestion fans out into city-specific acquisition and seeding paths, then converges into:
  - `DataPointSeeder`
  - `app:cache-metrics-data`
  - `reports:send`
- Weekly statistical analysis is a separate loop. In practice, it runs on the founder development machine because the analysis API is local there.
- Analysis artifacts are written to S3, then production pulls them back with `app:pull-analysis-reports` and materializes derived data for trends, hotspots, and scoring pages.
- The Boston and Everett acquisition paths depend on an external scraper/PDF helper service, and the production hostname for that service depends on the `sysadmin/` EC2-to-Hostinger DNS sync tool because the EC2 instance does not use an elastic IP.
- The highest-leverage backend-admin work is daily pipeline observability:
  - concise run summaries
  - failure extraction
  - freshness checks
  - dependency health checks
- Full automation of the weekly analysis loop is lower priority right now than reliable daily ingestion and metrics freshness.

## 1. Control Surfaces

The system is currently operated through several distinct surfaces:

1. Laravel CLI commands in `artisan`
2. Admin UI dispatch endpoints in the web app
3. Queue worker execution on Hostinger
4. S3 as the analysis artifact exchange layer
5. A separate Python `sysadmin/` tool for EC2 DNS correction
6. Founder-local execution for the weekly analysis loop

This matters because the system is not controlled by a single scheduler today. It is a stitched-together operational graph.

## 2. Daily Production-Critical Flow

### 2.1 Trigger and Orchestrator

The daily production-critical command is:

- `app:run-all-data-pipeline`

This command is the real top-level ingestion orchestrator. It:
- builds a run id
- creates a run directory under `storage/logs/pipeline_runs/<run_id>/`
- writes `run_summary.json`
- appends a compact entry to `storage/logs/pipeline_runs_history.json`
- executes configured stages in order
- writes one raw log file per command in the run

Stage definitions come from:
- `app/Support/AdminPipelineConfig.php`
- `config/admin_pipeline.php`

Each stage is executed as a subprocess through Symfony Process. Per-command timeout inside the pipeline is:

- `3600` seconds

Stage behavior:
- if a command fails, that stage stops
- later stages can still continue
- final pipeline status becomes `failed` if any stage failed

### 2.2 Important Current Scheduling Reality

The app currently defines no Laravel scheduled tasks:

- `php artisan schedule:list` -> `No scheduled tasks have been defined.`

So today:
- the queue worker is automated
- the daily pipeline trigger is not yet codified inside the app scheduler

That means the most important ingestion loop still depends on an external/manual trigger model.

### 2.3 Queue Runtime

Current known production queue command from founder notes:

- `queue:listen --timeout=500 --tries=3`

Relevant code-side timeouts:
- `app/Jobs/RunArtisanCommandJob.php` sets job timeout to `7200`
- `app:run-all-data-pipeline` sets subprocess timeout to `3600`

Operational implication:
- the configured queue listener timeout is shorter than both the queued command wrapper timeout and the pipeline's per-command subprocess timeout
- this is not a code change recommendation yet, but it is an important runtime mismatch to keep in view

## 3. Daily Pipeline Stage Map

The pipeline is organized by city/source, then by post-seeding aggregation and reporting.

### 3.1 Boston

Acquisition stage:
- stage name: `Boston Data Acquisition`
- command family: `app:download-boston-dataset-via-scraper --names=...`
- dataset list comes from `config/boston_datasets.php`

Configured Boston datasets:
- `311-service-requests-2026`
- `311-service-requests-2025`
- `construction-off-hours`
- `building-permits`
- `crime-incident-reports`
- `trash-schedules-by-address`
- `property-violations`
- `food-inspections`

Dependency:
- uses `config('services.scraper_service.base_url')`
- this is an external scraper helper, not a local Laravel fetch

Seeding stage:
- stage name: `Boston Data Seeding`
- seeders configured in `config/admin_pipeline.php`:
  - `TrashSchedulesByAddressSeeder`
  - `CrimeDataSeeder`
  - `ThreeOneOneSeeder`
  - `BuildingPermitsSeeder`
  - `PropertyViolationsSeeder`
  - `ConstructionOffHoursSeeder`
  - `FoodInspectionsSeeder`

### 3.2 Cambridge

Acquisition stage:
- stage name: `Cambridge Data Acquisition`
- generic Socrata downloads via `app:download-city-dataset`
- one extra command: `app:download-cambridge-logs`

Configured Cambridge datasets from `config/datasets.php`:
- `cambridge-311-service-requests`
- `cambridge-building-permits`
- `cambridge-sanitary-inspections`
- `cambridge-housing-code-violations`
- `cambridge-crime-reports`
- `cambridge-master-addresses-list`
- `cambridge-master-intersections-list`

Special case:
- `app:download-cambridge-logs` is a custom scraper/downloader with very verbose per-entry logging

Seeding stage:
- stage name: `Cambridge Data Seeding`
- configured seeders:
  - `NativeCambridgeBuildingPermitsSeeder`
  - `NativeCambridgeThreeOneOneSeeder`
  - `NativeCambridgeSanitaryInspectionsSeeder`
  - `NativeCambridgeHousingViolationsSeeder`
  - `CambridgeAddressesSeeder`
  - `CambridgeIntersectionsSeeder`
  - `CambridgeCrimeDataSeederMerge`
  - `CambridgePoliceLogSeeder`

### 3.3 Everett

Acquisition and processing stage:
- stage name: `Everett Data Acquisition & Processing`
- commands:
  - `app:download-everett-pdf-markdown`
  - `everett:process-data`
  - `app:generate-everett-csv`

Flow:
1. `app:download-everett-pdf-markdown`
   - uses the scraper/PDF helper service
   - fetches Everett source pages and converts PDFs to markdown
   - stores markdown under `storage/app/datasets/everett/...`
2. `everett:process-data`
   - parses Everett markdown
   - combines records into JSON
   - geocodes addresses using the Google geocoding key
   - writes combined JSON plus geocode cache
3. `app:generate-everett-csv`
   - combines parsed Everett JSON with the geocode cache
   - outputs the CSV that the seeder expects

Seeding stage:
- stage name: `Everett Data Seeding`
- configured seeder:
  - `EverettCrimeDataSeeder`

Dependencies:
- scraper helper service
- Google geocoding

### 3.4 Chicago

Acquisition stage:
- stage name: `Chicago Data Acquisition`
- dataset: `chicago-crimes-2001-to-present`
- command: `app:download-city-dataset`
- pagination style: Socrata by year
- configured for incremental download against `App\Models\ChicagoCrime`

Seeding stage:
- `ChicagoCrimeSeeder`
- `ChicagoDataPointSeeder`

### 3.5 San Francisco

Acquisition stage:
- stage name: `San Francisco Data Acquisition`
- dataset: `san_francisco-crimes`
- command: `app:download-city-dataset`

Seeding stage:
- `SanFranciscoCrimeSeeder`
- `SanFranciscoDataPointSeeder`

### 3.6 Seattle

Acquisition stage:
- stage name: `Seattle Data Acquisition`
- dataset: `seattle-crimes`
- command: `app:download-city-dataset`

Seeding stage:
- `SeattleCrimeSeeder`
- `SeattleDataPointSeeder`

### 3.7 Montgomery County, MD

Acquisition stage:
- stage name: `Montgomery County MD Data Acquisition`
- dataset: `montgomery_county_md-crimes`
- command: `app:download-city-dataset`

Seeding stage:
- `MontgomeryCountyMdCrimeSeeder`
- `MontgomeryCountyMdDataPointSeeder`

### 3.8 New York

Acquisition stage:
- stage name: `New York Data Acquisition`
- dataset: `new_york-311s`
- command: `app:download-city-dataset`
- configured for incremental download against `App\Models\NewYork311`

Seeding stage:
- `NewYork311Seeder`
- `NewYorkDataPointSeeder`

## 4. Cross-City Aggregation and Reporting

After city-level acquisition and seeding, the pipeline converges into two general sections from `config/admin_pipeline.php`.

### 4.1 Post-Seeding Aggregation and Caching

Stage name:
- `Post-Seeding Aggregation & Caching`

Commands:
- `db:seed --class=DataPointSeeder --force`
- `app:cache-metrics-data`

#### `DataPointSeeder`

This is the central aggregation step.

It:
- walks configured source models
- transforms them into unified map/search records
- populates aggregated data point tables
- truncates old `data_points` records older than about 183 days

City-level aggregation targets from `config/cities.php`:
- Boston and Everett share `data_points`
- Chicago uses `chicago_data_points`
- San Francisco uses `san_francisco_data_points`
- New York uses `new_york_data_points`
- Montgomery County uses `montgomery_county_md_data_points`
- Seattle uses `seattle_data_points`

This is the step that turns per-source raw tables into the shared browsing/search surface the site actually uses.

#### `app:cache-metrics-data`

This command:
- calculates summary counts and date freshness across key models
- computes model-specific summary metrics
- writes the result to the `metrics_snapshots` table

This is the current metrics snapshot artifact. It is database-backed, not Redis-backed.

### 4.2 Reporting

Stage name:
- `Reporting`

Command:
- `reports:send`

This command:
- finds eligible user/location subscriptions
- dispatches report email jobs
- currently sends both:
  - `SendLocationReportEmailNoAI`
  - `SendLocationReportEmail`

These reporting jobs depend on the freshly loaded data surfaces and therefore sit downstream of seeding and aggregation.

## 5. Daily Data Storage Path

The daily flow crosses several storage layers.

### 5.1 Raw/Intermediate Local Files

Examples:
- `storage/app/datasets/...`
- Everett markdown, JSON, and CSV intermediates
- Cambridge downloads
- Boston scraper outputs

### 5.2 Source Tables

City/source seeders load raw datasets into their model tables, for example:
- `crime_data`
- `three_one_one_cases`
- `everett_crime_data`
- city-specific equivalents in other databases/connections

The exact source-model inventory is driven by:
- `config/cities.php`
- the seeders configured in `config/admin_pipeline.php`

### 5.3 Aggregated Data Point Tables

After raw tables are loaded:
- `DataPointSeeder` builds the aggregated map/search tables

These are the main public-facing unified location/event layers.

### 5.4 Metrics Cache Artifact

After aggregation:
- `app:cache-metrics-data` writes the current row in `metrics_snapshots`

That row becomes the cached snapshot for dashboard/stat pages that consume metrics.

### 5.5 Pipeline Log Artifacts

Each pipeline run creates:
- `storage/logs/pipeline_runs/<run_id>/run_summary.json`
- `storage/logs/pipeline_runs/<run_id>/cmd_<command>_<timestamp>.log`
- `storage/logs/pipeline_runs_history.json`

This is the most structured current observability surface for daily ingestion.

## 6. Weekly Analysis Flow

This is the second major data loop, but it is not the same as the daily freshness pipeline.

### 6.1 Operational Reality

Based on founder notes:
- `app:run-weekly-analysis` is currently run on the development machine, not the Hostinger server
- the reason is cost and compute locality: the analysis API is run locally rather than on hosted infrastructure

This means the analysis loop is intentionally split across environments.

### 6.2 Orchestrator

Top-level command:
- `app:run-weekly-analysis`

Config:
- `config/analysis_schedule.php`

Stages:
- Stage 2 yearly count comparison
- Stage 4 statistical anomaly/trend analysis
- Stage 6 historical scoring

### 6.3 Export and Dispatch Pattern

Each analysis dispatcher follows the same basic pattern:

1. discover analyzable models
2. export data into `storage/app/public/analysis_exports/*.csv`
3. expose those exports through Laravel public storage URLs
4. POST job payloads to the Python analysis API
5. store local metadata in Laravel tables where applicable

Relevant dispatch commands:
- `app:dispatch-yearly-count-comparison-jobs`
- `app:dispatch-statistical-analysis-jobs`
- `app:dispatch-historical-scoring-jobs`

The Python API base URL comes from:
- `config('services.analysis_api.url')`

### 6.4 Artifacts Produced

Artifact names the production app explicitly knows how to consume:
- `stage2_yearly_count_comparison.json`
- `stage4_h3_anomaly.json`
- files prefixed with `scoring_results`
- files prefixed with `stage6`

These artifacts are stored in S3.

### 6.5 Production Pull-Back Path

Production ingests those analysis artifacts with:

- `app:pull-analysis-reports`

This command:
- scans S3 for relevant artifacts
- stores them in `analysis_report_snapshots`
- can skip already-snapshotted artifacts unless `--fresh` is passed
- re-materializes hotspot findings unless `--skip-hotspots` is used
- warms trend summaries
- clears listing caches

Derived downstream steps:
- `app:materialize-hotspot-findings`
- `TrendSummaryService::computeAndCache(...)`

### 6.6 Derived Analysis Storage

The analysis loop materializes into:
- `analysis_report_snapshots`
- `trends`
- `h3_hotspot_findings`
- cache entries like `trend_summary_v6_<job_id>`

These drive:
- `/trends`
- yearly comparison views
- scoring report views
- `/hotspots`
- admin S3/cache tooling

### 6.7 Current Priority Interpretation

Because the trend/scoring surfaces are still lightly marketed and lightly exposed, the weekly analysis loop is currently lower priority than the daily ingestion loop.

Operationally, that supports the current founder idea:
- daily ingestion should be robust and easy to review
- weekly analysis can remain a less frequent founder-run or monthly task until those surfaces matter more

## 7. External Dependency Flow

### 7.1 Scraper/PDF Helper Service

Used by:
- `app:download-boston-dataset-via-scraper`
- `app:download-everett-pdf-markdown`

Config:
- `config/services.php`
- `services.scraper_service.base_url`

This service handles work that is not directly Socrata-accessible or that needs HTML/PDF conversion.

### 7.2 EC2 Public-IP to Hostinger DNS Sync

This dependency lives outside Laravel in `sysadmin/`.

Relevant files:
- `sysadmin/main.py`
- `sysadmin/actions/sync_ec2_dns.py`
- `sysadmin/aws/ec2.py`
- `sysadmin/dns/hostinger.py`
- `sysadmin/config.py`

Flow:
1. resolve the target EC2 instance public IP by instance ID or Name tag
2. read the current Hostinger DNS A record
3. compare them
4. update the A record if the EC2 IP changed

This exists because:
- the EC2 instance does not use an elastic IP
- production still needs a stable hostname for the scraper/PDF helper path

This means the data pipeline has an off-app infrastructure dependency:
- if DNS drifts or the sync tool stops running, Boston/Everett scraper-dependent acquisition can fail even if Laravel itself is healthy

### 7.3 S3 Artifact Exchange

S3 is the exchange layer between:
- founder-local weekly analysis execution
- production consumption and rendering

Config lives in:
- `config/filesystems.php`
- `config/services.php`

This is not just backup storage. It is an operational bridge between environments.

## 8. Current Observability Surfaces

### 8.1 What Already Exists

- file-based pipeline run summaries
- per-command raw logs
- `pipeline_runs_history.json`
- admin pipeline history UI
- admin pipeline detail UI
- admin cache manager
- admin S3 browser
- Laravel logs

### 8.2 What Is Still Weak

- no single daily freshness summary
- no automatic failure digest across all subcommands
- log granularity varies heavily by command
- some commands log at record-by-record or near-record-by-record level
- the daily critical path is spread across raw logs, JSON summaries, queue behavior, and external dependencies

This matches the founder note that some logs are too verbose to be useful for daily review.

## 9. Current Risk Points

1. The production-critical daily pipeline is not yet scheduled inside Laravel.
2. The only known cron is a queue listener, not a data-pipeline trigger.
3. Queue timeout configuration appears shorter than some actual long-running command envelopes.
4. Boston and Everett acquisition depend on an external scraper service plus a separate DNS correction loop.
5. Weekly analysis depends on founder-local infrastructure and is not a self-contained production loop.
6. Daily review currently requires reading too many raw logs to find the real problems quickly.

## 10. Recommended Next Operational Focus

Before changing ingestion behavior, the next backend-admin work should focus on the daily loop.

### Priority 1

Make the daily pipeline easy to review in under a few minutes.

That means:
- summarize per-command success/failure cleanly
- extract the first meaningful error instead of dumping full raw logs
- report which datasets/tables were refreshed and when
- report which downstream steps ran:
  - aggregation
  - metrics cache
  - reports dispatch

### Priority 2

Make dependency health visible.

That means:
- scraper service reachability
- DNS target correctness for the scraper hostname
- queue worker health
- S3 availability only where needed for analysis pullback

### Priority 3

After observability is improved, formalize the trigger model.

That means choosing one of:
- Laravel scheduler driven by one Hostinger cron
- explicit external cron for `app:run-all-data-pipeline`

This report does not choose that implementation yet. It only records that the current model is fragmented.

### De-Prioritized For Now

- full automation of weekly analysis
- trend/scoring cadence optimization
- deeper analysis-page operational polish

Those can wait until the site is driving more traffic to those surfaces.

## 11. Files Scanned For This Report

### Core orchestration

- `app/Console/Kernel.php`
- `app/Support/AdminPipelineConfig.php`
- `config/admin_pipeline.php`
- `config/analysis_schedule.php`
- `app/Jobs/RunArtisanCommandJob.php`
- `app/Console/Commands/RunAllDataPipelineCommand.php`
- `app/Console/Commands/RunWeeklyAnalysisCommand.php`
- `app/Http/Controllers/AdminController.php`
- `resources/js/Pages/Admin/JobDispatcher.vue`

### Daily ingestion and aggregation

- `config/boston_datasets.php`
- `config/datasets.php`
- `config/cities.php`
- `app/Console/Commands/DownloadCityDataset.php`
- `app/Console/Commands/DownloadBostonDatasetViaScraper.php`
- `app/Console/Commands/DownloadCambridgeLogs.php`
- `app/Console/Commands/DownloadEverettPDFMarkdown.php`
- `app/Console/Commands/ProcessEverettDataCommand.php`
- `app/Console/Commands/GenerateEverettCsvCommand.php`
- `database/seeders/DataPointSeeder.php`
- `app/Console/Commands/CacheMetricsDataCommand.php`
- `app/Console/Commands/DispatchLocationReports.php`
- `app/Jobs/SendLocationReportEmailNoAI.php`

### Analysis export, pullback, and derived views

- `config/services.php`
- `config/filesystems.php`
- `app/Console/Commands/DispatchYearlyCountComparisonJobsCommand.php`
- `app/Console/Commands/DispatchStatisticalAnalysisJobsCommand.php`
- `app/Console/Commands/DispatchHistoricalScoringJobsCommand.php`
- `app/Console/Commands/PullAnalysisReportsCommand.php`
- `app/Console/Commands/MaterializeHotspotFindingsCommand.php`
- `app/Models/AnalysisReportSnapshot.php`
- `app/Models/Trend.php`
- `app/Models/HotspotFinding.php`
- `app/Services/TrendSummaryService.php`
- `app/Http/Controllers/AdminCacheController.php`
- `app/Http/Controllers/AdminS3BucketController.php`
- `app/Http/Controllers/TrendsController.php`
- `app/Http/Controllers/HotspotController.php`

### External DNS dependency

- `sysadmin/main.py`
- `sysadmin/actions/sync_ec2_dns.py`
- `sysadmin/aws/ec2.py`
- `sysadmin/dns/hostinger.py`
- `sysadmin/config.py`

### Runtime checks run during the scan

- `php artisan schedule:list`
- `php artisan about --json`

## 12. Decision Framing

The system is already doing real work. The problem is not lack of moving parts. The problem is that the daily critical path is more fragile and harder to inspect than it should be.

So the immediate backend-admin question is not:

- how do we automate everything?

It is:

- how do we make the daily ingestion loop obvious, reviewable, and safe enough that automation is trustworthy?

That should be the next implementation focus.
