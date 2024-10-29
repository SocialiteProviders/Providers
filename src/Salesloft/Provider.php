<?php

namespace SocialiteProviders\Salesloft;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

/**
 * @see https://developers.salesloft.com/docs/platform/api-basics/oauth-authentication
 * @see https://developers.salesloft.com/docs/api/me-index
 */
class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'SALESLOFT';

    public const PROVIDER_NAME = 'salesloft';

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase('https://accounts.salesloft.com/oauth/authorize', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl(): string
    {
        return 'https://accounts.salesloft.com/oauth/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get('https://api.salesloft.com/v2/me', [
            RequestOptions::HEADERS => [
                'Accept'        => 'application/json',
                'Authorization' => 'Bearer '.$token,
            ],
        ]);

        $response = json_decode((string) $response->getBody(), true);

        return $response['data'];
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user): User
    {
        return (new User)->setRaw($user)->map([
            'id'    => $user['id'],
            'name'  => $user['name'],
            'email' => $user['email'],
        ]);
    }
}
