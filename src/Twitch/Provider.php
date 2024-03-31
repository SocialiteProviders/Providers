<?php

namespace SocialiteProviders\Twitch;

use GuzzleHttp\RequestOptions;
use Illuminate\Support\Arr;
use Laravel\Socialite\Two\Token;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'TWITCH';

    /**
     * {@inheritdoc}
     */
    protected $scopes = ['user:read:email'];

    /**
     * {@inherticdoc}.
     */
    protected $scopeSeparator = ' ';

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase(
            'https://id.twitch.tv/oauth2/authorize',
            $state
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://id.twitch.tv/oauth2/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get(
            'https://api.twitch.tv/helix/users',
            [
                RequestOptions::HEADERS => [
                    'Accept'        => 'application/json',
                    'Authorization' => 'Bearer '.$token,
                    'Client-ID'     => $this->clientId,
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
        $user = $user['data']['0'];

        return (new User())->setRaw($user)->map([
            'id'       => $user['id'],
            'nickname' => $user['display_name'],
            'name'     => $user['display_name'],
            'email'    => Arr::get($user, 'email'),
            'avatar'   => $user['profile_image_url'],
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
