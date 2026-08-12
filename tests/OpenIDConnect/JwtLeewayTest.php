<?php

namespace SocialiteProviders\Tests\OpenIDConnect;

use Firebase\JWT\JWT;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;

/**
 * JWT::$leeway is a global static in firebase/php-jwt. The provider sets it
 * from `clock_skew` before decoding, so it has to put it back afterwards --
 * otherwise this provider's skew tolerance silently applies to every later JWT
 * validation in the process, which in a long-running worker (Octane,
 * RoadRunner, queues) means for the life of the worker.
 */
class JwtLeewayTest extends TestCase
{
    private int $originalLeeway;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalLeeway = JWT::$leeway;
    }

    protected function tearDown(): void
    {
        JWT::$leeway = $this->originalLeeway;

        parent::tearDown();
    }

    #[Test]
    public function leeway_is_restored_after_a_successful_verification(): void
    {
        $this->seedJwks();

        JWT::$leeway = 0;

        $provider = $this->oidcProvider(['clock_skew' => 300]);

        $this->assertSame('user-1', $provider->callVerifyFromToken($this->idToken())->sub);
        $this->assertSame(0, JWT::$leeway);
    }

    #[Test]
    public function leeway_is_restored_after_a_failed_verification(): void
    {
        $this->seedJwks();

        JWT::$leeway = 0;

        $provider = $this->oidcProvider(['clock_skew' => 300]);

        // Same header and payload, so the kid still resolves; only the
        // signature is junk, which fails inside JWT::decode().
        $tampered = substr($this->idToken(), 0, -4).'AAAA';

        try {
            $provider->callVerifyFromToken($tampered);
            $this->fail('Expected verification to fail.');
        } catch (InvalidArgumentException) {
            // Expected.
        }

        $this->assertSame(0, JWT::$leeway);
    }

    #[Test]
    public function a_preexisting_leeway_is_preserved_rather_than_zeroed(): void
    {
        // A provider with no clock_skew configured sets the leeway to 0. If it
        // did not restore, it would tighten validation for whoever set this.
        $this->seedJwks();

        JWT::$leeway = 60;

        $provider = $this->oidcProvider();

        $this->assertSame('user-1', $provider->callVerifyFromToken($this->idToken())->sub);
        $this->assertSame(60, JWT::$leeway);
    }

    #[Test]
    public function clock_skew_still_applies_during_the_decode(): void
    {
        // Restoring the value must not defeat the setting: a token that expired
        // within the configured skew is still accepted.
        $this->seedJwks();

        $provider = $this->oidcProvider(['clock_skew' => 300]);

        $token = $this->idToken(['exp' => time() - 60]);

        $this->assertSame('user-1', $provider->callVerifyFromToken($token)->sub);
    }
}
