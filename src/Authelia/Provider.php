<?php

namespace SocialiteProviders\Authelia;

use GuzzleHttp\RequestOptions;
use InvalidArgumentException;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'AUTHELIA';

    protected $scopes = ['openid profile email groups'];

    public static function additionalConfigKeys(): array
    {
        return ['base_url'];
    }

    protected function getBaseUrl()
    {
        $baseurl = $this->getConfig('base_url');
        if ($baseurl === null) {
            throw new InvalidArgumentException('Missing base_url');
        }

        return rtrim($baseurl, '/');
    }

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase($this->getBaseUrl() . '/api/oidc/authorization', $state);
    }

    protected function getTokenUrl(): string
    {
        return $this->getBaseUrl() . '/api/oidc/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get($this->getBaseUrl() . '/api/oidc/userinfo', [
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer ' . $token,
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
            'email'              => $user['email'] ?? null,
            'email_verified'     => $user['email_verified'] ?? null,
            'alt_emails'         => $user['alt_emails'] ?? null,
            'name'               => $user['name'] ?? null,
            'preferred_username' => $user['preferred_username'],
            'groups'             => $user['groups'] ?? null,
            'id'                 => $user['sub'],
        ]);
    }
}
