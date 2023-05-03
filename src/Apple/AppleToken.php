<?php

namespace SocialiteProviders\Apple;

use Carbon\CarbonImmutable;
use Lcobucci\JWT\Configuration;

class AppleToken
{
    private Configuration $jwtConfig;

    public function __construct(Configuration $jwtConfig)
    {
        $this->jwtConfig = $jwtConfig;
    }

    public function generate(): string
    {
        $now = CarbonImmutable::now();

        $token = $this->jwtConfig->builder()
            ->issuedBy(config('services.apple.team_id'))
            ->issuedAt($now)
            ->expiresAt($now->addHour())
            ->permittedFor(Provider::URL)
            ->relatedTo(config('services.apple.client_id'))
            ->withHeader('kid', config('services.apple.key_id'))
            ->getToken($this->jwtConfig->signer(), $this->jwtConfig->signingKey());

        return $token->toString();
    }
}
