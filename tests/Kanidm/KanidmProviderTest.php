<?php

namespace SocialiteProviders\Tests\Kanidm;

use Illuminate\Http\Request;
use SocialiteProviders\Kanidm\Provider;
use SocialiteProviders\Manager\Config;
use SocialiteProviders\Tests\TestCase;

class KanidmProviderTest extends TestCase
{
    private const BASE_URL = 'https://idm.example.com';

    protected function provider(): string
    {
        return Provider::class;
    }

    /**
     * Computes PKCE challenge
     * See getCodeChallenge() in AbstractProvider
     */
    private static function s256(string $verifier): string
    {
        return rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
    }

    private function configured(?Request $request = null, array $responses = [], array $config = []): Provider
    {
        /** @var Provider $provider */
        $provider = $this->makeProvider($request ?? $this->makeRequestWithSession());
        $provider->setConfig(new Config(
            static::CLIENT_ID,
            static::CLIENT_SECRET,
            static::REDIRECT_URI,
            array_merge(['base_url' => self::BASE_URL], $config)
        ));

        $client = $this->makeHttpClient($responses);
        $provider->setHttpClient($client);

        return $provider;
    }

    public function test_redirect_with_pkce_enabled_stores_verifier_and_derives_challenge_from_it(): void
    {
        $request = $this->makeRequestWithSession();
        $url = $this->configured($request, [], ['enable_pkce' => true])->redirect()->getTargetUrl();
        $params = $this->queryParams($url);

        $this->assertStringStartsWith(self::BASE_URL.'/ui/oauth2?', $url);
        $this->assertSame(static::CLIENT_ID, $params['client_id']);
        $this->assertSame(static::REDIRECT_URI, $params['redirect_uri']);
        $this->assertSame('code', $params['response_type']);
        $this->assertSame('email openid profile', $params['scope']);
        $this->assertSame($request->session()->get('state'), $params['state']);

        $verifier = $request->session()->get('code_verifier');
        $this->assertNotNull($verifier, 'code_verifier was not stored in the session');
        $this->assertSame('S256', $params['code_challenge_method']);
        $this->assertSame($this->s256($verifier), $params['code_challenge']);
        $this->assertNotSame($this->s256(''), $params['code_challenge']);
    }

    public function test_redirect_without_pkce_config_sends_no_challenge(): void
    {
        $request = $this->makeRequestWithSession();
        $url = $this->configured($request)->redirect()->getTargetUrl();
        $params = $this->queryParams($url);

        $this->assertArrayNotHasKey('code_challenge', $params);
        $this->assertArrayNotHasKey('code_challenge_method', $params);
        $this->assertFalse($request->session()->has('code_verifier'));
    }
}
