# Operating System

This document defines the conservative operating loop for reshaping and monetizing PublicDataWatch with stability, measurement quality, and low founder firefighting as the primary constraints.

## Goal

Build a repeatable operating system where:
- product changes are low-risk and well-instrumented
- growth is measured and compounding rather than rapid and chaotic
- backend data operations become increasingly autonomous over time
- monetization and social execution are directed by recommendations, with founder approval for high-risk or external actions

## Current Priorities

1. Analytics Foundation
   Make measurement trustworthy before optimizing anything else.

2. SEO And Low-Risk Frontend Growth
   Improve discoverability and conversion using changes unlikely to break core flows.

3. Backend Administration And Data Operations
   Document and stabilize operational command flows, then gradually automate them.

4. Monetization And Social Distribution
   Run pricing, content, and distribution work through recommendation plus founder execution.

## Domain Playbooks

- [Analytics](./analytics.md)
- [SEO](./seo.md)
- [Backend Administration](./backend-administration.md)
- [Growth And Monetization](./growth-monetization.md)
- [Social Distribution](./social-distribution.md)

## Operating Rules

- Optimize for stability and sure growth over speed.
- Prefer small reversible changes over large speculative changes.
- Instrument before optimizing whenever practical.
- Add test coverage around anything that materially affects user flows, payments, or data operations.
- Do not treat vanity engagement as evidence of product traction.
- Separate low-risk autonomous work from high-risk approval-required work.
- Keep the repo, remote branch, and production state aligned whenever shipping changes.

## Delivery Loop

Standard delivery sequence for code changes:
- make the change locally
- run the relevant local verification
- commit the change in git
- sync `main` to GitHub
- run the production deploy flow
- verify the live site and critical routes after deploy

Agent capability now includes:
- creating local commits for completed work
- syncing the current branch to GitHub when auth is configured
- running the standard production deploy flow

This capability should still be used conservatively:
- do not deploy unverified changes
- do not bypass founder approval for high-risk changes
- keep markdown operating docs updated when the delivery process changes

## Founder Action Queue

Use the local founder action queue in `tools/exoskeleton` as the default handoff mechanism for anything the founder needs to do outside the codebase.

Required discipline:
- when work reveals a `founder_review` or `founder_required` action, create or update the queue item before ending the task or asking the founder to act
- include the exact action, external system, success criteria, blocking reason, and source doc or workflow
- update or replace stale queue items instead of leaving vague or superseded tasks behind
- mention the queue item explicitly in the handoff response so the founder knows where to look

## Approval Boundaries

Low-risk, generally agent-drivable:
- analytics review and instrumentation plans
- roadmap updates
- issue creation and prioritization
- low-risk frontend/SEO changes
- backend procedure documentation
- test additions and reliability improvements

Medium-risk, usually founder review first:
- copy changes on key landing pages
- new event definitions that affect reporting conventions
- command sequencing changes in operational flows
- experiment design that affects user experience materially
- routine production deploys after low-risk code changes, unless the founder has already delegated that flow for the current workstream

High-risk, founder approval required:
- production pricing changes
- Stripe dashboard configuration changes
- public social posting
- account creation on external platforms
- actions with legal, billing, or brand consequences
- risky or unclear production deploys where the impact is not well understood

## Weekly Deliverables

Each week should produce:
- a metrics summary
- a short ranked priority list
- a list of recommended actions
- a list of actions requiring founder approval
- a log of what changed and what was learned

## Cadence

Daily:
- monitor operational failures, broken flows, and anomalies

Weekly:
- review analytics
- review SEO opportunities
- review backend administration status
- review monetization and content recommendations
- pick the next 1 to 3 safe improvements

Monthly:
- reassess the wedge, funnel, pricing, and channel performance

## Access Checklist

Needed over time:
- Google Analytics
- Google Search Console
- Stripe
- frontend test reports
- deployment/build logs
- issue tracker
- error monitoring
- social account performance data

## Open Questions

- Which analytics platform and property structure are current?
- Which Stripe products and prices are currently active?
- Which external social accounts already exist versus need to be created?
- Which backend command sequences are considered production-critical today?
