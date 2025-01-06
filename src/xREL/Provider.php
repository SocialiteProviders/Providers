<?php

namespace SocialiteProviders\xREL;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'XREL';

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase('https://api.xrel.to/v2/oauth2/auth', $state);
    }

    protected function getTokenUrl(): string
    {
        return 'https://api.xrel.to/v2/oauth2/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get(
            'https://api.xrel.to/v2/user/info.json',
            [
                RequestOptions::HEADERS => ['Authorization' => 'Bearer '.$token],
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
            'id'       => $user['id'],
            'nickname' => $user['name'],
            'name'     => $user['name'],
            'email'    => null,
            'avatar'   => null,
        ]);
    }
}
