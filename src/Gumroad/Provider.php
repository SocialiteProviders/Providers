<?php

namespace SocialiteProviders\Gumroad;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'GUMROAD';

    protected $scopes = ['view_sales'];

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase('https://gumroad.com/oauth/authorize', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://gumroad.com/oauth/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get('https://api.gumroad.com/v2/user', [
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer '.$token,
            ],
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        $user = $user['user'];

        return (new User)->setRaw($user)->map([
            'id'       => $user['user_id'],
            'nickname' => $user['twitter_handle'],
            'name'     => $user['name'],
        ]);
    }
}
