import test from 'node:test';
import assert from 'node:assert/strict';

import { buildPublicNavigation } from '../../resources/js/Utils/publicNavigation.js';

const routeMap = {
  home: '/',
  'crime-address.index': '/crime-address',
  'city.landing.boston': '/boston',
  'city.landing.everett': '/everett',
  'city.landing.chicago': '/chicago',
  'city.landing.san_francisco': '/san-francisco',
  'city.landing.seattle': '/seattle',
  'city.landing.new_york': '/new-york',
  'city.landing.montgomery_county_md': '/montgomery-county-md',
  'map.index': '/map',
  'data-map.combined': '/combined-map',
  'trends.index': '/trends',
  'scoring-reports.index': '/scoring-reports',
  'yearly-comparisons.index': '/yearly-comparisons',
  'data.metrics': '/data-metrics',
  'subscription.index': '/subscription',
  'help.index': '/help',
  'help.contact': '/help/contact',
  'help.users': '/help/users',
  'help.researchers': '/help/researchers',
  'help.investors': '/help/investors',
  'help.municipalities': '/help/municipalities',
  'about.us': '/about-us',
  'privacy.policy': '/privacy-policy',
  'terms.of.use': '/terms-of-use',
  'locations.index': '/locations',
  'saved-maps.index': '/saved-maps',
  'reports.index': '/reports',
  billing: '/billing-portal',
  'profile.edit': '/profile',
};

function fakeRoute(name) {
  const value = routeMap[name];
  if (!value) {
    throw new Error(`Unexpected route lookup: ${name}`);
  }

  return value;
}

test('cities navigation includes every public landing page link', () => {
  const navigation = buildPublicNavigation(fakeRoute, false);
  const citiesItem = navigation.primary.find((item) => item.label === 'Cities');

  assert.ok(citiesItem, 'expected cities dropdown to exist');
  assert.deepEqual(
    citiesItem.items.map((item) => item.label),
    [
      'Coverage overview',
      'Boston',
      'Everett',
      'Chicago',
      'San Francisco',
      'Seattle',
      'New York',
      'Montgomery County, MD',
    ],
  );
  assert.ok(citiesItem.routeNames.includes('city.landing.new_york'));
});

test('footer city list stays in sync with the cities navigation inventory', () => {
  const navigation = buildPublicNavigation(fakeRoute, false);
  const citiesItem = navigation.primary.find((item) => item.label === 'Cities');
  const footerSection = navigation.footerSections.find((section) => section.title === 'Cities & Regions');

  assert.ok(citiesItem, 'expected cities dropdown to exist');
  assert.ok(footerSection, 'expected footer cities section to exist');

  assert.deepEqual(
    footerSection.links.map((item) => item.label),
    citiesItem.items.map((item) => item.label),
  );
});
