<?php

namespace SocialiteProviders\Klarna;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'KLARNA';

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase('https://login.klarna.com/eu/lp/idp/oauth2/auth', $state);
    }

    protected function getTokenUrl(): string
    {
        return 'https://login.klarna.com/eu/lp/idp/oauth2/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get('https://login.klarna.com/eu/lp/idp/userinfo', [
            RequestOptions::HEADERS => [
                'Accept'        => 'application/json',
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
            'id'    => $user['sub'],
            'name'  => $user['given_name'].' '.$user['family_name'],
            'email' => $user['email'],
        ]);
    }
}
