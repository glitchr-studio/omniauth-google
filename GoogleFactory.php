<?php

namespace Omniauth\Google;

use Omniauth\OAuth\Provider\Provider;
use Omniauth\OAuth\Provider\ProviderFactory;
use Psr\Clock\ClockInterface;

/**
 * Google from a host's configuration, for omniauth/oauth's
 * Providers::configured(): an entry named "google", or of "type: google".
 *
 *     google: { client_id: …, client_secret: …, redirect_uri: /login/google/callback,
 *               scopes: [openid, email, profile], hosted_domain: ~ }
 *
 * redirect_uri is an absolute address or a path (each trip's host completes
 * it); scopes default to GoogleProvider::SCOPES; hosted_domain is a Google
 * Workspace domain the account must belong to, "*" for any.
 */
final class GoogleFactory implements ProviderFactory
{
    /** @param array<string, mixed> $collaborators league's, for every provider it makes (an "httpClient": tests, proxies) */
    public function __construct(
        private readonly ?ClockInterface $clock = null,
        private readonly array $collaborators = [],
    ) {
    }

    public function create(string $name, array $entry): ?Provider
    {
        if ('google' !== ($entry['type'] ?? ('google' === $name ? 'google' : null))) {
            return null;
        }
        $scopes = array_values(array_map(static fn (mixed $scope): string => \is_scalar($scope) ? (string) $scope : '', (array) ($entry['scopes'] ?? [])));
        $hostedDomain = $entry['hosted_domain'] ?? null;

        return new GoogleProvider(
            self::string($entry, 'client_id'),
            self::string($entry, 'client_secret'),
            self::string($entry, 'redirect_uri'),
            array_values(array_filter($scopes, static fn (string $scope) => '' !== $scope)) ?: GoogleProvider::SCOPES,
            \is_string($hostedDomain) && '' !== $hostedDomain ? $hostedDomain : null,
            $this->clock,
            $this->collaborators,
            $name,
        );
    }

    /** @param array<string, mixed> $entry */
    private static function string(array $entry, string $key): string
    {
        $value = $entry[$key] ?? '';

        return \is_scalar($value) ? (string) $value : '';
    }
}
