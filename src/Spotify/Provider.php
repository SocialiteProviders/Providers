<?php

namespace SocialiteProviders\Spotify;

use GuzzleHttp\RequestOptions;
use Illuminate\Support\Arr;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'SPOTIFY';

    /**
     * The separating character for the requested scopes.
     *
     * @var string
     */
    protected $scopeSeparator = ' ';

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase('https://accounts.spotify.com/authorize', $state);
    }

    protected function getTokenUrl(): string
    {
        return 'https://accounts.spotify.com/api/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get(
            'https://api.spotify.com/v1/me',
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
            'nickname'   => null,
            'name'       => $user['display_name'],
            'email'      => $user['email'] ?? null,
            'avatar'     => Arr::get($user, 'images.0.url'),
            'profileUrl' => $user['href'] ?? null,
        ]);
    }
}
