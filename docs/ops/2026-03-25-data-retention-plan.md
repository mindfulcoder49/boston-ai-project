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

Not yet automated in a retention-safe way:
- file cleanup in `storage/logs` and `storage/app/datasets`
- raw source-table retention
- any storage-pressure workflow tied to Hostinger limits

Legacy cleanup outside the retention plan:
- `DataCleanupSeeder` is a one-off corrective cleanup path, not a general retention routine

## First Implementation Pass

### 1. Database Retention Review Command

Command:
- `php artisan app:review-data-retention`

Purpose:
- preview database rows that fall outside the current retention window
- show cutoff date, candidate count, and sample rows
- delete nothing

Current scope:
- shared `data_points`
- city-specific `*_data_points` tables

### 2. File Cleanup Dry Run

Command:
- `php artisan app:cleanup --dry-run-before=YYYY-MM-DD`

Purpose:
- preview files that would be deleted from:
  - `storage/logs`
  - `storage/app/datasets`
- show file count, estimated freed space, and sample file paths
- delete nothing

### 3. Approval Boundary

No new cleanup automation should happen until:
1. a dry run has been reviewed
2. the founder approves the cleanup scope
3. the cleanup is trialed manually once

Only after that should scheduled cleanup be considered.

## Recommended Next Phases

### Phase 2. Centralize Apply Paths

Move retention deletion into explicit commands with:
- `--dry-run` as the default safety mode
- `--apply` required for real deletion
- clear summary output after execution

### Phase 3. Add Cleanup Observability

Surface cleanup summary fields in pipeline/admin views:
- rows deleted
- files deleted
- estimated space freed
- cutoff date used
- largest affected tables or directories

### Phase 4. Expand Beyond Aggregate Tables

Only after review and approval:
- define raw source-table retention by dataset family
- decide which raw datasets are historical assets versus disposable operational inputs

## Operating Notes

- Hostinger storage pressure is now a real operational constraint.
- `df` on the server is not enough by itself because filesystem capacity is not the same as plan quota.
- Until better hosting-limit telemetry exists, cleanup should be driven by dry-run review plus measured path sizes.
