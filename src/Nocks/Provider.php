<?php

namespace SocialiteProviders\Nocks;

use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    const IDENTIFIER = 'NOCKS';

    /**
     * {@inheritdoc}
     */
    protected $scopes = ['user.read'];

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase($this->getWebsiteUrl().'oauth/authorize', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return $this->getWebsiteUrl().'oauth/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get($this->getApiUrl().'user', [
            'headers' => [
                'Authorization' => 'Bearer '.$token,
                'Accept'        => 'application/json',
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
            'id'                => $user['data'][0]['uuid'],
            'nickname'          => null,
            'name'              => null,
            'email'             => $user['data'][0]['email'],
            'avatar'            => null,
            'email_verified'    => $user['data'][0]['email_verified'],
            'gender'            => $user['data'][0]['gender'],
            'first_name'        => $user['data'][0]['first_name'],
            'last_name'         => $user['data'][0]['last_name'],
            'mobile'            => $user['data'][0]['mobile'],
            'mobile_verified'   => $user['data'][0]['mobile_verified'],
            'is_active'         => $user['data'][0]['is_active'],
            'is_verified'       => $user['data'][0]['is_verified'],
            '2fa_enabled'       => $user['data'][0]['2fa_enabled'],
            'identity_verified' => $user['data'][0]['identity_verified'],
            'locale'            => $user['data'][0]['locale'],
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
    protected function getWebsiteUrl()
    {
        if ($this->getConfig('test')) {
            return 'https://sandbox.nocks.com/';
        }

        return 'https://www.nocks.com/';
    }

    /**
     * {@inheritdoc}
     */
    protected function getApiUrl()
    {
        if ($this->getConfig('test')) {
            return 'https://sandbox.nocks.com/api/v2/';
        }

        return 'https://api.nocks.com/api/v2/';
    }

    /**
     * {@inheritdoc}
     */
    public static function additionalConfigKeys()
    {
        return ['test'];
    }
}
