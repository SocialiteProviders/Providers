<?php

namespace SocialiteProviders\Coursera;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'COURSERA';

    /**
     * {@inheritdoc}
     */
    protected $scopes = ['view_profile'];

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase('https://accounts.coursera.org/oauth2/v1/auth', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://accounts.coursera.org/oauth2/v1/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get(
            'https://api.coursera.org/api/externalBasicProfiles.v1?q=me&fields=timezone,locale,privacy,name',
            [
                RequestOptions::HEADERS => [
                    'Authorization' => 'Bearer '.$token,
                ],
            ]
        );

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'id'     => $user['elements'][0]['id'], 'nickname' => null,
            'name'   => $user['elements'][0]['name'], 'email' => null,
            'avatar' => null,
        ]);
    }
}
