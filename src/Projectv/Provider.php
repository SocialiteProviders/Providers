<?php

namespace SocialiteProviders\projectv;

use SocialiteProviders\Manager\OAuth2\User;
use Laravel\Socialite\Two\ProviderInterface;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;

class Provider extends AbstractProvider implements ProviderInterface
{
    /**
     * Unique Provider Identifier.
     */
    const IDENTIFIER = 'PROJECTV';

    /**
     * {@inheritdoc}
     */
    protected $scopes = [
        'https://v.enl.one/oauth/api/v1/email',
        'https://v.enl.one/oauth/api/v1/googledata',
        'https://v.enl.one/oauth/api/v1/userinfo',
    ];

    /**
     * {@inheritdoc}
     */
    protected $scopeSeparator = ' ';

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase(
            'https://v.enl.one/oauth/authorize', $state
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://v.enl.one/oauth/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get(
            'https://v.enl.one/oauth/verify', [
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
            'gid'         => array_get($user, 'data')['gid'],               //googledata
            'vid'         => array_get($user, 'data')['enlid'],             //profile
            'nickname'    => array_get($user, 'data')['agent'],             //profile
            'forename'    => array_get($user, 'data')['forename'],          //googledata
            'lastname'    => array_get($user, 'data')['lastname'],          //googledata
            'avatarurl'   => array_get($user, 'data')['imageurl'],          //googledata
            'email'       => array_get($user, 'data')['email'],             //email
            'vlevel'      => array_get($user, 'data')['vlevel'],            //profile
            'vpoints'     => array_get($user, 'data')['vpoints'],           //profile
            'quarantine'  => array_get($user, 'data')['quarantine'],        //profile
            'active'      => array_get($user, 'data')['active'],            //profile
            'blacklisted' => array_get($user, 'data')['blacklisted'],       //profile
            'verified'    => array_get($user, 'data')['verified'],          //profile
            'flagged'     => array_get($user, 'data')['flagged'],            //profile
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
