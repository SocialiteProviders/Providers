<?php

namespace SocialiteProviders\Apple;

use Carbon\CarbonImmutable;
use Lcobucci\JWT\Configuration;

class AppleToken
{
    private Configuration $jwtConfig;
    private string $teamId;
    private string $clientId;
    private string $keyId;

    public function __construct(Configuration $jwtConfig, string $teamId, string $clientId, string $keyId)
    {
        $this->jwtConfig = $jwtConfig;
        $this->teamId = $teamId;
        $this->clientId = $clientId;
        $this->keyId = $keyId;
    }

    public function generate(): string
    {
        $now = CarbonImmutable::now();

        $token = $this->jwtConfig->builder()
            ->issuedBy($this->teamId)
            ->issuedAt($now)
            ->expiresAt($now->addHour())
            ->permittedFor(Provider::URL)
            ->relatedTo($this->clientId)
            ->withHeader('kid', $this->keyId)
            ->getToken($this->jwtConfig->signer(), $this->jwtConfig->signingKey());

        return $token->toString();
    }
}
