# 2026-03-25 Data Retention Plan

## Goal

Reduce storage pressure and make cleanup safe enough to automate later without blind deletions.

The immediate constraint is Hostinger storage pressure, not abstract neatness.

## Current Policy

Dry run first:
- no new destructive cleanup should be automated until the candidate set can be previewed
- the agent should review the dry-run output first
- founder approval should happen before any new `--apply` or real delete path is used
- after that, cleanup can be trialed manually before it is scheduled

This policy applies to:
- database retention routines
- file/log cleanup routines
- any future raw source-table cleanup

## Current Live Cleanup Shape

Already happening automatically in the app today:
- aggregate data point tables prune rows older than 183 days inside their seeders

Now available behind explicit review/apply commands:
- raw source-table retention for the main production database

Still not automated by default:
- file cleanup in `storage/logs` and `storage/app/datasets`
- any storage-pressure workflow tied to Hostinger limits

Legacy cleanup outside the retention plan:
- `DataCleanupSeeder` is a one-off corrective cleanup path, not a general retention routine

## First Implementation Pass

### 1. Database Retention Review Command

Command:
- `php artisan app:review-data-retention`
- `php artisan app:review-data-retention --group=main_raw_source_tables`

Purpose:
- preview database rows that fall outside the current retention window
- show cutoff date, candidate count, sample rows, and approximate reclaimable size
- delete nothing

Current scope:
- shared `data_points`
- city-specific `*_data_points` tables
- main production raw/source tables that feed current Boston, Cambridge, Everett, and Massachusetts crash coverage

### 2. Database Retention Apply Command

Command:
- `php artisan app:apply-data-retention --group=main_raw_source_tables --force`
- `php artisan app:apply-data-retention --rule=crime-data --force`

Purpose:
- delete reviewed database rows outside the retention window
- require explicit scope via `--group` or `--rule`
- batch deletes so large tables are not wiped in one statement

Operating rule:
- run the matching review command first
- founder approval is still required before production use or schedule enablement

### 3. File Cleanup Dry Run

Command:
- `php artisan app:cleanup --dry-run-before=YYYY-MM-DD`
- `php artisan app:cleanup --list-targets`
- `php artisan app:cleanup --dry-run-before=YYYY-MM-DD --target=logs`
- `php artisan app:cleanup --dry-run-before=YYYY-MM-DD --target=datasets`

Purpose:
- preview files that would be deleted from:
  - `storage/logs`
  - `storage/app/datasets`
- show file count, estimated freed space, and sample file paths
- delete nothing

Current target model:
- safe defaults are non-overlapping top-level targets:
  - `logs`
  - `datasets`
- narrower trial targets exist for high-pressure areas like:
  - `pipeline-runs`
  - `boston-datasets`
  - `cambridge-socrata-datasets`
  - `cambridge-logs`
  - other city-specific dataset buckets

Important Cambridge distinction:
- `cambridge-socrata-datasets` are full snapshot files downloaded by `app:download-city-dataset`
- `cambridge-logs` are daily police log CSVs built by `app:download-cambridge-logs`
- those should not be treated as the same retention class

Important Boston distinction:
- Boston full-refresh scraper downloads are stored at the top level of `storage/app/datasets`
- `boston-datasets` isolates them by filename pattern so they can be reviewed without sweeping in every city subdirectory

### 4. Approval Boundary

No new cleanup automation should happen until:
1. a dry run has been reviewed
2. the founder approves the cleanup scope
3. the cleanup is trialed manually once

Only after that should scheduled cleanup be considered.

## Recommended Trial Order

For the first manual cleanup trial, do not start with the full default scope.

Recommended order:
1. preview `pipeline-runs`
2. preview `boston-datasets`
3. preview `cambridge-socrata-datasets`
4. preview `logs`
5. preview all `datasets`

Reason:
- these are the largest and most obviously reproducible storage consumers
- they let cleanup be reviewed in narrower slices before any broader delete action

## Current Production Dry-Run Findings

Using a cutoff of `2026-02-24` on production:

- `pipeline-runs`: `5,469` files, about `9.73 GB`
- `boston-datasets`: `139` files, about `15.55 GB`
- `cambridge-socrata-datasets`: `1,651` files, about `41.75 GB`
- `cambridge-logs`: `265` files, about `1.53 MB`

Interpretation:
- `pipeline-runs` is still the safest first real delete trial
- `boston-datasets` is the clearest next dataset cleanup candidate because those files are known full snapshots
- `cambridge-socrata-datasets` is large and likely safe, but it stays behind Boston because Cambridge storage also contains a separate daily-log flow
- `cambridge-logs` is too small to matter right now and should not drive early cleanup decisions

## First Live Cleanup Trial

Executed on production on `2026-03-25`:

- command: `php artisan app:cleanup --delete-before=2026-02-24 --target=pipeline-runs`
- result: `5,469` files deleted, `9.73 GB` freed
- `storage/logs/pipeline_runs` dropped from about `11G` to `605M`
- a repeat dry run for the same cutoff now returns `0` candidate files

Outcome:
- the preview-first workflow worked as intended
- scoped cleanup is safe enough to continue manually on the next narrow target
- the next recommended manual trial was `boston-datasets`

## Second Live Cleanup Trial

Executed on production on `2026-03-25`:

- command: `php artisan app:cleanup --delete-before=2026-02-24 --target=boston-datasets`
- result: `139` files deleted, `15.55 GB` freed
- top-level files in `storage/app/datasets` dropped from about `21G` to `5.2G`
- a repeat dry run for the same cutoff now returns `0` Boston candidate files

Outcome:
- the Boston filename-pattern target behaved correctly and did not sweep in other city subdirectories
- the preview-first workflow is now validated on both log cleanup and dataset cleanup
- the next recommended manual trial is `cambridge-socrata-datasets`

## June 2026 Scoped Cleanup

Executed on production on `2026-06-08`.

First conservative pass used cutoff `2026-03-10`:

- `pipeline-runs` dry run: `44` files, about `52 MB`; delete result: `44` files deleted, `52 MB` freed
- `boston-datasets` dry run: `8` files, about `878.37 MB`; delete result: `8` files deleted, `878.37 MB` freed
- `cambridge-socrata-datasets` dry run: `7` files, about `143.92 MB`; delete result: `7` files deleted, `143.92 MB` freed
- repeat dry runs for all three targets returned `0` candidates for the same cutoff

Second retention pass used cutoff `2026-05-09`, keeping about 30 days of current PublicDataWatch artifacts:

- `boston-datasets` dry run: `462` files, about `46.11 GB`; delete result: `462` files deleted, `46.11 GB` freed
- `cambridge-socrata-datasets` dry run: `364` files, about `7.58 GB`; delete result: `364` files deleted, `7.58 GB` freed
- `pipeline-runs` dry run: `2,429` files, about `6.53 GB`; delete result: `2,429` files deleted, `6.53 GB` freed
- repeat dry runs for all three targets returned `0` candidates for the same cutoff

Additional dry-run-only findings from the initial sweep:

- `everett-datasets`: `2,547` files, about `39.08 MB`; left untouched because Everett ingestion was recently sensitive and the space savings are small
- `chicago-datasets`: `1` file, about `10.83 MB`; left untouched pending explicit review for newer city targets
- `san-francisco-datasets`: `1` file, about `2.24 MB`; left untouched pending explicit review for newer city targets
- `seattle-datasets`: `1` file, about `1.06 MB`; left untouched pending explicit review for newer city targets
- `montgomery-county-md-datasets`: `1` file, about `204.09 MB`; left untouched pending explicit review for newer city targets
- `massachusetts-datasets`, `new-york-datasets`, and current `laravel-log-archives`: `0` candidates

Legacy-host cleanup on `/home/u353344964/domains/alcivartech.com`:

- `bostonApp/storage/app/datasets`: manual dry run found `297` files older than 30 days, about `16.56 GB`; deleted those files while excluding Everett and Cambridge daily police log paths
- `bostonApp/storage/logs/laravel.log`: truncated from about `5.0 GB` to `0 B`
- `startupBos/midland/storage/logs/laravel.log`: truncated from about `15.2 GB` to `0 B`; removed two stale editor swap files beside it
- legacy Boston `app:cleanup` was unavailable, so cleanup used explicit path-scoped `find` checks instead of broad directory removal

Post-cleanup production measurements:

- current PublicDataWatch app `storage`: about `39G`, down from about `100G`
- current PublicDataWatch `storage/app/datasets`: about `34G`, down from about `87G`
- current PublicDataWatch `storage/logs`: about `4.5G`, down from about `12G`
- current PublicDataWatch `storage/logs/pipeline_runs`: about `3.7G`, down from about `11G`
- account home `/home/u353344964`: about `49G` by `du` after legacy cleanup

Outcome:

- the repeated scoped cleanup path remains safe for previously reviewed current-app targets
- 30-day artifact retention is the meaningful storage lever for current PublicDataWatch snapshots and pipeline logs
- broader city dataset cleanup should stay review-driven until each target is validated independently
- Everett cleanup is not currently worth the operational risk for only about `39 MB`
- oversized current logs in legacy apps were a separate storage issue and need follow-up log hygiene if those apps remain active

## Recommended Next Phases

### Phase 2. Add Cleanup Observability

Surface cleanup summary fields in pipeline/admin views:
- rows deleted
- files deleted
- estimated space freed
- cutoff date used
- largest affected tables or directories

### Phase 3. Enable Scheduled Retention After Review

Now possible, but still disabled by default:
- `app:apply-data-retention` can be scheduled weekly through `config/data_retention.php`
- activation requires reviewed dry-run output plus founder approval first

Current proposed database policy:
- keep `data_points` tables at 183 days
- keep main raw/source tables at 365 days, except Everett raw history
- keep Everett raw history in full for now by excluding `everett_crime_data` from the default raw-table retention group
- keep `analysis_report_snapshots` out of automated age-based deletion until its storage model is redesigned

## Operating Notes

- Hostinger storage pressure is now a real operational constraint.
- `df` on the server is not enough by itself because filesystem capacity is not the same as plan quota.
- Until better hosting-limit telemetry exists, cleanup should be driven by dry-run review plus measured path sizes.
