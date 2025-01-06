<?php

namespace SocialiteProviders\Mailru;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'MAILRU';

    protected $scopes = ['userinfo'];

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase('https://oauth.mail.ru/login', $state);
    }

    protected function getTokenUrl(): string
    {
        return 'https://oauth.mail.ru/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get('https://oauth.mail.ru/userinfo', [
            RequestOptions::QUERY => [
                'access_token' => $token,
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
            'nickname' => $user['nickname'],
            'name'     => $user['name'],
            'email'    => $user['email'],
            'avatar'   => $user['image'],
        ]);
    }
}
