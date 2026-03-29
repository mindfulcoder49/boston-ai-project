import { expect, test } from '@playwright/test';

function installConsoleGuards(page) {
  const consoleErrors = [];
  const pageErrors = [];

  page.on('console', (message) => {
    if (message.type() === 'error') {
      consoleErrors.push(message.text());
    }
  });

  page.on('pageerror', (error) => {
    pageErrors.push(error.message);
  });

  return { consoleErrors, pageErrors };
}

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
        preview_report: [
          {
            title: 'What happened nearby',
            body: 'Found 1 crime incident within 0.25 miles of 1 Beacon St, Boston, MA 02108, USA in the current preview window.',
          },
          {
            title: 'What stands out nearby',
            body: 'The most common nearby incident categories were Larceny (1).',
          },
          {
            title: 'What loads next',
            body: 'We are also checking citywide trend context and the local area score so you can compare this address with nearby parts of the city.',
          },
        ],
      }),
    });
  });

  await page.route('**/api/crime-address/context', async (route) => {
    await route.fulfill({
      contentType: 'application/json',
      body: JSON.stringify({
        supported: true,
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
            title: 'What city trends suggest',
            body: 'Boston is showing a few unusual crime patterns right now, especially around Larceny, Assault.',
          },
          {
            title: 'Area score context',
            body: 'An area score is available for this address. We compare it with the rest of the city and nearby areas so the number is easier to interpret.',
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
        score_context: {
          score: 82.4,
          percentile: 82,
          band: {
            label: 'Higher relative concern',
            description: 'This address-area scores above most scored areas in the same city or region.',
          },
          distribution: {
            count: 18,
            median: 63.2,
          },
          nearby_peers: {
            available: true,
            count: 6,
            median: 78.1,
            current_vs_median: 4.3,
          },
          top_drivers: [
            {
              label: 'Violent Crime',
              weighted_score: 42.1,
              share_percent: 53.2,
            },
            {
              label: 'Property Crime',
              weighted_score: 25.7,
              share_percent: 32.5,
            },
          ],
          methodology: {
            source: 'stage6_artifact',
            label: 'Historical neighborhood score',
            analysis_period_weeks: 52,
            resolution: 8,
          },
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
    const runtime = installConsoleGuards(page);

    await page.route('**/api/geocode-google-place', async (route) => {
      await route.fulfill({
        contentType: 'application/json',
        body: JSON.stringify({
          lat: 34.0522,
          lng: -118.2437,
          address: '200 N Spring St, Los Angeles, CA 90012, USA',
        }),
      });
    });

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

    await page.goto('/crime-address');

    await page.getByPlaceholder('Enter address (Google Search)...').fill('200 N Spring St, Los Angeles, CA 90012');
    await page.getByRole('button', { name: 'Search address' }).click();

    await expect(page.getByRole('heading', { name: 'We do not serve your address yet.' })).toBeVisible();
    await expect(page.getByText('We will only use your email for coverage updates about this area.')).toBeVisible();
    await page.getByPlaceholder('Email for updates').fill('alerts@example.com');
    await page.getByRole('button', { name: 'Notify me if coverage expands' }).click();
    await expect(page.locator('p.text-emerald-700')).toHaveText('We will look into adding your area and notify you if we do.');

    const searchInput = page.locator('input').first();
    await searchInput.fill('851 broadway Everett MA');
    await expect(page.getByRole('heading', { name: 'We do not serve your address yet.' })).toHaveCount(0);
    await expect(page.getByText('Enter an address to generate the crime preview')).toBeVisible();

    expect(runtime.consoleErrors).toEqual([]);
    expect(runtime.pageErrors).toEqual([]);
  });

  test('shows supported preview flow', async ({ page }) => {
    const runtime = installConsoleGuards(page);

    await stubSupportedPreview(page);

    await page.goto('/crime-address?address=1%20Beacon%20St%2C%20Boston%2C%20MA%2002108%2C%20USA&lat=42.3601&lng=-71.0589');

    await expect(page.getByRole('heading', { name: '1 Beacon St, Boston, MA 02108, USA' })).toBeVisible();
    await expect(page.getByText('What happened nearby')).toBeVisible();
    await expect(page.getByTestId('crime-address-trend-context')).toBeVisible();
    await expect(page.getByTestId('crime-address-neighborhood-score-value')).toHaveText('82.4');
    await expect(page.getByTestId('crime-address-score-context')).toContainText('Higher than most of Boston');
    await expect(page.getByTestId('crime-address-score-context')).toContainText('A little higher than nearby areas');
    await expect(page.getByText('What the area score suggests')).toBeVisible();
    await expect(page.getByText(/percentile/i)).toHaveCount(0);
    await expect(page.getByText(/\bH3\b/)).toHaveCount(0);
    await expect(page.getByRole('complementary').getByRole('link', { name: 'Create free account' })).toBeVisible();
    await expect(page.getByRole('complementary').getByRole('link', { name: 'Log in' })).toBeVisible();
    expect(runtime.consoleErrors).toEqual([]);
    expect(runtime.pageErrors).toEqual([]);
  });

  test('renders incident details before deferred context finishes loading', async ({ page }) => {
    const runtime = installConsoleGuards(page);

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
          preview_report: [
            {
              title: 'What happened nearby',
              body: 'Found 1 crime incident within 0.25 miles of 1 Beacon St, Boston, MA 02108, USA in the current preview window.',
            },
          ],
        }),
      });
    });

    await page.route('**/api/crime-address/context', async (route) => {
      await page.waitForTimeout(1500);
      await route.fulfill({
        contentType: 'application/json',
        body: JSON.stringify({
          supported: true,
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
          preview_report: [],
        }),
      });
    });

    await page.route('**/api/scoring-reports/score-for-location', async (route) => {
      await page.waitForTimeout(1500);
      await route.fulfill({
        contentType: 'application/json',
        body: JSON.stringify({
          score_details: {
            score: 82.4,
            score_composition: [],
          },
          score_context: {
            score: 82.4,
            percentile: 82,
            band: {
              label: 'Higher relative concern',
              description: 'This address-area scores above most scored areas in the same city or region.',
            },
            distribution: {
              count: 18,
              median: 63.2,
            },
            nearby_peers: {
              available: true,
              count: 6,
              median: 78.1,
              current_vs_median: 4.3,
            },
            top_drivers: [],
            methodology: {
              source: 'stage6_artifact',
              label: 'Historical neighborhood score',
              analysis_period_weeks: 52,
              resolution: 8,
            },
          },
          analysis_details: [],
        }),
      });
    });

    await page.goto('/crime-address?address=1%20Beacon%20St%2C%20Boston%2C%20MA%2002108%2C%20USA&lat=42.3601&lng=-71.0589');

    await expect(page.getByRole('heading', { name: '1 Beacon St, Boston, MA 02108, USA' })).toBeVisible();
    await expect(page.getByText('Wallet taken from parked car')).toBeVisible();
    await expect(page.getByTestId('crime-address-neighborhood-score-unavailable')).toHaveText('Checking how this area compares…');
    await expect(page.getByTestId('crime-address-neighborhood-score-value')).toHaveText('82.4');
    expect(runtime.consoleErrors).toEqual([]);
    expect(runtime.pageErrors).toEqual([]);
  });

  test('uses browser geolocation to load a supported preview', async ({ page }) => {
    const runtime = installConsoleGuards(page);

    await page.context().grantPermissions(['geolocation']);
    await page.context().setGeolocation({
      latitude: 42.418742,
      longitude: -71.04491,
    });

    await page.route('**/api/reverse-geocode-google-place', async (route) => {
      await route.fulfill({
        contentType: 'application/json',
        body: JSON.stringify({
          lat: 42.418742,
          lng: -71.04491,
          address: '851 Broadway, Everett, MA 02149, USA',
        }),
      });
    });

    await page.route('**/api/crime-address/preview', async (route) => {
      await route.fulfill({
        contentType: 'application/json',
        body: JSON.stringify({
          supported: true,
          address: '851 Broadway, Everett, MA 02149, USA',
          matched_city_key: 'everett',
          matched_city_name: 'Everett',
          latitude: 42.418742,
          longitude: -71.04491,
          radius: 0.25,
          map_data: {
            center: {
              latitude: 42.418742,
              longitude: -71.04491,
            },
            incidents: [
              {
                id: 1,
                latitude: 42.4170608,
                longitude: -71.0463439,
                date: '2026-03-26',
                category: 'Medical / Mental Health',
                description: '2 WAY 911-60 YR OLD MALE DIFF. BREATHING',
                location_label: '801 BROADWAY ST',
              },
            ],
            incident_count: 1,
          },
          incident_summary: {
            total_incidents: 1,
            top_categories: [
              { category: 'Medical / Mental Health', count: 1 },
            ],
            recent_incidents: [
              {
                date: '2026-03-26',
                category: 'Medical / Mental Health',
                description: '2 WAY 911-60 YR OLD MALE DIFF. BREATHING',
                location_label: '801 BROADWAY ST',
              },
            ],
          },
          preview_report: [
            {
              title: 'What happened nearby',
              body: 'Found 1 crime incident within 0.25 miles of 851 Broadway, Everett, MA 02149, USA in the current preview window.',
            },
          ],
        }),
      });
    });

    await page.route('**/api/crime-address/context', async (route) => {
      await route.fulfill({
        contentType: 'application/json',
        body: JSON.stringify({
          supported: true,
          score_report: null,
          trend_context: null,
          preview_report: [
            {
              title: 'Area score context',
              body: 'City trend and area score context are not available for this location yet. The nearby incident view is still shown below.',
            },
          ],
        }),
      });
    });

    await page.goto('/crime-address');
    await page.getByRole('button', { name: 'Use my location' }).click();

    await expect(page.getByRole('heading', { name: '851 Broadway, Everett, MA 02149, USA' })).toBeVisible();
    await expect(page.getByText('Everett • 1 recent incidents within 0.25 miles')).toBeVisible();
    await expect(page.getByTestId('crime-address-neighborhood-score-unavailable')).toBeVisible();
    expect(runtime.consoleErrors).toEqual([]);
    expect(runtime.pageErrors).toEqual([]);
  });

  test('shows a clean zero-incident state for supported addresses', async ({ page }) => {
    const runtime = installConsoleGuards(page);

    await page.route('**/api/crime-address/preview', async (route) => {
      await route.fulfill({
        contentType: 'application/json',
        body: JSON.stringify({
          supported: true,
          address: '121 N La Salle St, Chicago, IL 60602, USA',
          matched_city_key: 'chicago',
          matched_city_name: 'Chicago',
          latitude: 41.88386,
          longitude: -87.63238,
          radius: 0.25,
          map_data: {
            center: {
              latitude: 41.88386,
              longitude: -87.63238,
            },
            incidents: [],
            incident_count: 0,
          },
          incident_summary: {
            total_incidents: 0,
            top_categories: [],
            recent_incidents: [],
          },
          preview_report: [
            {
              title: 'What happened nearby',
              body: 'No recent crime incidents were found within 0.25 miles of 121 N La Salle St, Chicago, IL 60602, USA in the current preview window.',
            },
          ],
        }),
      });
    });

    await page.route('**/api/crime-address/context', async (route) => {
      await route.fulfill({
        contentType: 'application/json',
        body: JSON.stringify({
          supported: true,
          score_report: {
            job_id: 'job-score-2',
            artifact_name: 'stage6_historical_score_laravel-hist-score-chicago-crime.json',
            resolution: 8,
          },
          trend_context: null,
          preview_report: [
            {
              title: 'Area score context',
              body: 'An area score is available for this address. We compare it with the rest of the city and nearby areas so the number is easier to interpret.',
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
            score: 71.2,
            score_composition: [],
          },
          score_context: {
            score: 71.2,
            percentile: 48,
            band: {
              label: 'Typical relative concern',
              description: 'This address-area lands near the middle of the local score distribution.',
            },
            distribution: {
              count: 22,
              median: 70.4,
            },
            nearby_peers: {
              available: false,
              count: 0,
              median: null,
              current_vs_median: null,
            },
            top_drivers: [],
            methodology: {
              source: 'stage6_artifact',
              label: 'Historical neighborhood score',
              analysis_period_weeks: 52,
              resolution: 8,
            },
          },
          analysis_details: [],
        }),
      });
    });

    await page.goto('/crime-address?address=121%20N%20La%20Salle%20St%2C%20Chicago%2C%20IL%2060602%2C%20USA&lat=41.88386&lng=-87.63238');

    await expect(page.getByText('No recent incidents were found within this preview radius.')).toBeVisible();
    await expect(page.getByText('No incident categories stand out because no recent incidents were found in this preview radius.')).toBeVisible();
    await expect(page.getByText('No recent crime incidents were found within 0.25 miles of 121 N La Salle St, Chicago, IL 60602, USA in the current preview window.')).toBeVisible();
    expect(runtime.consoleErrors).toEqual([]);
    expect(runtime.pageErrors).toEqual([]);
  });

  test('homepage prioritizes the address funnel and grouped navigation', async ({ page }) => {
    const runtime = installConsoleGuards(page);

    await page.goto('/');

    await expect(page.getByRole('heading', { name: /Know what crime is happening around your address/i })).toBeVisible();
    await expect(page.getByRole('navigation').getByRole('link', { name: 'Crime Preview', exact: true })).toBeVisible();
    await expect(page.getByRole('navigation').getByRole('button', { name: 'Cities' })).toBeVisible();
    await expect(page.getByRole('navigation').getByRole('button', { name: 'Explore' })).toBeVisible();
    await expect(page.getByText('Supported cities and regions')).toBeVisible();
    await expect(page.getByText('Explore deeper when one address is not enough.')).toBeVisible();
    await expect(page.getByText('Cities & Regions')).toBeVisible();
    await expect(page.getByText('Crime around your address first.')).toBeVisible();

    expect(runtime.consoleErrors).toEqual([]);
    expect(runtime.pageErrors).toEqual([]);
  });

  test('registers from preview and returns to the same address before starting the trial', async ({ page }) => {
    const runtime = installConsoleGuards(page);

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
    expect(runtime.consoleErrors).toEqual([]);
    expect(runtime.pageErrors).toEqual([]);
  });
});
