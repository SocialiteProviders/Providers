<?php

namespace SocialiteProviders\PropelAuth;

use GuzzleHttp\RequestOptions;
use InvalidArgumentException;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'PROPELAUTH';

    protected $scopes = [
        'email',
        'profile',
    ];

    protected $scopeSeparator = ' ';

    /**
     * Get the base URL.
     *
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    protected function getBaseUrl(): string
    {
        $baseUrl = $this->getConfig('auth_url');

        if ($baseUrl === null) {
            throw new InvalidArgumentException('Missing Auth URL value.');
        }

        return rtrim($baseUrl, '/');
    }

    public static function additionalConfigKeys(): array
    {
        return ['auth_url'];
    }

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase($this->getBaseUrl().'/propelauth/oauth/authorize', $state);
    }

    protected function getTokenUrl(): string
    {
        return $this->getBaseUrl().'/propelauth/oauth/token';
    }

    protected function getUserByToken($token): array
    {
        $response = $this->getHttpClient()->get($this->getBaseUrl().'/propelauth/oauth/userinfo', [
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer '.$token,
            ],
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    protected function mapUserToObject(array $user): User
    {
        return (new User)->setRaw($user)->map([
            'id'         => $user['user_id'],
            'email'      => $user['email'],
            'first_name' => $user['first_name'] ?? null,
            'last_name'  => $user['last_name'] ?? null,
        ]);
    }
}
