<?php

namespace SocialiteProviders\Tests\Vipps;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;
use Laravel\Socialite\Two\InvalidStateException;
use PHPUnit\Framework\Attributes\DataProvider;
use SocialiteProviders\Manager\Config;
use SocialiteProviders\Tests\TestCase;
use SocialiteProviders\Vipps\Provider;
use UnexpectedValueException;

class VippsProviderTest extends TestCase
{
    private array $history = [];

    protected function setUp(): void
    {
        parent::setUp();

        Cache::swap(new Repository(new ArrayStore));
    }

    protected function tearDown(): void
    {
        Cache::clearResolvedInstances();

        parent::tearDown();
    }

    protected function provider(): string
    {
        return Provider::class;
    }

    private function configured(?Request $request = null, array $responses = [], array $config = []): Provider
    {
        /** @var Provider $provider */
        $provider = $this->makeProvider($request ?? $this->makeRequestWithSession());
        $provider->setConfig(new Config(static::CLIENT_ID, static::CLIENT_SECRET, static::REDIRECT_URI, $config));

        $client = $this->makeHttpClient($responses);
        $client->getConfig('handler')->push(Middleware::history($this->history));
        $provider->setHttpClient($client);

        return $provider;
    }

    private function discovery(string $baseUrl = 'https://api.vipps.no'): Response
    {
        // Distinct paths prove the provider uses discovery instead of fixed endpoints.
        return new Response(200, [], json_encode([
            'authorization_endpoint' => $baseUrl.'/discovered/authorize',
            'token_endpoint'         => $baseUrl.'/discovered/token',
            'userinfo_endpoint'      => $baseUrl.'/discovered/userinfo',
        ]));
    }

    public function test_redirect_uses_discovery_and_generates_state_and_s256_pkce(): void
    {
        $request = $this->makeRequestWithSession();
        $url = $this->configured($request, [$this->discovery()])->redirect()->getTargetUrl();
        $params = $this->queryParams($url);

        $this->assertStringStartsWith('https://api.vipps.no/discovered/authorize?', $url);
        $this->assertSame(static::CLIENT_ID, $params['client_id']);
        $this->assertSame(static::REDIRECT_URI, $params['redirect_uri']);
        $this->assertSame('code', $params['response_type']);
        $this->assertSame('openid name email', $params['scope']);
        $this->assertGreaterThanOrEqual(8, strlen($params['state']));
        $this->assertSame($request->session()->get('state'), $params['state']);
        $this->assertSame('S256', $params['code_challenge_method']);
        $this->assertSame(
            rtrim(strtr(base64_encode(hash('sha256', $request->session()->get('code_verifier'), true)), '+/', '-_'), '='),
            $params['code_challenge']
        );
        $this->assertArrayNotHasKey('client_secret', $params);
        $this->assertSame(
            'https://api.vipps.no/access-management-1.0/access/.well-known/openid-configuration',
            (string) $this->history[0]['request']->getUri()
        );
    }

    public function test_custom_scopes_keep_openid_and_mobile_parameters_are_forwarded(): void
    {
        $url = $this->configured(null, [$this->discovery()])
            ->setScopes(['phoneNumber', 'birthDate', 'customFlow'])
            ->with(['market' => 'SE', 'requested_flow' => 'app_to_app_v2'])
            ->redirect()->getTargetUrl();
        $params = $this->queryParams($url);

        $this->assertSame('openid phoneNumber birthDate customFlow', $params['scope']);
        $this->assertSame('SE', $params['market']);
        $this->assertSame('app_to_app_v2', $params['requested_flow']);
        $this->assertSame('S256', $params['code_challenge_method']);
    }

    public static function tokenConfigurations(): array
    {
        return [
            'production basic default' => [[], 'https://api.vipps.no', true],
            'test basic'               => [['test_mode' => true], 'https://apitest.vipps.no', true],
            'production post'          => [['test_mode' => 'false', 'token_auth_method' => 'client_secret_post'], 'https://api.vipps.no', false],
            'test post'                => [['test_mode' => 'true', 'token_auth_method' => 'client_secret_post'], 'https://apitest.vipps.no', false],
        ];
    }

    #[DataProvider('tokenConfigurations')]
    public function test_callback_exchanges_code_and_fetches_userinfo(array $config, string $baseUrl, bool $basic): void
    {
        $request = $this->makeRequestWithSession(
            ['code' => 'authorization-code', 'state' => 'expected-state'],
            ['state' => 'expected-state', 'code_verifier' => 'saved-code-verifier']
        );
        $tokenResponse = ['access_token' => 'access-token', 'expires_in' => 600, 'scope' => 'openid name email', 'id_token' => 'unused-id-token'];
        $provider = $this->configured($request, [
            $this->discovery($baseUrl),
            new Response(200, [], json_encode($tokenResponse)),
            new Response(200, [], $this->fixture('user.json')),
        ], array_merge($config, ['merchant_serial_number' => '123456']));
        $user = $provider->user();

        $this->assertSame('126684df-c056-4625-821d-f2905febe3f9', $user->getId());
        $this->assertSame('Test User', $user->getName());
        $this->assertSame('test@example.com', $user->getEmail());
        $this->assertNull($user->getNickname());
        $this->assertNull($user->getAvatar());
        $this->assertSame($this->fixtureJson('user.json'), $user->getRaw());
        $this->assertSame('access-token', $user->token);
        $this->assertSame(600, $user->expiresIn);
        $this->assertNull($user->refreshToken);
        $this->assertSame(['openid', 'name', 'email'], $user->approvedScopes);
        $this->assertSame($tokenResponse, $user->accessTokenResponseBody);
        $this->assertSame($user, $provider->user());
        $this->assertFalse($request->session()->has('state'));
        $this->assertFalse($request->session()->has('code_verifier'));
        $this->assertCount(3, $this->history);

        $tokenRequest = $this->history[1]['request'];
        $this->assertSame('POST', $tokenRequest->getMethod());
        $this->assertSame($baseUrl.'/discovered/token', (string) $tokenRequest->getUri());
        $this->assertSame('application/x-www-form-urlencoded', $tokenRequest->getHeaderLine('Content-Type'));
        parse_str((string) $tokenRequest->getBody(), $form);
        $expected = [
            'grant_type'    => 'authorization_code',
            'code'          => 'authorization-code',
            'redirect_uri'  => static::REDIRECT_URI,
            'code_verifier' => 'saved-code-verifier',
        ];
        if ($basic) {
            $this->assertSame('Basic '.base64_encode(static::CLIENT_ID.':'.static::CLIENT_SECRET), $tokenRequest->getHeaderLine('Authorization'));
        } else {
            $this->assertFalse($tokenRequest->hasHeader('Authorization'));
            $expected['client_id'] = static::CLIENT_ID;
            $expected['client_secret'] = static::CLIENT_SECRET;
        }
        $this->assertEquals($expected, $form);

        $userinfoRequest = $this->history[2]['request'];
        $this->assertSame('GET', $userinfoRequest->getMethod());
        $this->assertSame($baseUrl.'/discovered/userinfo', (string) $userinfoRequest->getUri());
        $this->assertSame('Bearer access-token', $userinfoRequest->getHeaderLine('Authorization'));
        foreach ($this->history as $transaction) {
            $this->assertSame('123456', $transaction['request']->getHeaderLine('Merchant-Serial-Number'));
            $this->assertSame('application/json', $transaction['request']->getHeaderLine('Accept'));
            foreach (['Vipps-System-Name', 'Vipps-System-Version', 'Vipps-System-Plugin-Name', 'Vipps-System-Plugin-Version'] as $header) {
                $this->assertNotEmpty($transaction['request']->getHeaderLine($header));
            }
        }
    }

    public function test_discovery_is_cached_across_instances_and_separated_by_environment(): void
    {
        $this->configured(null, [$this->discovery()])->redirect();
        $production = $this->configured()->redirect()->getTargetUrl();
        $test = $this->configured(null, [$this->discovery('https://apitest.vipps.no')], ['test_mode' => true])->redirect()->getTargetUrl();

        $this->assertStringStartsWith('https://api.vipps.no/discovered/authorize?', $production);
        $this->assertStringStartsWith('https://apitest.vipps.no/discovered/authorize?', $test);
        $this->assertCount(2, $this->history);
        $this->assertSame('https://apitest.vipps.no/access-management-1.0/access/.well-known/openid-configuration', (string) $this->history[1]['request']->getUri());
    }

    public function test_user_from_token_handles_unrequested_profile_fields(): void
    {
        $user = $this->configured(null, [
            $this->discovery(),
            new Response(200, [], '{"sub":"subject-id"}'),
        ])->userFromToken('access-token');

        $this->assertSame('subject-id', $user->getId());
        $this->assertNull($user->getName());
        $this->assertNull($user->getEmail());
        $this->assertSame(['sub' => 'subject-id'], $user->getRaw());
    }

    public function test_invalid_state_is_rejected_before_any_http_request(): void
    {
        $provider = $this->configured($this->makeRequestWithSession(
            ['code' => 'code', 'state' => 'tampered-state'],
            ['state' => 'expected-state']
        ));

        $this->expectException(InvalidStateException::class);
        $provider->user();
    }

    public function test_userinfo_without_a_subject_is_rejected(): void
    {
        $provider = $this->configured(null, [
            $this->discovery(),
            new Response(200, [], '{"email":"test@example.com"}'),
        ]);

        $this->expectException(UnexpectedValueException::class);
        $provider->userFromToken('access-token');
    }

    public function test_token_errors_are_propagated_without_fetching_userinfo(): void
    {
        $provider = $this->configured(null, [$this->discovery(), new Response(400, [], '{"error":"invalid_grant"}')]);

        $this->expectException(ClientException::class);
        $provider->getAccessTokenResponse('expired-code');
    }

    public function test_invalid_authentication_method_is_rejected(): void
    {
        $provider = $this->configured(null, [$this->discovery()], ['token_auth_method' => 'unsupported']);

        $this->expectException(InvalidArgumentException::class);
        $provider->getAccessTokenResponse('code');
    }

    public function test_incomplete_discovery_is_not_cached(): void
    {
        try {
            $this->configured(null, [new Response(200, [], '{}')])->redirect();
            $this->fail('Expected invalid discovery to be rejected.');
        } catch (UnexpectedValueException $exception) {
            $this->assertStringContainsString('authorization_endpoint', $exception->getMessage());
        }

        $url = $this->configured(null, [$this->discovery()])->redirect()->getTargetUrl();
        $this->assertStringStartsWith('https://api.vipps.no/discovered/authorize?', $url);
        $this->assertCount(2, $this->history);
    }
}
