<?php

namespace SocialiteProviders\ArcGIS;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    public const IDENTIFIER = 'ARCGIS';

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
            $this->getBaseUrl().'/sharing/rest/oauth2/authorize',
            $state
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return $this->getBaseUrl().'/sharing/rest/oauth2/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get(
            $this->getBaseUrl().'/sharing/rest/community/self',
            [
                RequestOptions::QUERY => [
                    'token' => $token,
                    'f'     => 'json',
                ],
            ]
        );

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'id'       => $user['username'],
            'nickname' => $user['username'],
            'name'     => $user['fullName'],
            'email'    => $user['email'],
            'avatar'   => $user['thumbnail'],
        ]);
    }

    protected function getServerHost()
    {
        return $this->getConfig('arcgis_host', 'www.arcgis.com');
    }

    protected function getServerPort()
    {
        return $this->getConfig('arcgis_port', null);
    }

    protected function getServerDirectory()
    {
        return $this->getConfig('arcgis_directory', null);
    }
}
