<?php

namespace SocialiteProviders\Uber;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'UBER';

    protected $scopeSeparator = ' ';

    protected $scopes = ['profile'];

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase('https://login.uber.com/oauth/authorize', $state);
    }

    protected function getTokenUrl(): string
    {
        return 'https://login.uber.com/oauth/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get(
            'https://api.uber.com/v1/me',
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
        return (new User)->setRaw($user)->map([
            'id'    => $user['uuid'], 'nickname' => null,
            'name'  => $user['first_name'].' '.$user['last_name'],
            'email' => $user['email'], 'avatar' => $user['picture'],
        ]);
    }
}
