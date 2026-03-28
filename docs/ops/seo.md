# SEO

## Purpose

Grow search and LLM discovery through pages that match real user intent instead of generic map-tool language.

## Current Search-Facing Surfaces

Primary indexable surfaces should be:

- homepage `/`
- crime preview `/crime-address`
- city and region landing pages `/{city-slug}`
- pricing `/subscription`
- help pages
- about page
- news pages

## Current SEO Thesis

PublicDataWatch should be discoverable through two complementary search shapes:

1. **Address-intent / neighborhood-intent**
   - “crime near my address”
   - “what crime is happening around [place]”
   - “neighborhood crime map”

2. **City-intent / region-intent**
   - “Boston crime map”
   - “New York 311 map”
   - “Montgomery County crime map”
   - similar city-specific searches

## City Landing Strategy

City landing pages are now a primary SEO surface, not an afterthought.

They should:

- match the actual dataset mix for that region
- avoid pretending every city has the same data
- explain what the user can do there quickly
- link into `/crime-address` when the region supports the crime preview funnel

Important nuance:

- Boston is a multi-dataset page
- New York is 311-first
- several other regions are crime-first

## Homepage SEO Role

The homepage should rank for the broader product story:

- crime around your address
- local public-data context
- daily neighborhood awareness

It should support city pages, not cannibalize them.

## Current Repo Guidance

Current SEO-critical files:

- `app/Support/SeoMetadata.php`
- `app/Http/Controllers/SitemapController.php`
- `resources/js/Pages/Home.vue`
- `resources/js/Pages/CrimeAddress/Index.vue`
- `app/Http/Controllers/CityLandingController.php`
- `resources/js/Pages/CityMapLite.vue`
- `resources/js/Utils/publicNavigation.js`

## Current Search Measurement State

- Search Console access is available through [tools/analytics](../../tools/analytics/README.md)
- default property: `sc-domain:publicdatawatch.com`
- production sitemap: `https://publicdatawatch.com/sitemap.xml`

Search performance is still early and sparse, so current SEO work should remain biased toward:

- clean metadata
- internal linking
- page clarity
- sitemap hygiene
- consistent city-page positioning

## Areas To Review

- titles and descriptions for `/`, `/crime-address`, and all city pages
- consistency between city page copy and actual dataset coverage
- internal links from home, footer, help, and pricing into city pages and the preview funnel
- sitemap inclusion for key public pages
- whether report viewers should remain indexable

## Current Indexation Decision Area

Public report and artifact-style pages can still enter search if left indexable.

That creates a product decision:

- deliberately support them as search surfaces with strong metadata and structure
- or mark them `noindex` so search stays focused on landing pages, help, pricing, and news

This remains a founder-review policy question, not a casual implementation detail.

## LLM Discoverability

LLM discovery improves when public pages are:

- explicit about purpose
- stable at the URL level
- clear in headings
- careful about methodology and limits
- internally well linked

City landing pages and help pages matter here just as much as classic SEO pages.

## Weekly Review Loop

1. Review Search Console performance.
2. Review landing-page traffic in GA4.
3. Pick one to three low-risk SEO improvements.
4. Ship and measure.

## Safe Agent-Driven Work

- metadata improvements
- sitemap and internal-link fixes
- city-page copy improvements
- help-page clarity improvements
- search-facing IA cleanup

## Founder-Review Work

- deciding whether public report pages stay indexable
- approving major messaging shifts on search-facing pages when positioning changes materially

## Near-Term Deliverables

- maintain homepage and crime-preview metadata quality
- maintain city landing pages as the main city-intent entry points
- improve city page to preview linking
- keep help/about/pricing aligned with the address-first product story
