<?php

namespace SocialiteProviders\Vercel;

use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    public const IDENTIFIER = 'vercel';

    protected static $authUrl = 'https://vercel.com/oauth/authorize';

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase(static::$authUrl, $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://api.vercel.com/v2/oauth/access_token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $teamId = $this->credentialsResponseBody['team_id'];

        $response = $this->getHttpClient()->get('https://api.vercel.com/www/user'.($teamId ? "?teamId={$teamId}" : ''), [
            'headers' => [
                'Authorization' => 'Bearer '.$token,
            ],
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user['user'])->map([
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
