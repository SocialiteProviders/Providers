<?php

namespace SocialiteProviders\EpicGames;

use GuzzleHttp\RequestOptions;
use Illuminate\Support\Arr;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'EPIC_GAMES';

    /**
     * The scopes being requested.
     *
     * @var array
     */
    protected $scopes = ['basic_profile'];

    /**
     * Build the authorization URL.
     */
    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase('https://www.epicgames.com/id/authorize', $state);
    }

    /**
     * Build the token URL.
     */
    protected function getTokenUrl(): string
    {
        return 'https://api.epicgames.dev/epic/oauth/v2/token';
    }

    /**
     * Get the POST fields for the token request.
     *
     * @param  string  $code
     * @return array
     */
    protected function getTokenFields($code)
    {
        return [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $this->redirectUrl,
        ];
    }

    /**
     * Get the headers for the token request.
     *
     * @param  string  $code
     * @return array
     */
    protected function getTokenHeaders($code)
    {
        return [
            'Authorization' => 'Basic '.base64_encode($this->clientId.':'.$this->clientSecret),
            'Content-Type' => 'application/x-www-form-urlencoded',
        ];
    }

    /**
     * Retrieve the logged in user data from Epic Games.
     *
     * @param  string  $token
     * @return array<string, mixed>
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get('https://api.epicgames.dev/epic/oauth/v2/userInfo', [
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer '.$token,
            ],
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * Map the raw payload to a Socialite user object.
     *
     * @param  array<string, mixed>  $user
     */
    protected function mapUserToObject(array $user): User
    {
        return (new User())->setRaw($user)->map([
            'id' => Arr::get($user, 'sub'),
            'nickname' => Arr::get($user, 'preferred_username'),
            'name' => Arr::get($user, 'name', Arr::get($user, 'preferred_username')),
            'email' => Arr::get($user, 'email'),
        ]);
    }
}
