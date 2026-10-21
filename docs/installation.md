---
title: Installation
order: 1
---

# Installation, in plain PHP

```sh
composer require omniscient/omniauth omniauth/oauth omniauth/google
```

It requires omniauth/oauth (the trip and the linking rule every provider shares),
league/oauth2-client and league/oauth2-google (framework-free; league talks HTTP through Guzzle).
No bundle, no framework.

Google by configuration, its return address a path each trip's host completes:

```php
<?php

require 'vendor/autoload.php';

use Omniauth\ArrayState;
use Omniauth\Context;
use Omniauth\Google\GoogleFactory;
use Omniauth\Method\Methods;
use Omniauth\OAuth\NoSecondFactorLinking;
use Omniauth\OAuth\OAuthMethod;
use Omniauth\OAuth\Provider\Providers;
use Omniauth\Omniauth;
use Omniauth\Testing\InMemoryStore;
use Omniauth\Testing\TestAccount;

$store = new InMemoryStore(new TestAccount('42', 'ringo', 'ringo@example.org'));   // your accounts and identities
$providers = Providers::configured([
    'google' => [
        'client_id' => '1234-example.apps.googleusercontent.com',
        'client_secret' => 'GOCSPX-…',
        'redirect_uri' => '/login/google/callback',   // or an absolute address
    ],
], new GoogleFactory());

$session = [];                                             // $_SESSION, in a real page
$methods = new Methods();
$methods->add(new OAuthMethod($store, $store, $providers, new NoSecondFactorLinking($methods)));
$omniauth = new Omniauth(accounts: $store, methods: $methods, state: new ArrayState($session));
$context = new Context(origin: 'https://example.org', host: 'example.org', ip: '203.0.113.7');

// /connect/google: the visitor is sent to Google.
$challenge = $omniauth->begin('oauth', $context, ['provider' => 'google']);
parse_str((string) parse_url($challenge->payload, PHP_URL_QUERY), $query);
echo parse_url($challenge->payload, PHP_URL_HOST), ' ', $query['scope'], ' -> ', $query['redirect_uri'], "\n";
// accounts.google.com openid email profile -> https://example.org/login/google/callback

// /login/google/callback: Google's return, as it is.
// $result = $omniauth->verify('oauth', $context, $_GET);
```

The return route hands its query to `verify()`; what follows (`Authenticated`,
`SecondFactorRequired`, `IdentityUnknown`, a failure) is omniauth/oauth's, described in its
[installation page](https://github.com/glitchr-studio/omniauth-oauth/blob/1.x/docs/installation.md).

Next: [Google](google.md).
