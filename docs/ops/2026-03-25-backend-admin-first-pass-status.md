# 2026-03-25 Backend Admin First-Pass Status

## Purpose

This document answers one narrow question:

- what still remains to be implemented in the first backend-administration pass

It is not a long-term roadmap.
It is the close-out checklist for the first serious visit to backend administration.

## What This First Pass Already Accomplished

Shipped or documented in this pass:

- backend command and control-surface audit
- production-relevant data-flow report
- daily ops runbook for `app:run-all-data-pipeline`
- Phase 1 pipeline observability in `run_summary.json` and admin pipeline pages
- production validation of a real failed run
- Cambridge seeder hardening for source header drift
- production `APP_ENV` and `APP_DEBUG` correction
- preview-first retention tooling
- successful live cleanup trials for:
  - `pipeline-runs`
  - `boston-datasets`

This means the system is no longer undocumented and no longer fully opaque during failures.

## What Is Still Missing In The First Pass

### 1. Scheduler Consolidation

Status:
- not implemented
- `app/Console/Kernel.php` still defines no scheduled tasks
- `php artisan schedule:list` still returns no scheduled tasks

Why it still matters:
- the daily production-critical pipeline is still not codified inside Laravel
- the current queue cron does not itself guarantee a daily ingestion trigger

What remains:
- add a Laravel schedule entry for the daily `app:run-all-data-pipeline` trigger
- move Hostinger toward one cron entry running `php artisan schedule:run`
- make the intended trigger time explicit in docs and code

### 2. Queue Runtime Policy

Status:
- partially understood
- not yet standardized

Current mismatch:
- Hostinger cron uses `queue:listen --timeout=500 --tries=3`
- `RunArtisanCommandJob` allows `7200` seconds
- `app:run-all-data-pipeline` allows `3600` seconds per subprocess

Why it still matters:
- the runtime policy is not coherent yet
- long-running admin work can be more fragile than the docs imply

What remains:
- decide the supported worker model for shared hosting
- document restart behavior and expectations
- align timeouts so the queue worker is not stricter than the jobs it runs

### 3. Dependency Health Checks

Status:
- not implemented

Why it still matters:
- Boston and Everett rely on the scraper/PDF helper
- that helper depends on EC2 public IP to Hostinger DNS correction in `sysadmin/`
- today those dependencies only become visible after the daily pipeline fails

What remains:
- add scraper reachability checks
- add DNS sync sanity checks
- add queue-heartbeat or recent-worker-evidence checks
- surface those checks in admin operations, not only in raw logs

### 4. Summary-First Logging For Noisy Commands

Status:
- not implemented beyond pipeline summary enrichment

Why it still matters:
- some command logs are still much too noisy for daily review
- Cambridge custom logging and scraper-dependent acquisition paths still create review friction

Highest-value commands:
- `app:download-boston-dataset-via-scraper`
- `app:download-everett-pdf-markdown`
- `app:download-cambridge-logs`
- `DataPointSeeder`
- `app:cache-metrics-data`

What remains:
- emit concise summary lines first
- push row-level verbosity behind explicit debug modes
- make output file, record counts, and latest-source-date information easy to spot

### 5. Daily Backend Health Dashboard

Status:
- not implemented

Why it still matters:
- operators still have to hop across pipeline list, detail page, cache view, and sometimes raw logs

What remains:
- build a daily backend health page showing:
  - latest run status
  - freshness
  - core freshness
  - first failed command
  - scraper dependency health
  - metrics freshness
  - last successful completion time

### 6. Alert Path For True Failures

Status:
- not implemented

Why it still matters:
- the current system is still pull-based
- someone has to remember to look

What remains:
- add the first alerting path for:
  - no successful run in 24 hours
  - failed `DataPointSeeder`
  - repeated failure of the same command
  - Boston/Everett scraper dependency failure
- the first delivery path can be modest:
  - founder queue item
  - admin warning banner
  - email

### 7. Finalize The Next Retention Trial

Status:
- still open

Current next candidate:
- `cambridge-socrata-datasets`
- current dry run on production:
  - `1,651` files
  - `41.75 GB`

Why it matters:
- Hostinger storage pressure is still real
- the preview-first cleanup workflow is now proven, but one large safe bucket remains open

What remains:
- execute the Cambridge snapshot cleanup in a separate trial
- only after that consider any broader cleanup automation

## What Is Deliberately Deferred

These are real tasks, but they do not need to block closure of the first backend-admin pass:

- automating the founder-local weekly analysis loop
- exposing every analysis command in the admin dispatcher
- full alerting infrastructure
- broader cleanup automation beyond narrow proven targets
- comprehensive test coverage across all backend operations

Reason:
- daily ingestion reliability is the immediate operational backbone
- weekly analysis remains lower leverage until those analysis surfaces matter more to traffic and product usage

## Recommended Order To Finish The First Pass

1. Decide and implement the scheduler path.
2. Standardize the queue runtime policy and timeouts around that decision.
3. Add dependency health checks for scraper and DNS-sensitive ingestion.
4. Improve summary-first logging for the noisiest daily commands.
5. Add the daily backend health dashboard.
6. Add the first lightweight alert path.
7. Run the Cambridge snapshot cleanup trial.

## Definition Of Done For The First Backend-Admin Pass

The first pass should be considered complete when all of the following are true:

1. The daily ingestion trigger is codified in Laravel scheduling.
2. The queue runtime policy is documented and no longer obviously mismatched.
3. Daily failures can be triaged from admin surfaces without raw-log archaeology in the common case.
4. Scraper and DNS dependency problems are surfaced explicitly.
5. At least one lightweight alert path exists for true freshness failures.
6. The largest safe cleanup buckets have been trialed manually and documented.

Until then, backend administration is improved, but still mid-pass rather than closed.
