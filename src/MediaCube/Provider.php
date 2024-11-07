<?php

namespace SocialiteProviders\MediaCube;

use GuzzleHttp\RequestOptions;
use Illuminate\Support\Arr;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'MEDIACUBE';

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase('https://mediacube.id/oauth/authorize', $state);
    }

    protected function getTokenUrl(): string
    {
        return 'https://mediacube.id/oauth/token';
    }

    /**
     * Get the raw user for the given access token.
     *
     * @param  string  $token
     * @return array
     */
    protected function getUserByToken($token)
    {
        $requestHeaders = [
            'Accept'        => 'application/json',
            'Authorization' => 'Bearer '.$token,
        ];

        $response = $this->getHttpClient()->get(
            'https://mediacube.id/oauth/user',
            [
                RequestOptions::HEADERS => $requestHeaders,
            ]
        );

        $userData = json_decode((string) $response->getBody(), true);

        return [
            'id'         => $userData['id'],
            'first_name' => $userData['first_name'],
            'last_name'  => $userData['last_name'],
            'email'      => $userData['email'],
        ];
    }

    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user)->map($user);
    }

    /**
     * Return all decoded data in order to retrieve additional params like 'email'.
     *
     * {@inheritdoc}
     */
    protected function parseAccessToken($body)
    {
        return Arr::get($body, 'access_token');
    }
}
