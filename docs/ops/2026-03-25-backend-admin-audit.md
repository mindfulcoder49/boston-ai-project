# 2026-03-25 Backend Administration Audit

## Purpose

This audit records the current backend administration surface for PublicDataWatch, with emphasis on command inventory, sequencing, validation, and operational risks.

This is the first real runbook draft for the backend-administration workstream.

## Executive Summary

PublicDataWatch already has a meaningful operational backend, but it is only partially documented.

The current state is:
- a large Artisan command surface for ingestion, seeding, analysis, reporting, translation, and sync work
- an admin job-dispatch UI for a subset of those commands
- a file-logged full data pipeline command
- a queued background-command path for long-running admin actions
- no Laravel scheduled tasks currently defined

This means the system is capable, but still operator-fragile.

The highest-leverage backend-administration work is:
1. document the real command sequences
2. define validation checks after each sequence
3. clarify what is manual versus queue-driven versus production-critical
4. only then add broader automation or test harnesses

## Current State Findings

### 1. No scheduler is active

`php artisan schedule:list` currently returns:
- `No scheduled tasks have been defined.`

So recurring operations are not being driven by Laravel's scheduler today.

### 2. Queue-backed background work exists

Current local app environment from `php artisan about --json`:
- queue driver: `database`
- log driver: `single`
- cache driver: `file`
- route cache: `true`

Long-running admin commands are dispatched through `RunArtisanCommandJob`, which simply calls Artisan in a queued job with a 2-hour timeout.

### 3. The admin UI exposes only part of the command surface

The admin job dispatcher currently exposes:
- `app:run-all-data-pipeline`
- `app:dispatch-statistical-analysis-jobs`
- `app:dispatch-historical-scoring-jobs`
- `app:dispatch-yearly-count-comparison-jobs`
- `app:dispatch-news-article-generation-jobs`
- `reports:send`

Important commands such as `app:run-weekly-analysis`, `app:pull-analysis-reports`, `app:materialize-hotspot-findings`, `app:sync-to-production`, and translation batch commands are not part of that admin UI flow.

### 4. The full data pipeline is already structured and file-logged

`app:run-all-data-pipeline` is the main ingestion-and-seeding orchestrator.

It:
- reads stage definitions from `config/admin_pipeline.php`
- supports partial runs by stage and section options
- writes per-run summaries under `storage/logs/pipeline_runs/<run_id>/run_summary.json`
- maintains `storage/logs/pipeline_runs_history.json`

This is currently the strongest foundation for backend-admin stability.

### 5. Weekly analysis is separate from ingestion

`app:run-weekly-analysis` is a second orchestrator that runs:
- Stage 2 yearly count comparisons
- Stage 4 anomaly and trend analysis
- Stage 6 historical scoring jobs

Its behavior is controlled by `config/analysis_schedule.php`.

So there are already two distinct major operational loops:
- data refresh and seeding
- statistical analysis and scoring

### 6. Production sync is narrow and explicit

`app:sync-to-production` currently exists only for:
- `--h3-names`

It requires `HOSTINGER_DB_*` environment variables and pushes local `h3_location_names` into the production database.

### 7. Deploy flow is separate from Laravel admin operations

Production deployment is currently external to Laravel backend commands:
- local verify
- commit
- push to GitHub
- SSH to server
- run `~/publicdatawatchdeploy.sh`
- verify live routes and site behavior

So deploy administration and data administration are related, but still separate flows.

### 8. Live production validation exposed the real March 25 failure mode

A live production validation was run on 2026-03-25 against the most recent pipeline run.

Findings:
- the latest production `app:run-all-data-pipeline` run was marked `failed`
- the first and only failing command in that run was `CambridgeCrimeDataSeederMerge`
- the failure was caused by a Cambridge CSV header mismatch:
  - live file header used `File Number`
  - the seeder assumed `file_number`
- despite the top-level `failed` status, the core freshness path still completed:
  - `DataPointSeeder` succeeded
  - `app:cache-metrics-data` succeeded
  - `reports:send` succeeded

This matters operationally:
- a top-level `failed` run is not always a total freshness failure
- the operator needs to distinguish partial city-stage failure from core freshness failure

Follow-up completed on 2026-03-25:
- the Cambridge crime seeders were patched to normalize CSV headers defensively
- `CambridgeCrimeDataSeederMerge` was rerun successfully on production and completed cleanly
- production `.env` was also corrected from:
  - `APP_ENV=local`
  - `APP_DEBUG=true`
  to:
  - `APP_ENV=production`
  - `APP_DEBUG=false`

## Command Inventory

### A. Ingestion And Seeding

Primary orchestrator:
- `app:run-all-data-pipeline`

Acquisition commands:
- `app:download-boston-dataset`
- `app:download-boston-dataset-via-scraper`
- `app:download-cambridge-logs`
- `app:download-city-dataset`
- `app:download-everett-pdf-markdown`
- `app:download-massdot-crash-data`
- `app:seed-new-york-311`
- `everett:process-data`
- `app:generate-everett-csv`

Seeding is mostly performed through `db:seed` classes configured in `config/admin_pipeline.php`.

Configured city sections in the current pipeline:
- Boston
- Cambridge
- Everett
- Chicago
- San Francisco
- Seattle
- Montgomery County MD
- New York

General post-seeding steps:
- `db:seed --class=DataPointSeeder --force`
- `app:cache-metrics-data`

Reporting step configured in the same pipeline:
- `reports:send`

### B. Statistical Analysis And Scoring

Main orchestrator:
- `app:run-weekly-analysis`

Underlying command surface:
- `app:dispatch-yearly-count-comparison-jobs`
- `app:dispatch-statistical-analysis-jobs`
- `app:dispatch-historical-scoring-jobs`
- `app:analyze-statistical-anomalies`
- `app:generate-anomaly-report`
- `app:materialize-hotspot-findings`
- `app:pull-analysis-reports`

### C. News And Reporting

- `app:dispatch-news-article-generation-jobs`
- `app:dispatch-local-news-article`
- `app:run-auto-news-generation`
- `reports:send`
- `report:intersections-crashes`

### D. Translation And Batch Operations

- `translate:dispatch`
- `translations:transform`
- `batch:create-translation-batch`
- `batch:upload-and-execute`
- `batch:check-status`
- `batch:download-results`

### E. Metadata, Sync, And Maintenance

- `generate:field-dictionary`
- `generate:model-metadata`
- `app:reextract-h3-location-names`
- `app:sync-to-production`
- `app:cleanup`
- `app:cache-metrics-data`

## Current Operational Control Surfaces

### Admin UI

Primary operator UI:
- Admin Job Dispatcher
- Job Runs page
- Pipeline File Log Viewer
- Pipeline File Run Detail page

Strengths:
- allows dispatch without shell access
- can run long commands in the queue for some operations
- exposes per-run pipeline log files

Limitations:
- only covers part of the command surface
- does not define an end-to-end runbook
- does not currently make the difference between ingestion, analysis, and post-analysis reconciliation explicit

### Shell / Local Operator Flow

Needed for:
- full Artisan access
- schedule inspection
- direct dry-runs
- production deploy
- sync-to-production
- any command not exposed in the admin UI

## Current Recommended Runbooks

### Runbook 1: City Ingestion Refresh

Use when refreshing raw data and reseeding application data.

Preferred entry point:
- `app:run-all-data-pipeline`

Sequence:
1. Decide whether the refresh is full or partial by city/stage.
2. Run `app:run-all-data-pipeline` with the needed stages or city options.
3. Ensure post-seeding steps run:
- `DataPointSeeder`
- `app:cache-metrics-data`
4. Review the latest pipeline run summary and command logs.

Validation:
- latest pipeline run status is `completed`
- no command entries are left in failed state
- expected city stages actually ran
- cached metrics step completed if data changed materially

Failure modes:
- partial command failure inside one stage
- stale pipeline history or missing log review
- refresh without cache rebuild

### Runbook 2: Weekly Analysis

Use when dispatching the statistical analysis and scoring cycle.

Preferred entry point:
- `app:run-weekly-analysis`

Sequence:
1. Run dry-run first if the scope changed materially.
2. Run the intended analysis stages:
- Stage 2 yearly count comparisons
- Stage 4 anomaly/trend jobs
- Stage 6 historical scoring
3. Confirm queue-backed jobs are actually being processed.
4. Reconcile outputs after job completion:
- `app:pull-analysis-reports`
- `app:materialize-hotspot-findings`
5. Only after reconciliation, evaluate downstream report or news generation work.

Validation:
- expected stages ran without command failure
- queue worker actually processed dispatched jobs
- analysis artifacts were pulled back into the database
- hotspot findings were rebuilt after fresh stage-4 outputs

Failure modes:
- jobs dispatched but no active queue worker
- stale S3 or report snapshots not pulled back
- hotspot findings not rematerialized after new analysis

### Runbook 3: Production Data Sync

Use only for explicit production-database sync work.

Current supported sync:
- `app:sync-to-production --h3-names`

Sequence:
1. Verify `HOSTINGER_DB_*` credentials exist locally.
2. Run dry-run first.
3. Run the real sync if dry-run output looks correct.
4. Spot-check production behavior that depends on the synced table.

Validation:
- Hostinger DB connection succeeds
- dry-run scope matches expectations
- real upsert run reports zero failed batches

Failure modes:
- missing Hostinger env vars
- pushing unintended local data because local state was not reviewed first

## Validation Checklist

Use this after any meaningful backend-admin action.

### After ingestion or seeding

- Did the intended stages run and complete?
- Did any stage quietly continue after a failure?
- Did `DataPointSeeder` run if underlying records changed?
- Did `app:cache-metrics-data` run if aggregate metrics changed?

### After analysis

- Were jobs merely dispatched, or actually processed?
- Were outputs pulled back into the app database?
- Were derived findings rebuilt after fresh results?

### After reporting or news generation

- Did queued jobs finish?
- Did the expected records or outbound actions appear?
- Are there any obvious failures in job logs?

### After production-affecting sync or deploy work

- Did live behavior actually change as intended?
- Were critical routes and public pages verified?
- Did caching or route state get rebuilt if needed?

## Operational Risks

1. No scheduler
- recurring work is not formally automated in Laravel yet

2. Queue-worker ambiguity
- the app can dispatch long-running jobs, but the operational ownership of the queue worker is not documented here yet

3. Split control surfaces
- some flows live in shell commands, some in the admin UI, some in an external deploy script

4. Partial runbook coverage
- the repo has orchestrators, but not one concise operator playbook tying them together

5. Thin automated test coverage for operations
- current test suite does not exercise the orchestration flows materially

## Recommended Next Backend-Admin Work

1. Document queue-worker ownership and restart procedure.
2. Add a concise daily/weekly admin runbook that references only the real active flows.
3. Add a backend-admin checklist for the queue worker, pipeline logs, and analysis reconciliation.
4. After the runbook is stable, add targeted tests for:
- pipeline option parsing
- analysis-schedule dispatch behavior
- admin job-dispatch authorization and parameter handling

## Recommendation On Testing Versus Backend Administration

Backend administration should stay ahead of comprehensive testing for now.

Reason:
- there is already a large live operational surface to stabilize
- the biggest current risk is operator ambiguity, not missing unit assertions
- once the true command sequences are documented, targeted testing will be much easier to design

The first testing work that actually makes sense after this audit is not generic “comprehensive testing.”

It is:
- command-orchestration tests
- admin dispatch tests
- later, Playwright smoke tests for the public routes and admin flows
