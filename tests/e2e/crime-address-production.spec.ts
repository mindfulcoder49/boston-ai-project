import { expect, test } from '@playwright/test';

const liveOnly = process.env.PLAYWRIGHT_LIVE === '1';

type SupportedCase = {
  label: string;
  address: string;
  latitude: number;
  longitude: number;
  expectedCityKey: string;
  expectRichIncidentContent?: boolean;
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
    label: 'Seattle',
    address: '600 4th Ave, Seattle, WA 98104, USA',
    latitude: 47.60345,
    longitude: -122.32947,
    expectedCityKey: 'seattle',
  },
];

async function waitForPreviewResponse(page) {
  const response = await page.waitForResponse((candidate) => {
    return candidate.url().includes('/api/crime-address/preview') && candidate.request().method() === 'POST';
  });

  expect(response.ok()).toBeTruthy();

  return response.json();
}

test.describe('crime-address live regional coverage', () => {
  test.skip(!liveOnly, 'Set PLAYWRIGHT_LIVE=1 to run live production smoke.');

  for (const scenario of supportedCases) {
    test(`supports ${scenario.label}`, async ({ page }) => {
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

      await expect(page.getByText('Address Report', { exact: true })).toBeVisible();
      await expect(page.getByText('Recent Incidents', { exact: true })).toBeVisible();
      await expect(page.getByRole('heading', { name: 'We do not serve your address yet.' })).toHaveCount(0);
    });
  }

  test('rejects unsupported address cleanly', async ({ page }) => {
    const previewResponsePromise = waitForPreviewResponse(page);

    await page.goto(
      '/crime-address?address=1%20Infinite%20Loop%2C%20Cupertino%2C%20CA%2095014%2C%20USA&lat=37.33182&lng=-122.03118',
    );

    const previewResponse = await previewResponsePromise;

    expect(previewResponse.supported).toBe(false);
    expect(previewResponse.message).toBe('We do not serve your address yet. We will look into adding your area and notify you if we do.');

    await expect(page.getByRole('heading', { name: 'We do not serve your address yet.' })).toBeVisible();
    await expect(page.getByPlaceholder('Email for updates')).toBeVisible();
  });
});
