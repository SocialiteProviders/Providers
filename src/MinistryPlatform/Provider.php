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
     * {@inheritdoc}
     */
    protected $scopes = ['http://www.thinkministry.com/dataplatform/scopes/all', 'openid', 'offline_access'];

    /**
     * {@inheritdoc}
     */

    protected function getMPUrl()
    {
        return $this->getConfig('base_url');
    }

    // /**
    //  * Indicates if PKCE should be used.
    //  *
    //  * @var bool
    //  */
    protected $usesPKCE = true;

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase($this->getMPUrl().'/oauth/connect/authorize', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return $this->getMPUrl().'/oauth/connect/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        
        $response = $this->getHttpClient()->get($this->getMPUrl().'/oauth/connect/userinfo', [
            'headers' => [
                'Authorization' => 'Bearer '.$token,
                'Accept: application/json', 
                'Content-type: application/json',
                'Scope: http://www.thinkministry.com/dataplatform/scopes/all openid'
            ]
        ]);

        return json_decode($response->getBody(), true);
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
