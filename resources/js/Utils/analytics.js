import { event } from 'vue-gtag';

const TRACKED_HOSTNAMES = new Set([
  'publicdatawatch.com',
  'www.publicdatawatch.com',
  'bostonscope.com',
  'www.bostonscope.com',
]);

const EXCLUDED_PATH_PREFIXES = [
  '/admin',
  '/reports/statistical-analysis',
  '/scoring-reports',
  '/csvreports',
];

const PAGE_LOCATION_QUERY_PARAM_ALLOWLIST = new Set([
  'utm_source',
  'utm_medium',
  'utm_campaign',
  'utm_term',
  'utm_content',
  'utm_id',
  'gclid',
  'fbclid',
  'msclkid',
]);

function inferDeviceType() {
  if (typeof window === 'undefined') {
    return 'unknown';
  }

  if (window.innerWidth < 768) {
    return 'mobile';
  }

  if (window.innerWidth < 1024) {
    return 'tablet';
  }

  return 'desktop';
}

function normalizeCity(city) {
  if (!city) {
    return undefined;
  }

  return String(city).trim().toLowerCase().replace(/\s+/g, '_').replace(/-/g, '_');
}

export function normalizePagePathForAnalytics(path = '') {
  if (!path) {
    return '';
  }

  try {
    const base = typeof window !== 'undefined' ? window.location.origin : 'https://publicdatawatch.com';
    return new URL(path, base).pathname || '';
  } catch (error) {
    return String(path).split('?')[0].split('#')[0] || '';
  }
}

export function normalizePageLocationForAnalytics(location = '') {
  if (!location) {
    return '';
  }

  try {
    const base = typeof window !== 'undefined' ? window.location.origin : 'https://publicdatawatch.com';
    const url = new URL(location, base);
    const filteredParams = new URLSearchParams();

    for (const [key, value] of url.searchParams.entries()) {
      if (PAGE_LOCATION_QUERY_PARAM_ALLOWLIST.has(key)) {
        filteredParams.append(key, value);
      }
    }

    url.search = filteredParams.toString() ? `?${filteredParams.toString()}` : '';
    url.hash = '';

    return url.toString();
  } catch (error) {
    return String(location).split('#')[0];
  }
}

function inferPageType(path = '') {
  if (path === '/') {
    return 'home';
  }

  if (path.startsWith('/map')) {
    return 'explore_map';
  }

  if (path.startsWith('/crime-address')) {
    return 'crime_address';
  }

  if (path.startsWith('/subscription')) {
    return 'pricing';
  }

  if (path.startsWith('/login')) {
    return 'login';
  }

  if (path.startsWith('/register')) {
    return 'register';
  }

  if (path.startsWith('/admin')) {
    return 'admin';
  }

  return 'other';
}

function normalizeValue(value) {
  if (value === undefined || value === null || value === '') {
    return undefined;
  }

  if (typeof value === 'boolean') {
    return value ? 'true' : 'false';
  }

  if (Array.isArray(value)) {
    return value.length ? value.join(',') : undefined;
  }

  return value;
}

function sanitizeParams(params) {
  const filtered = Object.entries(params)
    .map(([key, value]) => [key, normalizeValue(value)])
    .filter(([, value]) => value !== undefined);

  return Object.fromEntries(filtered);
}

export function isAnalyticsEnabledForCurrentRoute() {
  if (typeof window === 'undefined') {
    return false;
  }

  const hostname = window.location.hostname.toLowerCase();
  const path = window.location.pathname || '';

  if (!TRACKED_HOSTNAMES.has(hostname)) {
    return false;
  }

  return !EXCLUDED_PATH_PREFIXES.some((prefix) => path.startsWith(prefix));
}

export function buildCommonEventParams({
  city,
  pageType,
  languageCode,
  isAuthenticated,
  params = {},
} = {}) {
  const pagePath = normalizePagePathForAnalytics(
    params.page_path || (typeof window !== 'undefined' ? window.location.pathname : '')
  );

  return sanitizeParams({
    page_type: pageType || inferPageType(pagePath),
    city: normalizeCity(city),
    language_code: languageCode,
    device_type: inferDeviceType(),
    is_authenticated: isAuthenticated,
    ...params,
    page_path: pagePath,
    page_location: params.page_location
      ? normalizePageLocationForAnalytics(params.page_location)
      : undefined,
  });
}

export function trackAnalyticsEvent(name, options = {}) {
  if (!isAnalyticsEnabledForCurrentRoute()) {
    return;
  }

  try {
    event(name, buildCommonEventParams(options));
  } catch (error) {
    console.error(`Analytics event failed for ${name}:`, error);
  }
}

export function trackPageView(options = {}) {
  const defaultPath = typeof window !== 'undefined' ? window.location.pathname : '';
  const defaultLocation = typeof window !== 'undefined' ? window.location.href : '';

  trackAnalyticsEvent('page_view', {
    ...options,
    params: {
      page_title: typeof document !== 'undefined' ? document.title : '',
      page_location: normalizePageLocationForAnalytics(defaultLocation),
      page_path: normalizePagePathForAnalytics(defaultPath),
      ...(options.params || {}),
    },
  });
}

export function trackOncePerSession(storageKey, name, options = {}) {
  if (typeof window === 'undefined' || !window.sessionStorage) {
    trackAnalyticsEvent(name, options);
    return;
  }

  const normalizedKey = `pdw-analytics:${storageKey}`;
  if (window.sessionStorage.getItem(normalizedKey)) {
    return;
  }

  trackAnalyticsEvent(name, options);
  window.sessionStorage.setItem(normalizedKey, '1');
}
