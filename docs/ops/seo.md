# SEO

## Purpose

Drive steady discoverability from search engines and LLM discovery surfaces using low-risk frontend and content improvements.

## Primary Outcomes

- stronger organic entry points
- cleaner indexable landing pages
- better internal linking and metadata
- better discoverability for city-specific and neighborhood-specific use cases

## Scope

This work should favor low-breakage changes:
- metadata
- titles and descriptions
- schema where useful
- internal links
- crawl/index hygiene
- page clarity
- landing-page copy improvements

## First Objectives

1. Audit technical SEO basics.
2. Review city landing pages for search intent and clarity.
3. Define high-priority search surfaces.
4. Build a queue of low-risk frontend SEO improvements.

## Areas To Review

- page titles
- meta descriptions
- canonical behavior
- sitemap and robots behavior
- crawlability of public pages
- link structure between home, city pages, and maps
- content structure for city pages like `/everett`
- whether public pages communicate value clearly to both humans and search systems

## Current Repo Finding

Public analysis and scoring pages are currently indexable with generic metadata.

What the code does today:
- `SeoMetadata` only forces `noindex, nofollow` for admin, auth, and profile surfaces
- public report routes such as `/reports/statistical-analysis/{jobId}`, `/reports/yearly-comparison/{jobId}`, and `/scoring-reports/{jobId}/{artifactName}` stay `index, follow`
- the affected page components mostly provide title-only page metadata
- no dedicated structured data exists for those report viewers

Why this matters:
- operational or artifact-style pages can enter search indices
- snippets are likely weak because descriptions are generic or absent
- search inventory can drift away from the intended landing/news/help surfaces

Near-term decision required:
- either promote those report pages as deliberate SEO surfaces with dedicated titles, descriptions, and schema
- or mark them `noindex` so indexation stays focused on city pages, help content, pricing, and news

## LLM Discoverability

Treat LLM discoverability as adjacent to SEO:
- clear page purpose
- stable public URLs
- good headings
- concise, specific copy
- structured summaries
- transparent data sourcing

## Weekly Review Loop

1. Review search performance and landing-page traffic.
2. Review pages with impressions but weak clickthrough.
3. Select one to three low-risk SEO/frontend changes.
4. Ship and measure impact.

## Inputs Required From Founder

- Search Console access or exports
- any historical SEO work or constraints
- approval for public copy changes where messaging matters

## Agent-Driven Work

- technical SEO audit
- landing-page recommendations
- metadata improvements
- issue backlog creation
- copy drafts for search-facing pages

## Founder-Required Actions

- Search Console access
- final approval on major public messaging changes if desired

## Near-Term Deliverables

- SEO audit
- city-page SEO recommendations
- low-risk frontend SEO backlog
- search measurement checklist
