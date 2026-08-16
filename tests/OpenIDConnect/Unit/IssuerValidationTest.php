<?php

namespace SocialiteProviders\Tests\OpenIDConnect\Unit;

use InvalidArgumentException;
use SocialiteProviders\OpenIDConnect\IssuerValidators\DefaultIssuerValidator;
use SocialiteProviders\OpenIDConnect\IssuerValidators\EntraIssuerValidator;
use SocialiteProviders\Tests\OpenIDConnect\Support\AcceptSuffixIssuerValidator;
use SocialiteProviders\Tests\OpenIDConnect\Support\InteractsWithOidc;
use SocialiteProviders\Tests\OpenIDConnect\TestCase;
use stdClass;

class IssuerValidationTest extends TestCase
{
    use InteractsWithOidc;

    public function test_a_token_from_a_different_issuer_is_rejected(): void
    {
        $claims = $this->idTokenClaims(['iss' => 'https://evil.test']);

        $provider = $this->makeProvider([], $this->happyPathResponses($claims));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid issuer');

        $provider->user();
    }

    public function test_the_expected_issuer_defaults_to_the_discovery_documents(): void
    {
        // discoveryDocument() advertises issuer = base_url.
        $provider = $this->makeProvider([], $this->happyPathResponses());

        $this->assertSame('user-123', $provider->user()->getId());
    }

    public function test_the_issuer_config_overrides_the_discovery_document(): void
    {
        $claims = $this->idTokenClaims(['iss' => 'https://alias.test']);

        $provider = $this->makeProvider(
            ['issuer' => 'https://alias.test'],
            $this->happyPathResponses($claims),
        );

        $this->assertSame('user-123', $provider->user()->getId());
    }

    public function test_the_default_validator_does_not_substitute_templates(): void
    {
        $claims = $this->idTokenClaims([
            'iss' => 'https://login.microsoftonline.com/tenant-123/v2.0',
            'tid' => 'tenant-123',
        ]);

        $provider = $this->makeProvider(
            ['issuer' => 'https://login.microsoftonline.com/{tenantid}/v2.0'],
            $this->happyPathResponses($claims),
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid issuer');

        $provider->user();
    }

    public function test_the_entra_validator_substitutes_the_tenantid_template_from_the_tid_claim(): void
    {
        // The PR #1447 blocker: Entra advertises the placeholder literally.
        $claims = $this->idTokenClaims([
            'iss' => 'https://login.microsoftonline.com/tenant-123/v2.0',
            'tid' => 'tenant-123',
        ]);

        $provider = $this->makeProvider(
            [
                'issuer'           => 'https://login.microsoftonline.com/{tenantid}/v2.0',
                'issuer_validator' => EntraIssuerValidator::class,
            ],
            $this->happyPathResponses($claims),
        );

        $this->assertSame('user-123', $provider->user()->getId());
    }

    public function test_the_entra_validator_still_rejects_a_mismatched_issuer(): void
    {
        $claims = $this->idTokenClaims([
            'iss' => 'https://login.microsoftonline.com/tenant-999/v2.0',
            'tid' => 'tenant-123',
        ]);

        $provider = $this->makeProvider(
            [
                'issuer'           => 'https://login.microsoftonline.com/{tenantid}/v2.0',
                'issuer_validator' => EntraIssuerValidator::class,
            ],
            $this->happyPathResponses($claims),
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid issuer');

        $provider->user();
    }

    public function test_a_custom_validator_callable_replaces_the_comparison(): void
    {
        $claims = $this->idTokenClaims(['iss' => 'https://anything.test']);

        $provider = $this->makeProvider([], $this->happyPathResponses($claims));

        $seen = [];
        $provider->validateIssuerUsing(function (string $expected, stdClass $payload) use (&$seen): bool {
            $seen = [$expected, $payload->iss];

            return true;
        });

        $this->assertSame('user-123', $provider->user()->getId());
        $this->assertSame([static::$opBaseUrl, 'https://anything.test'], $seen);
    }

    public function test_a_rejecting_custom_callable_fails_the_login(): void
    {
        $provider = $this->makeProvider([], $this->happyPathResponses());
        $provider->validateIssuerUsing(fn (): bool => false);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid issuer');

        $provider->user();
    }

    public function test_an_issuer_validator_class_can_be_set_per_connection_config(): void
    {
        $claims = $this->idTokenClaims(['iss' => 'https://whatever.test/accepted']);

        $provider = $this->makeProvider(
            ['issuer_validator' => AcceptSuffixIssuerValidator::class],
            $this->happyPathResponses($claims),
        );

        $this->assertSame('user-123', $provider->user()->getId());
    }

    public function test_a_config_class_not_implementing_the_interface_is_refused(): void
    {
        $provider = $this->makeProvider(
            ['issuer_validator' => stdClass::class],
            $this->happyPathResponses(),
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must implement');

        $provider->user();
    }

    public function test_default_validator_rejects_a_missing_iss(): void
    {
        $validator = new DefaultIssuerValidator;

        $this->assertFalse($validator->validate('https://op.test', new stdClass));
    }

    public function test_entra_validator_is_case_insensitive_on_the_placeholder_only(): void
    {
        $validator = new EntraIssuerValidator;

        $payload = new stdClass;
        $payload->iss = 'https://login.microsoftonline.com/abc/v2.0';
        $payload->tid = 'abc';

        $this->assertTrue($validator->validate('https://login.microsoftonline.com/{tenantId}/v2.0', $payload));

        // But the issuer comparison itself stays exact.
        $payload->iss = 'https://LOGIN.microsoftonline.com/abc/v2.0';
        $this->assertFalse($validator->validate('https://login.microsoftonline.com/{tenantid}/v2.0', $payload));
    }

    public function test_entra_validator_compares_strictly_without_a_placeholder(): void
    {
        $validator = new EntraIssuerValidator;

        $payload = new stdClass;
        $payload->iss = 'https://login.microsoftonline.com/tenant-123/v2.0';
        $payload->tid = 'tenant-123';

        $this->assertTrue($validator->validate('https://login.microsoftonline.com/tenant-123/v2.0', $payload));
        $this->assertFalse($validator->validate('https://login.microsoftonline.com/tenant-999/v2.0', $payload));
    }
}
