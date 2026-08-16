<?php

namespace SocialiteProviders\OpenIDConnect\IssuerValidators;

use stdClass;

/**
 * Validates a token's `iss` claim against the issuer the RP expects.
 * Receives the full payload so other claims can inform the decision.
 */
interface IssuerValidator
{
    public function validate(string $expectedIssuer, stdClass $payload): bool;
}
