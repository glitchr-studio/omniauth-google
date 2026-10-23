<?php

namespace Omniauth\Google\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use Omniauth\Google\GoogleFactory;
use Omniauth\Google\GoogleProvider;
use Omniauth\OAuth\Authorization;
use Omniauth\OAuth\Provider\Providers;
use Omniauth\OAuth\Testing\FakeProvider;
use PHPUnit\Framework\TestCase;

/** Google from a host's configuration: omniauth's Providers::configured() with this package's factory. */
final class GoogleFactoryTest extends TestCase
{
    public function testGoogleByConfiguration(): void
    {
        $providers = Providers::configured([
            'google' => ['client_id' => 'g-id', 'client_secret' => 'g-secret', 'redirect_uri' => '/login/google/callback', 'scopes' => ['openid', 'email'], 'hosted_domain' => 'beatles.example'],
            'workspace' => ['type' => 'google', 'client_id' => 'w-id', 'client_secret' => 'w-secret', 'redirect_uri' => 'https://example.org/connect/workspace/check'],
            'facebook' => ['client_id' => '', 'redirect_uri' => '/connect/facebook/check'],
        ], new GoogleFactory(collaborators: ['httpClient' => new Client(['handler' => HandlerStack::create(new MockHandler())])]));

        self::assertSame(['google', 'workspace'], $providers->names(), 'a provider without credentials is not served');
        self::assertInstanceOf(GoogleProvider::class, $providers->get('google'));
        self::assertInstanceOf(GoogleProvider::class, $providers->get('workspace'));

        parse_str((string) parse_url((string) $providers->get('google')?->authorizationUrl(Authorization::fresh('https://example.org')), \PHP_URL_QUERY), $query);
        self::assertSame(['g-id', 'https://example.org/login/google/callback', 'openid email profile', 'beatles.example'], [$query['client_id'], $query['redirect_uri'], $query['scope'], $query['hd']]);
        parse_str((string) parse_url((string) $providers->get('workspace')?->authorizationUrl(Authorization::fresh('http://localhost:8132')), \PHP_URL_QUERY), $query);
        self::assertSame('https://example.org/connect/workspace/check', $query['redirect_uri'], 'an absolute address stays as configured');
    }

    public function testAnotherKindIsNotThisFactorys(): void
    {
        self::assertNull((new GoogleFactory())->create('facebook', ['client_id' => 'x', 'redirect_uri' => '/x']));
        self::assertNull((new GoogleFactory())->create('google', ['type' => 'gitea', 'client_id' => 'x', 'redirect_uri' => '/x']));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('omniauth/facebook');
        Providers::configured(['facebook' => ['client_id' => 'x', 'redirect_uri' => '/x']], new GoogleFactory());
    }

    public function testServicesAndConfigurationTogether(): void
    {
        $providers = new Providers(new FakeProvider('staging'), ...Providers::configured(['google' => ['client_id' => 'g', 'redirect_uri' => '/cb']], new GoogleFactory()));

        self::assertSame(['staging', 'google'], $providers->names());
    }

    /** omniauth/oauth's former entry point and names, kept one release: it makes Google through this package. */
    public function testTheDeprecatedFromConfigStillMakesGoogle(): void
    {
        $providers = @Providers::fromConfig(['google' => ['client_id' => 'g', 'client_secret' => 's', 'redirect_uri' => 'https://example.org/login/google/callback']]);

        self::assertInstanceOf(GoogleProvider::class, $providers->get('google'));
        self::assertInstanceOf(GoogleProvider::class, new \Omniauth\OAuth\Provider\GoogleProvider('g', 's', '/cb'), 'the former class name, an alias');
        self::assertTrue(@class_exists('Omniauth\Google\LeagueProvider'));
        self::assertTrue(is_a('Omniauth\Google\LeagueProvider', \Omniauth\OAuth\Provider\LeagueProvider::class, true) && is_a(\Omniauth\OAuth\Provider\LeagueProvider::class, 'Omniauth\Google\LeagueProvider', true), 'LeagueProvider\'s former name here, an alias of the core\'s');
    }
}
