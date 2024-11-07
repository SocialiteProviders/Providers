<?php

namespace SocialiteProviders\Salla;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'SALLA';

    protected $scopeSeparator = ' ';

    protected $scopes = [
        'offline_access',
    ];

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase('https://accounts.salla.sa/oauth2/auth', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://accounts.salla.sa/oauth2/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get('https://accounts.salla.sa/oauth2/user/info', [
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
        $data = $user['data'];

        return (new User)->setRaw($user)->map([
            'id'     => $data['id'],
            'name'   => $data['name'] ?? null,
            'email'  => $data['email'] ?? null,
            'mobile' => $data['mobile'] ?? null,
            'avatar' => $data['merchant']['avatar'] ?? null,
            'domain' => $data['merchant']['domain'] ?? null,
        ]);
    }
}
