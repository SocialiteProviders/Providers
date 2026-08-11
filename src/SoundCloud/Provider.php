<?php

namespace SocialiteProviders\SoundCloud;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'SOUNDCLOUD';

    protected $scopes = ['non-expiring'];

    /**
     * {@inheritdoc}
     */
    protected $usesPKCE = true;

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase('https://secure.soundcloud.com/authorize', $state);
    }

    protected function getTokenUrl(): string
    {
        return 'https://secure.soundcloud.com/oauth/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get(
            'https://api.soundcloud.com/me',
            [
                RequestOptions::HEADERS => [
                    'Accept'        => 'application/json; charset=utf-8',
                    'Authorization' => 'OAuth '.$token,
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
            'id'   => $user['id'], 'nickname' => $user['username'],
            'name' => null, 'email' => null, 'avatar' => $user['avatar_url'],
        ]);
    }
}
