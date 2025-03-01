<?php

namespace SocialiteProviders\ImmutableX;

use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;
use SocialiteProviders\Manager\OAuth2\ProviderInterface;

class Provider extends AbstractProvider implements ProviderInterface
{
    protected $scopes = ['openid', 'email', 'offline_access'];

    /**
     * Get the authorization URL for Immutable X OAuth2.
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase('https://auth.immutable.com/oauth/authorize', $state);
    }

    /**
     * Get the token URL to exchange an authorization code for an access token.
     */
    protected function getTokenUrl()
    {
        return 'https://auth.immutable.com/oauth/token';
    }

    /**
     * Retrieve the user’s information using the access token.
     */
    public function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get('https://auth.immutable.com/userinfo', [
            'headers' => ['Authorization' => 'Bearer ' . $token],
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * Map Immutable X user object to Socialite User.
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'id'       => $user['sub'] ?? null,
            'nickname' => null,
            'name'     => null,
            'email'    => $user['email'] ?? null,
            'avatar'   => null,
            'email_verified' => $user['email_verified'] ?? false,
        ]);
    }
}
