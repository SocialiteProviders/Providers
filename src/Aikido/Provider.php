<?php

namespace SocialiteProviders\Aikido;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

/**
 * The Socialite provider for Aikido OAuth.
 */
class Provider extends AbstractProvider
{
    /**
     * The base URL for the Aikido OAuth provider.
     */
    private const BASE_URL = 'https://app.aikido.dev';

    public const IDENTIFIER = 'AIKIDO';

    /**
     * {@inheritdoc}
     */
    protected $scopes = [''];

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase(
            self::BASE_URL . '/oauth/authorize',
            $state
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return self::BASE_URL . '/api/oauth/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get(
            self::BASE_URL . '/api/public/v1/workspace',
            [
                RequestOptions::HEADERS => [
                    'Authorization' => "Bearer {$token}",
                ],
            ]
        );

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * Create the headers for the token request.
     *
     * @return array{Authorization: string, Accept: string}
     */
    protected function getTokenHeaders($code): array
    {
        return array_merge(parent::getTokenHeaders($code), [
            'Authorization' => 'Basic ' . base64_encode($this->clientId . ':' . $this->clientSecret),
        ]);
    }

    /**
     * Map the raw user array to a Socialite User instance.
     *
     * @param array{id: string, name: string} $user
     */
    protected function mapUserToObject(array $user): User
    {
        return (new User())->setRaw($user)->map([
            'id' => $user['id'],
            'name' => $user['name'],
        ]);
    }

    /**
     * Get the refresh token response for the given refresh token.
     *
     * @param string $refreshToken
     * @return array{access_token: string, expires_in: int, refresh_token: string, scope: string}
     */
    protected function getRefreshTokenResponse($refreshToken): array
    {
        $response = json_decode($this->getHttpClient()->post($this->getTokenUrl(), [
            RequestOptions::HEADERS => $this->getTokenHeaders($refreshToken),
            RequestOptions::FORM_PARAMS => [
                'grant_type' => 'refresh_token',
                'refresh_token' => $refreshToken,
            ],
        ])->getBody(), true);

        return array_merge($response, ['refresh_token' => $refreshToken]);
    }
}
