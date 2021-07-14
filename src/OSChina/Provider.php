<?php

namespace SocialiteProviders\OSChina;

use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    public const IDENTIFIER = 'OSCHINA';

    /**
     * {@inheritdoc}
     */
    protected $scopes = [''];

    /**
     * @var string OAuth Domain
     */
    protected $domain = 'https://www.oschina.net';

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase($this->domain.'/action/oauth2/authorize', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return $this->domain.'/action/openapi/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get($this->domain.'/action/openapi/user', [
            'query' => [
                'access_token' => $token,
                'dataType'     => 'json',
            ],
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'id'         => $user['id'],
            'name'       => $user['name'],
            'nickname'   => $user['name'],
            'email'      => $user['email'],
            'avatar'     => $user['avatar'],
            'gender'     => $user['gender'],
            'location'   => $user['location'],
            'url'        => $user['url'],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenFields($code)
    {
        return array_merge(parent::getTokenFields($code), [
            'grant_type' => 'authorization_code',
            'dataType'   => 'json',
        ]);
    }
}
