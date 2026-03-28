import test from 'node:test';
import assert from 'node:assert/strict';

import {
  buildGoogleAuthRedirectHref,
  buildLoginRedirectHref,
  buildRegisterRedirectHref,
} from '../../resources/js/Utils/authRedirects.js';

const routeMap = {
  login: '/login',
  register: '/register',
  'socialite.redirect': '/login/google/redirect',
};

function fakeRoute(name) {
  const value = routeMap[name];
  if (!value) {
    throw new Error(`Unexpected route lookup: ${name}`);
  }

  return value;
}

test('login and register redirect URLs encode nested query strings safely', () => {
  const redirectTarget = '/subscription?source=crime-address&recommended=basic';

  assert.equal(
    buildLoginRedirectHref(fakeRoute, redirectTarget),
    '/login?redirect_to=%2Fsubscription%3Fsource%3Dcrime-address%26recommended%3Dbasic',
  );
  assert.equal(
    buildRegisterRedirectHref(fakeRoute, redirectTarget),
    '/register?redirect_to=%2Fsubscription%3Fsource%3Dcrime-address%26recommended%3Dbasic',
  );
});

test('google auth redirect URLs preserve the full target path', () => {
  const redirectTarget = '/subscribe/basic?source=crime-address';

  assert.equal(
    buildGoogleAuthRedirectHref(fakeRoute, redirectTarget),
    '/login/google/redirect?redirect_to=%2Fsubscribe%2Fbasic%3Fsource%3Dcrime-address',
  );
});
