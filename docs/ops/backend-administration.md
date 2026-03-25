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

## Current Production Deploy

- Production deploys currently run from `~/publicdatawatchdeploy.sh` on the Hostinger server.
- The exact SSH user, port, and any host alias should be read from local `~/.ssh/config` rather than committed into the repo docs.
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
