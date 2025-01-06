<?php

namespace SocialiteProviders\TwitCasting;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'TWITCASTING';

    /**
     * The Base URL.
     */
    public const BASE_URL = 'https://apiv2.twitcasting.tv';

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase(self::BASE_URL.'/oauth2/authorize', $state);
    }

    protected function getTokenUrl(): string
    {
        return self::BASE_URL.'/oauth2/access_token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get(self::BASE_URL.'/verify_credentials', [
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
        return (new User)->setRaw($user)->map([
            'id'       => $user['user']['id'],
            'nickname' => $user['user']['screen_id'],
            'name'     => $user['user']['name'],
            'email'    => null,
            'avatar'   => $user['user']['image'],
        ]);
    }
}
