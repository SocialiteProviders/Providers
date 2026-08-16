<?php

namespace SocialiteProviders\OpenIDConnect\IssuerValidators;

use stdClass;

/**
 * Entra ID multi-tenant discovery advertises the issuer as a literal
 * `https://login.microsoftonline.com/{tenantid}/v2.0` template, so the
 * token's `tid` claim is substituted into the placeholder (case-insensitive:
 * both {tenantid} and {tenantId} appear in the wild) before comparing
 * strictly. Without a placeholder this is a plain strict comparison.
 */
class EntraIssuerValidator extends DefaultIssuerValidator
{
    public function validate(string $expectedIssuer, stdClass $payload): bool
    {
        $tid = $payload->tid ?? null;

        if (is_string($tid) && $tid !== '') {
            $expectedIssuer = preg_replace('/\{tenantid\}/i', $tid, $expectedIssuer);
        }

        return parent::validate($expectedIssuer, $payload);
    }
}
