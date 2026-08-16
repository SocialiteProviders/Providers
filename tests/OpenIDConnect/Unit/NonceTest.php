<?php

namespace SocialiteProviders\Tests\OpenIDConnect\Unit;

use InvalidArgumentException;
use SocialiteProviders\OpenIDConnect\Provider;
use SocialiteProviders\Tests\OpenIDConnect\Support\InteractsWithOidc;
use SocialiteProviders\Tests\OpenIDConnect\TestCase;

class NonceTest extends TestCase
{
    use InteractsWithOidc;

    public function test_a_token_with_a_mismatched_nonce_is_rejected(): void
    {
        $claims = $this->idTokenClaims(['nonce' => 'a-different-nonce']);

        $provider = $this->makeProvider([], $this->happyPathResponses($claims));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('invalid nonce');

        $provider->user();
    }

    public function test_a_token_without_a_nonce_is_rejected_when_one_was_sent(): void
    {
        $claims = $this->idTokenClaims();
        unset($claims['nonce']);

        $provider = $this->makeProvider([], $this->happyPathResponses($claims));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('invalid nonce');

        $provider->user();
    }

    public function test_the_nonce_is_cleared_after_a_successful_login(): void
    {
        $request = $this->callbackRequest();

        $provider = $this->makeProvider([], $this->happyPathResponses(), $request);
        $provider->user();

        $this->assertNull($request->session()->get(Provider::NONCE_SESSION_KEY));
    }

    public function test_a_replayed_id_token_fails_once_the_nonce_is_consumed(): void
    {
        $request = $this->callbackRequest();
        $idToken = $this->encodeToken($this->idTokenClaims());

        $provider = $this->makeProvider([], [
            $this->jsonResponse($this->discoveryDocument()),
            $this->tokenEndpointResponse($idToken),
            $this->jsonResponse($this->jwksDocument()),
        ], $request);

        $provider->user();

        // Same session, same id_token, fresh provider -- as in a replayed
        // callback. State is re-primed to isolate the nonce check.
        $request->session()->put('state', static::$opState);

        $replay = $this->makeProvider([], [
            $this->tokenEndpointResponse($idToken),
        ], $request);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('invalid nonce');

        $replay->user();
    }

    public function test_use_nonce_false_accepts_a_token_without_a_nonce(): void
    {
        $claims = $this->idTokenClaims();
        unset($claims['nonce']);

        $provider = $this->makeProvider(['use_nonce' => false], $this->happyPathResponses($claims));

        $this->assertSame('user-123', $provider->user()->getId());
    }

    public function test_stateless_mode_skips_nonce_validation(): void
    {
        $claims = $this->idTokenClaims();
        unset($claims['nonce']);

        // PKCE must also be opted out: its verifier lives in the session.
        $provider = $this->makeProvider(
            [],
            $this->happyPathResponses($claims),
            $this->callbackRequest(withSession: false),
        );

        $this->assertSame('user-123', $provider->stateless()->withoutPKCE()->user()->getId());
    }
}
