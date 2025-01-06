<?php

namespace SocialiteProviders\DigitalOcean;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'DIGITALOCEAN';

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase('https://cloud.digitalocean.com/v1/oauth/authorize', $state);
    }

    protected function getTokenUrl(): string
    {
        return 'https://cloud.digitalocean.com/v1/oauth/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get(
            'https://api.digitalocean.com/v2/account',
            [
                RequestOptions::HEADERS => [
                    'Authorization' => 'Bearer '.$token,
                ],
            ]
        );

        return json_decode((string) $response->getBody(), true)['account'];
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user)->map([
            'id'     => $user['uuid'], 'nickname' => null, 'name' => null,
            'email'  => $user['email'],
            'avatar' => 'https://www.gravatar.com/avatar/'.md5($user['email']),
        ]);
    }
}
