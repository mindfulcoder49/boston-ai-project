export function normalizeRedirectTarget(redirectTo, fallback = '/') {
  if (!redirectTo) {
    return fallback;
  }

  if (redirectTo.startsWith('/')) {
    return redirectTo;
  }

  try {
    const url = new URL(redirectTo, 'http://localhost');
    return `${url.pathname}${url.search}${url.hash}`;
  } catch {
    return fallback;
  }
}

export function getCurrentRelativeUrl(fallback = '/') {
  if (typeof window === 'undefined') {
    return fallback;
  }

  return `${window.location.pathname}${window.location.search}${window.location.hash}`;
}

export function buildLoginRedirectHref(routeFn, redirectTo) {
  return `${routeFn('login')}?redirect_to=${encodeURIComponent(normalizeRedirectTarget(redirectTo))}`;
}

export function buildRegisterRedirectHref(routeFn, redirectTo) {
  return `${routeFn('register')}?redirect_to=${encodeURIComponent(normalizeRedirectTarget(redirectTo))}`;
}

export function buildGoogleAuthRedirectHref(routeFn, redirectTo) {
  return `${routeFn('socialite.redirect', 'google')}?redirect_to=${encodeURIComponent(normalizeRedirectTarget(redirectTo))}`;
}
