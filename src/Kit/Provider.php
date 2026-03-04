<?php

namespace SocialiteProviders\Kit;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'KIT';

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase(
            'https://app.kit.com/oauth/authorize',
            $state
        );
    }

    protected function getTokenUrl(): string
    {
        return 'https://app.kit.com/oauth/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get(
            'https://api.kit.com/v4/account',
            [
                RequestOptions::HEADERS => [
                    'Accept'        => 'application/json',
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
            'id'       => $user['user']['id'],
            'nickname' => null,
            'name'     => $user['account']['name'],
            'email'    => $user['user']['email'],
            'avatar'   => null,
        ]);
    }
}
