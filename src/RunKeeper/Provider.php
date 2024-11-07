<?php

namespace SocialiteProviders\RunKeeper;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'RUNKEEPER';

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase('https://runkeeper.com/apps/authorize', $state);
    }

    protected function getTokenUrl(): string
    {
        return 'https://runkeeper.com/apps/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get(
            'https://api.runkeeper.com/user',
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
            'id'    => $user['userID'], 'nickname' => null, 'name' => null,
            'email' => null, 'avatar' => null,
        ]);
    }
}
