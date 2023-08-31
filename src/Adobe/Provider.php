<?php

namespace SocialiteProviders\Adobe;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

/**
 * @see https://www.adobe.io/authentication/auth-methods.html#!AdobeDocs/adobeio-auth/master/OAuth/OAuth.md
 */
class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'ADOBE';

    public const BASE_URL = 'https://ims-na1.adobelogin.com/ims';

    protected $scopes = ['openid', 'email', 'profile'];

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase(self::BASE_URL.'/authorize', $state);
    }

    protected function getTokenUrl(): string
    {
        return self::BASE_URL.'/token';
    }

    /**
     * {@inheritDoc}
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function getUserByToken($token): array
    {
        // Check if the credentials response body already has the data provided to us
        // If not, fetch the data from their API
        if (empty($this->credentialsResponseBody) || empty($this->credentialsResponseBody['sub'])) {
            $response = $this->getHttpClient()->post(self::BASE_URL.'/userinfo', [
                RequestOptions::HEADERS => [
                    'Authorization' => "Bearer $token",
                    'Accept'        => 'application/json',
                ],
            ]);

            return json_decode((string) $response->getBody(), true);
        }

        return $this->credentialsResponseBody;
    }

    protected function mapUserToObject(array $user): User
    {
        return (new User())
            ->setRaw($user)
            ->map([
                'id'    => $user['sub'],
                'name'  => $user['name'],
                'email' => $user['email'],
            ]);
    }
}
