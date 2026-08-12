<?php

namespace SocialiteProviders\Tests\OpenIDConnect;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\Test;

/**
 * The token endpoint response is only loosely specified: expires_in is
 * optional (RFC 6749 4.2.2), access_token need not accompany an id_token, and
 * some IdPs report failure with a 200 and an error body.
 */
class UserRetrievalTest extends TestCase
{
    /**
     * @param  array<int, Response>  $responses
     */
    private function providerReturning(array $responses, array $config = []): ProviderStub
    {
        $this->seedJwks();

        $provider = $this->oidcProvider(
            $config,
            $this->request(['code' => 'the-auth-code', 'state' => 's'])
        );

        // Stateless keeps the callback focused on the token response rather
        // than on state/nonce plumbing.
        $provider->stateless();

        $stack = HandlerStack::create(new MockHandler($responses));

        return $provider->useHttpClient(new Client(['handler' => $stack]));
    }

    private function tokenResponse(array $body): Response
    {
        return new Response(200, ['Content-Type' => 'application/json'], json_encode($body));
    }

    #[Test]
    public function a_response_without_expires_in_is_handled(): void
    {
        $provider = $this->providerReturning([
            $this->tokenResponse([
                'id_token'     => $this->idToken(),
                'access_token' => 'the-access-token',
                // no expires_in
            ]),
        ]);

        $user = $provider->user();

        $this->assertSame('user-1', $user->getId());
        $this->assertSame('the-access-token', $user->token);
        $this->assertNull($user->expiresIn);
    }

    #[Test]
    public function a_response_with_only_an_id_token_is_handled(): void
    {
        $provider = $this->providerReturning([
            $this->tokenResponse(['id_token' => $this->idToken()]),
        ]);

        $user = $provider->user();

        $this->assertSame('user-1', $user->getId());
        $this->assertSame('user@example.test', $user->getEmail());
        $this->assertNull($user->token);
        $this->assertNull($user->refreshToken);
    }

    #[Test]
    public function a_refresh_token_is_captured_when_present(): void
    {
        $provider = $this->providerReturning([
            $this->tokenResponse([
                'id_token'      => $this->idToken(),
                'access_token'  => 'at',
                'refresh_token' => 'rt',
                'expires_in'    => 3600,
            ]),
        ]);

        $user = $provider->user();

        $this->assertSame('rt', $user->refreshToken);
        $this->assertSame(3600, $user->expiresIn);
    }

    #[Test]
    public function an_error_body_returned_with_a_200_is_reported(): void
    {
        $provider = $this->providerReturning([
            $this->tokenResponse([
                'error'             => 'invalid_grant',
                'error_description' => 'Authorization code expired',
            ]),
        ]);

        $this->expectExceptionMessage('Authorization code expired');

        $provider->user();
    }

    #[Test]
    public function an_error_body_without_a_description_still_reports_the_code(): void
    {
        $provider = $this->providerReturning([
            $this->tokenResponse(['error' => 'invalid_client']),
        ]);

        $this->expectExceptionMessage('invalid_client');

        $provider->user();
    }

    #[Test]
    public function a_response_with_no_id_token_reports_that_clearly(): void
    {
        // Previously this indexed a missing key and then handed null to a
        // string-typed parameter, surfacing as a TypeError.
        $provider = $this->providerReturning([
            $this->tokenResponse(['access_token' => 'at', 'token_type' => 'Bearer']),
        ]);

        $this->expectExceptionMessage('contained no id_token');

        $provider->user();
    }

    #[Test]
    public function a_non_json_response_body_is_reported(): void
    {
        $provider = $this->providerReturning([
            new Response(200, ['Content-Type' => 'text/html'], '<html>gateway</html>'),
        ]);

        $this->expectException(\JsonException::class);

        $provider->user();
    }

    #[Test]
    public function userinfo_is_consulted_when_the_id_token_carries_no_email(): void
    {
        $provider = $this->providerReturning([
            $this->tokenResponse([
                'id_token'     => $this->idToken(['email' => null]),
                'access_token' => 'the-access-token',
            ]),
            $this->tokenResponse(['sub' => 'user-1', 'email' => 'from-userinfo@example.test']),
        ]);

        $this->assertSame('from-userinfo@example.test', $provider->user()->getEmail());
    }

    #[Test]
    public function a_user_without_an_email_is_allowed_by_default(): void
    {
        // email depends on the `email` scope being granted; OIDC Core 2 makes
        // sub the only identifier an OP must return.
        $provider = $this->providerReturning([
            $this->tokenResponse(['id_token' => $this->idToken(['email' => null])]),
        ]);

        $user = $provider->user();

        $this->assertSame('user-1', $user->getId());
        $this->assertNull($user->getEmail());
    }

    #[Test]
    public function a_missing_email_can_be_made_fatal(): void
    {
        $provider = $this->providerReturning(
            [$this->tokenResponse(['id_token' => $this->idToken(['email' => null])])],
            ['require_email' => true]
        );

        $this->expectExceptionMessage('User has no email');

        $provider->user();
    }

    #[Test]
    public function a_token_without_a_sub_is_rejected(): void
    {
        $provider = $this->providerReturning([
            $this->tokenResponse(['id_token' => $this->idToken(['sub' => null])]),
        ]);

        $this->expectExceptionMessage('Missing required sub');

        $provider->user();
    }

    #[Test]
    public function a_userinfo_response_for_a_different_sub_is_rejected(): void
    {
        // OIDC Core 5.3.2: the sub must match the id_token exactly, or the
        // response must not be used.
        $provider = $this->providerReturning([
            $this->tokenResponse(['id_token' => $this->idToken(['email' => null]), 'access_token' => 'at']),
            $this->tokenResponse(['sub' => 'someone-else', 'email' => 'attacker@example.test']),
        ]);

        $this->expectExceptionMessage('sub does not match');

        $provider->user();
    }

    #[Test]
    public function userinfo_claims_are_merged_over_the_id_token_rather_than_replacing_it(): void
    {
        $provider = $this->providerReturning([
            $this->tokenResponse([
                'id_token'     => $this->idToken(['email' => null, 'groups' => ['admins']]),
                'access_token' => 'at',
            ]),
            $this->tokenResponse(['sub' => 'user-1', 'email' => 'from-userinfo@example.test']),
        ]);

        $user = $provider->user();

        $this->assertSame('from-userinfo@example.test', $user->getEmail());
        // Carried by the id_token but absent from userinfo.
        $this->assertSame(['admins'], $user->getRaw()['groups']);
    }

    #[Test]
    public function the_full_token_response_is_exposed_on_the_user(): void
    {
        $idToken = $this->idToken();

        $provider = $this->providerReturning([
            $this->tokenResponse(['id_token' => $idToken, 'access_token' => 'at', 'expires_in' => 60]),
        ]);

        $body = $provider->user()->accessTokenResponseBody;

        $this->assertSame($idToken, $body['id_token']);
        $this->assertSame('at', $body['access_token']);
        $this->assertSame(60, $body['expires_in']);
    }

    #[Test]
    public function the_approved_scopes_are_exposed_on_the_user(): void
    {
        $provider = $this->providerReturning([
            $this->tokenResponse([
                'id_token'     => $this->idToken(),
                'access_token' => 'at',
                'scope'        => 'openid email offline_access',
            ]),
        ]);

        $this->assertSame(['openid', 'email', 'offline_access'], $provider->user()->approvedScopes);
    }

    #[Test]
    public function the_approved_scopes_default_to_empty_when_not_returned(): void
    {
        $provider = $this->providerReturning([
            $this->tokenResponse(['id_token' => $this->idToken(), 'access_token' => 'at']),
        ]);

        $this->assertSame([], $provider->user()->approvedScopes);
    }

    #[Test]
    public function the_raw_user_holds_only_the_claims(): void
    {
        // The id_token lives on accessTokenResponseBody, not folded into the
        // claims, so the same value is not carried in two places.
        $provider = $this->providerReturning([
            $this->tokenResponse(['id_token' => $this->idToken(), 'access_token' => 'at']),
        ]);

        $raw = $provider->user()->getRaw();

        $this->assertArrayNotHasKey('id_token', $raw);
        $this->assertSame('user-1', $raw['sub']);
    }
}
