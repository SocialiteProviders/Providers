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
     * Indicates if the session state should be utilized.
     *
     * @var bool
     */
    protected $stateless = true;

    protected function getApiDomain(): string
    {
        return match (true) {
            config('services.clover.sandbox-mode') => 'sandbox.dev.clover.com',
            default => 'www.clover.com',
        };
    }

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase('https://'.$this->getApiDomain().'/oauth/v2/authorize', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        $domain = match (true) {
            config('services.clover.sandbox-mode') => 'apisandbox.dev.clover.com',
            default => 'api.clover.com',
        };

        return 'https://'.$domain.'/oauth/token?'.Arr::query([
            'client_id' => config('services.clover.client_id'),
            'client_secret' => config('services.clover.client_secret'),
            'code' => $this->getCode(),
        ]);
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
