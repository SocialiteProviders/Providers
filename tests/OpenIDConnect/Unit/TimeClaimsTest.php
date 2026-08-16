<?php

namespace SocialiteProviders\Tests\OpenIDConnect\Unit;

use Firebase\JWT\JWT;
use InvalidArgumentException;
use SocialiteProviders\Tests\OpenIDConnect\Support\InteractsWithOidc;
use SocialiteProviders\Tests\OpenIDConnect\TestCase;

class TimeClaimsTest extends TestCase
{
    use InteractsWithOidc;

    public function test_a_token_without_exp_never_slips_through(): void
    {
        // firebase/php-jwt only checks exp when present, so a token that
        // omits it would otherwise be valid forever.
        $claims = $this->idTokenClaims();
        unset($claims['exp']);

        $provider = $this->makeProvider([], $this->happyPathResponses($claims));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required exp claim');

        $provider->user();
    }

    public function test_an_expired_token_is_rejected(): void
    {
        $claims = $this->idTokenClaims(['exp' => time() - 120]);

        $provider = $this->makeProvider([], $this->happyPathResponses($claims));

        try {
            $provider->user();
            $this->fail('Expected the expired token to be rejected.');
        } catch (InvalidArgumentException $e) {
            $this->assertSame(401, $e->getCode());
            $this->assertStringContainsStringIgnoringCase('expired', $e->getMessage());
        }
    }

    public function test_clock_skew_leeway_saves_a_slightly_expired_token(): void
    {
        $claims = $this->idTokenClaims(['exp' => time() - 30]);

        $provider = $this->makeProvider(['clock_skew' => 120], $this->happyPathResponses($claims));

        $this->assertSame('user-123', $provider->user()->getId());
    }

    public function test_the_global_jwt_leeway_is_restored_after_verification(): void
    {
        // JWT::$leeway is a process-wide static, so a long-running worker
        // would otherwise inherit this provider's tolerance.
        $previous = JWT::$leeway;
        JWT::$leeway = 45;

        try {
            $provider = $this->makeProvider(['clock_skew' => 120], $this->happyPathResponses());
            $provider->user();

            $this->assertSame(45, JWT::$leeway);
        } finally {
            JWT::$leeway = $previous;
        }
    }

    public function test_the_global_jwt_leeway_is_restored_after_a_failed_verification(): void
    {
        $previous = JWT::$leeway;
        JWT::$leeway = 45;

        try {
            $claims = $this->idTokenClaims(['exp' => time() - 6000]);
            $provider = $this->makeProvider([], $this->happyPathResponses($claims));

            try {
                $provider->user();
                $this->fail('Expected the expired token to be rejected.');
            } catch (InvalidArgumentException) {
                // The restoration, not the rejection, is what is under test.
            }

            $this->assertSame(45, JWT::$leeway);
        } finally {
            JWT::$leeway = $previous;
        }
    }

    public function test_a_not_yet_valid_nbf_is_rejected(): void
    {
        $claims = $this->idTokenClaims(['nbf' => time() + 600]);

        $provider = $this->makeProvider([], $this->happyPathResponses($claims));

        try {
            $provider->user();
            $this->fail('Expected the not-yet-valid token to be rejected.');
        } catch (InvalidArgumentException $e) {
            $this->assertSame(401, $e->getCode());
        }
    }

    public function test_an_iat_from_the_future_is_rejected(): void
    {
        $claims = $this->idTokenClaims(['iat' => time() + 600]);

        $provider = $this->makeProvider([], $this->happyPathResponses($claims));

        try {
            $provider->user();
            $this->fail('Expected the future-issued token to be rejected.');
        } catch (InvalidArgumentException $e) {
            $this->assertSame(401, $e->getCode());
        }
    }

    public function test_clock_skew_leeway_saves_a_slightly_future_iat(): void
    {
        $claims = $this->idTokenClaims(['iat' => time() + 30, 'nbf' => time() + 30]);

        $provider = $this->makeProvider(['clock_skew' => 120], $this->happyPathResponses($claims));

        $this->assertSame('user-123', $provider->user()->getId());
    }

    public function test_a_non_numeric_exp_is_rejected(): void
    {
        // PHP 8 compares int >= non-numeric-string as strings, so a bogus
        // exp would sail through a bare comparison. The unverified path is
        // the one that leans on our own check rather than php-jwt's.
        $claims = $this->idTokenClaims(['exp' => 'soon']);

        $provider = $this->makeProvider(['verify_jwt' => false], [
            $this->jsonResponse($this->discoveryDocument()),
            $this->tokenEndpointResponse($this->unsignedToken($claims)),
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('exp');

        $provider->user();
    }
}
