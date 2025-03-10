<?php

namespace SocialiteProviders\OSChina;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'OSCHINA';

    /**
     * @var string OAuth Domain
     */
    protected $domain = 'https://www.oschina.net';

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase($this->domain.'/action/oauth2/authorize', $state);
    }

    protected function getTokenUrl(): string
    {
        return $this->domain.'/action/openapi/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get($this->domain.'/action/openapi/user', [
            RequestOptions::QUERY => [
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
        return (new User)->setRaw($user)->map([
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
            'dataType' => 'json',
        ]);
    }
}
