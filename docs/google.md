---
title: Google
order: 2
---

# Google

```php
new GoogleProvider(
    clientId: '…apps.googleusercontent.com',
    clientSecret: '…',
    redirectUri: '/login/google/callback',   // a path on each trip's host, or an absolute address
    scopes: ['openid', 'email', 'profile'],  // the default; openid is always there
    hostedDomain: null,                      // a Workspace domain, or '*' for any
);
```

Over league/oauth2-google: the address on `accounts.google.com/o/oauth2/v2/auth` with the state,
the S256 challenge, the nonce and the scopes; the code posted to `oauth2.googleapis.com/token`
with the verifier and the same return address. The person is read from the **ID token** the
exchange returns, its claims checked: issuer `https://accounts.google.com` (or
`accounts.google.com`), audience this client (and `azp` this client when there are several
audiences), not past `exp` (60 seconds of leeway), not issued in the future, the trip's nonce, and
`hd` when a hosted domain is set. The token comes straight from Google's token endpoint over TLS,
so its signature is not checked (OpenID Connect Core §3.1.3.7, point 6, lets the TLS server
validation stand in for it here); no key set is fetched. With no ID token, or one without the
address, the userinfo endpoint is read through the access token, and its subject must be the
token's.

`email_verified` decides whether an address may link an account: `true` (or the string `"true"`
Google once sent) only.

## The return address

An absolute address is sent as it is. A **path** is completed by the origin of the request that
began the trip (`Context::$origin`, kept in the attempt with the state): a site served on two hosts
(lesringostars and its Laravel twin), or by a long-running worker that serves several, sends each
visitor back to the host they left from, and the exchange repeats that trip's address, as Google
requires. Each address must be one of the client's authorized redirect URIs at Google.

## By configuration

`GoogleFactory` makes Google for omniauth/oauth's `Providers::configured()`: an entry named
`google`, or of `type: google` (`client_id`, `client_secret`, `redirect_uri`, `scopes`,
`hosted_domain`). An entry without a `client_id` is not served. Its constructor takes a clock and
league's collaborators (an `httpClient`: tests, proxies).

## Any league provider

`LeagueProvider` - GoogleProvider's ground - serves any league/oauth2-client provider by hand
(omniauth's state and PKCE in the address, the code exchanged with the verifier, the person read
from the resource owner's details, their keys named by `fields`), until that provider has its own
package. Leave the league provider's own `pkceMethod` unset: omniauth draws the verifier and keeps
it in the attempt (a league provider that draws its own is refused).

## Formerly

These classes were `Omniauth\OAuth\Provider\GoogleProvider` and `LeagueProvider` in omniauth/oauth;
the former names stay aliases for one release, and omniauth/oauth's `Providers::fromConfig()`
(deprecated) makes Google through this package's factory.
