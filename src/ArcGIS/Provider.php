<?php

namespace SocialiteProviders\ArcGIS;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'ARCGIS';

    protected function getBaseUrl()
    {
        $port = null === $this->getServerPort() ? '' : ':'.$this->getServerPort();
        $subdirectory = null === $this->getServerDirectory() ? '' : '/'.$this->getServerDirectory();

        return 'https://'.$this->getServerHost().$port.$subdirectory;
    }

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase($this->getBaseUrl().'/sharing/rest/oauth2/authorize', $state);
    }

    protected function getTokenUrl(): string
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
        return (new User)->setRaw($user)->map([
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
        return $this->getConfig('arcgis_port');
    }

    protected function getServerDirectory()
    {
        return $this->getConfig('arcgis_directory');
    }
}
