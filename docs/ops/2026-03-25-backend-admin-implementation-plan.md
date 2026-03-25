# 2026-03-25 Backend Admin Implementation Plan

## Purpose

This is the implementation plan for the remaining first-pass backend-administration work.

It translates the recommendation set into:
- workstreams
- file-level implementation targets
- rollout order
- test strategy
- acceptance criteria

It is the execution plan for the next backend-admin sessions, not just a list of ideas.

## Scope

This plan covers:
- scheduler consolidation
- queue runtime policy cleanup
- scraper and DNS dependency health checks
- summary-first logging for noisy daily commands
- a dedicated daily backend health dashboard
- a first lightweight alert path for true freshness failures

Out of scope for this plan:
- full weekly-analysis automation
- broad cleanup automation beyond narrow proven targets
- comprehensive end-to-end test automation for all backend operations

## Working Principles

- prefer narrow reversible changes over broad rewrites
- keep the daily ingestion loop as the top priority
- add dry-run or no-op modes wherever a new operational command could trigger work
- standardize operational state in machine-readable summaries before adding more UI
- test each workstream at three levels where practical:
  - unit or command-level behavior
  - local operator-facing behavior
  - production-safe manual verification

## Execution Order

1. Scheduler consolidation
2. Queue runtime policy cleanup
3. Dependency health checks
4. Summary-first logging
5. Daily backend health dashboard
6. Lightweight alert path

Reason:
- scheduling and queue policy define the control plane
- dependency health should be visible before more automation is added
- better logging should land before the dashboard
- alerts should come after the dashboard and summaries are trustworthy

## Workstream 1: Scheduler Consolidation

### Goal

Move the daily ingestion trigger into Laravel scheduling without making `schedule:run` block on a 10+ minute pipeline execution.

### Recommended Design

Add a new lightweight command:
- `app:dispatch-daily-pipeline`

Responsibilities:
- detect whether a recent pipeline run is still active
- if no run is active, dispatch `RunArtisanCommandJob` for `app:run-all-data-pipeline`
- if a run is already active, exit cleanly with a clear no-op result
- optionally run dependency preflight checks before dispatch

Laravel schedule:
- schedule `app:dispatch-daily-pipeline` at the chosen daily run time

Hostinger cron target state:
- one cron for scheduler:
  - `* * * * * /usr/bin/php /home/.../artisan schedule:run`
- one cron for the long-running admin queue worker until process supervision exists

### Files To Change

- [Kernel.php](/home/briarmoss/Documents/boston-ai-project/app/Console/Kernel.php)
- new [DispatchDailyPipelineCommand.php](/home/briarmoss/Documents/boston-ai-project/app/Console/Commands/DispatchDailyPipelineCommand.php)
- possibly [RunArtisanCommandJob.php](/home/briarmoss/Documents/boston-ai-project/app/Jobs/RunArtisanCommandJob.php)
- backend-admin docs

### Implementation Steps

1. Add `app:dispatch-daily-pipeline`.
2. Add a guard that checks pipeline history and current run status.
3. Add a `--dry-run` mode that reports whether a dispatch would occur.
4. Dispatch the existing queued job instead of calling the pipeline directly.
5. Register the command in `Kernel::schedule()`.
6. Document the intended run time and cron migration steps.

### Test Plan

Unit / command tests:
- command returns no-op if a running pipeline summary exists
- command dispatches exactly one queued job when no active run exists
- `--dry-run` does not dispatch

Local verification:
- `php artisan schedule:list` shows the new scheduled command
- `php artisan app:dispatch-daily-pipeline --dry-run` shows the correct decision

Production-safe verification:
- run the dispatch command in dry-run mode on production
- verify no duplicate run is created if a pipeline is already active

### Acceptance Criteria

- `schedule:list` is no longer empty
- daily pipeline triggering is codified in Laravel
- dispatch is idempotent against an already-running pipeline
- the schedule path is documented in the backend-admin playbook

### Founder / External Follow-Up

After code is ready:
- change the Hostinger cron from the old worker-only setup to the scheduler-driven setup

## Workstream 2: Queue Runtime Policy Cleanup

### Goal

Make the worker runtime consistent with the actual duration of queued admin orchestration jobs.

### Recommended Design

Use a dedicated queue for long-running orchestration:
- queue name: `admin-long`

Use `queue:work`, not `queue:listen`, for this path:
- `php artisan queue:work database --queue=admin-long --stop-when-empty --timeout=7200 --tries=1 --sleep=3`

If Hostinger permits it:
- wrap the worker invocation in `flock` to prevent overlap

### Files To Change

- [RunArtisanCommandJob.php](/home/briarmoss/Documents/boston-ai-project/app/Jobs/RunArtisanCommandJob.php)
- possibly new queue-policy helper or config entry
- backend-admin docs

### Implementation Steps

1. Put `RunArtisanCommandJob` on `admin-long`.
2. Document the required worker command and timeout policy.
3. Align queue timeout expectations with:
   - queued job timeout `7200`
   - pipeline subprocess timeout `3600`
4. Keep retries at `1` for orchestration jobs.
5. Add a small note in the admin UI or logs showing which queue long jobs are sent to.

### Test Plan

Unit / behavior tests:
- dispatched `RunArtisanCommandJob` uses `admin-long`
- long-running jobs do not inherit an unexpectedly short worker timeout in code paths we control

Local verification:
- dispatch a harmless long-running command onto `admin-long`
- run `queue:work database --queue=admin-long --stop-when-empty`
- verify the job drains and exits cleanly

Production-safe verification:
- verify the new worker command can process a background admin dispatch successfully
- verify that failed long jobs are not silently retried three times

### Acceptance Criteria

- long-running admin jobs have a dedicated queue
- worker invocation is documented and coherent with command timeouts
- retry policy is explicit and conservative

### Founder / External Follow-Up

After code is ready:
- update Hostinger cron or worker invocation to the documented `queue:work` command

## Workstream 3: Scraper And DNS Dependency Health Checks

### Goal

Detect Boston/Everett dependency failures before the daily pipeline fails.

### Recommended Design

Add a new Laravel command:
- `app:check-ingestion-dependencies`

It should report:
- scraper hostname resolution
- scraper HTTP reachability
- DNS sync status freshness
- DNS current IP vs EC2 current IP, when available through a shared health artifact
- recent queue-worker evidence

Because DNS repair lives in `sysadmin/`, keep repair there and expose read-only status outward.

Recommended `sysadmin/` addition:
- a JSON status mode or companion command that outputs:
  - `checked_at`
  - `record_label`
  - `dns_ip`
  - `ec2_ip`
  - `changed`
  - `status`

Recommended sharing path:
- publish the JSON status artifact somewhere Laravel can read without DNS credentials
- best first option: S3, since the app already uses it operationally

### Files To Change

Laravel side:
- new [CheckIngestionDependenciesCommand.php](/home/briarmoss/Documents/boston-ai-project/app/Console/Commands/CheckIngestionDependenciesCommand.php)
- new support class such as `App\Support\IngestionDependencyHealth`
- admin controller and admin UI for display

`sysadmin/` side:
- [main.py](/home/briarmoss/Documents/boston-ai-project/sysadmin/main.py)
- [sync_ec2_dns.py](/home/briarmoss/Documents/boston-ai-project/sysadmin/actions/sync_ec2_dns.py)
- possibly a new uploader/helper if the status artifact is written to S3

### Implementation Steps

1. Add JSON output support to the DNS sync tool.
2. Add a way to publish or persist the latest DNS health status.
3. Add Laravel-side dependency health aggregation.
4. Add a short-timeout scraper reachability probe.
5. Add queue-worker evidence, such as:
   - recent `jobs` activity
   - recent successful `RunArtisanCommandJob`
   - or a timestamp file written by the worker path
6. Optionally run the dependency check as part of `app:dispatch-daily-pipeline`.

### Test Plan

Python tests:
- dry-run/status mode returns valid JSON with expected keys
- mismatch and already-correct cases serialize correctly

Laravel tests:
- HTTP reachability check handles success, timeout, and DNS failure
- dependency-check command produces expected status payload with mocked inputs
- stale DNS status artifact is marked unhealthy

Local verification:
- run the sysadmin status command locally in dry-run mode
- run `php artisan app:check-ingestion-dependencies`

Production-safe verification:
- run the dependency check alone on production
- confirm it reports current scraper/DNS state without mutating anything

### Acceptance Criteria

- dependency health can be reviewed without running the pipeline
- DNS repair remains in Python tooling
- Laravel consumes read-only health information only
- Boston/Everett dependency issues become visible before ingestion failure

## Workstream 4: Summary-First Logging For Noisy Commands

### Goal

Make the noisiest daily commands reviewable from a few summary lines instead of raw-log archaeology.

### Recommended Design

Create a small shared logging convention for operational commands.

Each critical command should emit:
- one start summary
- one completion summary
- one failure summary

Standard fields:
- dataset or source name
- output file path
- bytes downloaded or written
- records attempted, inserted, skipped, deleted
- latest source date seen
- warning count
- failure excerpt

Keep row-level detail behind explicit verbosity.

### Files To Change

Potential helper:
- new support class or trait such as `App\Support\OperationalCommandLogger`

Priority commands:
- [DownloadBostonDatasetViaScraper.php](/home/briarmoss/Documents/boston-ai-project/app/Console/Commands/DownloadBostonDatasetViaScraper.php)
- [DownloadEverettPDFMarkdown.php](/home/briarmoss/Documents/boston-ai-project/app/Console/Commands/DownloadEverettPDFMarkdown.php)
- [DownloadCambridgeLogs.php](/home/briarmoss/Documents/boston-ai-project/app/Console/Commands/DownloadCambridgeLogs.php)
- [DataPointSeeder.php](/home/briarmoss/Documents/boston-ai-project/database/seeders/DataPointSeeder.php)
- [CacheMetricsDataCommand.php](/home/briarmoss/Documents/boston-ai-project/app/Console/Commands/CacheMetricsDataCommand.php)
- [RunAllDataPipelineCommand.php](/home/briarmoss/Documents/boston-ai-project/app/Console/Commands/RunAllDataPipelineCommand.php) if summary extraction is extended

### Implementation Steps

1. Define the summary field contract.
2. Add the shared helper or logging pattern.
3. Update the top 3 noisiest commands first.
4. Extend `RunAllDataPipelineCommand` or `PipelineRunSummary` if command summaries should be extracted into `run_summary.json`.
5. Add documentation for when detailed logging is appropriate.

### Test Plan

Unit / command tests:
- commands emit expected summary markers on success
- commands emit a failure summary on controlled failure paths

Local verification:
- run target commands against a safe local test case and inspect summary-first output
- confirm verbose output still works when explicitly enabled

Production-safe verification:
- inspect one fresh pipeline run after the change
- confirm the first failure and key counts are easier to identify without full raw-log reading

### Acceptance Criteria

- the target commands all emit recognizable summary lines
- operators can identify dataset output, record counts, and failure excerpt quickly
- row-level noise is reduced in the default path

## Workstream 5: Daily Backend Health Dashboard

### Goal

Provide one admin page that answers whether backend operations are healthy today.

### Recommended Design

Add a dedicated daily health page instead of expanding the pipeline log list further.

First version should show:
- latest pipeline run
- freshness
- core freshness
- first failed command
- dependency health
- metrics freshness
- storage pressure
- quick operator links

### Files To Change

- admin routes
- [AdminController.php](/home/briarmoss/Documents/boston-ai-project/app/Http/Controllers/AdminController.php)
- new support aggregator such as `App\Support\BackendHealthSnapshot`
- new Inertia page such as `resources/js/Pages/Admin/BackendHealth.vue`
- maybe small shared components for status cards

### Implementation Steps

1. Create a health snapshot aggregator that composes:
   - latest pipeline history entry
   - latest run summary
   - dependency-check output
   - storage cleanup/report summary
2. Add the admin route and controller action.
3. Build a narrow status-card UI.
4. Add links to:
   - pipeline log detail
   - cache manager
   - job dispatcher
   - cleanup review steps

### Test Plan

Laravel feature tests:
- admin route requires admin auth
- page renders with expected health payload structure

Local verification:
- open the page locally and verify cards render from current data
- verify behavior with missing history or missing dependency artifact

Production-safe verification:
- open the live page and confirm it reflects the latest pipeline state accurately

### Acceptance Criteria

- daily operator review can start from one page
- the page makes missing runs, failed core freshness, and dependency health obvious
- the page links directly to the next operator action

## Workstream 6: First Lightweight Alert Path

### Goal

Move from purely pull-based backend review to a small set of credible alerts.

### Recommended Design

Start with:
- admin warning banner
- direct email to founder/admin

Alert only on:
- no successful run in 24 hours
- failed `DataPointSeeder`
- same command failed on two consecutive daily runs
- failed Boston/Everett scraper-dependent acquisition

Avoid alerting on:
- every branch failure
- every warning
- transient noise that does not affect freshness

### Files To Change

- new evaluator command such as `app:evaluate-backend-health-alerts`
- notification or mail class
- admin banner data source
- maybe a simple state file or cache key to deduplicate repeated alerts

### Implementation Steps

1. Add alert evaluation logic over recent pipeline history.
2. Add dedupe state so the same condition does not spam repeatedly.
3. Add email delivery.
4. Add an admin warning banner sourced from the same evaluation logic.
5. Schedule the evaluator after daily pipeline execution or on an hourly cadence.

### Test Plan

Unit / feature tests:
- alert evaluator triggers on each of the defined severe conditions
- alert evaluator does not trigger on allowed partial failures
- dedupe suppresses repeated sends for the same unresolved incident

Local verification:
- run the evaluator against crafted history fixtures
- confirm email is queued or faked correctly in tests

Production-safe verification:
- run the evaluator in dry-run mode first
- confirm the admin banner shows the expected warning without sending duplicate emails

### Acceptance Criteria

- severe freshness failures produce one visible push signal
- normal partial failures do not spam
- alert logic is tied to the same summary data used by the dashboard

## Cross-Cutting Testing Strategy

### Test Layers

1. Code-level tests
- PHPUnit feature tests for new Laravel commands, admin routes, and alert logic
- Python tests for new `sysadmin/` status output behavior

2. Local operator verification
- run new commands in `--dry-run` mode where available
- inspect the resulting admin pages and summaries locally

3. Production-safe manual verification
- use read-only checks first
- only after dry-run verification, switch external cron or worker settings

### Minimum Test Matrix

Before calling the implementation complete, run:

- `php artisan schedule:list`
- `php artisan app:dispatch-daily-pipeline --dry-run`
- `php artisan app:check-ingestion-dependencies`
- targeted PHPUnit tests for:
  - scheduler dispatch guard
  - dependency-check output
  - backend health page
  - alert evaluator
- Python tests for the DNS status output contract

### Rollout Rule

For every workstream:
1. land code
2. run local tests
3. update docs
4. deploy
5. run production-safe manual verification
6. only then switch external Hostinger cron or worker behavior

## Playbook Updates Required During Implementation

The following docs must stay current as each workstream lands:

- [backend-administration.md](/home/briarmoss/Documents/boston-ai-project/docs/ops/backend-administration.md)
- [2026-03-25-backend-admin-first-pass-status.md](/home/briarmoss/Documents/boston-ai-project/docs/ops/2026-03-25-backend-admin-first-pass-status.md)
- [2026-03-25-daily-ops-runbook.md](/home/briarmoss/Documents/boston-ai-project/docs/ops/2026-03-25-daily-ops-runbook.md)
- [2026-03-25-daily-observability-plan.md](/home/briarmoss/Documents/boston-ai-project/docs/ops/2026-03-25-daily-observability-plan.md)
- [OPERATING_SYSTEM.md](/home/briarmoss/Documents/boston-ai-project/docs/ops/OPERATING_SYSTEM.md) if the control-plane rules change materially

## Recommended Session Breakdown

### Session 1

- implement scheduler dispatch command
- implement queue policy changes in code
- add local tests
- update docs

### Session 2

- implement dependency health checks across Laravel and `sysadmin/`
- add dry-run and JSON status coverage
- update docs

### Session 3

- implement summary-first logging for the highest-value commands
- extend summary extraction if needed
- update docs

### Session 4

- implement the backend health dashboard
- wire in dependency, freshness, and storage cards
- update docs

### Session 5

- implement the first alert path
- dry-run alert evaluation
- update docs

## Definition Of Done

This implementation plan is complete when:

1. Laravel owns the daily pipeline trigger.
2. Long-running admin work has a coherent queue policy.
3. Boston and Everett dependency issues are visible before pipeline failure.
4. The noisiest daily commands emit summary-first logs.
5. The admin has one daily backend health page.
6. Severe freshness failures create one lightweight push alert.
7. The playbook docs describe the live control plane, not an aspirational one.
