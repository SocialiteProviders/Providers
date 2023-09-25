<?php

namespace SocialiteProviders\MinistryPlatform;

use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    public const IDENTIFIER = 'MINISTRYPLATFORM';

    /**
     * Define the allowed scopes.
     */
    protected $scopes = ['http://www.thinkministry.com/dataplatform/scopes/all', 'openid', 'offline_access'];

    /**
     * {@inheritdoc}
     */

    protected function baseUrl()
    {
        return $this->getConfig('base_url');
    }

    /**
    * Indicates if PKCE should be used.
    *
    * @var bool
    */
    protected $usesPKCE = true;

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase($this->baseUrl().'/oauth/connect/authorize', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return $this->baseUrl().'/oauth/connect/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        
        $response = $this->getHttpClient()->get($this->baseUrl().'/oauth/connect/userinfo', [
            \GuzzleHttp\RequestOptions::HEADERS => [
                'Authorization' => 'Bearer '.$token,
                'Accept: application/json', 
                'Content-type: application/json',
                'Scope: http://www.thinkministry.com/dataplatform/scopes/all openid'
            ]
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'id' => $user['userid'],
            'name' => $user['display_name'],
            'nickname' => $user['nickname'],
            'email' => $user['email'],
            'zoneinfo' => $user['zoneinfo'],
            'locale' => $user['locale'],
        ]);
    }
}
