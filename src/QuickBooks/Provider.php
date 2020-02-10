<?php

namespace SocialiteProviders\QuickBooks;

use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    const IDENTIFIER = 'QUICKBOOKS';

    /**
     * The separating character for the requested scopes.
     *
     * @var string
     */
    protected $scopeSeparator = ' ';

    /**
     * {@inheritdoc}
     */
    protected $scopes = ['openid','profile','email','com.intuit.quickbooks.accounting'];

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
        if ( isset($this->parameters['sandbox']) && $this->parameters['sandbox'] ) {
            $url = 'https://sandbox-accounts.platform.intuit.com/v1/openid_connect/userinfo';
        } else {
            $url = 'https://accounts.platform.intuit.com/v1/openid_connect/userinfo';
        }
        $response = $this->getHttpClient()->get($url, [
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
        return (new User())->map([
            'id'       => $user['sub'],
            'nickname' => $user['givenName'],
            'name'     => $user['givenName'] . ' ' . $user['familyName'],
            'email'    => $user['email'],
            'avatar'   => null,
            'realmId' => $_GET['realmId']
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
