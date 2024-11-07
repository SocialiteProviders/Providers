<?php

namespace SocialiteProviders\Dailymotion;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'DAILYMOTION';

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase('https://www.dailymotion.com/oauth/authorize', $state);
    }

    protected function getTokenUrl(): string
    {
        return 'https://api.dailymotion.com/oauth/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get(
            'https://api.dailymotion.com/auth',
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
            'id'   => $user['id'], 'nickname' => $user['username'],
            'name' => $user['screenname'], 'email' => null, 'avatar' => null,
        ]);
    }
}
