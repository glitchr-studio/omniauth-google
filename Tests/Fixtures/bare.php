<?php

/*
 * omniauth/google in plain PHP: no bundle, no container, no framework. The
 * conductor built by hand on the in-memory stores, Google over
 * league/oauth2-google with its answers recorded (a Guzzle handler): a
 * verified address links its account, then the subject alone signs in; a
 * forged state reaches no endpoint; a return path takes the host of the
 * trip. Prints what happened and every class and file PHP loaded, as JSON.
 * Argument: the autoloader.
 */

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Omniauth\ArrayState;
use Omniauth\Context;
use Omniauth\Google\GoogleFactory;
use Omniauth\Method\Methods;
use Omniauth\OAuth\Authorization;
use Omniauth\OAuth\NoSecondFactorLinking;
use Omniauth\OAuth\OAuthMethod;
use Omniauth\OAuth\Provider\Providers;
use Omniauth\Omniauth;
use Omniauth\Result;
use Omniauth\Testing\FixedClock;
use Omniauth\Testing\InMemoryStore;
use Omniauth\Testing\TestAccount;

require $autoload = $argv[1];

$clock = new FixedClock('2026-10-21 12:00:00 UTC');
$store = new InMemoryStore($ringo = new TestAccount('1', 'ringo', 'ringo@example.org'));
$answers = new MockHandler();
$sent = [];
$stack = HandlerStack::create($answers);
$stack->push(Middleware::history($sent));
$providers = Providers::configured(
    ['google' => ['client_id' => 'client.apps.googleusercontent.com', 'client_secret' => 'secret', 'redirect_uri' => '/login/google/callback']],
    new GoogleFactory($clock, ['httpClient' => new Client(['handler' => $stack])]),
);
$session = [];
$methods = new Methods();
$methods->add(new OAuthMethod($store, $store, $providers, new NoSecondFactorLinking($methods), $clock));
$omniauth = new Omniauth($store, $methods, new ArrayState($session), clock: $clock);
$seen = static fn (Result $r) => ['status' => $r->status->value, 'account' => $r->account?->identifier, 'passed' => array_map(fn ($f) => $f->value, $r->passed), 'reason' => $r->reason];
$jwt = static fn (array $claims) => implode('.', [Authorization::base64url('{"alg":"RS256","typ":"JWT"}'), Authorization::base64url((string) json_encode($claims)), Authorization::base64url(random_bytes(64))]);
$google = static function (Context $context, string $sub) use ($omniauth, $answers, $jwt): array {
    $url = (string) $omniauth->begin('oauth', $context)->payload;
    parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
    $answers->append(new Response(200, ['Content-Type' => 'application/json'], (string) json_encode(['access_token' => 'ya29.x', 'expires_in' => 3599, 'token_type' => 'Bearer', 'id_token' => $jwt([
        'iss' => 'https://accounts.google.com', 'aud' => 'client.apps.googleusercontent.com', 'sub' => $sub, 'email' => 'ringo@example.org', 'email_verified' => true,
        'nonce' => $query['nonce'], 'iat' => 1792584000, 'exp' => 1792587600,
    ])])));

    return [$url, $query];
};

$context = new Context('https://example.org', 'example.org', '203.0.113.7');
[$url, $query] = $google($context, '108204');
$report['linked'] = $seen($omniauth->verify('oauth', $context, ['code' => '4/0AQ', 'state' => $query['state']])) + ['host' => parse_url($url, PHP_URL_HOST), 'return' => $query['redirect_uri']];
$report['identities'] = $ringo->identities;

$twin = new Context('http://localhost:8132', 'localhost', '203.0.113.7');
[, $query] = $google($twin, '108204');
$report['by-subject-on-another-host'] = $seen($omniauth->verify('oauth', $twin, ['code' => '4/0AQ', 'state' => $query['state']])) + ['return' => $query['redirect_uri']];
parse_str((string) $sent[\count($sent) - 1]['request']->getBody(), $form);
$report['exchange-return'] = $form['redirect_uri'];

$before = \count($sent);
$google($context, '108204');
$report['forged'] = $seen($omniauth->verify('oauth', $context, ['code' => 'stolen', 'state' => 'guessed'])) + ['requests' => \count($sent) - $before];

$report['loaded'] = [
    'symbols' => [...get_declared_classes(), ...get_declared_interfaces(), ...get_declared_traits()],
    'files' => array_map('realpath', get_included_files()),
    'eager' => array_map('realpath', array_values(is_file($files = dirname($autoload).'/composer/autoload_files.php') ? require $files : [])),
];

echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), "\n";
