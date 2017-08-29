<?php

namespace SocialiteProviders\ArcGIS;

use SocialiteProviders\Manager\OAuth2\User;
use Laravel\Socialite\Two\ProviderInterface;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;

class Provider extends AbstractProvider implements ProviderInterface
{
    /**
     * Unique Provider Identifier.
     */
    const IDENTIFIER = 'ARCGIS';

    /**
     * {@inheritdoc}
     */
    protected function getBaseUrl()
    {
        $port = is_null($this->getServerPort()) ? '' : ':' . $this->getServerPort();
        $subdirectory = is_null($this->getServerDirectory()) ? '' : '/' . $this->getServerDirectory();
        return 'https://' . $this->getServerHost() . $port . $subdirectory;
    }

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase(
            $this->getBaseUrl() . '/sharing/rest/oauth2/authorize', $state
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return $this->getBaseUrl() . '/sharing/rest/oauth2/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get(
            $this->getBaseUrl() . '/sharing/rest/community/self', [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
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
            'id' => $user['username'],
            'nickname' => $user['username'],
            'name' => $user['fullName'],
            'email' => $user['email'],
            'avatar' => $user['thumbnail'],
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
        return $this->getConfig('arcgis_host', 'www.arcgis.com');
    }

    /**
     * {@inheritdoc}
     */
    protected function getServerPort()
    {
        return $this->getConfig('arcgis_port', null);
    }

    /**
     * {@inheritdoc}
     */
    protected function getServerDirectory()
    {
        return $this->getConfig('arcgis_directory', null);
    }

}
