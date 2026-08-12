<?php

namespace SocialiteProviders\Tests\OpenIDConnect;

use Firebase\JWT\JWT;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;

/**
 * Regression cover for the algorithm substitution attack (RFC 8725 section 2.1,
 * OIDC Core 3.1.3.7 step 7): the verification algorithm must be pinned by the
 * RP, never read from the token's own header.
 */
class JwtAlgorithmTest extends TestCase
{
    #[Test]
    public function legitimate_rs256_token_is_accepted_against_a_configured_public_key(): void
    {
        $provider = $this->oidcProvider(['jwt_public_key' => $this->publicKey]);

        $payload = $provider->callVerifyFromToken($this->idToken(['sub' => 'alice']));

        $this->assertSame('alice', $payload->sub);
    }

    #[Test]
    public function token_forged_by_hmac_signing_with_the_public_key_is_rejected(): void
    {
        $provider = $this->oidcProvider(['jwt_public_key' => $this->publicKey]);

        // The attacker holds no private key -- only the PEM public key, which
        // is not secret -- and HMAC-signs with it while claiming alg: HS256.
        $forged = JWT::encode($this->claims(['sub' => 'admin']), $this->publicKey, 'HS256', self::KID);

        $this->expectException(InvalidArgumentException::class);

        $provider->callVerifyFromToken($forged);
    }

    #[Test]
    public function forged_token_is_rejected_even_when_the_op_advertises_hs256(): void
    {
        // The allow list alone is not enough here: HS256 is legitimately
        // advertised, so the asymmetric-key guard has to catch it.
        $provider = $this->oidcProvider(
            ['jwt_public_key' => $this->publicKey],
            discovery: $this->discoveryDocument([
                'id_token_signing_alg_values_supported' => ['RS256', 'HS256'],
            ])
        );

        $forged = JWT::encode($this->claims(['sub' => 'admin']), $this->publicKey, 'HS256', self::KID);

        $this->expectException(InvalidArgumentException::class);

        $provider->callVerifyFromToken($forged);
    }

    #[Test]
    public function hmac_is_rejected_on_the_jwks_path(): void
    {
        $this->seedJwks();

        $provider = $this->oidcProvider(discovery: $this->discoveryDocument([
            'id_token_signing_alg_values_supported' => ['RS256', 'HS256'],
        ]));

        $forged = JWT::encode($this->claims(['sub' => 'admin']), str_repeat('a', 64), 'HS256', self::KID);

        $this->expectException(InvalidArgumentException::class);

        $provider->callVerifyFromToken($forged);
    }

    #[Test]
    public function legitimate_token_verifies_against_the_jwks(): void
    {
        $this->seedJwks();

        $provider = $this->oidcProvider();

        $payload = $provider->callVerifyFromToken($this->idToken(['sub' => 'alice']));

        $this->assertSame('alice', $payload->sub);
    }

    #[Test]
    public function algorithm_outside_the_allow_list_is_rejected(): void
    {
        $provider = $this->oidcProvider([
            'jwt_public_key' => $this->publicKey,
            'jwt_algorithm'  => 'RS256',
        ]);

        $rs512 = $this->idToken([], 'RS512');

        $this->expectException(InvalidArgumentException::class);

        $provider->callVerifyFromToken($rs512);
    }

    #[Test]
    public function configured_algorithm_list_is_honoured(): void
    {
        $provider = $this->oidcProvider([
            'jwt_public_key' => $this->publicKey,
            'jwt_algorithm'  => 'RS256, RS512',
        ]);

        $this->assertSame(['RS256', 'RS512'], $provider->callAllowedSigningAlgorithms());
        $this->assertSame('user-1', $provider->callVerifyFromToken($this->idToken([], 'RS512'))->sub);
    }

    #[Test]
    public function allowed_algorithms_fall_back_to_the_discovery_document(): void
    {
        $provider = $this->oidcProvider(discovery: $this->discoveryDocument([
            'id_token_signing_alg_values_supported' => ['ES256', 'RS256'],
        ]));

        $this->assertSame(['ES256', 'RS256'], $provider->callAllowedSigningAlgorithms());
    }

    #[Test]
    public function allowed_algorithms_fall_back_to_rs256(): void
    {
        $provider = $this->oidcProvider(discovery: $this->discoveryDocument([
            'id_token_signing_alg_values_supported' => [],
        ]));

        $this->assertSame(['RS256'], $provider->callAllowedSigningAlgorithms());
    }

    #[Test]
    public function alg_none_is_never_accepted(): void
    {
        $provider = $this->oidcProvider(
            ['jwt_algorithm' => 'none'],
            discovery: $this->discoveryDocument([
                'id_token_signing_alg_values_supported' => ['none'],
            ])
        );

        $this->assertSame(['RS256'], $provider->callAllowedSigningAlgorithms());
    }

    #[Test]
    public function unsigned_token_is_rejected(): void
    {
        $this->seedJwks();

        $provider = $this->oidcProvider();

        $header = $this->base64UrlEncode(json_encode(['alg' => 'none', 'typ' => 'JWT']));
        $body = $this->base64UrlEncode(json_encode($this->claims(['sub' => 'admin'])));

        $this->expectException(InvalidArgumentException::class);

        $provider->callVerifyFromToken($header.'.'.$body.'.');
    }
}
