<?php

namespace SocialiteProviders\Tests\OpenIDConnect\Unit;

use Firebase\JWT\JWT;
use InvalidArgumentException;
use SocialiteProviders\Tests\OpenIDConnect\Support\InteractsWithOidc;
use SocialiteProviders\Tests\OpenIDConnect\TestCase;

class IdTokenVerificationTest extends TestCase
{
    use InteractsWithOidc;

    public function test_a_validly_signed_id_token_produces_a_mapped_user(): void
    {
        $provider = $this->makeProvider([], $this->happyPathResponses());

        $user = $provider->user();

        $this->assertSame('user-123', $user->getId());
        $this->assertSame('user@example.com', $user->getEmail());
        $this->assertSame('Test User', $user->getName());
        $this->assertSame('the-access-token', $user->token);
        $this->assertSame('the-refresh-token', $user->refreshToken);
        $this->assertSame(3600, $user->expiresIn);
        $this->assertSame(['openid', 'email', 'profile'], $user->approvedScopes);
        $this->assertArrayHasKey('id_token', $user->accessTokenResponseBody);

        // No userinfo call: the id_token already carried an email.
        $this->assertSame([
            'GET /.well-known/openid-configuration',
            'POST /token',
            'GET /jwks',
        ], $this->requestedPaths());
    }

    public function test_alg_none_is_always_rejected(): void
    {
        // Advertise `none` support and even pin it in config: it must still lose.
        $token = $this->unsignedToken($this->idTokenClaims(), ['alg' => 'none', 'typ' => 'JWT']);

        $provider = $this->makeProvider(['jwt_algorithm' => 'none'], [
            $this->jsonResponse($this->discoveryDocument([
                'id_token_signing_alg_values_supported' => ['none', 'RS256'],
            ])),
            $this->tokenEndpointResponse($token),
            $this->jsonResponse($this->jwksDocument()),
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Disallowed signing algorithm [none]');

        $provider->user();
    }

    public function test_algorithm_not_on_the_allow_list_is_rejected(): void
    {
        $token = $this->unsignedToken($this->idTokenClaims(), ['alg' => 'ES256', 'typ' => 'JWT']);

        $provider = $this->makeProvider([], [
            $this->jsonResponse($this->discoveryDocument()),
            $this->tokenEndpointResponse($token),
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Disallowed signing algorithm [ES256]');

        $provider->user();
    }

    public function test_a_token_with_no_alg_header_is_rejected(): void
    {
        $token = $this->unsignedToken($this->idTokenClaims(), ['typ' => 'JWT']);

        $provider = $this->makeProvider([], [
            $this->jsonResponse($this->discoveryDocument()),
            $this->tokenEndpointResponse($token),
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Disallowed signing algorithm [missing]');

        $provider->user();
    }

    public function test_a_tampered_signature_is_rejected(): void
    {
        $token = $this->encodeToken($this->idTokenClaims());

        [$header, , $signature] = explode('.', $token);
        $forgedPayload = static::base64Url(json_encode($this->idTokenClaims(['sub' => 'admin'])));
        $forged = $header.'.'.$forgedPayload.'.'.$signature;

        $provider = $this->makeProvider([], [
            $this->jsonResponse($this->discoveryDocument()),
            $this->tokenEndpointResponse($forged),
            $this->jsonResponse($this->jwksDocument()),
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Verification failed');

        $provider->user();
    }

    public function test_hmac_algorithms_are_rejected_against_a_jwks(): void
    {
        // Key confusion: HMAC-signing with public material.
        $token = JWT::encode($this->idTokenClaims(), str_repeat('shared-secret!', 4), 'HS256');

        $provider = $this->makeProvider(['jwt_algorithm' => 'HS256'], [
            $this->jsonResponse($this->discoveryDocument()),
            $this->tokenEndpointResponse($token),
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('HMAC algorithms cannot be verified against a JWKS');

        $provider->user();
    }

    public function test_hmac_algorithms_are_rejected_with_an_asymmetric_public_key(): void
    {
        // RFC 8725 2.1: HS256 with the (public!) PEM as the MAC secret.
        $publicPem = static::rsaKey()['public'];
        $token = JWT::encode($this->idTokenClaims(), $publicPem, 'HS256');

        $provider = $this->makeProvider([
            'jwt_algorithm'  => 'HS256',
            'jwt_public_key' => $publicPem,
        ], [
            $this->jsonResponse($this->discoveryDocument()),
            $this->tokenEndpointResponse($token),
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('HMAC algorithms cannot be used with an asymmetric public key');

        $provider->user();
    }

    public function test_a_pinned_jwt_algorithm_overrides_the_discovery_document(): void
    {
        $provider = $this->makeProvider(['jwt_algorithm' => 'RS256'], [
            $this->jsonResponse($this->discoveryDocument([
                'id_token_signing_alg_values_supported' => ['ES256'],
            ])),
            $this->tokenEndpointResponse($this->encodeToken($this->idTokenClaims())),
            $this->jsonResponse($this->jwksDocument()),
        ]);

        $this->assertSame('user-123', $provider->user()->getId());
    }

    public function test_missing_advertised_algorithms_fall_back_to_rs256(): void
    {
        $provider = $this->makeProvider([], [
            $this->jsonResponse($this->discoveryDocument([
                'id_token_signing_alg_values_supported' => null,
            ])),
            $this->tokenEndpointResponse($this->encodeToken($this->idTokenClaims())),
            $this->jsonResponse($this->jwksDocument()),
        ]);

        $this->assertSame('user-123', $provider->user()->getId());
    }

    public function test_a_pem_public_key_verifies_without_touching_the_jwks(): void
    {
        $provider = $this->makeProvider([
            'jwt_public_key' => static::rsaKey()['public'],
        ], [
            $this->jsonResponse($this->discoveryDocument()),
            $this->tokenEndpointResponse($this->encodeToken($this->idTokenClaims())),
        ]);

        $user = $provider->user();

        $this->assertSame('user-123', $user->getId());
        $this->assertNotContains('GET /jwks', $this->requestedPaths());
    }

    public function test_verify_jwt_false_skips_the_signature_but_not_the_claims(): void
    {
        $provider = $this->makeProvider(['verify_jwt' => false], [
            $this->jsonResponse($this->discoveryDocument()),
            $this->tokenEndpointResponse($this->unsignedToken($this->idTokenClaims())),
        ]);

        $this->assertSame('user-123', $provider->user()->getId());
        $this->assertNotContains('GET /jwks', $this->requestedPaths());
    }

    public function test_verify_jwt_false_still_rejects_an_expired_token(): void
    {
        $claims = $this->idTokenClaims(['exp' => time() - 60]);

        $provider = $this->makeProvider(['verify_jwt' => false], [
            $this->jsonResponse($this->discoveryDocument()),
            $this->tokenEndpointResponse($this->unsignedToken($claims)),
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('expired');

        $provider->user();
    }

    public function test_verify_jwt_false_still_rejects_a_wrong_audience(): void
    {
        $claims = $this->idTokenClaims(['aud' => 'someone-else']);

        $provider = $this->makeProvider(['verify_jwt' => false], [
            $this->jsonResponse($this->discoveryDocument()),
            $this->tokenEndpointResponse($this->unsignedToken($claims)),
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid audience');

        $provider->user();
    }

    public function test_malformed_token_with_two_segments_is_rejected(): void
    {
        $provider = $this->makeProvider([], [
            $this->jsonResponse($this->discoveryDocument()),
            $this->tokenEndpointResponse('only.twosegments'),
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('expected three segments');

        $provider->user();
    }

    public function test_malformed_base64_header_is_rejected(): void
    {
        $provider = $this->makeProvider([], [
            $this->jsonResponse($this->discoveryDocument()),
            $this->tokenEndpointResponse('!!!not-base64!!!.payload.signature'),
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Malformed base64url segment');

        $provider->user();
    }

    public function test_a_four_segment_token_is_rejected(): void
    {
        $provider = $this->makeProvider([], [
            $this->jsonResponse($this->discoveryDocument()),
            $this->tokenEndpointResponse($this->unsignedToken($this->idTokenClaims()).'.extra'),
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('expected three segments');

        $provider->user();
    }

    public function test_a_header_that_is_not_a_json_object_is_rejected(): void
    {
        $token = static::base64Url('[1,2]').'.'.static::base64Url('{}').'.sig';

        $provider = $this->makeProvider([], [
            $this->jsonResponse($this->discoveryDocument()),
            $this->tokenEndpointResponse($token),
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Failed to parse header');

        $provider->user();
    }

    public function test_an_out_of_alphabet_payload_is_rejected(): void
    {
        $token = static::base64Url(json_encode(['alg' => 'RS256'])).'.!!!.sig';

        $provider = $this->makeProvider(['verify_jwt' => false], [
            $this->jsonResponse($this->discoveryDocument()),
            $this->tokenEndpointResponse($token),
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Malformed base64url segment');

        $provider->user();
    }

    public function test_verify_jwt_as_a_falsy_string_also_opts_out(): void
    {
        // env('OIDC_VERIFY_JWT') style values arrive as strings.
        $provider = $this->makeProvider(['verify_jwt' => 'false'], [
            $this->jsonResponse($this->discoveryDocument()),
            $this->tokenEndpointResponse($this->unsignedToken($this->idTokenClaims())),
        ]);

        $this->assertSame('user-123', $provider->user()->getId());
        $this->assertNotContains('GET /jwks', $this->requestedPaths());
    }

    public function test_hs256_is_rejected_even_when_the_discovery_document_advertises_it(): void
    {
        // A compromised or misconfigured OP advertising HS256 must not turn
        // the (public) JWKS material into an acceptable MAC secret.
        $forged = JWT::encode($this->idTokenClaims(), static::rsaKey()['public'], 'HS256');

        $provider = $this->makeProvider([], [
            $this->jsonResponse($this->discoveryDocument([
                'id_token_signing_alg_values_supported' => ['RS256', 'HS256'],
            ])),
            $this->tokenEndpointResponse($forged),
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('HMAC algorithms cannot be verified against a JWKS');

        $provider->user();
    }
}
