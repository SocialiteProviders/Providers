<?php

namespace SocialiteProviders\Monzo;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'MONZO';

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase('https://auth.monzo.com/', $state);
    }

    protected function getTokenUrl(): string
    {
        return 'https://api.monzo.com/oauth2/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get('https://api.monzo.com/ping/whoami', [
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
            'id' => $user['user_id'],
        ]);
    }
}
