<?php

namespace SocialiteProviders\Tests\OpenIDConnect;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request as PsrRequest;
use GuzzleHttp\Psr7\Response;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;

class RevokeTest extends TestCase
{
    /**
     * @param  array<int, Response|\Throwable>  $responses
     */
    private function providerReturning(array $responses, array $discovery = []): ProviderStub
    {
        $provider = $this->oidcProvider(discovery: $this->discoveryDocument($discovery));

        $stack = HandlerStack::create(new MockHandler($responses));

        return $provider->useHttpClient(new Client(['handler' => $stack]));
    }

    #[Test]
    public function a_200_is_a_successful_revocation(): void
    {
        $this->assertTrue($this->providerReturning([new Response(200)])->revoke('a-token'));
    }

    #[Test]
    public function a_204_is_a_successful_revocation(): void
    {
        $this->assertTrue($this->providerReturning([new Response(204)])->revoke('a-token'));
    }

    #[Test]
    public function a_400_returns_false_rather_than_throwing(): void
    {
        // The already-revoked case on a non-conforming IdP. Previously this
        // threw a ClientException straight out of the logout controller.
        $response = new Response(400, [], json_encode(['error' => 'invalid_token']));

        $this->assertFalse($this->providerReturning([$response])->revoke('a-token'));
    }

    #[Test]
    public function a_401_returns_false_rather_than_throwing(): void
    {
        $this->assertFalse($this->providerReturning([new Response(401)])->revoke('a-token'));
    }

    #[Test]
    public function a_500_returns_false_rather_than_throwing(): void
    {
        $this->assertFalse($this->providerReturning([new Response(500)])->revoke('a-token'));
    }

    #[Test]
    public function a_transport_failure_still_throws(): void
    {
        // Deliberately not swallowed: an unreachable IdP is a real failure,
        // and the README shows callers wrapping this.
        $provider = $this->providerReturning([
            new ConnectException('Connection refused', new PsrRequest('POST', '/revoke')),
        ]);

        $this->expectException(ConnectException::class);

        $provider->revoke('a-token');
    }

    #[Test]
    public function a_provider_without_a_revocation_endpoint_is_reported(): void
    {
        $provider = $this->oidcProvider(discovery: array_diff_key(
            $this->discoveryDocument(),
            ['revocation_endpoint' => null]
        ));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('does not advertise a revocation_endpoint');

        $provider->revoke('a-token');
    }

    #[Test]
    public function the_token_and_hint_are_sent_in_the_request_body(): void
    {
        $sent = [];
        $stack = HandlerStack::create(new MockHandler([new Response(200)]));
        $stack->push(Middleware::history($sent));

        $provider = $this->oidcProvider()->useHttpClient(new Client(['handler' => $stack]));

        $provider->revoke('the-refresh-token', 'refresh_token');

        parse_str((string) $sent[0]['request']->getBody(), $body);

        $this->assertSame('the-refresh-token', $body['token']);
        $this->assertSame('refresh_token', $body['token_type_hint']);
    }

    #[Test]
    public function the_hint_defaults_to_refresh_token(): void
    {
        $sent = [];
        $stack = HandlerStack::create(new MockHandler([new Response(200)]));
        $stack->push(Middleware::history($sent));

        $provider = $this->oidcProvider()->useHttpClient(new Client(['handler' => $stack]));

        $provider->revoke('a-token');

        parse_str((string) $sent[0]['request']->getBody(), $body);

        $this->assertSame('refresh_token', $body['token_type_hint']);
    }
}
