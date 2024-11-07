<?php

namespace SocialiteProviders\Sage;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'SAGE';

    protected $scopes = ['full_access'];

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase('https://www.sageone.com/oauth2/auth/central', $state);
    }

    protected function getTokenUrl(): string
    {
        return 'https://oauth.accounting.sage.com/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get('https://api.accounting.sage.com/v3.1/user', [
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer '.$token,
            ],
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User)->map([
            'id'            => $user['id'],
            'name'          => $user['first_name'].' '.$user['last_name'],
            'first_name'    => $user['first_name'],
            'last_name'     => $user['last_name'],
            'email'         => $user['email'],
            'locale'        => $user['locale'],
        ]);
    }
}
