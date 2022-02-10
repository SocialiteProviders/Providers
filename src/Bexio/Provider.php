<?php

namespace SocialiteProviders\Bexio;

use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    const IDENTIFIER = 'BEXIO';

    /**
     * {@inheritdoc}
     */
    protected $scopes = ['openid profile'];

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase('https://idp.bexio.com/authorize', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://idp.bexio.com/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get('https://idp.bexio.com/userinfo', [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
            ],
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user): User
    {
        $fullName = [];
        if (!empty($user['given_name'])) {
            $fullName[] = $user['given_name'];
        }
        if (!empty($user['given_name'])) {
            $fullName[] = $user['family_name'];
        }

        return (new User())->setRaw($user)->map([
            'name' => implode(' ', $fullName),
            'email' => $user['sub'],
            'given_name' => $user['given_name'],
            'family_name' => $user['family_name'],
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
