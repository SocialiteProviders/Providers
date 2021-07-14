<?php

namespace SocialiteProviders\Nocks;

use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    public const IDENTIFIER = 'NOCKS';

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

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     * Please see https://docs.nocks.com/#users.
     */
    protected function mapUserToObject(array $user)
    {
        $data = $user['data'][0];

        return (new User())->setRaw($user)->map([
            'id'                => $data['uuid'],
            'nickname'          => null,
            'name'              => null,
            'email'             => $data['email'],
            'avatar'            => null,
            'email_verified'    => $data['email_verified'],
            'gender'            => $data['gender'],
            'first_name'        => $data['first_name'],
            'last_name'         => $data['last_name'],
            'mobile'            => $data['mobile'],
            'mobile_verified'   => $data['mobile_verified'],
            'is_active'         => $data['is_active'],
            'is_verified'       => $data['is_verified'],
            '2fa_enabled'       => $data['2fa_enabled'],
            'identity_verified' => $data['identity_verified'],
            'locale'            => $data['locale'],
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

    protected function getWebsiteUrl()
    {
        if ($this->getConfig('test')) {
            return 'https://sandbox.nocks.com/';
        }

        return 'https://www.nocks.com/';
    }

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
