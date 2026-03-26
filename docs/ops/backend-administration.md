# Backend Administration

## Purpose

Document and stabilize the command-driven operational flows that control ingestion, seeding, analysis, and reporting, then gradually move them toward a more autonomous loop.

## Primary Outcomes

- explicit command sequencing
- fewer operator mistakes
- clearer validation and rollback steps
- better visibility into data freshness and analysis health
- gradual reduction in founder-only operational knowledge

## Scope

This area covers:
- ingestion commands
- seeding commands
- analysis dispatch commands
- cache/refresh procedures
- data validation checks
- daily and weekly operational review

## First Objectives

1. Inventory production-relevant commands.
2. Document the current data flow by city and model.
3. Record the required sequencing and dependencies.
4. Define validation checks after each major operational stage.
5. Identify which parts still require founder judgment.

## Command Documentation Standards

For each command or sequence, document:
- purpose
- required inputs
- preconditions
- exact command
- expected outputs
- verification steps
- failure modes
- rollback or recovery actions

## Operational Loop

Near term:
- founder-guided observation and review
- agent-prepared procedures, checks, and summaries

Target state:
- documented daily operational runbook
- automated reporting on failures, stale data, and suspicious output
- founder reviewing summaries rather than manually reconstructing the system

## Inputs Required From Founder

- explanation of the current production-critical command flows
- identification of fragile or historically error-prone sequences
- clarification on city-by-city differences in data operations

## Agent-Driven Work

- command inventory
- runbook drafting
- sequencing documentation
- validation checklist creation
- issue identification around operational fragility

## Founder-Required Actions

- confirming the intended sequencing where code alone is not enough
- approving automation of procedures that currently depend on judgment

## Near-Term Deliverables

- command inventory
- city/model data-flow map
- daily admin runbook draft
- validation checklist for ingestion and analysis

Current audit and first runbook draft:
- [2026-03-25-backend-admin-audit.md](./2026-03-25-backend-admin-audit.md)
- [2026-03-25-data-flow-report.md](./2026-03-25-data-flow-report.md)
- [2026-03-25-daily-ops-runbook.md](./2026-03-25-daily-ops-runbook.md)
- [2026-03-25-daily-observability-plan.md](./2026-03-25-daily-observability-plan.md)
- [2026-03-25-data-retention-plan.md](./2026-03-25-data-retention-plan.md)
- [2026-03-25-backend-admin-first-pass-status.md](./2026-03-25-backend-admin-first-pass-status.md)
- [2026-03-25-backend-admin-implementation-plan.md](./2026-03-25-backend-admin-implementation-plan.md)

Current observability status:
- pipeline summary enrichment is implemented in the admin pipeline views and `run_summary.json`
- summary-first operational logging is now implemented for the highest-value noisy daily commands:
  - `app:download-boston-dataset-via-scraper`
  - `app:download-everett-pdf-markdown`
  - `app:download-cambridge-logs`
  - `DataPointSeeder`
  - `app:cache-metrics-data`
- the backend health dashboard, dependency health command, lightweight alert path, and Laravel scheduler entries are now implemented in code
- stale historical `running` pipeline entries no longer block the daily dispatch path forever; only recent active runs count as blockers
- the main remaining backend-admin follow-up is external cutover:
  - switch Hostinger from the old `queue:listen` cron to the scheduler-driven flow
  - confirm the `sysadmin/` DNS sync runtime is publishing its S3 status artifact in the environment that actually runs it
- the sysadmin runtime must have `S3_BUCKET_NAME` configured if you want DNS sync evidence published to `ops/health/ec2_dns_status.json`, but missing DNS evidence is now informational rather than a backend-health warning
- the scraper backend now exposes `GET /health` in the `opportunityHarvester` service for manual checks and future explicit probes, but Laravel still treats a direct reachability ping to the scraper base URL as sufficient for now

Current retention direction:
- storage pressure on Hostinger is now an explicit backend-admin concern
- new cleanup work should be preview-first, with dry-run review before any new delete automation
- aggregate table retention and file cleanup are the first safe areas to standardize
- current file-cleanup priority is:
  - `pipeline-runs`, now successfully trialed on production
  - `boston-datasets`, now successfully trialed on production
  - `cambridge-socrata-datasets`, now the next recommended manual trial
- Cambridge daily police logs are explicitly separated from Cambridge full snapshots and are not an early cleanup priority because they only account for about `1.53 MB` in the current dry run
- the first live cleanup trial deleted `5,469` old pipeline-run files and freed `9.73 GB`, which validates the preview-first retention workflow for further narrow manual trials
- the second live cleanup trial deleted `139` old Boston full-snapshot files and freed `15.55 GB`, which validates filename-pattern scoped dataset cleanup as the next step toward broader storage relief

Current first-pass closeout status:
- first-pass completion criteria and remaining implementation work are tracked in [2026-03-25-backend-admin-first-pass-status.md](./2026-03-25-backend-admin-first-pass-status.md)
- that document now also records the recommended implementation approach for each still-open first-pass issue
- the concrete execution order, file targets, acceptance criteria, and test plan for those issues are tracked in [2026-03-25-backend-admin-implementation-plan.md](./2026-03-25-backend-admin-implementation-plan.md)
- the first-pass code implementation is now complete
- the most important remaining follow-up areas are:
  - Hostinger cron cutover to the single scheduler entry
  - validation of worker heartbeat and DNS status artifact publishing in the real external runtimes
  - the next manual storage-retention trial for `cambridge-socrata-datasets`

## Current Production Deploy

- Production deploys currently run from `~/publicdatawatchdeploy.sh` on the Hostinger server.
- The exact SSH user, port, and any host alias should be read from local `~/.ssh/config` rather than committed into the repo docs.
- Laravel validation should be run through `./vendor/bin/sail ...` locally when Sail is available.
- Standard release sequence is:
  - verify changes locally
  - commit to git locally
  - push `main` to GitHub
  - SSH to the Hostinger server
  - run `~/publicdatawatchdeploy.sh`
  - verify the public site after deploy
- That script now includes `php artisan route:cache` as part of the deploy sequence, so route caching is no longer a manual post-deploy step.
- After deploy, verify the public site, the relevant city routes, and `sitemap.xml` before treating the release as complete.
- Agent execution is now allowed for the full low-risk release path when git auth and server access are available.
