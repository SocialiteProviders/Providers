<?php

declare(strict_types=1);

namespace SocialiteProviders\Apple;

use Lcobucci\JWT\Signer;

final class AppleSignerNone implements Signer
{
    public function algorithmId(): string
    {
        return 'none';
    }

    // @phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
    public function sign(string $payload, Signer\Key $key): string
    {
        return '';
    }

    // @phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
    public function verify(string $expected, string $payload, Signer\Key $key): bool
    {
        return $expected === '';
    }
}
