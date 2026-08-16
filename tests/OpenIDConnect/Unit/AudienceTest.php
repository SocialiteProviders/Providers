<?php

namespace SocialiteProviders\Tests\OpenIDConnect\Unit;

use InvalidArgumentException;
use SocialiteProviders\Tests\OpenIDConnect\Support\InteractsWithOidc;
use SocialiteProviders\Tests\OpenIDConnect\TestCase;

class AudienceTest extends TestCase
{
    use InteractsWithOidc;

    public function test_a_token_for_another_client_is_rejected(): void
    {
        $claims = $this->idTokenClaims(['aud' => 'someone-else']);

        $provider = $this->makeProvider([], $this->happyPathResponses($claims));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid audience');

        $provider->user();
    }

    public function test_an_audience_array_containing_this_client_is_accepted_with_azp(): void
    {
        $claims = $this->idTokenClaims([
            'aud' => [static::$opClientId, 'other-client'],
            'azp' => static::$opClientId,
        ]);

        $provider = $this->makeProvider([], $this->happyPathResponses($claims));

        $this->assertSame('user-123', $provider->user()->getId());
    }

    public function test_multiple_audiences_without_azp_are_rejected(): void
    {
        $claims = $this->idTokenClaims([
            'aud' => [static::$opClientId, 'other-client'],
        ]);

        $provider = $this->makeProvider([], $this->happyPathResponses($claims));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('azp');

        $provider->user();
    }

    public function test_an_azp_naming_a_different_party_is_rejected_even_with_a_single_audience(): void
    {
        $claims = $this->idTokenClaims([
            'aud' => static::$opClientId,
            'azp' => 'other-client',
        ]);

        $provider = $this->makeProvider([], $this->happyPathResponses($claims));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('azp');

        $provider->user();
    }

    public function test_a_single_string_audience_matching_this_client_is_accepted(): void
    {
        $provider = $this->makeProvider([], $this->happyPathResponses());

        $this->assertSame('user-123', $provider->user()->getId());
    }

    public function test_a_single_element_audience_array_with_a_foreign_azp_is_rejected(): void
    {
        $claims = $this->idTokenClaims([
            'aud' => [static::$opClientId],
            'azp' => 'another-client',
        ]);

        $provider = $this->makeProvider([], $this->happyPathResponses($claims));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('azp');

        $provider->user();
    }
}
