# 2026-03-25 Daily Observability Plan

## Goal

Make daily ingestion review fast enough that the operator can answer three questions in a few minutes:

1. Did the daily pipeline run?
2. Did it succeed?
3. If not, what exactly broke first?

Right now the system has logs, but not good operational visibility.

## Current State

What already exists:
- file-based `run_summary.json` per pipeline run
- `pipeline_runs_history.json`
- one raw log file per command
- admin pages for:
  - pipeline runs
  - pipeline run details
  - cache management
  - S3 artifact browsing

What is weak:
- no stage-level summary
- no extracted first error
- no health badge for the daily critical path
- no freshness SLA indicator
- log verbosity is inconsistent across commands
- Boston/Everett scraper dependency health is not surfaced alongside pipeline results
- top-level `failed` does not distinguish partial city-stage failure from true core freshness failure

## Desired Daily View

The target daily operator view should show, at minimum:

- latest pipeline run timestamp
- latest pipeline status
- first failed command, if any
- command-level durations
- whether post-seeding aggregation ran
- whether metrics cache ran
- whether reporting ran
- whether scraper-dependent steps passed
- whether the latest run is stale
- whether core freshness was preserved despite any branch failure

That should be visible without opening giant raw logs.

## Proposed Improvement Plan

### Phase 1. Improve Pipeline Summary Quality

Add more structured data to each pipeline run summary.

What to add:
- stage name on every command entry
- explicit stage status summary
- first failed command name
- first failure message excerpt
- total failed command count
- total warning/error counts if detectable
- run age / freshness classification
- a derived `core freshness status` based on:
  - `DataPointSeeder`
  - `app:cache-metrics-data`
  - `reports:send`

Result:
- the run detail page becomes useful before opening raw logs

### Phase 2. Standardize Command Logging

Introduce a small convention for high-signal log lines across ingestion commands.

Example summary fields to emit per command:
- source dataset name
- records downloaded
- rows seeded
- latest source date seen
- output file written
- rows skipped
- fatal error summary

Guideline:
- default logs should be summary-first
- verbose row-level logging should be behind an explicit debug or verbose mode

Result:
- commands that used to require reading huge logs become scannable

### Phase 3. Add a Daily Ops Dashboard

Create a dedicated admin page for the daily backend health loop.

The first version should include:
- latest run card
- stale/missing-run warning
- failed command card
- core freshness status card
- scraper dependency health card
- metrics freshness card
- last successful pipeline completion timestamp

This page should not be a log browser. It should be a decision surface.

Result:
- daily review becomes one page instead of four manual hops

### Phase 4. Add Dependency Health Checks

Surface checks for the external dependencies that can silently break ingestion.

Important dependency checks:
- scraper helper hostname resolution
- scraper helper reachability
- EC2 public IP versus current Hostinger DNS record
- queue worker heartbeat or recent job execution evidence

This does not have to block the whole UI at first. Even a visible warning panel is enough.

Result:
- Boston/Everett failures are easier to classify immediately

### Phase 5. Add Freshness and Failure Alerts

Once the summaries are clean, add lightweight alerts.

Good first alerts:
- no successful pipeline in 24 hours
- two consecutive failures of the same command
- failed `DataPointSeeder`
- failed scraper-dependent Boston/Everett acquisition
- core freshness failure even if the overall run kept going

Possible delivery paths later:
- founder action queue item
- email
- admin banner
- external webhook

Result:
- daily review becomes exception-based instead of manual archaeology

### Phase 6. Rationalize Scheduling After Observability

Only after the daily loop is observable should scheduling be tightened.

That is when to decide:
- one Hostinger cron -> Laravel scheduler
- explicit scheduled `app:run-all-data-pipeline`
- queue worker mode and timeout cleanup

Reason:
- better scheduling without observability just hides failures more efficiently

## Recommended Implementation Order

1. Enrich `run_summary.json`
2. Improve admin pipeline detail/list pages to show the summary fields
3. Standardize logging in the noisiest commands first
4. Add dependency health checks
5. Add alerts
6. Then move scheduling under a clearer single control loop

## Highest-Leverage Commands to Tackle First

For logging cleanup and summary extraction, prioritize:

1. `app:run-all-data-pipeline`
2. `app:download-boston-dataset-via-scraper`
3. `app:download-everett-pdf-markdown`
4. `app:download-cambridge-logs`
5. `DataPointSeeder`
6. `app:cache-metrics-data`

Reason:
- these commands control the daily freshness loop directly
- they also create most of the current daily review burden

## Suggested First Deliverable

The first implementation pass should produce:
- a clearer pipeline list page
- a clearer run detail page
- one extracted failure summary per run
- one freshness status per run

That is enough to materially improve daily operations before touching the scheduler.

## Phase 1 Shipped

Implemented on March 25, 2026:
- enriched `run_summary.json` with:
  - `summary_version`
  - `stage_name` on command entries
  - `failure_excerpt` on failed commands
  - stage summaries and stage counts
  - first failed command extraction
  - failed command counts
  - derived `core_freshness` status
- updated the admin pipeline list page to show:
  - freshness badge
  - core freshness badge
  - first failed command summary
- updated the admin pipeline detail page to show:
  - freshness status and age
  - core freshness summary
  - first failed command card
  - stage summary section
  - per-command failure excerpts
- per-command latest operational summary events
- standardized summary-first logging for the priority daily commands:
  - `app:download-boston-dataset-via-scraper`
  - `app:download-everett-pdf-markdown`
  - `app:download-cambridge-logs`
  - `DataPointSeeder`
  - `app:cache-metrics-data`
- dependency health checks:
  - scraper reachability
  - DNS status artifact consumption
  - queue worker heartbeat evidence
- backend health dashboard
- lightweight alerts:
  - admin banner
  - direct email alerts
- scheduler consolidation in code:
  - scheduled long-queue worker
  - scheduled dependency checks
  - scheduled daily pipeline dispatch
  - scheduled alert evaluation

Backward-compatibility note:
- older historical summaries do not contain stage names, failure excerpts, or operational summary events, so those fields may be blank on runs created before this change

Still remaining after implementation:
- Hostinger cron cutover to `php artisan schedule:run`
- confirming that the real external sysadmin runtime is publishing its DNS status artifact
- confirming fresh worker heartbeat evidence in the real scheduled runtime

Retention and storage follow-up:
- cleanup review is now explicitly dry-run-first
- file cleanup preview and database retention preview should feed later storage-pressure observability
- storage pressure should eventually surface alongside pipeline freshness because Hostinger limits are now an operational constraint
