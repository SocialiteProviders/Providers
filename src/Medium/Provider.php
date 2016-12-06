<?php

namespace SocialiteProviders\Medium;

use SocialiteProviders\Manager\OAuth2\User;
use Laravel\Socialite\Two\ProviderInterface;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;

class Provider extends AbstractProvider implements ProviderInterface
{
    /**
     * Unique Provider Identifier.
     */
    const IDENTIFIER = 'MEDIUM';

    protected $scopes = ['basicProfile'];

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase(
            'https://medium.com/m/oauth/authorize', $state
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://api.medium.com/v1/tokens';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get(
            'https://api.medium.com/v1/me', [
            'headers' => [
                'Authorization' => 'Bearer '.$token,
            ],
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'id' => $user['data']['id'],
            'name' => $user['data']['name'],

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
