<?php

namespace SocialiteProviders\Tests\OpenIDConnect;

use GuzzleHttp\Client;
use SocialiteProviders\OpenIDConnect\Provider;

/**
 * Exposes the provider's protected surface so behaviour can be asserted
 * without driving a full HTTP round trip.
 */
class ProviderStub extends Provider
{
    public function callUsesPKCE(): bool
    {
        return $this->usesPKCE();
    }

    public function callGetCodeFields($state = null): array
    {
        return $this->getCodeFields($state);
    }

    /**
     * Mirrors how decodeJWT() sources the algorithm: straight off the token
     * header, exactly as an attacker would supply it.
     */
    public function callVerifyFromToken(string $jwt)
    {
        $alg = $this->decodeJwtHeader($jwt)->alg ?? null;

        return $this->verifyAndDecodeJWT($jwt, $alg);
    }

    public function callDecodeJWT(string $jwt, ?string $accessToken = null)
    {
        return $this->decodeJWT($jwt, $accessToken);
    }

    public function callAllowedSigningAlgorithms(): array
    {
        return $this->getAllowedSigningAlgorithms();
    }

    public function callResolveTokenAuthMethod(): string
    {
        return $this->resolveTokenAuthMethod();
    }

    public function callGetBaseUrl(): string
    {
        return $this->getBaseUrl();
    }

    public function callUsesNonce(): bool
    {
        return $this->usesNonce();
    }

    /**
     * getHttpClient() builds its own client, so tests inject one here.
     */
    public function useHttpClient(Client $client): static
    {
        $this->httpClient = $client;

        return $this;
    }

    public function callShouldVerifyJwt(): bool
    {
        return $this->shouldVerifyJwt();
    }

    public function callTokenRequestOptions(array $fields): array
    {
        return $this->tokenRequestOptions($fields);
    }

    public function callGetCacheTtl(): int
    {
        return $this->getCacheTtl();
    }

    public function callLogoutTokenReplayTtl($expiresAt = null): int
    {
        return $this->logoutTokenReplayTtl($expiresAt);
    }
}
