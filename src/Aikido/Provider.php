<?php

namespace SocialiteProviders\Aikido;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'AIKIDO';

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase('https://app.aikido.dev/oauth/authorize', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl(): string
    {
        return 'https://app.aikido.dev/api/oauth/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get('https://app.aikido.dev/api/public/v1/workspace', [
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer '.$token,
            ],
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenHeaders($code): array
    {
        return array_merge(parent::getTokenHeaders($code), [
            'Authorization' => 'Basic '.base64_encode($this->clientId.':'.$this->clientSecret),
        ]);
    }

    /**
     * Map the raw user array to a Socialite User instance.
     *
     * @param  array{id: string, name: string}  $user
     * @return \SocialiteProviders\Manager\OAuth2\User
     */
    protected function mapUserToObject(array $user): User
    {
        return (new User)->setRaw($user)->map([
            'id'   => $user['id'],
            'name' => $user['name'],
        ]);
    }

    /**
     * Get the refresh token response for the given refresh token.
     *
     * @param  string  $refreshToken
     * @return array{access_token: string, expires_in: int, refresh_token: string, scope: string}
     */
    protected function getRefreshTokenResponse($refreshToken): array
    {
        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            RequestOptions::FORM_PARAMS => [
                'grant_type'    => 'refresh_token',
                'refresh_token' => $refreshToken,
            ],
            RequestOptions::HEADERS => [
                'Accept'        => 'application/json',
                'Authorization' => 'Basic '.base64_encode($this->clientId.':'.$this->clientSecret),
            ],
        ]);

        $data = json_decode((string) $response->getBody(), true);

        return array_merge($data, ['refresh_token' => $refreshToken]);
    }
}
