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

  test('homepage uses customer-facing copy and complete city navigation', async ({ page }) => {
    const runtime = installConsoleGuards(page);

    await page.goto('/');

    await expect(page.getByText('The first action should always be a concrete address lookup, not a tour of maps, metrics, and reports.')).toHaveCount(0);
    await expect(page.getByText('The score only makes sense when it sits next to incidents and trends.')).toHaveCount(0);
    await expect(page.getByText('Advanced workflows belong under one explore layer, not in the first user decision.')).toHaveCount(0);
    await expect(page.getByText('The homepage should make that clear before the user ever hits an unsupported address.')).toHaveCount(0);

    await page.getByRole('navigation').getByRole('button', { name: 'Cities' }).click();
    await expect(page.getByRole('navigation').getByRole('link', { name: 'New York', exact: true })).toBeVisible();
    await expect(page.locator('footer').getByRole('link', { name: 'New York', exact: true })).toBeVisible();

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
