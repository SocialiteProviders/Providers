<?php

namespace SocialiteProviders\Tests\Apple;

use Laravel\Socialite\Two\InvalidStateException;

class AppleProviderTest extends TestCase
{
    public function test_identity_token_with_matching_audience_is_accepted(): void
    {
        $user = $this->makeAppleProvider()->userByIdentityToken($this->identityToken());

        $this->assertSame('apple-user-id', $user->getId());
        $this->assertSame('user@example.com', $user->getEmail());
    }

    public function test_identity_token_with_different_audience_is_rejected(): void
    {
        $this->expectException(InvalidStateException::class);
        $this->expectExceptionMessage('The token is not allowed to be used by this audience');

        $this->makeAppleProvider()->userByIdentityToken(
            $this->identityToken(['aud' => 'another-client-id'])
        );
    }
}
