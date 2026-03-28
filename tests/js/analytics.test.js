import test from 'node:test';
import assert from 'node:assert/strict';

import {
  normalizePageLocationForAnalytics,
  normalizePagePathForAnalytics,
} from '../../resources/js/Utils/analytics.js';

test('normalizePagePathForAnalytics removes query strings and fragments', () => {
  assert.equal(
    normalizePagePathForAnalytics('/crime-address?address=851+Broadway&lat=42.4#preview'),
    '/crime-address',
  );
  assert.equal(
    normalizePagePathForAnalytics('https://publicdatawatch.com/subscription?source=crime-address'),
    '/subscription',
  );
});

test('normalizePageLocationForAnalytics keeps attribution params and drops address query noise', () => {
  assert.equal(
    normalizePageLocationForAnalytics(
      'https://publicdatawatch.com/crime-address?address=851+Broadway&lat=42.4&utm_source=linkedin&utm_campaign=launch'
    ),
    'https://publicdatawatch.com/crime-address?utm_source=linkedin&utm_campaign=launch',
  );
  assert.equal(
    normalizePageLocationForAnalytics('/crime-address?lng=-71.0&fbclid=abc123'),
    'https://publicdatawatch.com/crime-address?fbclid=abc123',
  );
});
