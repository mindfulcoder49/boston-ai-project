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

test.describe('public surface regressions', () => {
  test('city landing pages use city-specific copy instead of one generic shell', async ({ page }) => {
    const runtime = installConsoleGuards(page);

    const cases = [
      {
        path: '/boston',
        tagline: 'Boston is the fullest PublicDataWatch city: crime, 311, permits, inspections, violations, and crashes in one place.',
        focusLabel: '311',
      },
      {
        path: '/everett',
        tagline: 'Check recent Everett crime around an address fast.',
        focusLabel: 'Fast block check',
      },
      {
        path: '/chicago',
        tagline: 'Check recent Chicago crime around an address before you commit to a block.',
        focusLabel: 'Block-level first pass',
      },
      {
        path: '/san-francisco',
        tagline: 'See recent San Francisco crime around an address without jumping straight into the full map.',
        focusLabel: 'Neighborhood check',
      },
      {
        path: '/new-york',
        tagline: 'See recent New York 311 requests around an address.',
        focusLabel: 'Quality-of-life signals',
      },
      {
        path: '/montgomery-county-md',
        tagline: 'Check recent Montgomery County crime around an address across Bethesda, Rockville, Silver Spring, and nearby communities.',
        focusLabel: 'Cross-community checks',
      },
      {
        path: '/seattle',
        tagline: 'Check recent Seattle crime around an address fast.',
        focusLabel: 'Fast neighborhood scan',
      },
    ];

    for (const cityCase of cases) {
      await page.goto(cityCase.path);
      await expect(page.getByText(cityCase.tagline)).toBeVisible();
      await expect(page.locator('.focus-chip', { hasText: cityCase.focusLabel })).toBeVisible();
    }

    expect(runtime.consoleErrors).toEqual([]);
    expect(runtime.pageErrors).toEqual([]);
  });

  test('city landing guide starts collapsed on mobile and can be toggled', async ({ page }) => {
    const runtime = installConsoleGuards(page);

    await page.setViewportSize({ width: 390, height: 844 });

    await page.route('**/api/map-data', async (route) => {
      await route.fulfill({
        contentType: 'application/json',
        body: JSON.stringify({
          mapConfiguration: {
            dataPointModelConfig: {},
          },
          dataPoints: [],
        }),
      });
    });

    await page.goto('/boston');

    await expect(page.getByRole('button', { name: 'Show guide' })).toBeVisible();
    await expect(page.getByText('How to use this page')).toHaveCount(0);

    await page.getByRole('button', { name: 'Show guide' }).click();
    await expect(page.getByText('How to use this page')).toBeVisible();

    await page.getByRole('button', { name: 'Hide guide' }).click();
    await expect(page.getByText('How to use this page')).toHaveCount(0);

    expect(runtime.consoleErrors).toEqual([]);
    expect(runtime.pageErrors).toEqual([]);
  });

  test('city landing geolocation redirects to the correct city page', async ({ page }) => {
    const runtime = installConsoleGuards(page);
    const requestedCities = [];

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

    await page.route('**/api/map-data', async (route) => {
      const payload = route.request().postDataJSON();
      requestedCities.push(payload.city);

      await route.fulfill({
        contentType: 'application/json',
        body: JSON.stringify({
          mapConfiguration: {
            dataPointModelConfig: {},
          },
          dataPoints: payload.city === 'everett'
            ? [
              {
                data_point_id: 1,
                alcivartech_type: 'Crime Reports',
                alcivartech_model: 'App\\Models\\EverettCrime',
                alcivartech_date: '2026-03-28T12:00:00Z',
              },
            ]
            : [],
        }),
      });
    });

    await page.goto('/boston');
    await page.getByRole('button', { name: 'Use my location' }).click();

    await expect(page).toHaveURL(/\/everett\?/);
    await expect(page.getByText('Check recent Everett crime around an address fast.')).toBeVisible();
    expect(requestedCities).toContain('everett');
    expect(runtime.consoleErrors).toEqual([]);
    expect(runtime.pageErrors).toEqual([]);
  });

  test('city landing can relocate from a map tap after choosing location mode', async ({ page }) => {
    const runtime = installConsoleGuards(page);
    const mapRequests = [];

    await page.route('**/api/reverse-geocode-google-place', async (route) => {
      await route.fulfill({
        contentType: 'application/json',
        body: JSON.stringify({
          lat: 42.3431,
          lng: -71.0986,
          address: '200 Huntington Ave, Boston, MA 02115, USA',
        }),
      });
    });

    await page.route('**/api/map-data', async (route) => {
      const payload = route.request().postDataJSON();
      mapRequests.push(payload);

      await route.fulfill({
        contentType: 'application/json',
        body: JSON.stringify({
          mapConfiguration: {
            dataPointModelConfig: {},
          },
          dataPoints: [],
        }),
      });
    });

    await page.goto('/boston');

    await expect(page.getByTestId('choose-location-button')).toBeVisible();
    await page.getByTestId('choose-location-button').click();
    await expect(page.getByTestId('choose-location-hint')).toContainText('Tap anywhere on the map');

    const initialRequestCount = mapRequests.length;
    const map = page.locator('.leaflet-container');
    const box = await map.boundingBox();

    if (!box) {
      throw new Error('Leaflet map did not render.');
    }

    await page.mouse.click(box.x + (box.width * 0.82), box.y + (box.height * 0.34));

    await expect.poll(() => mapRequests.length).toBeGreaterThan(initialRequestCount);

    const lastRequest = mapRequests.at(-1);
    expect(lastRequest.centralLocation.address).toBe('200 Huntington Ave, Boston, MA 02115, USA');
    expect(lastRequest.centralLocation.latitude).not.toBe(42.3601);
    expect(lastRequest.centralLocation.longitude).not.toBe(-71.0589);

    expect(runtime.consoleErrors).toEqual([]);
    expect(runtime.pageErrors).toEqual([]);
  });

  test('homepage uses customer-facing copy and complete city navigation', async ({ page }) => {
    const runtime = installConsoleGuards(page);

    await page.goto('/');

    await expect(page.getByText('The first action should always be a concrete address lookup, not a tour of maps, metrics, and reports.')).toHaveCount(0);
    await expect(page.getByText('The score only makes sense when it sits next to incidents and trends.')).toHaveCount(0);
    await expect(page.getByText('Advanced workflows belong under one explore layer, not in the first user decision.')).toHaveCount(0);
    await expect(page.getByText('The homepage should make that clear before the user ever hits an unsupported address.')).toHaveCount(0);
    await expect(page.getByRole('heading', { name: /Pick a supported city page from the map previews/i })).toBeVisible();
    await expect(page.getByTestId('home-trust-proof')).toContainText('Built from official public records');
    await expect(page.getByTestId('home-trust-proof')).toContainText('See data freshness and coverage');
    await expect(page.getByTestId('home-featured-coverage')).toContainText('Boston, MA');
    await expect(page.getByTestId('home-featured-coverage')).toContainText('Everett, MA');
    await expect(page.getByTestId('home-featured-coverage')).toContainText('Chicago, IL');
    await expect(page.getByTestId('home-featured-coverage')).toContainText('San Francisco, CA');
    await expect(page.getByTestId('home-featured-coverage')).toContainText('New York, NY');
    await expect(page.getByTestId('home-featured-coverage')).toContainText('Montgomery County, MD');
    await expect(page.getByTestId('home-featured-coverage')).toContainText('Seattle, WA');

    await page.getByRole('navigation').getByRole('button', { name: 'Cities' }).click();
    await expect(page.getByRole('navigation').getByRole('link', { name: 'New York', exact: true })).toBeVisible();
    await expect(page.getByRole('navigation').getByRole('link', { name: 'News', exact: true })).toBeVisible();
    await expect(page.locator('footer').getByRole('link', { name: 'New York', exact: true })).toBeVisible();
    await expect(page.locator('footer').getByRole('link', { name: 'News', exact: true })).toBeVisible();

    expect(runtime.consoleErrors).toEqual([]);
    expect(runtime.pageErrors).toEqual([]);
  });

  test('mobile navigation is scrollable and includes the news link', async ({ page }) => {
    const runtime = installConsoleGuards(page);

    await page.setViewportSize({ width: 390, height: 640 });
    await page.goto('/');

    await page.getByRole('button', { name: 'Open navigation menu' }).click();

    const panel = page.getByTestId('mobile-nav-panel');
    await expect(panel).toBeVisible();
    await expect(panel.getByRole('link', { name: 'News', exact: true })).toBeVisible();

    const metrics = await panel.evaluate((element) => {
      const style = window.getComputedStyle(element);

      return {
        overflowY: style.overflowY,
        clientHeight: element.clientHeight,
        scrollHeight: element.scrollHeight,
      };
    });

    expect(['auto', 'scroll']).toContain(metrics.overflowY);
    expect(metrics.scrollHeight).toBeGreaterThan(metrics.clientHeight);
    expect(runtime.consoleErrors).toEqual([]);
    expect(runtime.pageErrors).toEqual([]);
  });

  test('help surfaces reflect the address-first product and current coverage', async ({ page }) => {
    const runtime = installConsoleGuards(page);

    await page.goto('/help');
    await expect(page.getByText('PublicDataWatch starts with one address, then expands into city landing pages, maps, trends, scores, and recurring reports.')).toBeVisible();
    const quickLinksCard = page.getByRole('heading', { name: 'Quick Links' }).locator('..');
    await expect(quickLinksCard.getByRole('link', { name: 'Crime Preview', exact: true })).toBeVisible();
    await expect(quickLinksCard.getByRole('link', { name: 'Cities & Coverage', exact: true })).toBeVisible();

    await page.goto('/help/users');
    await expect(page.getByRole('heading', { name: 'Crime Preview' })).toBeVisible();
    await expect(page.getByText('New York, NY')).toBeVisible();
    await expect(page.getByText('Free / Trial')).toBeVisible();

    await page.goto('/help/municipalities');
    await expect(page.getByText('8 cities and regions')).toBeVisible();

    await page.goto('/help/investors');
    await expect(page.getByText('8 cities and regions:')).toBeVisible();
    await expect(page.getByText('Address-first acquisition funnel')).toBeVisible();

    expect(runtime.consoleErrors).toEqual([]);
    expect(runtime.pageErrors).toEqual([]);
  });

  test('pricing page guest auth links preserve crime-address context', async ({ page }) => {
    const runtime = installConsoleGuards(page);

    await page.goto('/subscription?source=crime-address&recommended=basic');

    await expect(page.getByRole('link', { name: 'Register Manually', exact: true })).toHaveAttribute(
      'href',
      /\/register\?redirect_to=%2Fsubscription%3Fsource%3Dcrime-address%26recommended%3Dbasic$/,
    );

    const subscribeLinks = page.getByRole('link', { name: 'Or register manually to subscribe' });
    await expect(subscribeLinks.nth(0)).toHaveAttribute(
      'href',
      /\/register\?redirect_to=%2Fsubscribe%2Fbasic%3Fsource%3Dcrime-address$/,
    );
    await expect(subscribeLinks.nth(1)).toHaveAttribute(
      'href',
      /\/register\?redirect_to=%2Fsubscribe%2Fpro%3Fsource%3Dcrime-address$/,
    );

    const googleLinks = page.locator('a').filter({ hasText: 'Login with Google to Subscribe' });
    await expect(googleLinks.nth(0)).toHaveAttribute(
      'href',
      /\/login\/google\/redirect\?redirect_to=%2Fsubscribe%2Fbasic%3Fsource%3Dcrime-address$/,
    );
    await expect(googleLinks.nth(1)).toHaveAttribute(
      'href',
      /\/login\/google\/redirect\?redirect_to=%2Fsubscribe%2Fpro%3Fsource%3Dcrime-address$/,
    );

    expect(runtime.consoleErrors).toEqual([]);
    expect(runtime.pageErrors).toEqual([]);
  });

  test('radial map shows New York 311 detail fields in the selected case panel', async ({ page }) => {
    const runtime = installConsoleGuards(page);

    await page.route('**/api/map-data', async (route) => {
      await route.fulfill({
        contentType: 'application/json',
        body: JSON.stringify({
          dataPoints: [
            {
              data_point_id: 1,
              alcivartech_type: '311 Case',
              alcivartech_date: '2026-03-15T10:29:08Z',
              longitude: -74.006,
              latitude: 40.7128,
              alcivartech_model: 'new_york_311s',
              new_york_311_data: {
                unique_key: 68333421,
                created_date: '2026-03-15 10:29:08',
                agency_name: 'Department of Sanitation',
                complaint_type: 'Vendor Enforcement',
                descriptor: 'Non-Food Vendor',
                status: 'Closed',
                resolution_description: 'N/A',
                borough: 'MANHATTAN',
                incident_address: '222 BROADWAY',
              },
            },
          ],
          centralLocation: {
            latitude: 40.7128,
            longitude: -74.006,
            address: 'New York, NY',
          },
          mapConfiguration: {
            dataPointModelConfig: {
              new_york_311s: {
                dataObjectKey: 'new_york_311_data',
                displayTitle: '311 Case',
                mainIdentifierLabel: 'Service Request Unique Key',
                mainIdentifierField: 'unique_key',
                descriptionLabel: 'Problem / Complaint Type',
                descriptionField: 'complaint_type',
                additionalFields: [
                  { label: 'Agency', key: 'agency_name' },
                  { label: 'Status', key: 'status' },
                  { label: 'Resolution', key: 'resolution_description' },
                  { label: 'Borough', key: 'borough' },
                  { label: 'Incident Address', key: 'incident_address' },
                ],
              },
            },
            modelToSubObjectKeyMap: {
              new_york_311s: 'new_york_311_data',
            },
          },
          city: 'new_york',
        }),
      });
    });

    await page.goto('/map/40.712800/-74.006000');

    const caseDetails = page.locator('.case-details');

    await expect(caseDetails.getByRole('heading', { name: 'Selected Case Details' })).toBeVisible();
    await expect(caseDetails.getByText('Vendor Enforcement').first()).toBeVisible();
    await expect(caseDetails.getByText('Department of Sanitation').first()).toBeVisible();
    await expect(caseDetails.getByText('MANHATTAN').first()).toBeVisible();

    expect(runtime.consoleErrors).toEqual([]);
    expect(runtime.pageErrors).toEqual([]);
  });

  test('combined map banner auth links preserve the current page', async ({ page }) => {
    const runtime = installConsoleGuards(page);

    await page.goto('/combined-map?types=crime_data');

    await expect(page.getByText('Enhance Your Experience!')).toBeVisible();
    await expect(page.getByRole('link', { name: 'Or register manually' })).toHaveAttribute(
      'href',
      /\/register\?redirect_to=%2Fcombined-map%3Ftypes%3Dcrime_data$/,
    );
    await expect(page.locator('a').filter({ hasText: 'Login with Google' }).first()).toHaveAttribute(
      'href',
      /\/login\/google\/redirect\?redirect_to=%2Fcombined-map%3Ftypes%3Dcrime_data$/,
    );

    expect(runtime.consoleErrors).toEqual([]);
    expect(runtime.pageErrors).toEqual([]);
  });

  test('map save-location auth links preserve the current map route', async ({ page }) => {
    const runtime = installConsoleGuards(page);

    await page.goto('/map/42.360100/-71.058900');
    await page.getByRole('button', { name: /Manage Saved Locations/i }).click();

    await expect(page.getByRole('link', { name: 'Or login manually to save' })).toHaveAttribute(
      'href',
      /\/login\?redirect_to=%2Fmap%2F42\.360100%2F-71\.058900$/,
    );
    await expect(page.locator('a').filter({ hasText: 'Login with Google to Save' }).first()).toHaveAttribute(
      'href',
      /\/login\/google\/redirect\?redirect_to=%2Fmap%2F42\.360100%2F-71\.058900$/,
    );

    expect(runtime.consoleErrors).toEqual([]);
    expect(runtime.pageErrors).toEqual([]);
  });
});
