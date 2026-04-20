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
- keep main raw/source tables at 365 days
- keep `analysis_report_snapshots` out of automated age-based deletion until its storage model is redesigned

## Operating Notes

- Hostinger storage pressure is now a real operational constraint.
- `df` on the server is not enough by itself because filesystem capacity is not the same as plan quota.
- Until better hosting-limit telemetry exists, cleanup should be driven by dry-run review plus measured path sizes.
