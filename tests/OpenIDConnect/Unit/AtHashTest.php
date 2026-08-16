<?php

namespace SocialiteProviders\Tests\OpenIDConnect\Unit;

use InvalidArgumentException;
use ReflectionMethod;
use SocialiteProviders\Tests\OpenIDConnect\Support\InteractsWithOidc;
use SocialiteProviders\Tests\OpenIDConnect\TestCase;

class AtHashTest extends TestCase
{
    use InteractsWithOidc;

    public function test_a_correct_at_hash_is_accepted(): void
    {
        $claims = $this->idTokenClaims([
            'at_hash' => $this->atHashFor('the-access-token'),
        ]);

        $provider = $this->makeProvider([], $this->happyPathResponses($claims));

        $this->assertSame('user-123', $provider->user()->getId());
    }

    public function test_an_at_hash_for_a_different_access_token_is_rejected(): void
    {
        $claims = $this->idTokenClaims([
            'at_hash' => $this->atHashFor('a-stolen-access-token'),
        ]);

        $provider = $this->makeProvider([], $this->happyPathResponses($claims));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('at_hash mismatch');

        $provider->user();
    }

    public function test_at_hash_validation_fails_closed_on_an_unmappable_algorithm(): void
    {
        $provider = $this->makeProvider([], []);
        $validate = new ReflectionMethod($provider, 'validateAtHash');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot validate at_hash for algorithm [NOPE]');

        $validate->invoke($provider, 'any-hash', 'the-access-token', 'NOPE');
    }

    public function test_eddsa_at_hash_is_validated_with_sha512(): void
    {
        $provider = $this->makeProvider([], []);
        $validate = new ReflectionMethod($provider, 'validateAtHash');

        $validate->invoke($provider, $this->atHashFor('the-access-token', 'sha512'), 'the-access-token', 'EdDSA');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('at_hash mismatch');

        $validate->invoke($provider, $this->atHashFor('a-different-token', 'sha512'), 'the-access-token', 'EdDSA');
    }

    public function test_a_token_without_at_hash_is_tolerated(): void
    {
        // at_hash is OPTIONAL for the code flow (OIDC Core 3.1.3.6).
        $provider = $this->makeProvider([], $this->happyPathResponses());

        $this->assertSame('user-123', $provider->user()->getId());
    }

    public function test_at_hash_is_validated_in_the_unverified_path_too(): void
    {
        $claims = $this->idTokenClaims([
            'at_hash' => $this->atHashFor('a-stolen-access-token'),
        ]);

        $provider = $this->makeProvider(['verify_jwt' => false], [
            $this->jsonResponse($this->discoveryDocument()),
            $this->tokenEndpointResponse($this->unsignedToken($claims)),
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('at_hash mismatch');

        $provider->user();
    }
}
