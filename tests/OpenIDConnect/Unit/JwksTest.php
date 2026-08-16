<?php

namespace SocialiteProviders\Tests\OpenIDConnect\Unit;

use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;
use SocialiteProviders\Tests\OpenIDConnect\Support\InteractsWithOidc;
use SocialiteProviders\Tests\OpenIDConnect\TestCase;

class JwksTest extends TestCase
{
    use InteractsWithOidc;

    public function test_the_jwks_is_cached_across_logins(): void
    {
        $provider = $this->makeProvider([], $this->happyPathResponses());
        $provider->user();

        $request = $this->callbackRequest();
        $second = $this->makeProvider([], [
            $this->tokenEndpointResponse($this->encodeToken($this->idTokenClaims())),
        ], $request);

        $this->assertSame('user-123', $second->user()->getId());
        $this->assertSame(['POST /token'], $this->requestedPaths());
    }

    public function test_a_token_without_a_kid_verifies_against_the_jwks(): void
    {
        $token = $this->encodeTokenWithoutKid($this->idTokenClaims());

        $provider = $this->makeProvider([], [
            $this->jsonResponse($this->discoveryDocument()),
            $this->tokenEndpointResponse($token),
            $this->jsonResponse($this->jwksDocument([$this->jwk('kid-1', 1)])),
        ], $this->callbackRequest());

        $this->assertSame('user-123', $provider->user()->getId());
    }

    public function test_a_token_without_a_kid_still_survives_key_rotation(): void
    {
        $token = $this->encodeTokenWithoutKid($this->idTokenClaims(), slot: 2);

        $provider = $this->makeProvider([], [
            $this->jsonResponse($this->discoveryDocument()),
            $this->tokenEndpointResponse($token),
            $this->jsonResponse($this->jwksDocument([$this->jwk('kid-1', 1)])),
            $this->jsonResponse($this->jwksDocument([$this->jwk('kid-2', 2)])),
        ], $this->callbackRequest());

        $this->assertSame('user-123', $provider->user()->getId());
        $this->assertSame(
            ['GET /.well-known/openid-configuration', 'POST /token', 'GET /jwks', 'GET /jwks'],
            $this->requestedPaths()
        );
    }

    public function test_a_kid_less_token_signed_by_a_foreign_key_is_rejected(): void
    {
        $forged = $this->encodeTokenWithoutKid($this->idTokenClaims(), slot: 2);

        $provider = $this->makeProvider([], [
            $this->jsonResponse($this->discoveryDocument()),
            $this->tokenEndpointResponse($forged),
            $this->jsonResponse($this->jwksDocument([$this->jwk('kid-1', 1)])),
            $this->jsonResponse($this->jwksDocument([$this->jwk('kid-1', 1)])),
        ], $this->callbackRequest());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('no key in the JWKS verifies this token');

        $provider->user();
    }

    public function test_a_claim_failure_on_a_kid_less_token_is_reported_as_itself(): void
    {
        $expired = $this->encodeTokenWithoutKid($this->idTokenClaims(['exp' => time() - 6000]));

        $provider = $this->makeProvider([], [
            $this->jsonResponse($this->discoveryDocument()),
            $this->tokenEndpointResponse($expired),
            $this->jsonResponse($this->jwksDocument([$this->jwk('kid-1', 1)])),
        ], $this->callbackRequest());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expired token');

        $provider->user();
    }

    public function test_an_unknown_kid_triggers_a_jwks_refetch_so_key_rotation_works(): void
    {
        // The cached JWKS only knows kid-1; the OP has rotated to kid-2.
        $rotated = $this->encodeToken($this->idTokenClaims(), kid: 'kid-2', slot: 2);

        $provider = $this->makeProvider([], [
            $this->jsonResponse($this->discoveryDocument()),
            $this->tokenEndpointResponse($rotated),
            $this->jsonResponse($this->jwksDocument([$this->jwk('kid-1', 1)])),
            $this->jsonResponse($this->jwksDocument([
                $this->jwk('kid-1', 1),
                $this->jwk('kid-2', 2),
            ])),
        ]);

        $this->assertSame('user-123', $provider->user()->getId());

        $this->assertSame(2, count(array_filter(
            $this->requestedPaths(),
            static fn (string $path) => $path === 'GET /jwks',
        )));
    }

    public function test_the_rotation_refetch_sends_no_cache_headers(): void
    {
        $rotated = $this->encodeToken($this->idTokenClaims(), kid: 'kid-2', slot: 2);

        $provider = $this->makeProvider([], [
            $this->jsonResponse($this->discoveryDocument()),
            $this->tokenEndpointResponse($rotated),
            $this->jsonResponse($this->jwksDocument([$this->jwk('kid-1', 1)])),
            $this->jsonResponse($this->jwksDocument([$this->jwk('kid-2', 2)])),
        ]);
        $provider->user();

        [$first, $second] = array_values(array_filter(
            $this->httpHistory,
            static fn (array $entry) => $entry['request']->getUri()->getPath() === '/jwks',
        ));

        $this->assertFalse($first['request']->hasHeader('Cache-Control'));
        $this->assertSame('no-cache', $second['request']->getHeaderLine('Cache-Control'));
        $this->assertSame('no-cache', $second['request']->getHeaderLine('Pragma'));
    }

    public function test_a_known_kid_does_not_refetch_the_jwks(): void
    {
        $provider = $this->makeProvider([], $this->happyPathResponses());

        $provider->user();

        $this->assertSame(1, count(array_filter(
            $this->requestedPaths(),
            static fn (string $path) => $path === 'GET /jwks',
        )));
    }

    public function test_a_kid_still_missing_after_the_refetch_fails(): void
    {
        $rotated = $this->encodeToken($this->idTokenClaims(), kid: 'kid-99', slot: 2);

        $provider = $this->makeProvider([], [
            $this->jsonResponse($this->discoveryDocument()),
            $this->tokenEndpointResponse($rotated),
            $this->jsonResponse($this->jwksDocument([$this->jwk('kid-1', 1)])),
            $this->jsonResponse($this->jwksDocument([$this->jwk('kid-1', 1)])),
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Verification failed');

        $provider->user();
    }

    public function test_missing_jwks_uri_in_discovery_is_reported(): void
    {
        $doc = $this->discoveryDocument();
        unset($doc['jwks_uri']);

        $provider = $this->makeProvider([], [
            $this->jsonResponse($doc),
            $this->tokenEndpointResponse($this->encodeToken($this->idTokenClaims())),
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('JWKS URI not found');

        $provider->user();
    }

    public function test_jwks_cache_key_is_scoped_to_the_issuer(): void
    {
        $provider = $this->makeProvider([], $this->happyPathResponses());
        $provider->user();

        $this->assertTrue(Cache::has('openidconnect_jwks_'.md5(static::$opBaseUrl)));
    }
}
