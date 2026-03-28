export function buildPublicNavigation(routeFn, isAuthenticated = false) {
  const homeHref = routeFn('home');
  const citiesOverviewHref = `${homeHref}#cities`;
  const exploreOverviewHref = `${homeHref}#explore-tools`;

  return {
    primary: [
      {
        label: 'Home',
        href: homeHref,
        routeNames: ['home'],
      },
      {
        label: 'Crime Preview',
        href: routeFn('crime-address.index'),
        routeNames: ['crime-address.index'],
      },
      {
        label: 'Cities',
        kind: 'dropdown',
        routeNames: [
          'city.landing.boston',
          'city.landing.everett',
          'city.landing.chicago',
          'city.landing.san_francisco',
          'city.landing.seattle',
          'city.landing.montgomery_county_md',
        ],
        items: [
          { label: 'Coverage overview', href: citiesOverviewHref },
          { label: 'Boston', href: routeFn('city.landing.boston') },
          { label: 'Everett', href: routeFn('city.landing.everett') },
          { label: 'Chicago', href: routeFn('city.landing.chicago') },
          { label: 'San Francisco', href: routeFn('city.landing.san_francisco') },
          { label: 'Seattle', href: routeFn('city.landing.seattle') },
          { label: 'Montgomery County, MD', href: routeFn('city.landing.montgomery_county_md') },
        ],
      },
      {
        label: 'Explore',
        kind: 'dropdown',
        routeNames: [
          'map.index',
          'data-map.combined',
          'trends.index',
          'scoring-reports.index',
          'scoring-reports.show',
          'yearly-comparisons.index',
          'reports.yearly-comparison.show',
          'data.metrics',
        ],
        items: [
          { label: 'Explore overview', href: exploreOverviewHref },
          { label: 'Radial map', href: routeFn('map.index') },
          { label: 'Full data map', href: routeFn('data-map.combined') },
          { label: 'Trends', href: routeFn('trends.index') },
          { label: 'Neighborhood scores', href: routeFn('scoring-reports.index') },
          { label: 'Yearly comparisons', href: routeFn('yearly-comparisons.index') },
          { label: 'Data metrics', href: routeFn('data.metrics') },
        ],
      },
      {
        label: 'Pricing',
        href: routeFn('subscription.index'),
        routeNames: ['subscription.index', 'subscribe.checkout'],
      },
      {
        label: 'Help',
        href: routeFn('help.index'),
        routeNames: [
          'help.index',
          'help.contact',
          'help.users',
          'help.researchers',
          'help.investors',
          'help.municipalities',
        ],
      },
    ],
    footerSections: [
      {
        title: 'Product',
        links: [
          { label: 'Crime Preview', href: routeFn('crime-address.index') },
          { label: 'Pricing', href: routeFn('subscription.index') },
          { label: 'Full Data Map', href: routeFn('data-map.combined') },
          { label: 'Trends', href: routeFn('trends.index') },
          { label: 'Neighborhood Scores', href: routeFn('scoring-reports.index') },
        ],
      },
      {
        title: 'Cities & Regions',
        links: [
          { label: 'Coverage overview', href: citiesOverviewHref },
          { label: 'Boston', href: routeFn('city.landing.boston') },
          { label: 'Everett', href: routeFn('city.landing.everett') },
          { label: 'Chicago', href: routeFn('city.landing.chicago') },
          { label: 'San Francisco', href: routeFn('city.landing.san_francisco') },
          { label: 'Seattle', href: routeFn('city.landing.seattle') },
          { label: 'Montgomery County, MD', href: routeFn('city.landing.montgomery_county_md') },
        ],
      },
      {
        title: 'Resources',
        links: [
          { label: 'Help Center', href: routeFn('help.index') },
          { label: 'For Users', href: routeFn('help.users') },
          { label: 'For Researchers', href: routeFn('help.researchers') },
          { label: 'About Us', href: routeFn('about.us') },
        ],
      },
      {
        title: 'Legal',
        links: [
          { label: 'Privacy Policy', href: routeFn('privacy.policy') },
          { label: 'Terms of Use', href: routeFn('terms.of.use') },
        ],
      },
      ...(isAuthenticated
        ? [
            {
              title: 'Account',
              links: [
                { label: 'Saved Locations', href: routeFn('locations.index') },
                { label: 'Saved Maps', href: routeFn('saved-maps.index') },
                { label: 'Report History', href: routeFn('reports.index') },
                { label: 'Billing', href: routeFn('billing') },
                { label: 'Profile', href: routeFn('profile.edit') },
              ],
            },
          ]
        : []),
    ],
  };
}
