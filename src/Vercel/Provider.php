<?php

namespace SocialiteProviders\Vercel;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'VERCEL';

    protected static $authUrl = 'https://vercel.com/oauth/authorize';

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase(static::$authUrl, $state);
    }

    protected function getTokenUrl(): string
    {
        return 'https://api.vercel.com/v2/oauth/access_token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $teamId = $this->credentialsResponseBody['team_id'] ?? null;
        $queryParameters = $teamId !== null ? ['teamId' => $teamId] : [];

        $response = $this->getHttpClient()->get('https://api.vercel.com/www/user', [
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer '.$token,
            ],
            RequestOptions::QUERY => $queryParameters,
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user['user'])->map([
            'id'       => $user['user']['uid'],
            'nickname' => $user['user']['username'],
            'name'     => $user['user']['name'],
            'email'    => $user['user']['email'],
            'avatar'   => 'https://api.vercel.com/www/avatar/'.$user['user']['uid'],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public static function setAuthUrl(string $url)
    {
        static::$authUrl = $url;
    }
}
