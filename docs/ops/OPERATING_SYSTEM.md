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
   Current execution plan: [2026-03-25-backend-admin-implementation-plan.md](./2026-03-25-backend-admin-implementation-plan.md)

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
- For new cleanup or retention automation, require dry-run review first and founder approval before enabling destructive automation.
- Treat broad health or status checks as production checks by default unless the founder explicitly asks for local, dev, or staging.
- When production access is unavailable and a local or dev fallback is used, label the result explicitly as non-production.

## Current Confirmed Facts

- As of March 24, 2026, local GA4 access is available through `tools/analytics` for GA4 property `properties/490826923` (`PublicDataWatch`).
- As of March 24, 2026, that GA4 property's timezone was updated to `America/New_York`.
- As of March 24, 2026, Search Console API access is available through `tools/analytics` for `sc-domain:publicdatawatch.com`, and `https://publicdatawatch.com/sitemap.xml` was submitted successfully.
- As of March 25, 2026, Laravel scheduler entries for the scheduled `admin-long` worker, dependency checks, daily pipeline dispatch, and backend alert evaluation are implemented in code.
- As of March 26, 2026, the founder confirmed Hostinger production is using a single cron entry:
  - `* * * * * /usr/bin/php /home/u353344964/domains/publicdatawatch.com/bostonApp/artisan schedule:run`
- The remaining backend-admin follow-up is confirming that the scheduler-driven worker heartbeat and DNS status artifact are visible in the live runtimes.

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
- queue items for the founder must be self-contained and actionable without requiring the founder to open markdown files
- if the action is a post, message, setting change, or external form entry, include the exact copy or exact values directly in the queue item
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

Confirmed local access today:
- Google Analytics 4 via `tools/analytics`
- Google Search Console via `tools/analytics`

Needed over time:
- Stripe
- frontend test reports
- deployment/build logs
- issue tracker
- error monitoring
- social account performance data

## Open Questions

- Is internal traffic and environment separation configured cleanly in GA4 property `properties/490826923`?
- Which Stripe products and prices are currently active?
- Which external social accounts already exist versus need to be created?
- Why is the live worker-heartbeat evidence stale even though Hostinger production is already on the scheduler cron?
