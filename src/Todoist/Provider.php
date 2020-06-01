<?php

namespace SocialiteProviders\Todoist;

use Illuminate\Support\Arr;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    const IDENTIFIER = 'TODOIST';

    const AUTH_URL = 'https://todoist.com/oauth/authorize';
    const TOKEN_URL = 'https://todoist.com/oauth/access_token';
    const SYNC_URL = 'https://api.todoist.com/sync/v8/sync';

    /**
     * {@inheritdoc}
     */
    protected $scopes = [
        'data:read',
    ];

    /**
     * {@inheritdoc}
     */
    public static function additionalConfigKeys()
    {
        return ['base_url'];
    }

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
            'query' => [
                'token'          => $token,
                'sync_token'     => '*',
                'resource_types' => json_encode(['user']),
            ],
        ]);

        return json_decode($response->getBody(), true)['user'];
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'id'       => $user['id'],
            'name'     => $user['full_name'],
            'email'    => $user['email'],
            'avatar'   => null,
        ]);
    }
}
