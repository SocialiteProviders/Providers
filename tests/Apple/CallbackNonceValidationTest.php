<?php

namespace SocialiteProviders\Tests\Apple;

use GuzzleHttp\Psr7\Response;
use Illuminate\Http\Request;
use Laravel\Socialite\Two\InvalidStateException;
use SocialiteProviders\Apple\Provider;

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

    public function test_nonce_is_not_checked_when_the_session_never_issued_one(): void
    {
        $request = $this->callbackRequest(['state' => self::STATE]);

        $user = $this->providerReturning($request, ['nonce' => 'whatever-apple-sent'])->user();

        $this->assertSame('apple-user-id', $user->getId());
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
