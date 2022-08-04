<?php

namespace SocialiteProviders\Todoist;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    public const IDENTIFIER = 'TODOIST';

    public const AUTH_URL = 'https://todoist.com/oauth/authorize';

    public const TOKEN_URL = 'https://todoist.com/oauth/access_token';

    public const SYNC_URL = 'https://api.todoist.com/sync/v8/sync';

    /**
     * {@inheritdoc}
     */
    protected $scopes = [
        'data:read',
    ];

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase(
            self::AUTH_URL,
            $state
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return self::TOKEN_URL;
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get(self::SYNC_URL, [
            RequestOptions::QUERY => [
                'token' => $token,
                'sync_token' => '*',
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
        return (new User())->setRaw($user)->map([
            'id' => $user['id'],
            'name' => $user['full_name'],
            'email' => $user['email'],
            'avatar' => null,
        ]);
    }
}
