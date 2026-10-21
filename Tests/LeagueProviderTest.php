<?php

namespace Omniauth\Google\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use League\OAuth2\Client\Provider\GenericProvider;
use Omniauth\Google\LeagueProvider;
use Omniauth\OAuth\Authorization;
use PHPUnit\Framework\TestCase;

/** Any league/oauth2-client provider through LeagueProvider: omniauth's state and PKCE, the fields named. */
final class LeagueProviderTest extends TestCase
{
    public function testAGenericProvider(): void
    {
        $sent = [];
        $stack = HandlerStack::create(new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], '{"access_token":"at-1","token_type":"bearer","expires_in":3600}'),
            new Response(200, ['Content-Type' => 'application/json'], '{"id":"42","email":"ringo@example.org","name":"Ringo"}'),
        ]));
        $stack->push(Middleware::history($sent));
        $league = new GenericProvider([
            'clientId' => 'gt-id', 'clientSecret' => 'gt-secret', 'redirectUri' => 'https://unused.example/cb',
            'urlAuthorize' => 'https://git.example/login/oauth/authorize', 'urlAccessToken' => 'https://git.example/login/oauth/access_token',
            'urlResourceOwnerDetails' => 'https://git.example/api/v1/user', 'scopeSeparator' => ' ', 'responseResourceOwnerId' => 'id',
        ], ['httpClient' => new Client(['handler' => $stack])]);
        $gitea = new LeagueProvider('gitea', $league, ['read:user'], ['subject' => 'id', 'email' => 'email', 'verified' => null, 'name' => 'name'], '/connect/gitea/check');

        $authorization = Authorization::fresh('https://example.org');
        parse_str((string) parse_url($gitea->authorizationUrl($authorization), \PHP_URL_QUERY), $query);
        self::assertSame(['read:user', $authorization->state, $authorization->challenge(), 'S256', 'https://example.org/connect/gitea/check'], [$query['scope'], $query['state'], $query['code_challenge'], $query['code_challenge_method'], $query['redirect_uri']]);

        $identity = $gitea->identify('code', $authorization);
        parse_str((string) $sent[0]['request']->getBody(), $form);
        self::assertSame([$authorization->verifier, 'https://example.org/connect/gitea/check'], [$form['code_verifier'], $form['redirect_uri']]);
        self::assertSame(['gitea', '42', 'ringo@example.org', 'Ringo'], [$identity->provider, $identity->subject, $identity->email, $identity->name]);
        self::assertFalse($identity->emailVerified, 'a provider that says nothing of the address does not vouch for it: no linking by it');
    }

    public function testALeagueProviderThatDrawsItsOwnVerifierIsRefused(): void
    {
        $league = new GenericProvider(['clientId' => 'x', 'redirectUri' => 'https://example.org/x', 'urlAuthorize' => 'https://p.example/a', 'urlAccessToken' => 'https://p.example/t', 'urlResourceOwnerDetails' => 'https://p.example/u', 'pkceMethod' => GenericProvider::PKCE_METHOD_S256]);

        $this->expectException(\LogicException::class);
        (new LeagueProvider('x', $league))->authorizationUrl(Authorization::fresh());
    }
}
