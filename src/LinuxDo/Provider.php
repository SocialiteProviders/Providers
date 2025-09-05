<?php

namespace SocialiteProviders\LinuxDo;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    const IDENTIFIER = 'LINUXDO';

    protected $scopes = ['user'];

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase('https://connect.linux.do/oauth2/authorize', $state);
    }

    protected function getTokenUrl(): string
    {
        return 'https://connect.linux.do/oauth2/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get('https://connect.linux.do/api/user', [
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer ' . $token,
            ],
        ]);

        return json_decode((string)$response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        $login = $user['login'];
        $avatar_url = $user['avatar_url'];

        return (new User)->setRaw($user)->map([
            'id'              => $user['id'],
            'sub'             => $user['sub'],
            'username'        => $user['username'],
            'login'           => $login,
            'name'            => $user['name'],
            'email'           => $user['email'],
            'avatar_url'      => $avatar_url,
            'avatar_template' => $user['avatar_template'],
            'active'          => $user['active'],
            'trust_level'     => $user['trust_level'],
            'silenced'        => $user['silenced'],
            'external_ids'    => $user['external_ids'],
            'api_key'         => $user['api_key'],
            'nickname'        => $login,
            'avatar'          => $avatar_url,
        ]);
    }
}
