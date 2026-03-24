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

