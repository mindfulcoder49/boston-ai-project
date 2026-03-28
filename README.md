<img src="https://github.com/mindfulcoder49/boston-ai-project/blob/main/public/images/logo.png" alt="PublicDataWatch Logo" width="200"/>

# PublicDataWatch

PublicDataWatch is a Laravel + Vue civic-data product built around one simple question:

`What is happening around this address right now?`

The public experience starts with an address-first crime preview, then expands into city landing pages, the full explore map, trend reports, neighborhood scoring, and recurring email reports.

## Current Product Surface

- `GET /` — address-first homepage
- `GET /crime-address` — lightweight crime preview funnel
- `GET /{city}` — city and region landing pages for supported coverage areas
- `GET /map/{lat?}/{lng?}` — radial explore map
- `GET /combined-map` — full multi-dataset map
- `GET /trends` — statistical trend reports
- `GET /yearly-comparisons` — year-over-year comparison reports
- `GET /scoring-reports` — neighborhood scoring tools
- `GET /subscription` — pricing and paid plan conversion

## Supported Cities And Regions

- Boston, MA
- Cambridge, MA
- Everett, MA
- Chicago, IL
- San Francisco, CA
- New York, NY
- Montgomery County, MD
- Seattle, WA

Coverage is not uniform across every region. Boston has the broadest multi-dataset mix. Several other regions are crime-first. New York is currently 311-first.

## Stack

- Laravel 10
- Vue 3 + Inertia.js
- MySQL / MariaDB
- Redis queues and caching
- Leaflet for maps
- H3 for spatial indexing
- Stripe via Laravel Cashier
- OpenAI and Google Gemini integrations
- Playwright for browser coverage

## Local Development

Install dependencies:

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Start the Laravel app with Sail when available:

```bash
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate
npm run dev
```

If you are not using Sail:

```bash
php artisan migrate
php artisan serve
npm run dev
```

## Testing

Backend:

```bash
./vendor/bin/sail test
```

Targeted browser coverage:

```bash
npx playwright test tests/e2e/public-surface-regressions.spec.ts
```

Production build:

```bash
npm run build
```

## Production Deploy

Standard deploy flow:

```bash
git push origin main
ssh <host-alias-from-ssh-config> '~/publicdatawatchdeploy.sh'
```

That deploy script currently:

- hard-resets production to `origin/main`
- runs Composer install
- runs `npm run build`
- copies the built assets to `public_html`
- runs `php artisan route:cache`

The exact SSH user, port, and host alias should be read from local `~/.ssh/config` rather than hard-coded into repo docs.

## Key Docs

- [AGENTS.md](./AGENTS.md)
- [CLAUDE.md](./CLAUDE.md)
- [docs/PAGES_AND_UX.md](./docs/PAGES_AND_UX.md)
- [docs/ADDING_A_CITY.md](./docs/ADDING_A_CITY.md)
- [docs/CHICAGO_INTEGRATION_GUIDE.md](./docs/CHICAGO_INTEGRATION_GUIDE.md)
- [docs/ops/OPERATING_SYSTEM.md](./docs/ops/OPERATING_SYSTEM.md)
- [docs/ops/analytics.md](./docs/ops/analytics.md)
- [docs/ops/seo.md](./docs/ops/seo.md)
- [docs/ops/growth-monetization.md](./docs/ops/growth-monetization.md)
- [docs/ops/social-distribution.md](./docs/ops/social-distribution.md)
- [tools/analytics/README.md](./tools/analytics/README.md)
- [tools/exoskeleton/README.md](./tools/exoskeleton/README.md)
