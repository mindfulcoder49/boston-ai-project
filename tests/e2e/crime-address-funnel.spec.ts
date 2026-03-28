import { expect, test } from '@playwright/test';

async function stubSupportedPreview(page) {
  await page.route('**/api/crime-address/preview', async (route) => {
    await route.fulfill({
      contentType: 'application/json',
      body: JSON.stringify({
        supported: true,
        address: '1 Beacon St, Boston, MA 02108, USA',
        matched_city_key: 'boston',
        matched_city_name: 'Boston',
        latitude: 42.3601,
        longitude: -71.0589,
        radius: 0.25,
        map_data: {
          center: {
            latitude: 42.3601,
            longitude: -71.0589,
          },
          incidents: [
            {
              id: 1,
              latitude: 42.3603,
              longitude: -71.0586,
              date: '2026-03-26',
              category: 'Larceny',
              description: 'Wallet taken from parked car',
              location_label: 'Beacon St',
            },
          ],
          incident_count: 1,
        },
        incident_summary: {
          total_incidents: 1,
          top_categories: [
            { category: 'Larceny', count: 1 },
          ],
          recent_incidents: [
            {
              date: '2026-03-26',
              category: 'Larceny',
              description: 'Wallet taken from parked car',
              location_label: 'Beacon St',
            },
          ],
        },
        score_report: {
          job_id: 'job-score-1',
          artifact_name: 'stage6_historical_score_laravel-hist-score-crime-data-boston.json',
          resolution: 8,
        },
        trend_context: {
          summary: {
            status: 'ok',
            total_findings: 8,
            anomaly_count: 2,
            trend_count: 6,
            affected_h3_count: 4,
            top_categories: ['Larceny', 'Assault'],
          },
        },
        preview_report: [
          {
            title: 'What happened nearby',
            body: 'Found 1 crime incident within 0.25 miles of 1 Beacon St, Boston, MA 02108, USA.',
          },
          {
            title: 'Most common incident categories',
            body: 'Larceny (1)',
          },
          {
            title: 'Trend context',
            body: 'Boston has 8 significant recent findings across 4 hexagons.',
          },
          {
            title: 'Neighborhood score context',
            body: 'A location-specific neighborhood score is available for this address and will load with the preview.',
          },
        ],
      }),
    });
  });

  await page.route('**/api/scoring-reports/score-for-location', async (route) => {
    await route.fulfill({
      contentType: 'application/json',
      body: JSON.stringify({
        score_details: {
          score: 82.4,
          score_composition: [
            {
              secondary_group: 'Violent Crime',
              weighted_score: 42.1,
            },
            {
              secondary_group: 'Property Crime',
              weighted_score: 25.7,
            },
          ],
        },
        analysis_details: [
          {
            secondary_group: 'Violent Crime',
          },
        ],
      }),
    });
  });
}

test.describe('crime-address funnel', () => {
  test('shows unsupported coverage request flow', async ({ page }) => {
    await page.route('**/api/crime-address/preview', async (route) => {
      await route.fulfill({
        contentType: 'application/json',
        body: JSON.stringify({
          supported: false,
          message: 'We do not serve your address yet. We will look into adding your area and notify you if we do.',
          serviceability: {
            normalized_address: '200 N Spring St, Los Angeles, CA 90012, USA',
            latitude: 34.0522,
            longitude: -118.2437,
          },
        }),
      });
    });

    await page.route('**/api/crime-address/coverage-request', async (route) => {
      await route.fulfill({
        status: 201,
        contentType: 'application/json',
        body: JSON.stringify({
          message: 'We will look into adding your area and notify you if we do.',
          coverage_request_id: 1,
          created: true,
        }),
      });
    });

    await page.goto('/crime-address?address=200%20N%20Spring%20St%2C%20Los%20Angeles%2C%20CA%2090012%2C%20USA&lat=34.0522&lng=-118.2437');

    await expect(page.getByRole('heading', { name: 'We do not serve your address yet.' })).toBeVisible();
    await page.getByPlaceholder('Email for updates').fill('alerts@example.com');
    await page.getByRole('button', { name: 'Notify me' }).click();
    await expect(page.locator('p.text-emerald-700')).toHaveText('We will look into adding your area and notify you if we do.');
  });

  test('shows supported preview flow', async ({ page }) => {
    await stubSupportedPreview(page);

    await page.goto('/crime-address?address=1%20Beacon%20St%2C%20Boston%2C%20MA%2002108%2C%20USA&lat=42.3601&lng=-71.0589');

    await expect(page.getByRole('heading', { name: '1 Beacon St, Boston, MA 02108, USA' })).toBeVisible();
    await expect(page.getByText('What happened nearby')).toBeVisible();
    await expect(page.getByRole('heading', { name: 'Neighborhood score', exact: true })).toBeVisible();
    await expect(page.getByRole('link', { name: 'Create free account' })).toBeVisible();
    await expect(page.getByRole('link', { name: 'Log in' })).toBeVisible();
  });

  test('registers from preview and returns to the same address before starting the trial', async ({ page }) => {
    await stubSupportedPreview(page);
    await page.route('**/api/crime-address/trial/start', async (route) => {
      await route.fulfill({
        contentType: 'application/json',
        body: JSON.stringify({
          active: true,
          message: 'Your 7-day free trial has started.',
          trial_ends_at: '2026-04-03',
          location: {
            id: 99,
            address: '1 Beacon St, Boston, MA 02108, USA',
            latitude: 42.3601,
            longitude: -71.0589,
          },
        }),
      });
    });

    await page.goto('/crime-address?address=1%20Beacon%20St%2C%20Boston%2C%20MA%2002108%2C%20USA&lat=42.3601&lng=-71.0589');
    await page.getByRole('link', { name: 'Create free account' }).click();

    await expect(page).toHaveURL(/\/register\?redirect_to=/);

    const email = `crime-address-e2e-${Date.now()}@example.com`;
    await page.getByLabel('Name').fill('Crime Address E2E');
    await page.getByLabel('Email').fill(email);
    await page.getByLabel('Password', { exact: true }).fill('password');
    await page.getByLabel('Confirm Password').fill('password');
    await page.getByRole('button', { name: 'Register', exact: true }).click();

    await expect(page).toHaveURL(/\/crime-address\?address=.*lat=42\.3601&lng=-71\.0589/);
    await expect(page.getByRole('button', { name: 'Start 7-day free trial' })).toBeVisible();

    await page.getByRole('button', { name: 'Start 7-day free trial' }).click();
    await expect(page.getByText('Your 7-day free trial has started.')).toBeVisible();
  });
});
