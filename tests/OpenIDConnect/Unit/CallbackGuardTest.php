<?php

namespace SocialiteProviders\Tests\OpenIDConnect\Unit;

use GuzzleHttp\Psr7\Response;
use InvalidArgumentException;
use SocialiteProviders\Tests\OpenIDConnect\Support\InteractsWithOidc;
use SocialiteProviders\Tests\OpenIDConnect\TestCase;

class CallbackGuardTest extends TestCase
{
    use InteractsWithOidc;

    public function test_idp_error_response_is_surfaced_without_any_http_calls(): void
    {
        $provider = $this->makeProvider([], [], $this->callbackRequest([
            'error'             => 'access_denied',
            'error_description' => 'User cancelled',
        ]));

        try {
            $provider->user();
            $this->fail('Expected the IdP error to be surfaced.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('User cancelled', $e->getMessage());
            $this->assertSame(401, $e->getCode());
        }

        $this->assertSame([], $this->requestedPaths());
    }

    public function test_mismatched_state_is_rejected(): void
    {
        $provider = $this->makeProvider([], [], $this->callbackRequest(
            ['code' => 'the-auth-code', 'state' => 'attacker-state'],
        ));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('invalid state');

        $provider->user();
    }

    public function test_missing_code_is_rejected(): void
    {
        $provider = $this->makeProvider([], [], $this->callbackRequest(
            ['state' => static::$opState],
        ));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('missing authorization code');

        $provider->user();
    }

    public function test_token_endpoint_error_with_http_200_is_rejected(): void
    {
        // Some IdPs answer 200 with an error body; Guzzle won't raise for us.
        $provider = $this->makeProvider([], [
            $this->jsonResponse($this->discoveryDocument()),
            $this->jsonResponse(['error' => 'invalid_grant', 'error_description' => 'Code expired']),
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Code expired');

        $provider->user();
    }

    public function test_token_response_without_id_token_is_rejected(): void
    {
        $provider = $this->makeProvider([], [
            $this->jsonResponse($this->discoveryDocument()),
            $this->jsonResponse(['access_token' => 'the-access-token', 'token_type' => 'Bearer']),
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('no id_token');

        $provider->user();
    }

    public function test_id_token_without_sub_is_rejected(): void
    {
        $claims = $this->idTokenClaims(['sub' => null]);

        $provider = $this->makeProvider([], $this->happyPathResponses($claims));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('sub');

        $provider->user();
    }

    public function test_a_non_json_token_response_is_rejected(): void
    {
        $provider = $this->makeProvider([], [
            $this->jsonResponse($this->discoveryDocument()),
            new Response(200, ['Content-Type' => 'text/html'], '<html>gateway error</html>'),
        ]);

        $this->expectException(InvalidArgumentException::class);

        $provider->user();
    }
}
