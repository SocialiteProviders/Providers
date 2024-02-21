<?php

namespace SocialiteProviders\Clover;

use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    const IDENTIFIER = 'CLOVER';

    /**
     * {@inheritdoc}
     */
    protected $scopes = [''];

    /**
     * Tests whether we are integrating to the Clover sandbox or production environments
     * Change the value of CLOVER_ENVIRONMENT in your .env to switch.
     *
     * @return bool
     */
    protected function useSandbox()
    {
        return $this->getConfig('environment') !== 'production';
    }

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        $domain = match ($this->useSandbox()) {
            'sandbox' => 'sandbox.dev.clover.com',
            default => 'www.clover.com',
        };

        return $this->buildAuthUrlFromBase('https://'.$domain.'/oauth/v2/authorize', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        $domain = match ($this->useSandbox()) {
            'sandbox' => 'apisandbox.dev.clover.com',
            default => 'api.clover.com',
        };

        return 'https://'.$domain.'/oauth/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get('', [
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
            'id'       => $user['id'],
            'nickname' => $user['username'],
            'name'     => $user['name'],
            'email'    => $user['email'],
            'avatar'   => $user['avatar'],
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
