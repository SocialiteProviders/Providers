<?php

namespace SocialiteProviders\Trakt;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    public const IDENTIFIER = 'TRAKT';

    /**
     * {@inheritdoc}
     */
    protected $scopes = [''];

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase('https://api.trakt.tv/oauth/authorize', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://api.trakt.tv/oauth/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get('https://api.trakt.tv/users/me?extended=full', [
            RequestOptions::HEADERS =>  [
                'Authorization'     => 'Bearer '.$token,
                'trakt-api-version' => $this->getConfig('api_version', '2'),
                'trakt-api-key'     => $this->clientId,
            ],
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'nickname' => $user['username'],
            'name'     => $user['name'],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenFields($code)
    {
        return array_merge(parent::getTokenFields($code), [
            'grant_type' => 'authorization_code',
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
