<?php

namespace SocialiteProviders\Salesloft;

use SocialiteProviders\Manager\OAuth2\User;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'SALESLOFT';
    public const PROVIDER_NAME = 'salesloft';

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase(
            'https://accounts.salesloft.com/oauth/authorize',
            $state
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl(): string
    {
        return 'https://accounts.salesloft.com/oauth/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get(
            'https://api.salesloft.com/v2/me',
            [
                'headers' => [
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer ' . $token,
                ],
            ]
        );

        $response = json_decode($response->getBody()->getContents(), true);

        return $response['data'];
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user): User
    {
        return (new User())->setRaw($user)->map([
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
        ]);
    }
}
