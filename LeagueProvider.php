<?php

namespace Omniauth\Google;

/*
 * Omniauth\Google\LeagueProvider is now Omniauth\OAuth\Provider\LeagueProvider (omniscient/omniauth):
 * the ground of every provider package built on league/oauth2-client, so it lives with the
 * mechanics they share. The former name is an alias of the new class for one release.
 *
 * @deprecated use Omniauth\OAuth\Provider\LeagueProvider
 */
@trigger_error('Omniauth\Google\LeagueProvider is deprecated: use Omniauth\OAuth\Provider\LeagueProvider (omniscient/omniauth).', \E_USER_DEPRECATED);
class_alias(\Omniauth\OAuth\Provider\LeagueProvider::class, LeagueProvider::class);
