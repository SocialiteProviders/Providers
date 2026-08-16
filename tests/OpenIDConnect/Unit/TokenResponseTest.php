<?php

namespace SocialiteProviders\Tests\OpenIDConnect\Unit;

use SocialiteProviders\Tests\OpenIDConnect\Support\InteractsWithOidc;
use SocialiteProviders\Tests\OpenIDConnect\TestCase;

class TokenResponseTest extends TestCase
{
    use InteractsWithOidc;

    public function test_the_full_token_response_and_approved_scopes_are_exposed(): void
    {
        $provider = $this->makeProvider([], $this->happyPathResponses());

        $user = $provider->user();

        $this->assertSame('the-access-token', $user->token);
        $this->assertSame('the-refresh-token', $user->refreshToken);
        $this->assertSame(['openid', 'email', 'profile'], $user->approvedScopes);
        $this->assertArrayHasKey('id_token', $user->accessTokenResponseBody);
    }

    public function test_a_minimal_token_response_is_tolerated(): void
    {
        $idToken = $this->encodeToken($this->idTokenClaims());

        $provider = $this->makeProvider([], [
            $this->jsonResponse($this->discoveryDocument()),
            $this->jsonResponse(['access_token' => 'the-access-token', 'id_token' => $idToken]),
            $this->jsonResponse($this->jwksDocument()),
        ]);

        $user = $provider->user();

        $this->assertSame('user-123', $user->getId());
        $this->assertNull($user->refreshToken);
        $this->assertNull($user->expiresIn);
        $this->assertSame([], $user->approvedScopes);
    }
}
