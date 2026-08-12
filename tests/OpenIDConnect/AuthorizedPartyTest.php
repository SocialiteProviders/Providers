<?php

namespace SocialiteProviders\Tests\OpenIDConnect;

use PHPUnit\Framework\Attributes\Test;

/**
 * OIDC Core 3.1.3.7 keeps the two azp rules separate:
 *
 *   step 4 - multiple audiences should carry an azp claim;
 *   step 5 - a present azp must be our client_id, whatever the audience count.
 *
 * Gating step 5 on the audience count conflates them and lets a token
 * authorized to another party through.
 */
class AuthorizedPartyTest extends TestCase
{
    private const NONCE = 'test-nonce-value';

    private function decode(array $claims)
    {
        $this->seedJwks();

        $provider = $this->oidcProvider([], $this->request(session: ['nonce' => self::NONCE]));

        return $provider->callDecodeJWT($this->idToken($claims + ['nonce' => self::NONCE]));
    }

    #[Test]
    public function a_single_audience_token_authorized_to_another_party_is_rejected(): void
    {
        // The hole: aud names us, but the token was issued for someone else.
        $this->expectExceptionMessage('Invalid authorized party');

        $this->decode([
            'aud' => self::CLIENT_ID,
            'azp' => 'some-other-client',
        ]);
    }

    #[Test]
    public function a_single_element_audience_array_authorized_to_another_party_is_rejected(): void
    {
        $this->expectExceptionMessage('Invalid authorized party');

        $this->decode([
            'aud' => [self::CLIENT_ID],
            'azp' => 'some-other-client',
        ]);
    }

    #[Test]
    public function a_multi_audience_token_authorized_to_another_party_is_rejected(): void
    {
        $this->expectExceptionMessage('Invalid authorized party');

        $this->decode([
            'aud' => [self::CLIENT_ID, 'another-client'],
            'azp' => 'another-client',
        ]);
    }

    #[Test]
    public function a_multi_audience_token_without_an_azp_is_rejected(): void
    {
        // Step 4, which the previous merged condition enforced only by
        // accident and which the suggested replacement would have dropped.
        $this->expectExceptionMessage('Multiple audiences require an azp');

        $this->decode(['aud' => [self::CLIENT_ID, 'another-client']]);
    }

    #[Test]
    public function a_matching_azp_is_accepted_with_a_single_audience(): void
    {
        $payload = $this->decode([
            'aud' => self::CLIENT_ID,
            'azp' => self::CLIENT_ID,
        ]);

        $this->assertSame('user-1', $payload->sub);
    }

    #[Test]
    public function a_matching_azp_is_accepted_with_multiple_audiences(): void
    {
        $payload = $this->decode([
            'aud' => [self::CLIENT_ID, 'another-client'],
            'azp' => self::CLIENT_ID,
        ]);

        $this->assertSame('user-1', $payload->sub);
    }

    #[Test]
    public function a_single_audience_token_without_an_azp_is_accepted(): void
    {
        // azp is optional for a single-audience token.
        $payload = $this->decode(['aud' => self::CLIENT_ID]);

        $this->assertSame('user-1', $payload->sub);
    }
}
