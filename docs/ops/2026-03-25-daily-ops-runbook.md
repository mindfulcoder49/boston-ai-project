# 2026-03-25 Daily Ops Runbook

## Purpose

This is the initial daily operating procedure for PublicDataWatch backend data freshness.

It is intentionally based on the system as it exists today:
- file-based pipeline logs
- the admin pipeline UI
- the backend health dashboard
- Laravel scheduling as the intended control plane in code
- the current split between production ingestion and founder-local analysis

This runbook is for daily data loading and daily health review first.

Environment default for broad checks:
- production first
- start from production admin pages, production artisan commands, or production runtime artifacts
- do not treat local `storage/logs` or local artisan output as the default source of truth for a broad system-health question
- if production access is unavailable and you fall back to local or dev evidence, label the result clearly as non-production

Related retention policy:
- see [2026-03-25-data-retention-plan.md](./2026-03-25-data-retention-plan.md)

## Daily Objective

By the end of the daily check, you should know:
- whether the latest production ingestion run happened
- whether it finished successfully
- which command failed if it did not
- whether metrics and reporting steps ran
- whether there is a scraper or DNS-related dependency problem affecting Boston or Everett
- whether the failure broke core freshness or only one city-specific branch

## Current Critical Path

The daily production-critical command is:

- `app:run-all-data-pipeline`

The most important success condition is:

- a recent successful run of that command, including post-seeding aggregation and metrics caching

## Current Tools

Use these existing surfaces first:

- Admin dashboard
- Admin pipeline log viewer
- Admin pipeline run detail page
- Admin cache manager
- Laravel storage logs on the server only if the admin pages are not enough
- direct production `php artisan` checks over SSH when you need to verify live runtime state outside the admin UI

Relevant current routes in the app:

- `admin.backend-health.index`
- `admin.pipeline.fileLogs.index`
- `admin.pipeline.fileLogs.show`
- `admin.cache-manager.index`
- `admin.job-dispatcher.index`

The pipeline log UI now surfaces these summary fields directly:
- freshness badge (`Fresh`, `Aging`, `Stale`, `Running`)
- core freshness badge
- first failed command
- stage summary
- short failure excerpts
- latest operational summary event per command

New CLI surfaces for daily ops:

- `php artisan app:check-ingestion-dependencies`
- `php artisan app:dispatch-daily-pipeline --dry-run`
- `php artisan app:evaluate-backend-health-alerts --dry-run`

## Daily Review Workflow

### 1. Check for a Recent Pipeline Run

Open the pipeline log viewer and review the most recent run.

Expected:
- there is a run from the last 24 hours
- its status is `completed`
- freshness should normally read `Fresh`

If there is no run in the last 24 hours:
- treat that as a daily freshness failure
- do not assume the old Hostinger cron covered it
- the intended model is now Laravel scheduler plus the scheduled long-queue worker
- if production still has the old cron, treat that as an external cutover gap

### 2. Open the Latest Run Detail

Review:
- run status
- freshness
- core freshness
- start time
- end time
- command list
- duration per command

Expected:
- acquisition stages completed
- seeding stages completed
- `DataPointSeeder` succeeded
- `app:cache-metrics-data` succeeded
- `reports:send` succeeded

If the run status is `failed`:
- identify the first failed command
- check whether the core freshness badge still says `Core freshness preserved`
- do not read every raw log first
- use the run detail page to narrow the problem to one command or one stage

Important:
- a top-level `failed` run does not automatically mean the whole site is stale
- the pipeline currently continues into later stages after a stage failure
- so you must distinguish:
  - critical freshness failure
  - partial city-stage failure

### 3. Triage by Failure Type

#### If a Boston or Everett acquisition command failed

Likely commands:
- `app:download-boston-dataset-via-scraper`
- `app:download-everett-pdf-markdown`

Likely dependency area:
- scraper/PDF helper service
- EC2 public IP -> Hostinger DNS sync

Next check:
- confirm whether the scraper hostname or service appears unavailable
- if needed, review the separate `sysadmin/` DNS sync tool status before retrying

#### If a Cambridge acquisition command failed

Likely commands:
- `app:download-city-dataset`
- `app:download-cambridge-logs`

Likely issue area:
- upstream source format drift
- custom Cambridge scraper/parser verbosity hiding the real error

Next check:
- inspect only the failing command log

#### If a seeder failed

Likely issue area:
- schema mismatch
- changed input file format
- malformed or unexpected data

Next check:
- inspect that seeder's command log
- avoid rerunning the entire pipeline blindly until the failing seeder is understood

#### If `DataPointSeeder` failed

Impact:
- public-facing aggregated map/search surfaces may be stale or inconsistent even if raw source tables updated

Next check:
- inspect the `DataPointSeeder` command log
- treat this as a blocking ingestion failure

#### If `app:cache-metrics-data` failed

Impact:
- public metrics snapshot in the `metrics_snapshots` table may be stale

Next check:
- inspect that command log
- if the rest of the ingestion succeeded, this is still important but narrower than a failed raw-data acquisition run

Interpretation note:
- the backend health metrics-freshness card now reflects the latest source timestamp stored in `metrics_snapshots.last_updated_at`
- that card is about source-data recency, not just whether the command ran
- so an aging metrics-freshness value can coexist with a successful `app:cache-metrics-data` command

#### If `reports:send` failed

Impact:
- user-facing reports may not have been dispatched

Next check:
- inspect the `reports:send` command log
- this is downstream of ingestion, so first confirm whether ingestion itself succeeded

Interpretation note:
- a successful `reports:send` command means the report email jobs were dispatched to the queue
- it does not by itself prove the queued report jobs were already processed
- if report delivery looks delayed, verify that the scheduled queue worker is servicing the queues that `reports:send` uses

### 4. Decide Whether to Re-Run

Use the admin job dispatcher or CLI only after you know what failed.

Safe re-run patterns today:
- rerun the full pipeline if failure cause was transient and early
- rerun selected stages if the failure was isolated and upstream stages already succeeded
- rerun `app:cache-metrics-data` alone if only the metrics cache step failed

Do not default to re-running everything if:
- a seeder is failing because of format drift
- Everett parsing or geocoding broke
- the scraper service hostname is wrong

That just produces more noise.

### 5. Confirm Downstream Freshness

After a successful run, verify:
- the latest run is marked `completed`
- `DataPointSeeder` succeeded
- `app:cache-metrics-data` succeeded
- `reports:send` succeeded

After a failed run, also verify whether these still succeeded:
- `DataPointSeeder`
- `app:cache-metrics-data`
- `reports:send`

If all three succeeded, treat the run as:
- `partial failure with core freshness preserved`

If one of those failed, treat the run as:
- `core freshness failure`

If needed, use the cache manager to:
- warm metrics manually
- clear stale listing caches

Do not use the cache manager as a substitute for understanding the failed pipeline step.

## Current Daily Success Criteria

Treat the day as operationally healthy if all of the following are true:

1. A pipeline run exists from the last 24 hours.
2. The run status is `completed`.
3. No command in the run has status `failed`.
4. `DataPointSeeder` succeeded.
5. `app:cache-metrics-data` succeeded.
6. No Boston/Everett scraper failure is present in the latest run.

Treat the day as operationally usable but degraded if:

1. The latest run status is `failed`.
2. The failure is isolated to a city-specific branch.
3. `DataPointSeeder` still succeeded.
4. `app:cache-metrics-data` still succeeded.
5. `reports:send` still succeeded.

In that case:
- the site likely refreshed overall
- one source or city path still needs follow-up
- do not classify it the same way as a fully broken daily refresh

## Current Escalation Rules

Escalate immediately if:
- there is no daily run
- the latest run failed before seeding
- `DataPointSeeder` failed
- scraper-dependent Boston or Everett acquisition failed
- the same command has failed across two consecutive daily runs

Escalate but lower priority if:
- only `reports:send` failed
- only `app:cache-metrics-data` failed
- only one city-specific seeder failed and downstream core freshness still completed
- weekly analysis artifacts are stale but daily ingestion is healthy

## Weekly or As-Needed Review

The weekly analysis loop is lower priority right now.

Current state:
- `app:run-weekly-analysis` runs on the founder development machine
- artifacts are written to S3
- production later pulls them with `app:pull-analysis-reports`

For now, analysis review can be weekly or even monthly if the public trends/scoring surfaces are still low-traffic.

Do not let analysis maintenance crowd out daily ingestion health.

## Storage Pressure Review

Storage pressure is now a real operational concern.

Use dry-run review before any new cleanup is approved:
- `php artisan app:review-data-retention`
- `php artisan app:cleanup --dry-run-before=YYYY-MM-DD`

Current policy:
- preview candidate deletions first
- review the output
- get founder approval before any new delete path is applied or automated

## Current Known Fragilities

- production Hostinger cron is confirmed to be the scheduler entry `* * * * * /usr/bin/php /home/u353344964/domains/publicdatawatch.com/bostonApp/artisan schedule:run`
- the remaining uncertainty is whether the scheduler-driven queue-worker path is publishing clean heartbeat evidence in the live runtime
- queue timeout policy outside the intended scheduled `admin-long` worker may not match long-running command envelopes
- Boston and Everett rely on an external scraper helper
- that scraper helper relies on the EC2-to-Hostinger DNS sync loop
- the real worker-heartbeat and DNS-status evidence still need confirmation in the live external runtimes

## What This Runbook Does Not Yet Solve

This runbook does not yet guarantee:
- that the scheduler-driven worker path is publishing clean heartbeat evidence
- that the external sysadmin runtime is publishing clean DNS status evidence
- that storage-pressure review is surfaced in the same one-page view as freshness health

Those are the main remaining external-runtime follow-ups, not missing code features inside Laravel.
