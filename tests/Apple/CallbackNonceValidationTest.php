<?php

namespace SocialiteProviders\Tests\Apple;

use GuzzleHttp\Psr7\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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

    public function test_managed_stateless_caches_the_nonce_under_the_state(): void
    {
        $request = $this->makeRequestWithSession();

        $response = $this->makeAppleProvider($request)->stateless()->statelessNonce()->redirect();

        $params = $this->queryParams($response->getTargetUrl());

        $this->assertNotEmpty($params['state']);
        $this->assertNotEmpty($params['nonce']);
        $this->assertSame(
            $params['nonce'],
            Cache::get('socialite:apple:nonce:'.$params['state'])
        );
    }

    public function test_managed_stateless_callback_verifies_and_consumes_the_cached_nonce(): void
    {
        $state = 'echoed-state';
        Cache::put('socialite:apple:nonce:'.$state, self::NONCE, 600);

        $request = $this->makeRequestWithSession([
            'code'  => 'authorization-code',
            'state' => $state,
        ]);

        $user = $this->providerReturning($request, ['nonce' => self::NONCE])
            ->stateless()
            ->statelessNonce()
            ->user();

        $this->assertSame('apple-user-id', $user->getId());
        $this->assertNull(Cache::get('socialite:apple:nonce:'.$state));
    }

    public function test_managed_stateless_callback_rejects_an_unknown_state(): void
    {
        $request = $this->makeRequestWithSession([
            'code'  => 'authorization-code',
            'state' => 'never-issued',
        ]);

        $this->expectException(InvalidStateException::class);

        $this->providerReturning($request, ['nonce' => self::NONCE])
            ->stateless()
            ->statelessNonce()
            ->user();
    }

    public function test_managed_stateless_callback_rejects_a_replayed_state(): void
    {
        $state = 'echoed-state';
        Cache::put('socialite:apple:nonce:'.$state, self::NONCE, 600);

        $request = fn () => $this->makeRequestWithSession([
            'code'  => 'authorization-code',
            'state' => $state,
        ]);

        $this->providerReturning($request(), ['nonce' => self::NONCE])
            ->stateless()->statelessNonce()->user();

        // Second use of the same state must fail: the nonce was consumed.
        $this->expectException(InvalidStateException::class);

        $this->providerReturning($request(), ['nonce' => self::NONCE])
            ->stateless()->statelessNonce()->user();
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
