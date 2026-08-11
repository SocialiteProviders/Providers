<?php

namespace SocialiteProviders\Logto;

use GuzzleHttp\RequestOptions;
use InvalidArgumentException;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'LOGTO';

    protected $scopeSeparator = ' ';

    protected $scopes = ['openid', 'profile', 'email'];

    public static function additionalConfigKeys(): array
    {
        return ['base_url'];
    }

    /**
     * The Logto tenant endpoint, e.g. https://auth.example.com or
     * https://<tenant-id>.logto.app. The OIDC routes live under /oidc.
     */
    protected function getBaseUrl(): string
    {
        $baseUrl = $this->getConfig('base_url');
        if ($baseUrl === null) {
            throw new InvalidArgumentException('Missing base_url');
        }

        return rtrim($baseUrl, '/');
    }

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase($this->getBaseUrl().'/oidc/auth', $state);
    }

    protected function getTokenUrl(): string
    {
        return $this->getBaseUrl().'/oidc/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get($this->getBaseUrl().'/oidc/me', [
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer '.$token,
            ],
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    public function refreshToken($refreshToken)
    {
        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            RequestOptions::FORM_PARAMS => [
                'client_id'     => $this->clientId,
                'client_secret' => $this->clientSecret,
                'grant_type'    => 'refresh_token',
                'refresh_token' => $refreshToken,
            ],
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user)->map([
            'id'                 => $user['sub'],
            'nickname'           => $user['username'] ?? null,
            'name'               => $user['name'] ?? null,
            'email'              => $user['email'] ?? null,
            'email_verified'     => $user['email_verified'] ?? null,
            'avatar'             => $user['picture'] ?? null,
            'given_name'         => $user['given_name'] ?? null,
            'family_name'        => $user['family_name'] ?? null,
            'preferred_username' => $user['username'] ?? null,
        ]);
    }
}
