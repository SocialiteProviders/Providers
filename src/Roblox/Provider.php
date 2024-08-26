<?php

namespace SocialiteProviders\Roblox;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'ROBLOX';

    /**
     * {@inheritdoc}
     */
    protected $scopes = ['openid', 'profile'];

    /**
     * {@inheritdoc}
     */
    protected $scopeSeparator = ' ';

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase(
            'https://apis.roblox.com/oauth/v1/authorize',
            $state
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getCodeFields($state = null)
    {
        return array_merge(parent::getCodeFields($state), [
            'client_id'     => $this->clientId,
            'response_type' => 'code',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://apis.roblox.com/oauth/v1/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get(
            'https://apis.roblox.com/oauth/v1/userinfo',
            [
                RequestOptions::HEADERS => [
                    'Authorization' => 'Bearer '.$token,
                ],
            ]
        );

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user)->map([
            'id'       => $user['sub'],  // Roblox user ID
            'username' => $user['preferred_username'], // Roblox username (not display name)
            'nickname' => $user['preferred_username'], // Roblox display name (not guaranteed to be unique)
            'picture'  => $user['picture'] ?? null,  // Roblox may leave this null if the account is new
        ]);
    }
}
