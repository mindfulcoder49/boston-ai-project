# AGENTS.md

Repository guidance for coding agents working in this project.

## Session Start

At the beginning of every session, before doing substantial work:

1. Read [docs/ops/OPERATING_SYSTEM.md](docs/ops/OPERATING_SYSTEM.md).
2. Read the relevant playbook in `docs/ops/` for the domain you are touching:
   - [docs/ops/analytics.md](docs/ops/analytics.md)
   - [docs/ops/seo.md](docs/ops/seo.md)
   - [docs/ops/backend-administration.md](docs/ops/backend-administration.md)
   - [docs/ops/growth-monetization.md](docs/ops/growth-monetization.md)
   - [docs/ops/social-distribution.md](docs/ops/social-distribution.md)
3. Use [CLAUDE.md](CLAUDE.md) for repo architecture, commands, and implementation details.
4. If the task touches local ops tooling, also read the relevant workspace doc:
   - [tools/analytics/README.md](tools/analytics/README.md) for GA4 and Search Console work
   - [tools/exoskeleton/README.md](tools/exoskeleton/README.md) for founder action queue work

## Operating Expectations

- Treat [docs/ops/OPERATING_SYSTEM.md](docs/ops/OPERATING_SYSTEM.md) as the top-level operating policy for planning and prioritization.
- Follow the approval boundaries documented there, especially for pricing, billing, public posting, and external account actions.
- Prefer small, reversible, well-instrumented changes.
- If a task touches analytics, SEO, backend operations, or monetization, align the work with the corresponding `docs/ops` playbook.
- If a task touches social distribution, product page management, or public content planning, align the work with [docs/ops/social-distribution.md](docs/ops/social-distribution.md).
- Prefer standalone local tooling for analytics, ops automation, and non-production test infrastructure when the work does not need to run on Hostinger or inside Laravel.
- For Laravel commands, tests, and runtime validation, prefer `./vendor/bin/sail ...` when Sail is available so verification matches the actual app runtime.
- For deployment or server-access work, check [docs/ops/backend-administration.md](docs/ops/backend-administration.md) for the deploy flow, and use local `~/.ssh/config` for exact SSH connection details.
- When work identifies a `founder_review` or `founder_required` external action, create or update the task in `tools/exoskeleton` before handing the task back to the founder.
- Founder queue tasks must be self-contained. Do not rely on the founder opening markdown files to know what to do; include exact copy, exact values, or exact step text directly in the task.
- Treat new cleanup or retention automation as approval-sensitive work: add a dry-run review path first, then get founder approval before enabling destructive automation.
- When making recommendations or planning work, separate:
  - safe agent-driven actions
  - founder-review actions
  - founder-required actions

## Default System Check

When the founder asks a broad status question such as:
- `how is the system?`
- `check on the system`
- `give me the morning check`

treat that as a request for the default system check unless they narrow the scope.

Default scope:

- production first
- for backend health, use live production evidence first:
  - production admin surfaces
  - production `php artisan ...` checks over SSH on Hostinger
  - production logs or runtime artifacts
- for analytics and search, use the live GA4 and Search Console properties
- do not answer a broad system-check request from local or dev-state evidence unless the founder explicitly asks for the dev/local system or production access is unavailable
- if you must fall back to local or dev checks, say that explicitly and label the result as non-production

Default review order:

1. Backend health first.
   - confirm you are looking at the production system before interpreting backend freshness
   - check the latest pipeline run
   - check whether it completed in the last 24 hours
   - identify the first failed command if not
   - check core freshness:
     - `DataPointSeeder`
     - `app:cache-metrics-data`
     - `reports:send`
   - check ingestion dependencies, alerts, and storage-pressure concerns
   - note whether any issue is a true freshness failure, a partial city/source degradation, or an external-runtime cutover problem

2. Analytics sanity second.
   - use `tools/analytics` for GA4 checks
   - look for obvious traffic anomalies and key-event breakage
   - treat GA4 property `properties/490826923` as the default live property unless the task surfaces a current config problem
   - if analytics instrumentation or GA4 settings changed recently, split the review into:
     - a post-change validation window anchored to the production deploy or the last 24 to 72 hours
     - a broader 14 to 30 day context window that is labeled as mixed historical data
   - do not conclude that a newly added event is missing, or that a legacy event still reflects the current frontend, until you check a post-change window first
   - always state the exact GA4 window used when interpreting recent analytics changes

3. Search visibility third when useful.
   - use `tools/analytics` for Search Console checks
   - treat `sc-domain:publicdatawatch.com` as the default property
   - focus on notable search changes, sitemap/indexation issues, or query/page movement if data exists

Default response shape:
- short metrics/status summary
- ranked issues or anomalies
- recommended actions
- founder-review actions
- founder-required actions

Always state the checked environment if there is any realistic chance of ambiguity.

If the check surfaces a `founder_review` or `founder_required` external action, create or update the task in `tools/exoskeleton` before handing back the result.

## Deliverable Bias

When doing product or ops-oriented work, favor outputs that match the operating system:

- metrics summaries
- ranked priorities
- recommended actions
- approval-required actions
- change and learning logs

## Notes

- If `docs/ops/` and code behavior disagree, inspect the code, note the discrepancy, and surface it explicitly.
- Documented local access exists for Google Analytics 4 and Google Search Console through `tools/analytics`.
- Treat GA4 property `properties/490826923` and Search Console property `sc-domain:publicdatawatch.com` as available through that tooling unless the task surfaces a current credential or runtime failure.
- Do not assume access to other external systems such as Stripe or social accounts unless confirmed.
