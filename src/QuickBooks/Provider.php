<?php

namespace SocialiteProviders\QuickBooks;

use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    public const IDENTIFIER = 'QUICKBOOKS';

    /**
     * Sandbox endpoint for retrieving user info.
     */
    public const USER_SANDBOX_ENDPOINT = 'https://sandbox-accounts.platform.intuit.com/v1/openid_connect/userinfo';

    /**
     * Production endpoint for retrieving user info.
     */
    public const USER_PRODUCTION_ENDPOINT = 'https://accounts.platform.intuit.com/v1/openid_connect/userinfo';

    /**
     * {@inheritdoc}
     */
    protected $scopes = ['openid', 'profile', 'email'];

    /**
     * {@inheritdoc}
     */
    protected $scopeSeparator = ' ';

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
        $endpoint = self::USER_PRODUCTION_ENDPOINT;

        if ($this->getConfig('env', 'production') === 'development') {
            $endpoint = self::USER_SANDBOX_ENDPOINT;
        }

        $response = $this->getHttpClient()->get($endpoint, [
            'headers' => [
                'Accept'        => 'application/json',
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
            'id'    => $user['sub'],
            'name'  => trim(sprintf('%s %s', $user['givenName'], $user['familyName'])),
            'email' => $user['email'],
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
    public static function additionalConfigKeys()
    {
        return ['env'];
    }
}
