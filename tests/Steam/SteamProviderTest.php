<?php

namespace SocialiteProviders\Tests\Steam;

use GuzzleHttp\Psr7\Response;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\NullStore;
use Illuminate\Cache\Repository;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\DataProvider;
use SocialiteProviders\Manager\Config;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Steam\OpenIDValidationException;
use SocialiteProviders\Steam\Provider;
use SocialiteProviders\Tests\TestCase;
use Spatie\Snapshots\MatchesSnapshots;

class SteamProviderTest extends TestCase
{
    use MatchesSnapshots;

    private const STEAM_ID = '76561197960287930';

    private Repository $nonceCache;

    protected function setUp(): void
    {
        parent::setUp();

        $this->nonceCache = new Repository(new ArrayStore);
    }

    protected function provider(): string
    {
        return Provider::class;
    }

    protected function makeProvider(?Request $request = null, array $responses = []): AbstractProvider
    {
        return parent::makeProvider($request, $responses)->setNonceCache($this->nonceCache);
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
                new Response(200, [], 'ns:'.Provider::OPENID_NS."\nis_valid:true\n"),
                new Response(200, [], $this->fixture('player.json')),
            ]
        );

        $this->assertSame(self::STEAM_ID, $provider->user()->getId());
    }

    public function test_user_throws_when_openid_validation_fails(): void
    {
        $provider = $this->makeProvider(
            $this->makeRequest($this->validOpenidQuery()),
            [new Response(200, [], 'ns:'.Provider::OPENID_NS."\nis_valid:false\n")]
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

    public function test_validate_rejects_a_return_to_with_userinfo_that_spoofs_the_host(): void
    {
        $query = $this->validOpenidQuery();
        $query['openid.return_to'] = 'https://attacker.example\\@example.com/auth/callback';

        $provider = $this->makeProvider($this->makeRequest($query));
        $provider->setConfig(new Config('', '', self::REDIRECT_URI, ['allowed_hosts' => ['example.com']]));

        $this->expectException(OpenIDValidationException::class);
        $this->expectExceptionMessage('Invalid return_to');

        $provider->validate();
    }

    public function test_validate_rejects_a_return_to_on_another_path(): void
    {
        $query = $this->validOpenidQuery();
        $query['openid.return_to'] = 'https://example.com/capture';

        $provider = $this->makeProvider($this->makeRequest($query));

        $this->expectException(OpenIDValidationException::class);
        $this->expectExceptionMessage('Invalid return_to');

        $provider->validate();
    }

    /**
     * @return array<string, array{string}>
     */
    public static function requiredSignedFieldProvider(): array
    {
        return array_combine(
            Provider::REQUIRED_SIGNED_FIELDS,
            array_map(fn (string $field) => [$field], Provider::REQUIRED_SIGNED_FIELDS)
        );
    }

    #[DataProvider('requiredSignedFieldProvider')]
    public function test_validate_rejects_an_assertion_missing_a_required_signed_field(string $field): void
    {
        $query = $this->validOpenidQuery();
        $signed = array_diff(explode(',', $query['openid.signed']), [$field]);
        $query['openid.signed'] = implode(',', $signed);

        $provider = $this->makeProvider($this->makeRequest($query));

        $this->expectException(OpenIDValidationException::class);
        $this->expectExceptionMessage('Invalid openid.signed');

        $provider->validate();
    }

    public function test_validate_accepts_extra_signed_fields(): void
    {
        $query = $this->validOpenidQuery();
        $query['openid.signed'] .= ',ns,mode';

        $provider = $this->makeProvider(
            $this->makeRequest($query),
            [new Response(200, [], 'ns:'.Provider::OPENID_NS."\nis_valid:true\n")]
        );

        $this->assertTrue($provider->validate());
    }

    public function test_validate_still_enforces_allowed_hosts(): void
    {
        $provider = $this->makeProvider($this->makeRequest($this->validOpenidQuery()));
        $provider->setConfig(new Config('', '', self::REDIRECT_URI, ['allowed_hosts' => ['other.example']]));

        $this->expectException(OpenIDValidationException::class);
        $this->expectExceptionMessage('Invalid return_to host');

        $provider->validate();
    }

    public function test_validate_rejects_every_return_to_without_a_redirect_url(): void
    {
        $query = $this->validOpenidQuery();
        $query['openid.return_to'] = '';

        $provider = $this->makeProvider($this->makeRequest($query))->redirectUrl('');

        $this->expectException(OpenIDValidationException::class);
        $this->expectExceptionMessage('Invalid return_to');

        $provider->validate();
    }

    public function test_validate_honours_a_redirect_url_override(): void
    {
        $override = 'https://example.com/auth/steam/other';

        $query = $this->validOpenidQuery();
        $query['openid.return_to'] = $override;

        $provider = $this->makeProvider(
            $this->makeRequest($query),
            [new Response(200, [], 'ns:'.Provider::OPENID_NS."\nis_valid:true\n")]
        )->redirectUrl($override);

        $this->assertTrue($provider->validate());

        $this->expectException(OpenIDValidationException::class);
        $this->makeProvider($this->makeRequest($query))->validate();
    }

    /**
     * @return array<string, array{string, string|null, string}>
     */
    public static function tamperedAssertionProvider(): array
    {
        return [
            'wrong mode'             => ['openid.mode', 'cancel', 'Invalid openid.mode'],
            'wrong namespace'        => ['openid.ns', 'http://specs.openid.net/auth/1.1', 'Invalid openid.ns'],
            'foreign op endpoint'    => ['openid.op_endpoint', 'https://openid.example.com/login', 'Invalid openid.op_endpoint'],
            'missing op endpoint'    => ['openid.op_endpoint', null, 'Invalid openid.op_endpoint'],
            'identity mismatch'      => ['openid.identity', 'https://steamcommunity.com/openid/id/76561197960287931', 'Invalid openid.claimed_id'],
            'non-steam claimed id'   => ['openid.claimed_id', 'https://example.com/openid/id/76561197960287930', 'Invalid openid.claimed_id'],
            'malformed nonce'        => ['openid.response_nonce', 'not-a-nonce', 'Invalid openid.response_nonce'],
            'expired nonce'          => ['openid.response_nonce', '2020-01-01T00:00:00Zabc', 'Expired openid.response_nonce'],
            'future nonce'           => ['openid.response_nonce', gmdate('Y-m-d\\TH:i:s\\Z', time() + 600).'abc', 'Expired openid.response_nonce'],
        ];
    }

    #[DataProvider('tamperedAssertionProvider')]
    public function test_validate_rejects_a_tampered_assertion(string $key, ?string $value, string $message): void
    {
        $query = $this->validOpenidQuery();

        if ($key === 'openid.claimed_id') {
            $query['openid.identity'] = $value;
        }

        if ($value === null) {
            unset($query[$key]);
        } else {
            $query[$key] = $value;
        }

        $provider = $this->makeProvider($this->makeRequest($query));

        $this->expectException(OpenIDValidationException::class);
        $this->expectExceptionMessage($message);

        $provider->validate();
    }

    public function test_validate_rejects_a_replayed_nonce(): void
    {
        $valid = fn () => [new Response(200, [], 'ns:'.Provider::OPENID_NS."\nis_valid:true\n")];
        $query = $this->validOpenidQuery();

        $this->assertTrue($this->makeProvider($this->makeRequest($query), $valid())->validate());

        $this->expectException(OpenIDValidationException::class);
        $this->expectExceptionMessage('Replayed openid.response_nonce');

        $this->makeProvider($this->makeRequest($query), $valid())->validate();
    }

    public function test_validate_skips_replay_check_on_the_null_cache_store(): void
    {
        $this->nonceCache = new Repository(new NullStore);
        $valid = fn () => [new Response(200, [], 'ns:'.Provider::OPENID_NS."\nis_valid:true\n")];
        $query = $this->validOpenidQuery();

        $this->assertTrue($this->makeProvider($this->makeRequest($query), $valid())->validate());
        $this->assertTrue($this->makeProvider($this->makeRequest($query), $valid())->validate());
    }

    public function test_validate_accepts_a_new_nonce_after_a_previous_login(): void
    {
        $valid = fn () => [new Response(200, [], 'ns:'.Provider::OPENID_NS."\nis_valid:true\n")];
        $query = $this->validOpenidQuery();

        $this->assertTrue($this->makeProvider($this->makeRequest($query), $valid())->validate());

        $query['openid.response_nonce'] = gmdate('Y-m-d\\TH:i:s\\Z').'other';

        $this->assertTrue($this->makeProvider($this->makeRequest($query), $valid())->validate());
    }

    public function test_validate_rejects_a_steam_response_without_the_openid_namespace(): void
    {
        $provider = $this->makeProvider(
            $this->makeRequest($this->validOpenidQuery()),
            [new Response(200, [], "is_valid:true\n")]
        );

        $this->assertFalse($provider->validate());
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
        $claimedId = 'https://steamcommunity.com/openid/id/'.self::STEAM_ID;

        return [
            'openid.ns'             => Provider::OPENID_NS,
            'openid.mode'           => 'id_res',
            'openid.op_endpoint'    => Provider::OPENID_URL,
            'openid.claimed_id'     => $claimedId,
            'openid.identity'       => $claimedId,
            'openid.return_to'      => self::REDIRECT_URI,
            'openid.response_nonce' => gmdate('Y-m-d\\TH:i:s\\Z').'abc123',
            'openid.assoc_handle'   => '1234567890',
            'openid.signed'         => 'signed,op_endpoint,claimed_id,identity,return_to,response_nonce,assoc_handle',
            'openid.sig'            => 'signature',
        ];
    }
}
