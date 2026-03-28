import { expect, test } from '@playwright/test';

const liveOnly = process.env.PLAYWRIGHT_LIVE === '1';

type SupportedCase = {
  label: string;
  address: string;
  latitude: number;
  longitude: number;
  expectedCityKey: string;
  expectRichIncidentContent?: boolean;
  expectTrendContext?: boolean;
  expectNeighborhoodScore?: boolean;
};

type UnsupportedCase = {
  label: string;
  address: string;
  latitude: number;
  longitude: number;
};

const supportedCases: SupportedCase[] = [
  {
    label: 'Boston core',
    address: '1 Beacon St, Boston, MA 02108, USA',
    latitude: 42.35798,
    longitude: -71.06173,
    expectedCityKey: 'boston',
  },
  {
    label: 'Cambridge coverage',
    address: '795 Massachusetts Ave, Cambridge, MA 02139, USA',
    latitude: 42.36634,
    longitude: -71.10548,
    expectedCityKey: 'boston',
  },
  {
    label: 'Everett',
    address: '484 Broadway, Everett, MA 02149, USA',
    latitude: 42.40879,
    longitude: -71.05368,
    expectedCityKey: 'everett',
    expectRichIncidentContent: true,
    expectTrendContext: true,
    expectNeighborhoodScore: true,
  },
  {
    label: 'Chicago',
    address: '121 N La Salle St, Chicago, IL 60602, USA',
    latitude: 41.88386,
    longitude: -87.63238,
    expectedCityKey: 'chicago',
  },
  {
    label: 'San Francisco',
    address: '1 Dr Carlton B Goodlett Pl, San Francisco, CA 94102, USA',
    latitude: 37.77927,
    longitude: -122.41927,
    expectedCityKey: 'san_francisco',
  },
  {
    label: 'Montgomery County, MD',
    address: '101 Monroe St, Rockville, MD 20850, USA',
    latitude: 39.08373,
    longitude: -77.14893,
    expectedCityKey: 'montgomery_county_md',
  },
  {
    label: 'Bethesda in Montgomery County, MD',
    address: '4800 Hampden Ln, Bethesda, MD 20814, USA',
    latitude: 38.9814,
    longitude: -77.0962,
    expectedCityKey: 'montgomery_county_md',
  },
  {
    label: 'Silver Spring in Montgomery County, MD',
    address: '1 Veterans Pl, Silver Spring, MD 20910, USA',
    latitude: 38.9979,
    longitude: -77.0261,
    expectedCityKey: 'montgomery_county_md',
  },
  {
    label: 'Seattle',
    address: '600 4th Ave, Seattle, WA 98104, USA',
    latitude: 47.60345,
    longitude: -122.32947,
    expectedCityKey: 'seattle',
  },
];

const unsupportedRegionalCases: UnsupportedCase[] = [
  {
    label: 'Somerville should not borrow Everett coverage',
    address: '93 Highland Ave, Somerville, MA 02143, USA',
    latitude: 42.3874,
    longitude: -71.0995,
  },
  {
    label: 'Brookline should not borrow Boston coverage',
    address: '333 Washington St, Brookline, MA 02445, USA',
    latitude: 42.3318,
    longitude: -71.1212,
  },
  {
    label: 'Chelsea should not borrow Everett coverage',
    address: '500 Broadway, Chelsea, MA 02150, USA',
    latitude: 42.3918,
    longitude: -71.0328,
  },
  {
    label: 'Evanston should not borrow Chicago coverage',
    address: '2100 Ridge Ave, Evanston, IL 60201, USA',
    latitude: 42.0643,
    longitude: -87.6862,
  },
  {
    label: 'Daly City should not borrow San Francisco coverage',
    address: '333 90th St, Daly City, CA 94015, USA',
    latitude: 37.6895,
    longitude: -122.4707,
  },
  {
    label: 'Bellevue should not borrow Seattle coverage',
    address: '450 110th Ave NE, Bellevue, WA 98004, USA',
    latitude: 47.6144,
    longitude: -122.1942,
  },
];

async function waitForPreviewResponse(page) {
  const response = await page.waitForResponse((candidate) => {
    return candidate.url().includes('/api/crime-address/preview') && candidate.request().method() === 'POST';
  });

  expect(response.ok()).toBeTruthy();

  return response.json();
}

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

test.describe('crime-address live regional coverage', () => {
  test.skip(!liveOnly, 'Set PLAYWRIGHT_LIVE=1 to run live production smoke.');

  for (const scenario of supportedCases) {
    test(`supports ${scenario.label}`, async ({ page }) => {
      const runtime = installConsoleGuards(page);
      const previewResponsePromise = waitForPreviewResponse(page);

      await page.goto(
        `/crime-address?address=${encodeURIComponent(scenario.address)}&lat=${scenario.latitude}&lng=${scenario.longitude}`,
      );

      const previewResponse = await previewResponsePromise;

      expect(previewResponse.supported).toBe(true);
      expect(previewResponse.matched_city_key).toBe(scenario.expectedCityKey);

      if (scenario.expectRichIncidentContent) {
        expect(previewResponse.incident_summary.total_incidents).toBeGreaterThan(0);
        expect(previewResponse.incident_summary.top_categories[0].category).not.toBe('Crime incident');
        expect(previewResponse.incident_summary.recent_incidents[0].description).toBeTruthy();
        expect(previewResponse.incident_summary.recent_incidents[0].location_label).toBeTruthy();
      }

      if (scenario.expectTrendContext) {
        expect(previewResponse.trend_context?.summary?.status).toBe('ok');
        await expect(page.getByTestId('crime-address-trend-context')).toBeVisible();
      }

      if (scenario.expectNeighborhoodScore) {
        expect(previewResponse.score_report).toBeTruthy();
        await expect(page.getByTestId('crime-address-neighborhood-score-value')).toHaveText(/^\d+(\.\d+)?$/);
      }

      await expect(page.getByText('Address Report', { exact: true })).toBeVisible();
      await expect(page.getByText('Recent Incidents', { exact: true })).toBeVisible();
      if (previewResponse.incident_summary.total_incidents === 0) {
        await expect(page.getByText('No recent incidents were found within this preview radius.')).toBeVisible();
      }
      await expect(page.getByRole('heading', { name: 'We do not serve your address yet.' })).toHaveCount(0);
      expect(runtime.consoleErrors).toEqual([]);
      expect(runtime.pageErrors).toEqual([]);
    });
  }

  test('supports Everett via browser geolocation', async ({ page }) => {
    const runtime = installConsoleGuards(page);

    await page.context().grantPermissions(['geolocation']);
    await page.context().setGeolocation({
      latitude: 42.418742,
      longitude: -71.04491,
    });

    const previewResponsePromise = waitForPreviewResponse(page);

    await page.goto('/crime-address');
    await page.getByRole('button', { name: 'Use my location' }).click();

    const previewResponse = await previewResponsePromise;

    expect(previewResponse.supported).toBe(true);
    expect(previewResponse.matched_city_key).toBe('everett');
    await expect(page.getByRole('heading', { name: '851 Broadway, Everett, MA 02149, USA' })).toBeVisible();
    await expect(page.getByTestId('crime-address-trend-context')).toBeVisible();
    await expect(page.getByTestId('crime-address-neighborhood-score-value')).toHaveText(/^\d+(\.\d+)?$/);
    expect(runtime.consoleErrors).toEqual([]);
    expect(runtime.pageErrors).toEqual([]);
  });

  for (const scenario of unsupportedRegionalCases) {
    test(`rejects nearby unsupported locality: ${scenario.label}`, async ({ page }) => {
      const runtime = installConsoleGuards(page);
      const previewResponsePromise = waitForPreviewResponse(page);

      await page.goto(
        `/crime-address?address=${encodeURIComponent(scenario.address)}&lat=${scenario.latitude}&lng=${scenario.longitude}`,
      );

      const previewResponse = await previewResponsePromise;

      expect(previewResponse.supported).toBe(false);
      expect(previewResponse.message).toBe('We do not serve your address yet. We will look into adding your area and notify you if we do.');

      await expect(page.getByRole('heading', { name: 'We do not serve your address yet.' })).toBeVisible();
      await expect(page.getByPlaceholder('Email for updates')).toBeVisible();
      expect(runtime.consoleErrors).toEqual([]);
      expect(runtime.pageErrors).toEqual([]);
    });
  }

  test('rejects unsupported address cleanly', async ({ page }) => {
    const runtime = installConsoleGuards(page);
    const previewResponsePromise = waitForPreviewResponse(page);

    await page.goto(
      '/crime-address?address=1%20Infinite%20Loop%2C%20Cupertino%2C%20CA%2095014%2C%20USA&lat=37.33182&lng=-122.03118',
    );

    const previewResponse = await previewResponsePromise;

    expect(previewResponse.supported).toBe(false);
    expect(previewResponse.message).toBe('We do not serve your address yet. We will look into adding your area and notify you if we do.');

    await expect(page.getByRole('heading', { name: 'We do not serve your address yet.' })).toBeVisible();
    await expect(page.getByPlaceholder('Email for updates')).toBeVisible();
    expect(runtime.consoleErrors).toEqual([]);
    expect(runtime.pageErrors).toEqual([]);
  });
});
