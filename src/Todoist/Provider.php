<?php

namespace SocialiteProviders\Todoist;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'TODOIST';

    public const AUTH_URL = 'https://todoist.com/oauth/authorize';

    public const TOKEN_URL = 'https://todoist.com/oauth/access_token';

    public const SYNC_URL = 'https://api.todoist.com/sync/v9/sync';

    protected $scopes = [
        'data:read',
    ];

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase(self::AUTH_URL, $state);
    }

    protected function getTokenUrl(): string
    {
        return self::TOKEN_URL;
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get(self::SYNC_URL, [
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer '.$token,
            ],
            RequestOptions::QUERY => [
                'sync_token'     => '*',
                'resource_types' => json_encode(['user']),
            ],
        ]);

        return json_decode((string) $response->getBody(), true)['user'];
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user)->map([
            'id'       => $user['id'],
            'name'     => $user['full_name'],
            'email'    => $user['email'],
            'avatar'   => null,
        ]);
    }
}
