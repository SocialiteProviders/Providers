<?php

namespace SocialiteProviders\Tests\OpenIDConnect\Unit;

use InvalidArgumentException;
use SocialiteProviders\Tests\OpenIDConnect\Support\InteractsWithOidc;
use SocialiteProviders\Tests\OpenIDConnect\TestCase;

class UserInfoTest extends TestCase
{
    use InteractsWithOidc;

    private function responsesWithEmaillessIdToken(array $userInfo): array
    {
        $claims = $this->idTokenClaims(['email' => null, 'groups' => ['admins']]);

        return [
            $this->jsonResponse($this->discoveryDocument()),
            $this->tokenEndpointResponse($this->encodeToken($claims)),
            $this->jsonResponse($this->jwksDocument()),
            $this->jsonResponse($userInfo),
        ];
    }

    public function test_userinfo_fills_in_a_missing_email(): void
    {
        $provider = $this->makeProvider([], $this->responsesWithEmaillessIdToken([
            'sub'   => 'user-123',
            'email' => 'from-userinfo@example.com',
        ]));

        $user = $provider->user();

        $this->assertSame('from-userinfo@example.com', $user->getEmail());

        // Merged, not substituted: claims only the id_token carried survive.
        $this->assertSame(['admins'], $user->getRaw()['groups']);
    }

    public function test_the_access_token_travels_in_the_authorization_header_never_the_query(): void
    {
        $provider = $this->makeProvider([], $this->responsesWithEmaillessIdToken([
            'sub'   => 'user-123',
            'email' => 'from-userinfo@example.com',
        ]));

        $provider->user();

        $userInfoRequest = $this->lastRequestTo('/userinfo');

        $this->assertNotNull($userInfoRequest);
        $this->assertSame('Bearer the-access-token', $userInfoRequest->getHeaderLine('Authorization'));
        $this->assertStringNotContainsString('access_token', (string) $userInfoRequest->getUri());
    }

    public function test_a_userinfo_response_for_a_different_sub_is_rejected(): void
    {
        // OIDC Core 5.3.2: the subs must match exactly or the response is
        // unusable.
        $provider = $this->makeProvider([], $this->responsesWithEmaillessIdToken([
            'sub'   => 'a-different-user',
            'email' => 'attacker@example.com',
        ]));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('sub does not match');

        $provider->user();
    }

    public function test_userinfo_is_not_consulted_when_the_id_token_has_an_email(): void
    {
        $provider = $this->makeProvider([], $this->happyPathResponses());

        $provider->user();

        $this->assertNotContains('GET /userinfo', $this->requestedPaths());
    }

    public function test_require_email_fails_the_login_when_no_email_can_be_found(): void
    {
        $provider = $this->makeProvider(
            ['require_email' => true],
            $this->responsesWithEmaillessIdToken(['sub' => 'user-123', 'name' => 'No Email']),
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('no email');

        $provider->user();
    }

    public function test_a_missing_email_is_tolerated_by_default(): void
    {
        $provider = $this->makeProvider(
            [],
            $this->responsesWithEmaillessIdToken(['sub' => 'user-123', 'name' => 'No Email']),
        );

        $this->assertNull($provider->user()->getEmail());
    }
}
