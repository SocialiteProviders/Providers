<?php

namespace SocialiteProviders\Identity;

use Laravel\Socialite\Two\ProviderInterface;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider implements ProviderInterface
{
    /**
     * Unique Provider Identifier.
     */
    const IDENTIFIER = 'IDENTITY';

    /**
     * {@inheritdoc}
     */
    protected function getBaseUrl()
    {
        $port = is_null($this->getServerPort()) ? '' : ':'.$this->getServerPort();
        $subdirectory = is_null($this->getServerDirectory()) ? '' : '/'.$this->getServerDirectory();

        return 'https://'.$this->getServerHost().$port.$subdirectory;
    }

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase(
            $this->getBaseUrl().'/oauth2/authorize?scope=openid&', $state
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return $this->getBaseUrl().'/oauth2/token?scope=openid&';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get(
            $this->getBaseUrl().'/userinfo', [
            'query' => [
                'schema' => 'openid',
            ],
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
            'id'       => $user['id'],
            'nickname' => $user['username'],
            'name'     => $user['fullName'],
            'email'    => $user['email'],
            'avatar'   => $user['thumbnail'],
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
    protected function getServerHost()
    {
        return $this->getConfig('identity_host', null);
    }

    /**
     * {@inheritdoc}
     */
    protected function getServerPort()
    {
        return $this->getConfig('identity_port', null);
    }

    /**
     * {@inheritdoc}
     */
    protected function getServerDirectory()
    {
        return $this->getConfig('identity_directory', null);
    }

}