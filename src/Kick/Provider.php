<?php

namespace SocialiteProviders\Kick;

use GuzzleHttp\RequestOptions;
use Illuminate\Support\Arr;
use Laravel\Socialite\Two\Token;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'KICK';

    protected $scopes = ['user:read'];

    protected $usesPKCE = true;

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase('https://id.kick.com/oauth/authorize', $state);
    }

    protected function getTokenUrl(): string
    {
        return 'https://id.kick.com/oauth/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get(
            'https://api.kick.com/public/v1/users',
            [
                RequestOptions::HEADERS => [
                    'Accept'        => 'application/json',
                    'Authorization' => 'Bearer ' . $token
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
        $user = $user['data'][0];

        return (new User)->setRaw($user)->map([
            'id'       => $user['user_id'],
            'nickname' => $user['name'],
            'name'     => $user['name'],
            'email'    => $user['email'],
            'avatar'   => $user['profile_picture'] ?? null,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function refreshToken($refreshToken)
    {
        $response = $this->getRefreshTokenResponse($refreshToken);

        return new Token(
            Arr::get($response, 'access_token'),
            Arr::get($response, 'refresh_token'),
            Arr::get($response, 'expires_in'),
            Arr::get($response, 'scope', [])
        );
    }
}
