<?php

namespace SocialiteProviders\Tests\OpenIDConnect;

use PHPUnit\Framework\Attributes\Test;

/**
 * Malformed tokens should fail where they are malformed, not several checks
 * later. base64_decode() in non-strict mode silently discards characters
 * outside the alphabet and returns plausible bytes for input that was never
 * valid base64.
 */
class MalformedTokenTest extends TestCase
{
    private const NONCE = 'test-nonce-value';

    private function providerFor(array $config = [], $request = null, ?array $discovery = null): ProviderStub
    {
        return $this->oidcProvider(
            $config,
            $request ?? $this->request(session: ['nonce' => self::NONCE]),
            $discovery
        );
    }

    private function segment(array $data): string
    {
        return $this->base64UrlEncode(json_encode($data));
    }

    #[Test]
    public function a_token_with_no_separators_is_reported(): void
    {
        // Previously a warning followed by a TypeError, which is an Error and
        // so escaped the surrounding catch (Exception) altogether.
        $this->expectExceptionMessage('expected three segments');

        $this->providerFor()->callDecodeJWT('not-a-jwt-at-all');
    }

    #[Test]
    public function a_two_segment_token_is_reported(): void
    {
        $this->expectExceptionMessage('expected three segments');

        $this->providerFor()->callDecodeJWT($this->segment(['alg' => 'RS256']).'.'.$this->segment(['sub' => 'x']));
    }

    #[Test]
    public function a_four_segment_token_is_reported(): void
    {
        $this->expectExceptionMessage('expected three segments');

        $this->providerFor()->callDecodeJWT('a.b.c.d');
    }

    #[Test]
    public function an_out_of_alphabet_header_is_reported(): void
    {
        $this->expectExceptionMessage('Malformed base64url segment');

        $this->providerFor()->callDecodeJWT('abc$%^&*def.'.$this->segment(['sub' => 'x']).'.sig');
    }

    #[Test]
    public function an_out_of_alphabet_payload_is_reported(): void
    {
        $provider = $this->providerFor(['verify_jwt' => false]);

        $this->expectExceptionMessage('Malformed base64url segment');

        $provider->callDecodeJWT($this->segment(['alg' => 'RS256']).'.abc$%^&*def.sig');
    }

    #[Test]
    public function a_header_that_is_not_json_is_reported(): void
    {
        $this->expectExceptionMessage('Failed to parse header');

        $this->providerFor()->callDecodeJWT(
            $this->base64UrlEncode('this is not json').'.'.$this->segment(['sub' => 'x']).'.sig'
        );
    }

    #[Test]
    public function a_header_that_is_not_an_object_is_reported(): void
    {
        $this->expectExceptionMessage('Failed to parse header');

        $this->providerFor()->callDecodeJWT(
            $this->base64UrlEncode('"a string"').'.'.$this->segment(['sub' => 'x']).'.sig'
        );
    }

    #[Test]
    public function a_payload_that_is_not_json_is_reported(): void
    {
        $provider = $this->providerFor(['verify_jwt' => false]);

        $this->expectExceptionMessage('JWT: Failed to parse.');

        $provider->callDecodeJWT(
            $this->segment(['alg' => 'RS256']).'.'.$this->base64UrlEncode('this is not json').'.sig'
        );
    }

    #[Test]
    public function a_payload_that_is_not_an_object_is_reported(): void
    {
        $provider = $this->providerFor(['verify_jwt' => false]);

        $this->expectExceptionMessage('JWT: Failed to parse.');

        $provider->callDecodeJWT(
            $this->segment(['alg' => 'RS256']).'.'.$this->base64UrlEncode('[1,2,3]').'.sig'
        );
    }

    #[Test]
    public function a_valid_token_is_unaffected(): void
    {
        $this->seedJwks();

        $payload = $this->providerFor()->callDecodeJWT($this->idToken(['nonce' => self::NONCE]));

        $this->assertSame('user-1', $payload->sub);
    }

    #[Test]
    public function unpadded_segments_of_every_length_class_still_decode(): void
    {
        // The padding fix and strict mode have to agree for all three valid
        // base64 length classes (len % 4 of 0, 2 and 3).
        $this->seedJwks();

        foreach (['a', 'ab', 'abc', 'abcd', 'abcde'] as $filler) {
            $payload = $this->providerFor()->callDecodeJWT(
                $this->idToken(['nonce' => self::NONCE, 'name' => $filler])
            );

            $this->assertSame($filler, $payload->name);
        }
    }
}
