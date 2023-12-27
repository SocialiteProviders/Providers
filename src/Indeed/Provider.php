<?php

namespace SocialiteProviders\Indeed;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'INDEED';

    /**
     * {@inheritdoc}
     */
    protected $scopeSeparator = ' ';

    protected $scopes = ['email', 'offline_access'];

    /**
     * @inheritDoc
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase('https://secure.indeed.com/oauth/v2/authorize', $state);
    }

    /**
     * @inheritDoc
     */
    protected function getTokenUrl()
    {
        return "https://apis.indeed.com/oauth/v2/tokens";
    }

    /**
     * @inheritDoc
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get('https://secure.indeed.com/v2/api/userinfo', [
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer ' . $token,
            ],
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * @inheritDoc
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user);
    }
}
