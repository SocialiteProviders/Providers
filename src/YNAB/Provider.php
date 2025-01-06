<?php

namespace SocialiteProviders\YNAB;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'YNAB';

    /**
     * Scopes
     *
     * @var string
     */
    public const SCOPE_READONLY = 'read-only';

    protected $scopes = [
        self::SCOPE_READONLY,
    ];

    protected $scopeSeparator = ' ';

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase('https://api.ynab.com/oauth/authorize', $state);
    }

    protected function getTokenUrl(): string
    {
        return 'https://api.ynab.com/oauth/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token): array
    {
        $response = $this->getHttpClient()->get('https://api.ynab.com/v1/user', [
            RequestOptions::HEADERS => [
                'Authorization' => "Bearer $token",
            ],
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * Returned user array containing all available user attributes.
     * YNAB only returns the user's UUIDv4.
     *
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user)->map([
            'id'       => $user['id'],
            'nickname' => null,
            'name'     => null,
            'email'    => null,
            'avatar'   => null,
        ]);
    }
}
