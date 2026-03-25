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

Recommended solution:
- do not schedule the full long-running pipeline command directly from `schedule:run`
- add a lightweight dispatch command such as `app:dispatch-daily-pipeline` that:
  - checks for an existing recent running pipeline
  - dispatches `RunArtisanCommandJob` for `app:run-all-data-pipeline`
  - records a clear no-op message if a run is already active
- schedule that dispatch command daily in `app/Console/Kernel.php`
- move Hostinger to a single cron entry:
  - `* * * * * /usr/bin/php /home/.../artisan schedule:run`

Reason:
- this keeps Laravel scheduling as the control plane without making `schedule:run` block for 10+ minutes
- it fits the existing queue-backed admin execution model

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

Recommended solution:
- stop using `queue:listen` for this path
- move long-running admin orchestration onto a dedicated queue such as `admin-long`
- dispatch `RunArtisanCommandJob` onto that queue explicitly
- process it with a cron-invoked worker that drains and exits, for example:
  - `php artisan queue:work database --queue=admin-long --stop-when-empty --timeout=7200 --tries=1 --sleep=3`
- if Hostinger allows it, wrap the worker in a lock such as `flock` to avoid overlap
- keep `--tries=1` for long orchestration jobs so failures are visible instead of silently retried

Reason:
- the current `500` second worker timeout is stricter than the `3600` second pipeline subprocess timeout and the `7200` second queued job timeout
- automatic retries are high-risk for long ingestion orchestrators because they can duplicate noisy partial work

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

Recommended solution:
- add a dedicated command such as `app:check-ingestion-dependencies`
- have it produce one compact status object covering:
  - scraper hostname resolution
  - scraper HTTP reachability with a short timeout
  - current DNS target versus current EC2 public IP
  - recent queue worker evidence
- run it as a preflight check before the daily pipeline dispatch and also expose it in admin
- if the scraper service supports it, add a cheap `/health` endpoint on the helper service and check that instead of a heavy conversion route

Reason:
- Boston and Everett failures are currently detected too late
- dependency health should be visible before a full pipeline run burns time and fails

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

Recommended solution:
- create a small shared logging convention or helper for operational commands
- every critical daily command should emit:
  - one start summary
  - one completion summary
  - one failure summary
- standard fields should include:
  - source or dataset name
  - output file path
  - bytes downloaded or written
  - records attempted, inserted, skipped, and deleted
  - latest source date seen
  - warning count
  - failure excerpt
- move row-level or per-record diagnostics behind `-v` or an explicit debug mode

Reason:
- the problem is not lack of logs
- the problem is that operators cannot quickly extract the one line that explains what happened

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

Recommended solution:
- add a dedicated admin route and page for backend health instead of overloading the pipeline log list
- populate it from:
  - the latest `pipeline_runs_history.json` entry
  - the latest `run_summary.json`
  - the new dependency-check command output
  - lightweight queue and storage checks
- keep the first version narrow:
  - latest run card
  - core freshness card
  - first failed command card
  - dependency health card
  - metrics freshness card
  - storage pressure card
  - quick links to rerun, logs, cache manager, and cleanup review

Reason:
- the daily operator needs a decision surface, not another general-purpose log browser

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

Recommended solution:
- start with two channels only:
  - admin warning banner for anyone in the backend
  - direct email to the founder/admin address for true freshness failures
- trigger alerts only for:
  - no successful run in 24 hours
  - failed `DataPointSeeder`
  - repeated failure of the same command across two daily runs
  - failed Boston or Everett scraper-dependent acquisition
- do not alert on every branch failure or every warning

Reason:
- the first alert path should be credible, not noisy
- since `reports:send` already implies mail infrastructure is in use, email is the simplest first push channel

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

Recommended solution:
- keep the same preview-first manual workflow
- run `cambridge-socrata-datasets` as the next narrow delete
- after that, pause before broadening cleanup scope and decide whether:
  - additional dataset families deserve their own scoped targets
  - recurring automated cleanup is justified yet

Reason:
- the current workflow has already proven safe on two separate targets
- there is no need to jump from narrow wins to broad destructive automation

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
