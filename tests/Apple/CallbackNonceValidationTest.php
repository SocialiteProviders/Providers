<?php

namespace SocialiteProviders\Tests\Apple;

use GuzzleHttp\Psr7\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Laravel\Socialite\Two\InvalidStateException;
use SocialiteProviders\Apple\Provider;
use Symfony\Component\HttpFoundation\Cookie;

class CallbackNonceValidationTest extends TestCase
{
    private const STATE = 'expected-state';

    private const NONCE = 'expected-nonce';

    public function test_callback_whose_token_nonce_matches_is_accepted(): void
    {
        $request = $this->callbackRequest(['state' => self::STATE, 'nonce' => self::NONCE]);

        $user = $this->providerReturning($request, ['nonce' => self::NONCE])->user();

        $this->assertSame('apple-user-id', $user->getId());
    }

    public function test_callback_whose_token_nonce_differs_is_rejected(): void
    {
        $request = $this->callbackRequest(['state' => self::STATE, 'nonce' => self::NONCE]);

        $this->expectException(InvalidStateException::class);

        $this->providerReturning($request, ['nonce' => 'nonce-from-another-request'])->user();
    }

    public function test_callback_whose_token_has_no_nonce_is_rejected(): void
    {
        $request = $this->callbackRequest(['state' => self::STATE, 'nonce' => self::NONCE]);

        $this->expectException(InvalidStateException::class);

        $this->providerReturning($request)->user();
    }

    public function test_nonce_is_consumed_by_the_callback(): void
    {
        $request = $this->callbackRequest(['state' => self::STATE, 'nonce' => self::NONCE]);

        $this->providerReturning($request, ['nonce' => self::NONCE])->user();

        $this->assertFalse($request->session()->has('nonce'));
        $this->assertFalse($request->session()->has('state'));
    }

    public function test_callback_with_state_but_no_session_nonce_is_rejected(): void
    {
        $request = $this->callbackRequest(['state' => self::STATE]);

        $this->expectException(InvalidStateException::class);

        $this->providerReturning($request, ['nonce' => 'whatever-apple-sent'])->user();
    }

    public function test_stateless_callback_without_a_nonce_is_refused(): void
    {
        $request = $this->makeRequestWithSession(['code' => 'authorization-code']);

        $this->expectException(InvalidStateException::class);

        $this->providerReturning($request, ['nonce' => 'anything'])->stateless()->user();
    }

    public function test_stateless_callback_verifies_the_supplied_nonce(): void
    {
        $request = $this->makeRequestWithSession(['code' => 'authorization-code']);

        $user = $this->providerReturning($request, ['nonce' => self::NONCE])
            ->stateless()
            ->setNonce(self::NONCE)
            ->user();

        $this->assertSame('apple-user-id', $user->getId());
    }

    public function test_stateless_callback_rejects_a_mismatched_supplied_nonce(): void
    {
        $request = $this->makeRequestWithSession(['code' => 'authorization-code']);

        $this->expectException(InvalidStateException::class);

        $this->providerReturning($request, ['nonce' => 'a-different-nonce'])
            ->stateless()
            ->setNonce(self::NONCE)
            ->user();
    }

    public function test_managed_stateless_redirect_sets_an_encrypted_nonce_cookie(): void
    {
        [$params, $cookie] = $this->statelessRedirect();

        $this->assertNotEmpty($params['nonce']);

        // The cookie carries the nonce encrypted, not in plain text.
        $this->assertNotSame($params['nonce'], $cookie->getValue());
        $this->assertSame($params['nonce'], Crypt::decryptString($cookie->getValue()));

        // It must cross Apple's cross-site form_post and stay TLS-only.
        $this->assertSame('none', $cookie->getSameSite());
        $this->assertTrue($cookie->isSecure());
        $this->assertTrue($cookie->isHttpOnly());
    }

    public function test_managed_stateless_callback_from_the_same_browser_is_accepted(): void
    {
        [$params, $cookie] = $this->statelessRedirect();

        $user = $this->statelessCallback($params['nonce'], $cookie->getValue())->user();

        $this->assertSame('apple-user-id', $user->getId());
    }

    public function test_managed_stateless_callback_from_another_browser_is_rejected(): void
    {
        // Attacker runs the flow in their browser and captures a full callback,
        // but the victim's browser does not carry the attacker's nonce cookie.
        [$params] = $this->statelessRedirect();

        $this->expectException(InvalidStateException::class);

        $this->statelessCallback($params['nonce'], null)->user();
    }

    public function test_managed_stateless_callback_rejects_a_forged_cookie(): void
    {
        [$params] = $this->statelessRedirect();

        // A cookie the app did not sign fails to decrypt and is rejected.
        $this->expectException(InvalidStateException::class);

        $this->statelessCallback($params['nonce'], 'not-a-valid-encrypted-value')->user();
    }

    public function test_managed_stateless_callback_rejects_a_cookie_for_a_different_nonce(): void
    {
        $this->statelessRedirect();

        // Valid, app-signed cookie, but it does not match the token's nonce.
        $this->expectException(InvalidStateException::class);

        $this->statelessCallback('token-nonce', Crypt::encryptString('a-different-nonce'))->user();
    }

    /**
     * Run a managed-stateless redirect and return its query params and cookie.
     *
     * @return array{0: array<string, string>, 1: Cookie}
     */
    private function statelessRedirect(): array
    {
        $response = $this->makeAppleProvider($this->makeRequestWithSession())
            ->stateless()
            ->statelessNonce()
            ->redirect();

        $cookie = collect($response->headers->getCookies())
            ->first(fn ($c) => $c->getName() === 'socialite_apple_nonce');

        $this->assertNotNull($cookie, 'redirect did not set the nonce cookie');

        return [$this->queryParams($response->getTargetUrl()), $cookie];
    }

    /**
     * A managed-stateless callback provider carrying the given cookie, whose
     * token echoes $tokenNonce.
     */
    private function statelessCallback(string $tokenNonce, ?string $cookie): Provider
    {
        $request = $this->makeRequestWithSession(['code' => 'authorization-code']);

        if ($cookie !== null) {
            $request->cookies->set('socialite_apple_nonce', $cookie);
        }

        $provider = $this->makeAppleProvider($request);

        $provider->setHttpClient($this->makeHttpClient([
            new Response(200, [], json_encode([
                'id_token' => $this->identityToken(['nonce' => $tokenNonce]),
            ])),
        ]));

        return $provider->stateless()->statelessNonce();
    }

    /**
     * @param  array<string, string>  $session
     */
    private function callbackRequest(array $session): Request
    {
        return $this->makeRequestWithSession([
            'code'  => 'authorization-code',
            'state' => self::STATE,
        ], $session);
    }

    /**
     * A provider whose token exchange returns an identity token with the given claims.
     *
     * @param  array<string, mixed>  $claims
     */
    private function providerReturning(Request $request, array $claims = []): Provider
    {
        $provider = $this->makeAppleProvider($request);

        $provider->setHttpClient($this->makeHttpClient([
            new Response(200, [], json_encode([
                'id_token' => $this->identityToken($claims),
            ])),
        ]));

        return $provider;
    }
}
