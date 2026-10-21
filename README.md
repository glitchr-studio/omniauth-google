# omniauth/google

Google sign-in for [omniscient/omniauth](https://github.com/glitchr-studio/omniauth), **under any
PHP host**, over league/oauth2-google: Google as an OpenID Connect provider of
[omniauth/oauth](https://github.com/glitchr-studio/omniauth-oauth), which carries what every
provider shares (the state, PKCE and the nonce kept in the attempt, the linking by `sub`, the
sign-up of an unknown identity, the second factor after it).

```php
use Omniauth\Google\GoogleFactory;
use Omniauth\OAuth\{NoSecondFactorLinking, OAuthMethod};
use Omniauth\OAuth\Provider\Providers;

$providers = Providers::configured(
    ['google' => ['client_id' => $clientId, 'client_secret' => $clientSecret, 'redirect_uri' => '/login/google/callback']],
    new GoogleFactory(),
);
$methods->add(new OAuthMethod($accounts, $identities, $providers, new NoSecondFactorLinking($methods)));
```

One package per provider (Marco's decision, 2026-10-21), as omnishield has one per captcha: this
one requires league/oauth2-client and league/oauth2-google (and Guzzle through them); omniauth/oauth
requires no OAuth library.

## The shape

| | |
|---|---|
| `GoogleProvider` | Google over league/oauth2-google: scopes `openid email profile`, the nonce, the ID token's issuer, audience, authorized party, lifetime, nonce and hosted domain checked; `email_verified` as Google sends it; the userinfo when there is no ID token |
| `GoogleFactory` | Google from a host's configuration, for `Providers::configured()` |
| `LeagueProvider` | any league/oauth2-client provider as omniauth's `Provider`: omniauth's state and PKCE, the profile's fields named |

The return address may be a path: each trip returns to the host it began at (a site on two hosts,
or a long-running worker serving several), and the exchange repeats that address.

## Documentation

- [Installation, in plain PHP](docs/installation.md)
- [Google](docs/google.md)

## Verified, and not

- Verified (omniauth's harness, `docker compose run --rm omniauth test` in the core's `docker/`,
  PHP 8.4), **Google's answers recorded** (a Guzzle mock handler with the shapes of Google's
  documentation): the authorization address (Google's endpoint, every parameter); the token
  request (POST to `oauth2.googleapis.com/token`, the code, the verifier whose S256 is the
  challenge that went out, the client, the return address); the ID token's checks; `email_verified`
  as a boolean and as a string; the userinfo through the bearer token, its subject compared; a code
  Google refuses; a sign-in through the conductor; **a return path on the host of each trip, the
  same at the exchange**; the factory; a generic league provider; the former names; in plain PHP
  (`BareTest`), nothing loaded but omniauth, omniauth/oauth, league, Guzzle, the PSR interfaces and
  the password hasher.
- Not verified: **a real Google** - no client id and secret were at hand: the recorded answers are
  written from Google's documentation, not captured, and the ID token's signature is not checked
  (a token from the token endpoint over TLS, OpenID Connect Core §3.1.3.7 (6)).

## Licence

MIT.
