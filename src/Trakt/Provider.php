<?php

namespace SocialiteProviders\Trakt;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'TRAKT';

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase('https://api.trakt.tv/oauth/authorize', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl(): string
    {
        return 'https://api.trakt.tv/oauth/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get('https://api.trakt.tv/users/me', [
            RequestOptions::HEADERS => [
                'Authorization'     => 'Bearer '.$token,
                'trakt-api-version' => $this->getConfig('api_version', '2'),
                'trakt-api-key'     => $this->clientId,
            ],
            RequestOptions::QUERY => [
                'extended' => 'full',
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
            'nickname' => $user['username'],
            'name'     => $user['name'],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public static function additionalConfigKeys()
    {
        return ['api_version'];
    }
}
