<?php

namespace SocialiteProviders\Authentik;

use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    const IDENTIFIER = 'AUTHENTIK';

    /**
     * {@inheritdoc}
     */
    protected $scopes = ['openid goauthentik.io/api profile email'];

    /**
     * {@inheritdoc}
     */
    public static function additionalConfigKeys()
    {
        return ['base_url'];
    }

    protected function getBaseUrl()
    {
        return rtrim($this->getConfig('base_url'), '/');
    }

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase($this->getBaseUrl().'/application/o/authorize/', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return $this->getBaseUrl().'/application/o/token/';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get($this->getBaseUrl().'/application/o/userinfo/', [
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
        return (new User())->setRaw($user)->map([
            'email'              => $user['email'] ?? "",
            'email_verified'     => $user['email_verified'] ?? null,
            'name'               => $user['name'] ?? "",
            'given_name'         => $user['given_name'] ?? "",
            'family_name'        => $user['family_name'] ?? "",
            'preferred_username' => $user['preferred_username'] ?? "",
            'nickname'           => $user['nickname'] ?? "",
            'groups'             => $user['groups'] ?? "",
            'sub'                => $user['sub'] ?? "",
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
