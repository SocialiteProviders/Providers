<?php

namespace SocialiteProviders\Rekono;

use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    public const IDENTIFIER = 'REKONO';

    /**
     * {@inheritdoc}
     */
    protected $scopes = [
        'openid',
        'profile',
        'email',
    ];

    /**
     * The separating character for the requested scopes.
     */
    protected $scopeSeparator = ' ';

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase(
            'https://idp.rekono.si/openid-connect-server-webapp/authorize',
            $state
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://idp.rekono.si/openid-connect-server-webapp/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->post(
            'https://idp.rekono.si/openid-connect-server-webapp/userinfo',
            [
                'headers' => [
                    'Authorization' => 'Bearer '.$token,
                ],
            ]
        );

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject($user)
    {
        return (new User())->setRaw($user)->map([
            'id'    => $user['sub'],
            'name'  => $user['name'] ?? null,
            'email' => $user['email'],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenFields($code)
    {
        return array_merge(parent::getTokenFields($code), [
            'grant_type' => 'authorization_code',
        ]);
    }
}
