<?php

namespace SocialiteProviders\Wonderlist;

use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    const IDENTIFIER = 'WONDERLIST';

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase(
            'https://www.wunderlist.com/oauth/authorize', $state
        );
    }
    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://www.wunderlist.com/oauth/access_token';
    }
    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $config = app()->make('config');
        $response = $this->getHttpClient()->get('a.wunderlist.com/api/v1/user', [
            'headers' => [
                'X-Access-Token' => $token,
                'X-Client-ID' => $config->get('services.wonderlist.client_id')
            ],
        ]);
        return json_decode($response->getBody()->getContents(), true);
    }
    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user)->map([
            'id'       => $user['id'],
            'name'     => $user['name'],
            'email'    => $user['email']
        ]);
    }
}
