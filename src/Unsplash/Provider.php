<?php

namespace SocialiteProviders\Unsplash;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'UNSPLASH';

    protected $scopeSeparator = '+';

    protected $scopes = ['public'];

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase('https://unsplash.com/oauth/authorize', $state);
    }

    protected function getTokenUrl(): string
    {
        return 'https://unsplash.com/oauth/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get(
            'https://api.unsplash.com/me',
            [
                RequestOptions::HEADERS => [
                    'Accept'        => 'application/json',
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
            'id'         => $user['id'],
            'nickname'   => $user['username'],
            'name'       => $user['name'] ?? null,
            'email'      => $user['email'] ?? null,
            'avatar'     => $user['profile_image']['medium'],
            'profileUrl' => $user['links']['html'],
        ]);
    }
}
