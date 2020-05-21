<?php

namespace SocialiteProviders\Quickbooks2;

use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;
use Laravel\Socialite\Two\ProviderInterface;
use SocialiteProviders\Manager\ConfigTrait;

class Provider extends AbstractProvider implements ProviderInterface
{
    use ConfigTrait;

    /**
     * Unique Provider Identifier.
     */
    const IDENTIFIER = 'QUICKBOOKS';

    /**
     * Sandbox endpoint for retrieving user info.
     */
    const USERSANDBOXENDPOINT = 'https://sandbox-accounts.platform.intuit.com/v1/openid_connect/userinfo';

    /**
     * Production endpoint for retrieving user info.
     */
    const USERPRODUCTIONENDPOINT = 'https://accounts.platform.intuit.com/v1/openid_connect/userinfo';

    /**
     * {@inheritdoc}
     */
    protected $scopes = [];

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase('https://appcenter.intuit.com/connect/oauth2', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://oauth.platform.intuit.com/oauth2/v1/tokens/bearer';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $endpoint = $this->getConfig('app.env', 'local') == 'production' ? self::USERPRODUCTIONENDPOINT : self::USERSANDBOXENDPOINT;

        $response = $this->getHttpClient()->get($endpoint, [
            'headers' => [
                'Accept' => 'application/json',
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
            'id'       => $user['sub'],
            'name'     => $user['givenName'] ?? '' . ' ' . $user['familyName'] ?? '',
            'email'    => $user['email'],
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
