# Analysis API Integration

This document covers how the PublicDataWatch Laravel app integrates with the `open-data-statistics` FastAPI service.

## Environment Configuration

```properties
# .env
ANALYSIS_API_URL=http://open-data-statistics-backend-1:8080  # local Sail network
ANALYSIS_API_URL=http://localhost:8030                       # local dev when Laravel runs natively
ANALYSIS_API_URL=https://your-api-domain.com                 # production
```

Accessed in code via `config('services.analysis_api.url')`, configured in `config/services.php`.

---

## Integration Architecture

```
Laravel (PublicDataWatch)
  │
  ├─ Artisan Commands (dispatch jobs) ──────────────────► open-data-statistics API
  │    app:dispatch-statistical-analysis-jobs               POST /api/v1/jobs
  │    app:dispatch-yearly-count-comparison-jobs            POST /api/v1/jobs
  │    app:dispatch-historical-scoring-jobs                 POST /api/v1/jobs/stage6
  │
  ├─ Laravel models track job IDs
  │    Trend model ──────────────────────────────────────► Stage 4 job IDs
  │    YearlyCountComparison model ──────────────────────► Stage 2 job IDs
  │    (Stage 6 jobs logged only, no model yet)
  │
  ├─ Results stored by open-data-statistics ──────────────► local disk or S3 bucket
  │
  └─ Laravel controllers serve results to frontend
       AnalysisReportSnapshot table ────────────────────── primary artifact cache
       S3 ──────────────────────────────────────────────── fallback/archive path
       Derived tables/caches ───────────────────────────── h3_hotspot_findings, trend summaries, listings
```

---

## Artisan Commands (Dispatching Jobs)

### `app:dispatch-statistical-analysis-jobs`

**File**: `app/Console/Commands/DispatchStatisticalAnalysisJobsCommand.php`

Discovers all Mappable models with `$statisticalAnalysisColumns`, exports their data as CSVs to `storage/public/analysis_exports/`, and submits Stage 4 H3 anomaly jobs to the API for each (model × column × H3 resolution) combination.

After a successful dispatch, creates or updates a `Trend` record with the new `job_id`.

```bash
# Run all models, all columns, default resolutions (9,8,7,6,5)
php artisan app:dispatch-statistical-analysis-jobs

# Single model
php artisan app:dispatch-statistical-analysis-jobs CrimeData

# Specific columns + resolutions
php artisan app:dispatch-statistical-analysis-jobs CrimeData --columns=offense_description,district --resolutions=8,9

# Force fresh CSV exports
php artisan app:dispatch-statistical-analysis-jobs --fresh

# With options
php artisan app:dispatch-statistical-analysis-jobs \
  --resolutions=9,8 \
  --trend-weeks=4,26,52 \
  --anomaly-weeks=4 \
  --p-anomaly=0.05 \
  --p-trend=0.05 \
  --export-timespan=108
```

**API payload** sent to `POST /api/v1/jobs`:
```json
{
  "job_id": "laravel-crime-data-offense-description-res9-{timestamp}",
  "data_sources": [{
    "data_url": "http://.../storage/analysis_exports/crime-data_all_fields.csv",
    "timestamp_col": "occurred_on_date",
    "lat_col": "lat",
    "lon_col": "long",
    "secondary_group_col": "offense_description"
  }],
  "config": {
    "analysis_stages": ["stage4_h3_anomaly"],
    "parameters": {
      "stage4_h3_anomaly": {
        "h3_resolution": 9,
        "p_value_anomaly": 0.05,
        "p_value_trend": 0.05,
        "analysis_weeks_trend": [4, 26, 52],
        "analysis_weeks_anomaly": 4,
        "plot_generation": "none"
      }
    }
  }
}
```

**Model eligibility**: Must implement `Mappable` trait (`getMappableTraitUsageCheck()`) and have `$statisticalAnalysisColumns` static property. Must also have an entry in `config/model_metadata_suggestions.php`.

---

### `app:dispatch-yearly-count-comparison-jobs`

**File**: `app/Console/Commands/DispatchYearlyCountComparisonJobsCommand.php`

Similar flow: exports data → submits Stage 2 jobs. Creates/updates `YearlyCountComparison` records.

```bash
php artisan app:dispatch-yearly-count-comparison-jobs
php artisan app:dispatch-yearly-count-comparison-jobs CrimeData --columns=offense_description --baseline-year=2019 --fresh
```

**API payload** sent to `POST /api/v1/jobs` (Stage 2):
```json
{
  "job_id": "laravel-{model}-{column}-{timestamp}",
  "data_sources": [...],
  "config": {
    "analysis_stages": ["stage2_yearly_count_comparison"],
    "parameters": {
      "stage2_yearly_count_comparison": {
        "timestamp_col": "...",
        "group_by_col": "offense_description",
        "baseline_year": 2019
      }
    }
  }
}
```

---

### `app:dispatch-historical-scoring-jobs`

**File**: `app/Console/Commands/DispatchHistoricalScoringJobsCommand.php`

Exports model data → submits Stage 6 historical scoring jobs. No Laravel model tracks these job IDs yet, so the dispatched job IDs are logged. Results become visible after their JSON artifacts are ingested into `analysis_report_snapshots` by `app:ingest-analysis-artifacts` or `app:pull-analysis-reports`.

```bash
php artisan app:dispatch-historical-scoring-jobs CrimeData \
  --column=offense_description \
  --resolution=8,9 \
  --analysis-weeks=52 \
  --group-weights='{"ROBBERY": 2.0, "AUTO THEFT": 1.5}' \
  --default-weight=1.0 \
  --city="Boston" \
  --fresh
```

**API payload** sent to `POST /api/v1/jobs/stage6`:
```json
{
  "job_id": "laravel-hist-score-crime-data-offense-description-res8-{timestamp}",
  "city": "Boston",
  "data_sources": [...],
  "output_filename": "stage6_historical_score_{job_id}.json",
  "group_weights": { "ROBBERY": 2.0 },
  "default_group_weight": 1.0,
  "h3_resolution": 8,
  "analysis_period_weeks": 52
}
```

---

## Laravel Models Tracking Job IDs

### `Trend` Model

Tracks Stage 4 analysis jobs. Key fields: `model_class`, `column_name`, `h3_resolution`, `job_id`, `p_value_anomaly`, `p_value_trend`, `analysis_weeks_anomaly`, `analysis_weeks_trend`.

Used by `TrendsController` (index listing) and `StatisticalAnalysisReportController` (fetches S3 artifact using `job_id`).

### `YearlyCountComparison` Model

Tracks Stage 2 jobs. Key fields: `model_class`, `group_by_col`, `baseline_year`, `job_id`.

Used by `YearlyCountComparisonController`, which now prefers the pulled-back `AnalysisReportSnapshot` artifact cache keyed by `job_id`.

### `NewsArticle` Model

Not directly tied to analysis API jobs, but linked to `YearlyCountComparison` records via `source_report_id`. Generated by `app:dispatch-news-article-generation-jobs` using AI (OpenAI).

---

## How Controllers Consume Results

### StatisticalAnalysisReportController (Stage 4)

1. Looks up `Trend` by `$trendId` → gets `job_id`
2. Resolves `{job_id}/stage4_h3_anomaly.json` from `AnalysisReportSnapshot`
3. Falls back to S3 only when no local snapshot exists
4. Passes full `reportData` JSON to `Reports/StatisticalAnalysisViewer` Vue page via Inertia
5. **No live API call at page render** — relies on the artifact cache

### YearlyCountComparisonController (Stage 2)

1. Looks up `YearlyCountComparison` by `$reportId` → gets `job_id`
2. Resolves `stage2_yearly_count_comparison.json` from `AnalysisReportSnapshot`
3. Falls back to S3 listing only when the snapshot table is empty
4. Passes `reportData` to `Reports/YearlyCountComparisonViewer` Vue page
5. The historical live-API dependency described in earlier docs is no longer the primary render path

### ScoringReportController (Stage 5 & 6)

Index:
1. Reads `AnalysisReportSnapshot` rows whose artifact names start with `scoring_results` or `stage6`
2. Falls back to scanning S3 when snapshots are unavailable
3. Reads metadata from each file's JSON (`parameters.city`, `parameters.date_range`, `parameters.h3_resolution`)
4. Groups by city/date where applicable and caches the listing
5. Cache is cleared by `app:ingest-analysis-artifacts`, `app:pull-analysis-reports`, or the admin refresh action

Show:
1. Finds target report by `job_id` + `artifact_name`
2. Resolves report JSON from `AnalysisReportSnapshot` first, then S3
3. Passes `reportGroup[]` + `initialReport` to `Reports/Scoring/Viewer` Vue page

Client-side API calls (from Viewer Vue page):
- `POST /api/scoring-reports/score-for-location` → `ScoringReportController::getScoreForLocation()` — reads scoring artifact + source Stage 4 artifact from the snapshot cache or S3, returns score + analysis for a given H3 index. Source Stage 4 data cached in Laravel by `analysis_data_{sourceJobId}` key.
- `GET /api/scoring-reports/source-analysis/{jobId}` → `ScoringReportController::getSourceAnalysisData()` — returns full Stage 4 JSON for a job (cached forever).

---

## Data Flow: Stage 4 → Scoring Reports

The link between a Stage 4 job and its scoring reports is the `source_job_id` field in the scoring artifact JSON:

```json
{
  "source_job_id": "laravel-crime-data-offense-description-res8-...",
  "config": { "city": "Boston", ... },
  "results": [...]
}
```

When `getScoreForLocation()` receives an H3 index lookup, it:
1. Reads the scoring artifact → finds `source_job_id`
2. Fetches `{source_job_id}/stage4_h3_anomaly.json` from S3 (cached)
3. Finds the matching H3 rows in Stage 4 results
4. Returns combined score + analysis data to the Vue component

**Stage 6 artifacts do not have `source_job_id`** (they're computed directly from data, not from a prior Stage 4 job).

---

## Admin: Job Dispatcher

`/admin/job-dispatcher` (AdminController) provides a web UI for dispatching analysis jobs without using the command line. It calls the same analysis API directly from the controller, with configurable parameters including file upload support.

- `POST /admin/job-dispatcher` → `AdminController::dispatchJob()`
- `POST /admin/job-dispatcher/unique-values` → `AdminController::getUniqueColumnValues()` (proxies to analysis API `/api/v1/data/unique-values`)

---

## Artifact Storage Layout

```
{artifact-root}/
  {job_id}/
    stage2_yearly_count_comparison.json    # Stage 2 results
    stage4_h3_anomaly.json                 # Stage 4 results (main analysis)
    scoring_results_{...}.json             # Stage 5 scoring results
    stage6_historical_score_{...}.json     # Stage 6 scoring results
```

All scoring report artifacts (Stage 5 and 6) are stored under the job_id of the job that produced them, not under the source Stage 4 job_id. The `ScoringReportController` index scans all directories to find scoring files.

## Local Artifact Ingestion

Use `app:ingest-analysis-artifacts` when `open-data-statistics` writes results to local disk instead of S3. The command accepts a full `storage/results` tree, one job directory, or one JSON artifact file.

```bash
# Inside a Laravel/Sail container, after artifacts have been copied into the container:
php artisan app:ingest-analysis-artifacts /tmp/pdw-analysis-results --fresh

# Preview without writing snapshots:
php artisan app:ingest-analysis-artifacts /tmp/pdw-analysis-results --dry-run

# Limit to one job:
php artisan app:ingest-analysis-artifacts /tmp/pdw-analysis-results \
  --job-id=laravel-everett-crime-data-incident_type_group-res8-1781327339 \
  --fresh
```

The command ingests these artifact types:

- `stage2_yearly_count_comparison.json`
- `stage4_h3_anomaly.json`
- `scoring_results*.json`
- `stage6*.json`

After a successful write it clears per-artifact caches, materializes newly ingested Stage 4 artifacts into `h3_hotspot_findings`, warms trend summaries, and clears listing caches. This gives the local open-data-statistics flow the same Laravel read path as the S3 pull flow.

For production publishing, prefer uploading artifacts to a Hostinger-local incoming directory and running this artisan command over SSH. Avoid direct production database writes from the dev machine. S3 can remain an archive/fallback, but app reads should be made fresh through `analysis_report_snapshots`.

## S3 Pull Fallback

`app:pull-analysis-reports` remains available for S3-backed artifacts. It scans the bucket for the same artifact names, writes them into `analysis_report_snapshots`, materializes hotspots, warms summaries, and clears caches.

---

## Failure Modes to Be Aware Of

| Scenario | Effect |
|----------|--------|
| Analysis API unreachable | `YearlyCountComparisonViewer` renders with null data; dispatch commands fail immediately |
| S3 unreachable | Existing snapshots continue to work; S3 fallback/archive reads fail |
| Local artifact path missing | `app:ingest-analysis-artifacts` exits before writing snapshots |
| Stale scoring cache | `scoring-reports.index` shows old/missing reports; fix with admin refresh |
| Stage 4 artifact missing from snapshots and S3 | `StatisticalAnalysisReportController` renders with null data; `getScoreForLocation` returns 404 for analysis details |
| CSV export public URL not accessible | Analysis API worker cannot fetch data; job fails |
