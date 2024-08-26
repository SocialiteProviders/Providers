<?php

namespace SocialiteProviders\HabrCareer;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'HABRCAREER';

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase('https://career.habr.com/integrations/oauth/authorize', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://career.habr.com/integrations/oauth/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get('https://career.habr.com/api/v1/integrations/users/me', [
            RequestOptions::QUERY => [
                'access_token' => $token,
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
            'id'       => $user['login'],
            'nickname' => $user['login'],
            'name'     => trim($user['first_name'].' '.$user['last_name']),
            'email'    => $user['email'],
            'avatar'   => $user['avatar'],
        ]);
    }

    protected function isStateless()
    {
        return true;
    }
}
