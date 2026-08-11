<?php

namespace SocialiteProviders\Tests\Steam;

use GuzzleHttp\Psr7\Response;
use SocialiteProviders\Steam\OpenIDValidationException;
use SocialiteProviders\Steam\Provider;
use SocialiteProviders\Tests\TestCase;
use Spatie\Snapshots\MatchesSnapshots;

class SteamProviderTest extends TestCase
{
    use MatchesSnapshots;

    private const STEAM_ID = '76561197960287930';

    protected function provider(): string
    {
        return Provider::class;
    }

    public function test_redirect_builds_an_openid_checkid_setup_url(): void
    {
        $url = $this->makeProvider()->redirect()->getTargetUrl();

        $this->assertStringStartsWith(Provider::OPENID_URL, $url);
        $this->assertMatchesJsonSnapshot($this->queryParams($url));
    }

    public function test_redirect_realm_honours_the_request_scheme(): void
    {
        // parse_str() rewrites dots in keys to underscores.
        $params = $this->queryParams(
            $this->makeProvider($this->makeRequest())->redirect()->getTargetUrl()
        );

        $this->assertSame('https://example.com', $params['openid_realm']);
    }

    public function test_redirect_realm_uses_the_configured_host(): void
    {
        $provider = $this->makeProvider(
            $this->makeRequest([], 'https://game.example.org/auth/callback')
        );

        $params = $this->queryParams($provider->redirect()->getTargetUrl());

        $this->assertSame('https://game.example.org', $params['openid_realm']);
    }

    public function test_user_maps_the_steam_player_summary(): void
    {
        $provider = $this->makeProvider(
            $this->makeRequest($this->validOpenidQuery()),
            [
                new Response(200, [], 'ns:'.Provider::OPENID_NS."\nis_valid:true\n"),
                new Response(200, [], $this->fixture('player.json')),
            ]
        );

        $user = $provider->user();

        $this->assertSame(self::STEAM_ID, $user->getId());
        $this->assertMatchesJsonSnapshot([
            'id'       => $user->getId(),
            'nickname' => $user->getNickname(),
            'name'     => $user->getName(),
            'email'    => $user->getEmail(),
            'avatar'   => $user->getAvatar(),
        ]);
    }

    /**
     * Steam returns underscore-separated keys on some callbacks (openid_sig rather
     * than openid.sig). Both spellings must resolve.
     *
     * @see https://github.com/socialiteproviders/providers/pull/1469
     */
    public function test_user_accepts_underscore_separated_openid_keys(): void
    {
        $query = [];

        foreach ($this->validOpenidQuery() as $key => $value) {
            $query[str_replace('openid.', 'openid_', $key)] = $value;
        }

        $provider = $this->makeProvider(
            $this->makeRequest($query),
            [
                new Response(200, [], "is_valid:true\n"),
                new Response(200, [], $this->fixture('player.json')),
            ]
        );

        $this->assertSame(self::STEAM_ID, $provider->user()->getId());
    }

    public function test_user_throws_when_openid_validation_fails(): void
    {
        $provider = $this->makeProvider(
            $this->makeRequest($this->validOpenidQuery()),
            [new Response(200, [], "is_valid:false\n")]
        );

        $this->expectException(OpenIDValidationException::class);

        $provider->user();
    }

    public function test_validate_throws_when_a_required_param_is_missing(): void
    {
        $query = $this->validOpenidQuery();
        unset($query['openid.sig']);

        $provider = $this->makeProvider($this->makeRequest($query));

        $this->expectException(OpenIDValidationException::class);
        $this->expectExceptionMessage('critical openid parameter is missing');

        $provider->validate();
    }

    public function test_parse_results_splits_the_key_value_body(): void
    {
        $parsed = $this->makeProvider()->parseResults('ns:'.Provider::OPENID_NS."\nis_valid:true\n");

        $this->assertSame('true', $parsed['is_valid']);
        $this->assertSame(Provider::OPENID_NS, $parsed['ns']);
    }

    public function test_parse_steam_id_extracts_the_id_from_the_claimed_id(): void
    {
        $provider = $this->makeProvider($this->makeRequest($this->validOpenidQuery()));
        $provider->parseSteamID();

        $this->assertSame(self::STEAM_ID, $provider->steamId);
    }

    public function test_parse_steam_id_falls_back_to_zero_for_an_unexpected_claimed_id(): void
    {
        $query = $this->validOpenidQuery();
        $query['openid.claimed_id'] = 'https://example.com/not-a-steam-id';

        $provider = $this->makeProvider($this->makeRequest($query));
        $provider->parseSteamID();

        $this->assertSame(0, $provider->steamId);
    }

    /**
     * @return array<string, string>
     */
    private function validOpenidQuery(): array
    {
        return [
            'openid.assoc_handle' => '1234567890',
            'openid.signed'       => 'signed,claimed_id',
            'openid.sig'          => 'signature',
            'openid.return_to'    => self::REDIRECT_URI,
            'openid.claimed_id'   => 'https://steamcommunity.com/openid/id/'.self::STEAM_ID,
        ];
    }
}
