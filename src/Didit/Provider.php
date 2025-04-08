<?php

namespace SocialiteProviders\Didit;

use GuzzleHttp\RequestOptions;
use Illuminate\Support\Arr;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    const IDENTIFIER = 'DIDIT';

    protected $scopes = ['openid', 'profile', 'names', 'email', 'picture'];

    protected $scopeSeparator = ' ';

    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase('https://apx.didit.me/auth/v2/authorize', $state);
    }

    protected function getTokenUrl()
    {
        return 'https://apx.didit.me/auth/v2/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get('https://apx.didit.me/auth/v2/users/retrieve', [
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
        return (new User)->setRaw($user)->map([
            'id' => Arr::get($user, 'sub'),
            'nickname' => null,
            'name' => Arr::get($user, 'names.full_name'),
            'email' => Arr::get($user, 'email.email'),
            'avatar' => Arr::get($user, 'picture'),
        ]);
    }
}
