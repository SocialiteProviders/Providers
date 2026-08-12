<?php

namespace SocialiteProviders\Tests\OpenIDConnect;

use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;

/**
 * A nonce is carried in the session between the redirect and the callback, so
 * it cannot survive a stateless (API/SPA) flow.
 */
class StatelessTest extends TestCase
{
    #[Test]
    public function the_nonce_is_disabled_in_stateless_mode(): void
    {
        $provider = $this->oidcProvider();
        $provider->stateless();

        $this->assertFalse($provider->callUsesNonce());
    }

    #[Test]
    public function the_authorize_request_omits_the_nonce_in_stateless_mode(): void
    {
        $provider = $this->oidcProvider();
        $provider->stateless();

        $this->assertArrayNotHasKey('nonce', $provider->callGetCodeFields('state'));
    }

    #[Test]
    public function the_redirect_writes_no_nonce_to_the_session_in_stateless_mode(): void
    {
        $request = $this->request();
        $provider = $this->oidcProvider(request: $request);
        $provider->stateless();

        $url = $provider->redirect()->getTargetUrl();
        parse_str(parse_url($url, PHP_URL_QUERY) ?: '', $query);

        $this->assertNull($request->session()->get('nonce'));
        $this->assertArrayNotHasKey('nonce', $query);
        $this->assertArrayNotHasKey('state', $query);
    }

    #[Test]
    public function a_token_without_a_nonce_is_accepted_in_stateless_mode(): void
    {
        $this->seedJwks();

        $provider = $this->oidcProvider();
        $provider->stateless();

        // No session nonce exists to compare against, so requiring one would
        // make every stateless login fail.
        $payload = $provider->callDecodeJWT($this->idToken(['sub' => 'alice']));

        $this->assertSame('alice', $payload->sub);
    }

    #[Test]
    public function the_nonce_still_applies_to_the_normal_session_flow(): void
    {
        $provider = $this->oidcProvider();

        $this->assertTrue($provider->callUsesNonce());
        $this->assertArrayHasKey('nonce', $provider->callGetCodeFields('state'));
    }

    #[Test]
    public function the_nonce_can_be_disabled_explicitly(): void
    {
        $this->assertFalse($this->oidcProvider(['use_nonce' => false])->callUsesNonce());
        $this->assertFalse($this->oidcProvider(['use_nonce' => 'false'])->callUsesNonce());
        $this->assertTrue($this->oidcProvider(['use_nonce' => true])->callUsesNonce());
    }

    #[Test]
    public function the_nonce_can_be_disabled_via_the_fluent_setter(): void
    {
        $provider = $this->oidcProvider();

        $this->assertSame($provider, $provider->withoutNonce());
        $this->assertFalse($provider->callUsesNonce());
    }

    #[Test]
    public function a_session_less_redirect_reports_the_pkce_requirement_clearly(): void
    {
        // Request::create() leaves no session store on the request at all.
        // Socialite's PKCE support needs one, so the useful behaviour is a
        // clear error rather than an opaque "Session store not set on request."
        $provider = $this->oidcProvider(request: Request::create(self::REDIRECT, 'GET'));
        $provider->stateless();

        $this->expectExceptionMessage('PKCE requires a session');

        $provider->redirect();
    }

    #[Test]
    public function a_stateless_redirect_works_without_a_session_once_pkce_is_disabled(): void
    {
        $provider = $this->oidcProvider(request: Request::create(self::REDIRECT, 'GET'));
        $provider->stateless()->withoutPKCE();

        $url = $provider->redirect()->getTargetUrl();
        parse_str(parse_url($url, PHP_URL_QUERY) ?: '', $query);

        $this->assertStringStartsWith(self::BASE_URL.'/authorize', $url);
        $this->assertArrayNotHasKey('nonce', $query);
        $this->assertArrayNotHasKey('state', $query);
        $this->assertArrayNotHasKey('code_challenge', $query);
    }

    #[Test]
    public function a_stateful_flow_without_a_session_still_fails_on_the_state(): void
    {
        // Not stateless, so the `state` write comes first and Socialite's own
        // error stands: a stateful flow genuinely cannot work without a
        // session, and the PKCE guard is not what should be reported.
        $provider = $this->oidcProvider(request: Request::create(self::REDIRECT, 'GET'));

        $this->expectExceptionMessage('Session store not set on request.');

        $provider->redirect();
    }
}
