<?php

namespace SocialiteProviders\Rekono;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'REKONO';

    protected $scopes = [
        'openid',
        'profile',
        'email',
    ];

    protected $scopeSeparator = ' ';

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase('https://idp.rekono.si/openid-connect-server-webapp/authorize', $state);
    }

    protected function getTokenUrl(): string
    {
        return 'https://idp.rekono.si/openid-connect-server-webapp/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->post(
            'https://idp.rekono.si/openid-connect-server-webapp/userinfo',
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
    protected function mapUserToObject($user)
    {
        return (new User)->setRaw($user)->map([
            'id'    => $user['sub'],
            'name'  => $user['name'] ?? null,
            'email' => $user['email'],
        ]);
    }
}
