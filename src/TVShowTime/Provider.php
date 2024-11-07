<?php

namespace SocialiteProviders\TVShowTime;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'TVSHOWTIME';

    protected $scopes = ['basic'];

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase('https://www.tvshowtime.com/oauth/authorize', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://api.tvshowtime.com/v1/oauth/access_token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get('https://api.tvshowtime.com/v1/user', [
            RequestOptions::HEADERS => [
                'TVST_ACCESS_TOKEN' => $token,
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
            'id'       => $user['user']['id'],
            'nickname' => $user['user']['name'],
            'avatar'   => $user['user']['image'],
        ]);
    }
}
