<?php

namespace SocialiteProviders\Gitea;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'GITEA';

    protected $scopeSeparator = ' ';

    protected static array $additionalConfigKeys = ['instance_uri'];

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase($this->getInstanceUri().'login/oauth/authorize', $state);
    }

    protected function getTokenUrl(): string
    {
        return $this->getInstanceUri().'login/oauth/access_token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get($this->getInstanceUri().'api/v1/user', [
            RequestOptions::HEADERS => [
                'Authorization' => 'token '.$token,
            ],
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user)->map([
            'id'       => $user['id'],
            'nickname' => $user['login'],
            'name'     => $user['full_name'],
            'email'    => $user['email'],
            'avatar'   => $user['avatar_url'],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getInstanceUri()
    {
        return $this->getConfig('instance_uri', 'http://gitea:3000/');
    }
}
