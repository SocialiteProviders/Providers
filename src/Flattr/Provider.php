<?php

namespace SocialiteProviders\Flattr;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'FLATTR';

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase('https://flattr.com/oauth/authorize', $state);
    }

    protected function getTokenUrl(): string
    {
        return 'https://flattr.com/oauth/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get(
            'https://api.flattr.com/rest/v2/user',
            [
                RequestOptions::HEADERS => [
                    'Authorization' => 'Bearer '.$token,
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
            'id'     => $user['id'], 'nickname' => $user['username'],
            'name'   => $user['firstname'].' '.$user['lastname'], 'email' => null,
            'avatar' => $user['avatar'],
        ]);
    }
}
