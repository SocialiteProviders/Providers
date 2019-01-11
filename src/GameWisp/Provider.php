<?php

namespace SocialiteProviders\GameWisp;

use Illuminate\Support\Arr;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    const IDENTIFIER = 'GAMEWISP';

    /**
     * {@inheritdoc}
     */
    protected $scopes = ['user_read'];

    /**
     * {@inherticdoc}.
     */
    protected $scopeSeparator = ',';

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase(
            'https://api.gamewisp.com/pub/v1/oauth/authorize', $state
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://api.gamewisp.com/pub/v1/oauth/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $query = [
            'access_token' => $token,
            'include'      => 'profile.picture',
        ];

        $response = $this->getHttpClient()->get(
            'https://api.gamewisp.com/pub/v1/user/information', [
            'query'   => $query,
            'headers' => [
                'Accept'        => 'application/json',
                'Authorization' => 'Bearer '.$token,
            ],
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        //need to do some work here to get the email address and the profile picture.
        //hotfix the api to return email addresses with the user object.
        return (new User())->setRaw($user)->map([
            'id'          => Arr::get($user, 'data.id'), 'username' => Arr::get($user, 'data.username'),
            'email'       => Arr::get($user, 'data.email'), 'avatar' => Arr::get($user, 'data.profile.data.picture.data.content'),
            'deactivated' => Arr::get($user, 'data.deactivated'), 'banned' => Arr::get($user, 'data.banned'),
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
