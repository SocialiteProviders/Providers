<?php

namespace SocialiteProviders\Tests\OpenIDConnect\Unit;

use InvalidArgumentException;
use SocialiteProviders\OpenIDConnect\Provider;
use SocialiteProviders\Tests\OpenIDConnect\Support\InteractsWithOidc;
use SocialiteProviders\Tests\OpenIDConnect\TestCase;

class RedirectFlowTest extends TestCase
{
    use InteractsWithOidc;

    public function test_redirect_carries_state_nonce_and_pkce_challenge(): void
    {
        $request = $this->redirectRequest();
        $provider = $this->makeProvider([], [
            $this->jsonResponse($this->discoveryDocument()),
        ], $request);

        $url = $provider->redirect()->getTargetUrl();
        $query = $this->queryOf($url);

        $this->assertStringStartsWith(static::$opBaseUrl.'/authorize?', $url);
        $this->assertSame('code', $query['response_type']);
        $this->assertSame(static::$opClientId, $query['client_id']);
        $this->assertSame(static::$opRedirect, $query['redirect_uri']);

        // State, nonce and the PKCE verifier survive in the session for the callback.
        $this->assertSame($request->session()->get('state'), $query['state']);
        $this->assertSame($request->session()->get(Provider::NONCE_SESSION_KEY), $query['nonce']);
        $this->assertNotEmpty($request->session()->get('code_verifier'));

        // PKCE is on by default.
        $this->assertSame('S256', $query['code_challenge_method']);
        $this->assertNotEmpty($query['code_challenge']);
    }

    public function test_default_scopes_are_openid_email_profile(): void
    {
        $provider = $this->makeProvider([], [
            $this->jsonResponse($this->discoveryDocument()),
        ], $this->redirectRequest());

        $query = $this->queryOf($provider->redirect()->getTargetUrl());

        $this->assertSame('openid email profile', $query['scope']);
    }

    public function test_a_fragment_on_the_authorization_endpoint_stays_behind_the_query(): void
    {
        $provider = $this->makeProvider([], [
            $this->jsonResponse($this->discoveryDocument([
                'authorization_endpoint' => static::$opBaseUrl.'/authorize#done',
            ])),
        ], $this->redirectRequest());

        $url = $provider->redirect()->getTargetUrl();

        $this->assertStringStartsWith(static::$opBaseUrl.'/authorize?', $url);
        $this->assertStringEndsWith('#done', $url);
        $this->assertSame('code', $this->queryOf($url)['response_type']);
    }

    public function test_without_pkce_omits_the_challenge(): void
    {
        $request = $this->redirectRequest();
        $provider = $this->makeProvider([], [
            $this->jsonResponse($this->discoveryDocument()),
        ], $request);

        $query = $this->queryOf($provider->withoutPKCE()->redirect()->getTargetUrl());

        $this->assertArrayNotHasKey('code_challenge', $query);
        $this->assertArrayNotHasKey('code_challenge_method', $query);
        $this->assertNull($request->session()->get('code_verifier'));
    }

    public function test_without_nonce_omits_the_nonce(): void
    {
        $request = $this->redirectRequest();
        $provider = $this->makeProvider([], [
            $this->jsonResponse($this->discoveryDocument()),
        ], $request);

        $query = $this->queryOf($provider->withoutNonce()->redirect()->getTargetUrl());

        $this->assertArrayNotHasKey('nonce', $query);
        $this->assertNull($request->session()->get(Provider::NONCE_SESSION_KEY));
    }

    public function test_use_nonce_config_false_omits_the_nonce(): void
    {
        $provider = $this->makeProvider(['use_nonce' => false], [
            $this->jsonResponse($this->discoveryDocument()),
        ], $this->redirectRequest());

        $query = $this->queryOf($provider->redirect()->getTargetUrl());

        $this->assertArrayNotHasKey('nonce', $query);
    }

    public function test_pkce_without_a_session_is_refused_with_a_pointed_error(): void
    {
        $provider = $this->makeProvider([], [
            $this->jsonResponse($this->discoveryDocument()),
        ], $this->redirectRequest(withSession: false));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('PKCE requires a session');

        $provider->stateless()->redirect();
    }

    public function test_stateless_without_pkce_redirects_without_a_session(): void
    {
        $provider = $this->makeProvider([], [
            $this->jsonResponse($this->discoveryDocument()),
        ], $this->redirectRequest(withSession: false));

        $query = $this->queryOf($provider->stateless()->withoutPKCE()->redirect()->getTargetUrl());

        $this->assertArrayNotHasKey('state', $query);
        $this->assertArrayNotHasKey('nonce', $query);
        $this->assertArrayNotHasKey('code_challenge', $query);
        $this->assertSame('code', $query['response_type']);
    }

    public function test_authorization_endpoint_with_existing_query_is_extended_not_mangled(): void
    {
        $provider = $this->makeProvider([], [
            $this->jsonResponse($this->discoveryDocument([
                'authorization_endpoint' => static::$opBaseUrl.'/authorize?realm=prod',
            ])),
        ], $this->redirectRequest());

        $url = $provider->redirect()->getTargetUrl();

        $this->assertSame(1, substr_count($url, '?'));
        $query = $this->queryOf($url);
        $this->assertSame('prod', $query['realm']);
        $this->assertSame('code', $query['response_type']);
    }

    public function test_the_code_challenge_is_derived_from_the_stored_verifier(): void
    {
        $request = $this->redirectRequest();
        $provider = $this->makeProvider([], [
            $this->jsonResponse($this->discoveryDocument()),
        ], $request);

        $query = $this->queryOf($provider->redirect()->getTargetUrl());
        $verifier = $request->session()->get('code_verifier');

        $this->assertSame(
            static::base64Url(hash('sha256', $verifier, true)),
            $query['code_challenge'],
        );
    }
}
