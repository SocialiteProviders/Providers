<?php

namespace SocialiteProviders\Pinterest;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'PINTEREST';

    protected $scopes = ['user_accounts:read'];

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase('https://www.pinterest.com/oauth/', $state);
    }

    protected function getTokenUrl(): string
    {
        return 'https://api.pinterest.com/v5/oauth/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get(
            'https://api.pinterest.com/v5/user_account',
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
        return (new User)->setRaw($user)->map(
            [
                'id'       => $user['username'],
                'nickname' => $user['username'],
                'name'     => null,
                'email'    => null,
                'avatar'   => $user['profile_image'],
            ]
        );
    }

    protected function getTokenHeaders($code)
    {
        return [
            'Authorization' => 'Basic '.base64_encode($this->clientId.':'.$this->clientSecret),
            'Content-Type'  => 'application/x-www-form-urlencoded',
        ];
    }
}
