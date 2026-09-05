<?php

namespace SocialiteProviders\Tests\Apple;

use Laravel\Socialite\Two\InvalidStateException;

class NonceValidationTest extends TestCase
{
    public function test_it_accepts_a_token_whose_nonce_matches(): void
    {
        $user = $this->makeAppleProvider()->userByIdentityToken(
            $this->identityToken(['nonce' => 'expected-nonce']),
            'expected-nonce'
        );

        $this->assertSame('apple-user-id', $user->getId());
    }

    public function test_it_rejects_a_token_whose_nonce_differs(): void
    {
        $this->expectException(InvalidStateException::class);

        $this->makeAppleProvider()->userByIdentityToken(
            $this->identityToken(['nonce' => 'some-other-nonce']),
            'expected-nonce'
        );
    }

    public function test_it_rejects_a_token_with_no_nonce_when_one_is_expected(): void
    {
        $this->expectException(InvalidStateException::class);

        $this->makeAppleProvider()->userByIdentityToken(
            $this->identityToken(),
            'expected-nonce'
        );
    }

    public function test_it_skips_nonce_verification_when_none_is_expected(): void
    {
        $user = $this->makeAppleProvider()->userByIdentityToken(
            $this->identityToken(['nonce' => 'some-nonce'])
        );

        $this->assertSame('apple-user-id', $user->getId());
    }
}
