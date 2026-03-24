import { event } from 'vue-gtag';

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

function inferPageType(path = '') {
  if (path === '/') {
    return 'home';
  }

  if (path.startsWith('/map')) {
    return 'explore_map';
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

export function buildCommonEventParams({
  city,
  pageType,
  languageCode,
  isAuthenticated,
  params = {},
} = {}) {
  const pagePath = params.page_path || (typeof window !== 'undefined' ? window.location.pathname : '');

  return sanitizeParams({
    page_type: pageType || inferPageType(pagePath),
    city: normalizeCity(city),
    language_code: languageCode,
    device_type: inferDeviceType(),
    is_authenticated: isAuthenticated,
    ...params,
  });
}

export function trackAnalyticsEvent(name, options = {}) {
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
      page_location: defaultLocation,
      page_path: defaultPath,
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
