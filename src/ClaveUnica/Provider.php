<?php

namespace SocialiteProviders\ClaveUnica;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'CLAVEUNICA';

    protected $scopes = [
        'openid',
        'run',
        'name',
    ];

    protected $scopeSeparator = ' ';

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase('https://accounts.claveunica.gob.cl/openid/authorize', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://accounts.claveunica.gob.cl/openid/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->post('https://accounts.claveunica.gob.cl/openid/userinfo', [
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
            'id'         => $user['RolUnico']['numero'],
            'name'       => $user['name'],
            'first_name' => implode(' ', $user['name']['nombres']),
            'last_name'  => implode(' ', $user['name']['apellidos']),
            'run'        => $user['RolUnico']['numero'],
            'dv'         => $user['RolUnico']['DV'],
        ]);
    }
}
