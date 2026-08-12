<?php

namespace SocialiteProviders\Tests\OpenIDConnect;

use PHPUnit\Framework\Attributes\Test;

/**
 * Regression cover for the provider declaring its PKCE flag under a property
 * name Socialite does not read, which silently downgraded the flow to a plain
 * authorization code exchange.
 */
class PkceTest extends TestCase
{
    #[Test]
    public function pkce_is_enabled(): void
    {
        $this->assertTrue($this->oidcProvider()->callUsesPKCE());
    }

    #[Test]
    public function authorize_request_carries_a_code_challenge(): void
    {
        $request = $this->request();
        $provider = $this->oidcProvider(request: $request);

        $url = $provider->redirect()->getTargetUrl();
        parse_str(parse_url($url, PHP_URL_QUERY) ?: '', $query);

        $this->assertArrayHasKey('code_challenge', $query);
        $this->assertSame('S256', $query['code_challenge_method'] ?? null);
        $this->assertNotEmpty($query['code_challenge']);
    }

    #[Test]
    public function code_verifier_is_stored_in_the_session_and_matches_the_challenge(): void
    {
        $request = $this->request();
        $provider = $this->oidcProvider(request: $request);

        $url = $provider->redirect()->getTargetUrl();
        parse_str(parse_url($url, PHP_URL_QUERY) ?: '', $query);

        $verifier = $request->session()->get('code_verifier');

        $this->assertNotEmpty($verifier);
        $this->assertSame(
            $this->base64UrlEncode(hash('sha256', $verifier, true)),
            $query['code_challenge']
        );
    }

    #[Test]
    public function nonce_and_state_are_also_issued(): void
    {
        $request = $this->request();
        $provider = $this->oidcProvider(request: $request);

        $url = $provider->redirect()->getTargetUrl();
        parse_str(parse_url($url, PHP_URL_QUERY) ?: '', $query);

        $this->assertNotEmpty($request->session()->get('nonce'));
        $this->assertSame($request->session()->get('nonce'), $query['nonce'] ?? null);
        $this->assertSame($request->session()->get('state'), $query['state'] ?? null);
    }
}
