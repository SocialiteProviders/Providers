<?php

namespace SocialiteProviders\Neto;

use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    public const IDENTIFIER = 'NETO';

    /**
     * {@inheritdoc}
     */
    protected $scopes = [
        'api',
        'user',
        'address',
    ];

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        $this->with([
            'store_domain' => $this->getConfig('app_portal_domain'),
        ]);

        return $this->buildAuthUrlFromBase('https://apps.getneto.com/oauth/v2/auth', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://'.$this->getConfig('app_portal_domain').'/oauth/v2/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get($this->getTokenUrl(), [
            'headers' => [
                'X_ACCESS_KEY' => $this->getConfig('client_id'),
                'X_SECRET_KEY' => $token,
            ],
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'id'       => $user['user']['id'],
            'nickname' => null,
            'name'     => $user['user']['first_name'].' '.$user['user']['last_name'],
            'email'    => $user['user']['email'],
            'avatar'   => null,
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

    /**
     * {@inheritdoc}
     */
    public static function additionalConfigKeys()
    {
        return ['app_portal_domain'];
    }
}
