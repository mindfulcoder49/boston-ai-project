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

## Operating Expectations

- Treat [docs/ops/OPERATING_SYSTEM.md](docs/ops/OPERATING_SYSTEM.md) as the top-level operating policy for planning and prioritization.
- Follow the approval boundaries documented there, especially for pricing, billing, public posting, and external account actions.
- Prefer small, reversible, well-instrumented changes.
- If a task touches analytics, SEO, backend operations, or monetization, align the work with the corresponding `docs/ops` playbook.
- If a task touches social distribution, product page management, or public content planning, align the work with [docs/ops/social-distribution.md](docs/ops/social-distribution.md).
- Prefer standalone local tooling for analytics, ops automation, and non-production test infrastructure when the work does not need to run on Hostinger or inside Laravel.
- For deployment or server-access work, check [docs/ops/backend-administration.md](docs/ops/backend-administration.md) for the deploy flow, and use local `~/.ssh/config` for exact SSH connection details.
- When making recommendations or planning work, separate:
  - safe agent-driven actions
  - founder-review actions
  - founder-required actions

## Deliverable Bias

When doing product or ops-oriented work, favor outputs that match the operating system:

- metrics summaries
- ranked priorities
- recommended actions
- approval-required actions
- change and learning logs

## Notes

- If `docs/ops/` and code behavior disagree, inspect the code, note the discrepancy, and surface it explicitly.
- Do not assume access to external systems such as Google Analytics, Search Console, Stripe, or social accounts unless confirmed.
