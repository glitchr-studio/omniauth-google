<?php

namespace Omniauth\Google\Tests\Fixtures;

use Omniauth\ArrayState;
use Omniauth\Context;
use Omniauth\Method\Methods;
use Omniauth\OAuth\NoSecondFactorLinking;
use Omniauth\OAuth\OAuthMethod;
use Omniauth\OAuth\Provider\Provider;
use Omniauth\OAuth\Provider\Providers;
use Omniauth\Omniauth;
use Omniauth\Testing\FixedClock;
use Omniauth\Testing\InMemoryStore;
use Omniauth\Testing\TestAccount;

/** omniauth with one identity provider on the in-memory stores: ringo, his address verified at Google. */
final class Kit
{
    public readonly FixedClock $clock;
    public readonly InMemoryStore $store;
    public readonly TestAccount $ringo;
    public readonly Omniauth $omniauth;
    /** @var array<string, mixed> */
    public array $session = [];

    public function __construct(Provider $provider)
    {
        $this->clock = new FixedClock('2026-10-21 12:00:00 UTC');
        $this->store = new InMemoryStore($this->ringo = new TestAccount('1', 'ringo', 'ringo@example.org', null, 'Ringo Starr'));
        $methods = new Methods();
        $methods->add(new OAuthMethod($this->store, $this->store, new Providers($provider), new NoSecondFactorLinking($methods), $this->clock));
        $this->omniauth = new Omniauth($this->store, $methods, new ArrayState($this->session), clock: $this->clock);
    }

    public static function context(): Context
    {
        return new Context('https://example.org', 'example.org', '203.0.113.7');
    }

    /** Begins at the sign-in page: the authorization address the visitor is sent to. */
    public function depart(string $provider = 'google'): string
    {
        $challenge = $this->omniauth->begin('oauth', self::context(), ['provider' => $provider]);
        if ('redirect' !== $challenge->kind) {
            throw new \RuntimeException('No authorization address: '.$challenge->reason);
        }

        return (string) $challenge->payload;
    }
}
