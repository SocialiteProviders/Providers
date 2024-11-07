<?php

namespace SocialiteProviders\IFSP;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'IFSP';

    protected $scopes = ['read'];

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase('https://suap.ifsp.edu.br/o/authorize/', $state);
    }

    protected function getTokenUrl(): string
    {
        return 'https://suap.ifsp.edu.br/o/token/';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get('https://suap.ifsp.edu.br/comum/user_info/', [
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer '.$token,
            ],
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        $name = $user['name'] ?? $user['username'];

        return (new User)->setRaw($user)->map([
            'id'       => $user['id'],
            'nickname' => $user['first_name'].' '.$user['last_name'],
            'name'     => $name,
            'email'    => $user['email'],
            'avatar'   => null,
        ]);
    }
}
