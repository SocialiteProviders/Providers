<?php

namespace SocialiteProviders\Intercom;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'INTERCOM';

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase('https://app.intercom.io/oauth', $state);
    }

    protected function getTokenUrl(): string
    {
        return 'https://api.intercom.io/auth/eagle/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get('https://api.intercom.io/me', [
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer '.$token,
                'Accept'        => 'application/json',
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
            'id'        => $user['id'],
            'name'      => $user['name'],
            'email'     => $user['email'],
            'avatar'    => $user['avatar']['image_url'],
        ]);
    }
}
