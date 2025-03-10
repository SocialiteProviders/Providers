<?php

namespace SocialiteProviders\Harvest;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'HARVEST';

    public static function additionalConfigKeys(): array
    {
        return ['client_account'];
    }

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase('https://id.getharvest.com/oauth2/authorize', $state);
    }

    protected function getTokenUrl(): string
    {
        return 'https://id.getharvest.com/api/v1/oauth2/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get('https://api.harvestapp.com/v2/users/me', [
            RequestOptions::HEADERS => [
                'Harvest-Account-ID' => $this->getConfig('client_account'),
                'Authorization'      => 'Bearer '.$token,
            ],
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user)->map([
            'id'         => $user['id'],
            'first_name' => $user['first_name'],
            'last_name'  => $user['last_name'],
            'email'      => $user['email'],
            'avatar'     => $user['avatar_url'],
        ]);
    }
}
