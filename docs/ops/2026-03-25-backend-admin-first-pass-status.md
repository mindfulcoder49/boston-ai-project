# 2026-03-25 Backend Admin First-Pass Status

## Purpose

This document now answers a different close-out question than the earlier draft:

- what is finished in the first backend-admin implementation pass
- what still remains after the code work landed

Detailed work order, file targets, and test coverage still live in:

- [2026-03-25-backend-admin-implementation-plan.md](./2026-03-25-backend-admin-implementation-plan.md)

## Code-Side First Pass: Complete

Implemented on March 25, 2026:

- scheduler consolidation in Laravel
  - scheduled `app:run-admin-long-worker`, which wraps `queue:work --stop-when-empty --queue=admin-long,default --timeout=7200 --tries=1`
  - scheduled `app:check-ingestion-dependencies`
  - scheduled `app:dispatch-daily-pipeline`
  - scheduled `app:evaluate-backend-health-alerts`
- queue runtime policy cleanup
  - `RunArtisanCommandJob` now targets the dedicated `admin-long` queue
  - long-running orchestration uses a 2-hour queue timeout with `tries=1`
- scraper and DNS dependency health checks
  - `app:check-ingestion-dependencies`
  - queue worker heartbeat tracking
  - read-only DNS status artifact consumption from S3
- summary-first operational logging
  - `app:download-boston-dataset-via-scraper`
  - `app:download-everett-pdf-markdown`
  - `app:download-cambridge-logs`
  - `DataPointSeeder`
  - `app:cache-metrics-data`
- admin backend health dashboard
- lightweight alert path
  - admin alert banner
  - direct email alerting
  - dry-run alert evaluation command
- stale-running dispatch guard
  - old historical `running` summaries no longer block the daily dispatch forever

## Verification Completed

Validated on March 25, 2026:

- `./vendor/bin/sail artisan test tests/Feature/BackendAdmin/BackendAdminFlowTest.php`
- `./vendor/bin/sail artisan schedule:list`
- `./vendor/bin/sail artisan app:dispatch-daily-pipeline --dry-run --skip-dependency-check`
- `./vendor/bin/sail artisan app:check-ingestion-dependencies --json`
- `./vendor/bin/sail artisan app:evaluate-backend-health-alerts --dry-run --json`
- `npm run build`
- `PYTHONPATH=sysadmin sysadmin/.venv/bin/python -m unittest discover -s sysadmin/tests`

## What Still Remains

### 1. Hostinger Cron Cutover

Status:
- complete as of March 26, 2026

Confirmed production cron:
- `* * * * * /usr/bin/php /home/u353344964/domains/publicdatawatch.com/bostonApp/artisan schedule:run`

Why it matters:
- the Laravel scheduler is now the intended control plane
- production is using the intended cron entry, so remaining investigation is now about runtime evidence rather than cron setup

### 2. External Runtime Signal Validation

Status:
- partially complete

What remains:
- confirm the real sysadmin runtime is publishing `ops/health/ec2_dns_status.json`
- confirm the real scheduled worker path is leaving fresh heartbeat evidence

Why it matters:
- until those signals appear, dependency health will still show warnings instead of a clean healthy state

### 3. Next Manual Retention Trial

Status:
- still open

Current next candidate:
- `cambridge-socrata-datasets`
- current dry run on production:
  - `1,651` files
  - `41.75 GB`

Why it matters:
- Hostinger storage pressure is still real
- the preview-first cleanup workflow is proven, but one large safe bucket remains

## Practical Interpretation

The first backend-admin pass is no longer blocked on missing code.

The remaining work is now mostly:

- external cutover
- runtime confirmation
- the next approved retention action
