<?php

namespace SocialiteProviders\Adobe;

use GuzzleHttp\Exception\GuzzleException;
use SocialiteProviders\Manager\Contracts\OAuth2\ProviderInterface;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider implements ProviderInterface
{
    public const IDENTIFIER = 'ADOBE';
    public const BASE_URL = 'https://ims-na1.adobelogin.com/ims';

    protected array $scopes = ['openid', 'email', 'profile'];

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase(self::BASE_URL . '/authorize', $state);
    }

    protected function getTokenUrl(): string
    {
        return self::BASE_URL . '/token';
    }

    protected function getTokenFields($code): array
    {
        return [
            ...parent::getTokenFields(),
            'grant_type' => 'authorization_code'
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @throws GuzzleException
     */
    protected function getUserByToken($token): array
    {
        // Check if the credentials response body already has the data provided to us
        // If not, fetch the data from their API
        if (empty($this->credentialsResponseBody) || empty($this->credentialsResponseBody['sub'])) {
            $response = $this->httpClient->post(self::BASE_URL . '/userinfo', [
                'headers' => [
                    'Authorization' => "Bearer $token",
                    'Accept' => 'application/json'
                ]
            ]);

            return json_decode($response->getBody(), true);
        }

        return $this->credentialsResponseBody;
    }

    protected function mapUserToObject(array $user): User
    {
        return (new User)
            ->setRaw($user)
            ->map([
                'id' => $user['sub'],
                'name' => $user['name'],
                'email' => $user['email']
            ]);
    }
}
