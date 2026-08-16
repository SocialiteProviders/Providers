<?php

namespace SocialiteProviders\OpenIDConnect\IssuerValidators;

use stdClass;

/**
 * Strict equality, as OIDC Core 3.1.3.7 step 2 requires. Anything looser
 * belongs in a provider-specific validator.
 */
class DefaultIssuerValidator implements IssuerValidator
{
    public function validate(string $expectedIssuer, stdClass $payload): bool
    {
        $iss = $payload->iss ?? null;

        return is_string($iss) && $iss !== '' && $iss === $expectedIssuer;
    }
}
