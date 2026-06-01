<?php

namespace SocialiteProviders\Linear;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    const IDENTIFIER = 'LINEAR';

    protected $scopes = ['read'];

    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase('https://linear.app/oauth/authorize', $state);
    }

    protected function getTokenUrl()
    {
        return 'https://api.linear.app/oauth/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->post('https://api.linear.app/graphql', [
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type'  => 'application/json',
            ],
            RequestOptions::BODY => json_encode([
                'query' => '{ viewer { id name email avatarUrl } }',
            ]),
        ]);

        return json_decode((string) $response->getBody(), true)['data']['viewer'];
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user)->map([
            'id'       => $user['id'],
            'name'     => $user['name'],
            'email'    => $user['email'],
            'avatar'   => $user['avatarUrl'],
        ]);
    }
}
