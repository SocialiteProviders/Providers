<?php

namespace SocialiteProviders\Disqus;

use Illuminate\Support\Arr;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    const IDENTIFIER = 'DISQUS';

    /**
     * {@inheritdoc}
     */
    protected $scopes = ['read'];

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase(
            'https://disqus.com/api/oauth/2.0/authorize', $state
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://disqus.com/api/oauth/2.0/access_token/';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get(
            'https://disqus.com/api/3.0/users/details.json', [
            'query' => [
                'access_token' => $token, 'api_key' => $this->clientId,
                'api_secret'   => $this->clientSecret,
            ],
        ]);

        return json_decode($response->getBody()->getContents(), true)['response'];
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'id'     => $user['id'], 'nickname' => $user['username'],
            'name'   => $user['name'], 'email' => Arr::get($user, 'email'),
            'avatar' => $user['avatar']['permalink'],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenFields($code)
    {
        return array_merge(parent::getTokenFields($code), [
            'grant_type' => 'authorization_code',
        ]);
    }
}
