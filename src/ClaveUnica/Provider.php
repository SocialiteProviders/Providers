<?php

namespace SocialiteProviders\ClaveUnica;

use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    const IDENTIFIER = 'CLAVEUNICA';

    /**
     * {@inheritdoc}
     */
    protected $scopes = [
        'openid',
        'run',
        'name',
        'email'
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
        $response = $this->getHttpClient()->post('https://www.claveunica.gob.cl/openid/userinfo', [
            'headers' => [
                'Authorization' => 'Bearer '.$token,
            ],
        ]);

        return json_decode($response->getBody(), true);
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
            'dv'         => $user['RolUnico']['DV']
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenFields($code)
    {
        return array_merge(parent::getTokenFields($code), [
            'grant_type' => 'authorization_code'
        ]);
    }
}
