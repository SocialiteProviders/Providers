<?php

namespace SocialiteProviders\FreshBooks;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'FRESHBOOKS';

    protected $scopes = ['user:profile:read'];

    protected $scopeSeparator = ' ';

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase('https://auth.freshbooks.com/oauth/authorize', $state);
    }

    protected function getTokenUrl(): string
    {
        return 'https://api.freshbooks.com/auth/oauth/token';
    }

    protected function getUserByToken($token): array
    {
        $response = $this->getHttpClient()->get('https://api.freshbooks.com/auth/api/v1/users/me', [
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer '.$token,
            ],
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    protected function mapUserToObject(array $user): User
    {
        $data = $user['response'];

        return (new User)->setRaw($user)->map([
            'id'    => $data['id'],
            'name'  => trim($data['first_name'].' '.$data['last_name']),
            'email' => $data['email'],
        ]);
    }

    protected function getRefreshTokenResponse($refreshToken): array
    {
        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            RequestOptions::FORM_PARAMS => [
                'client_id'     => $this->clientId,
                'client_secret' => $this->clientSecret,
                'grant_type'    => 'refresh_token',
                'redirect_uri'  => $this->redirectUrl,
                'refresh_token' => $refreshToken,
            ],
            RequestOptions::HEADERS => ['Accept' => 'application/json'],
        ]);

        return json_decode((string) $response->getBody(), true);
    }
}
