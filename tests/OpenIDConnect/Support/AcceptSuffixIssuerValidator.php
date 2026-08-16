<?php

namespace SocialiteProviders\Tests\OpenIDConnect\Support;

use SocialiteProviders\OpenIDConnect\IssuerValidators\IssuerValidator;
use stdClass;

/**
 * Accepts any issuer ending in '/accepted', regardless of the expected value.
 * Exists to prove the `issuer_validator` config key swaps the comparison.
 */
class AcceptSuffixIssuerValidator implements IssuerValidator
{
    public function validate(string $expectedIssuer, stdClass $payload): bool
    {
        return is_string($payload->iss ?? null) && str_ends_with($payload->iss, '/accepted');
    }
}
