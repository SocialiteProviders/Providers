<?php

namespace SocialiteProviders\Tests\Apple;

use Laravel\Socialite\Two\InvalidStateException;
use SocialiteProviders\Apple\Provider;
use SocialiteProviders\Manager\Config;

class IdentityTokenTest extends TestCase
{
    public function test_it_accepts_a_token_whose_audience_list_contains_the_client_id(): void
    {
        $user = $this->makeAppleProvider()->userByIdentityToken(
            $this->identityToken(['aud' => ['another-client-id', static::CLIENT_ID]])
        );

        $this->assertSame('apple-user-id', $user->getId());
    }

    public function test_it_rejects_a_token_whose_audience_list_omits_the_client_id(): void
    {
        $this->expectException(InvalidStateException::class);
        $this->expectExceptionMessage('The token is not allowed to be used by this audience');

        $this->makeAppleProvider()->userByIdentityToken(
            $this->identityToken(['aud' => ['another-client-id', 'a-third-client-id']])
        );
    }

    public function test_it_rejects_a_token_with_no_audience_claim(): void
    {
        $this->expectException(InvalidStateException::class);
        $this->expectExceptionMessage('The token is not allowed to be used by this audience');

        $this->makeAppleProvider()->userByIdentityToken(
            $this->identityTokenWithoutAudience()
        );
    }

    public function test_it_accepts_a_token_for_an_additional_audience(): void
    {
        $user = $this->providerWithAudiences(['com.example.app', 'com.example.app.macos'])
            ->userByIdentityToken($this->identityToken(['aud' => 'com.example.app.macos']));

        $this->assertSame('apple-user-id', $user->getId());
    }

    public function test_it_still_accepts_the_client_id_when_audiences_are_configured(): void
    {
        $user = $this->providerWithAudiences(['com.example.app'])
            ->userByIdentityToken($this->identityToken());

        $this->assertSame('apple-user-id', $user->getId());
    }

    public function test_it_accepts_a_single_audience_given_as_a_string(): void
    {
        $user = $this->providerWithAudiences('com.example.app')
            ->userFromToken($this->identityToken(['aud' => 'com.example.app']));

        $this->assertSame('apple-user-id', $user->getId());
    }

    public function test_it_rejects_a_token_for_an_audience_that_is_not_configured(): void
    {
        $this->expectException(InvalidStateException::class);
        $this->expectExceptionMessage('The token is not allowed to be used by this audience');

        $this->providerWithAudiences(['com.example.app'])
            ->userByIdentityToken($this->identityToken(['aud' => 'com.attacker.app']));
    }

    public function test_it_decodes_claims_whose_payload_uses_base64url_characters(): void
    {
        // A nonce chosen so the encoded payload contains - and _, which plain
        // base64_decode does not understand.
        $user = $this->makeAppleProvider()->userByIdentityToken(
            $this->identityToken(['nonce' => 'aa?bb>>cc']),
            'aa?bb>>cc'
        );

        $this->assertSame('apple-user-id', $user->getId());
        $this->assertSame('user@example.com', $user->getEmail());
    }

    public function test_it_rejects_a_token_from_another_issuer(): void
    {
        $this->expectException(InvalidStateException::class);
        $this->expectExceptionMessage('The token was not issued by the given issuers');

        $this->makeAppleProvider()->userByIdentityToken(
            $this->identityToken(['iss' => 'https://accounts.google.com'])
        );
    }

    public function test_it_rejects_a_token_signed_by_an_unknown_key(): void
    {
        $token = $this->identityToken();

        // Re-key the JWKS so the token's kid no longer resolves.
        $this->jwks['keys'][0]['kid'] = 'a-different-key';

        $this->expectException(InvalidStateException::class);
        $this->expectExceptionMessage('Invalid JWT Signature');

        $this->makeAppleProvider()->userByIdentityToken($token);
    }

    private function providerWithAudiences(array|string $audiences): Provider
    {
        return $this->makeAppleProvider()->setConfig(new Config(
            static::CLIENT_ID,
            static::CLIENT_SECRET,
            static::REDIRECT_URI,
            ['audiences' => $audiences]
        ));
    }
}
