<?php

namespace SocialiteProviders\Imgur;

use SocialiteProviders\Manager\OAuth2\User;
use Laravel\Socialite\Two\ProviderInterface;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;

class Provider extends AbstractProvider implements ProviderInterface
{
    /**
     * Unique Provider Identifier.
     */
    const IDENTIFIER = 'IMGUR';

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase(
            'https://api.imgur.com/oauth2/authorize', $state
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://api.imgur.com/oauth2/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get(
            'https://api.imgur.com/3/account/me', [
            'headers' => [
                'Authorization' => 'Bearer '.$token,
            ],
        ]);
        $response2 = $this->getHttpClient()->get(
            'https://api.imgur.com/3/account/me/settings', [
            'headers' => [
                'Authorization' => 'Bearer '.$token,
            ],
        ]);


        return array_merge(json_decode($response->getBody()->getContents(), true)['data'], json_decode($response2->getBody()->getContents(), true)['data']);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'id' => $user['id'],
            'nickname' => $user['url'],
            'name' => $user['url'],
            'email' => $user['email'],
            'avatar' => null,
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
