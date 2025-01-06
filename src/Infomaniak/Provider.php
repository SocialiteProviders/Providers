<?php

namespace SocialiteProviders\Infomaniak;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    const IDENTIFIER = 'INFOMANIAK';

    protected $scopes = ['openid', 'email', 'profile'];

    protected $scopeSeparator = ' ';

    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase('https://login.infomaniak.com/authorize', $state);
    }

    protected function getTokenUrl(): string
    {
        return 'https://login.infomaniak.com/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get('https://login.infomaniak.com/oauth2/userinfo', [
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
        return (new User)->setRaw($user)->map([
            'id'     => $user['sub'],
            'name'   => $user['name'],
            'email'  => $user['email'],
            'avatar' => $user['picture'],
        ]);
    }
}
