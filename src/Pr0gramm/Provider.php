<?php

namespace SocialiteProviders\Pr0gramm;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'PR0GRAMM';

    protected $scopes = [
        'user.me',
    ];

    /**
     * {@inheritdoc}
     */
    protected $encodingType = PHP_QUERY_RFC3986;

    protected $scopeSeparator = ' ';

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase('https://pr0gramm.com/oauth/authorize', $state);
    }

    protected function getTokenUrl(): string
    {
        return 'https://pr0gramm.com/api/oauth/createAccessToken';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get('https://pr0gramm.com/api/user/me', [
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer '.$token,
                'Cache-Control' => 'no-cache',
                'Content-Type'  => 'application/x-www-form-urlencoded',
                'User-Agent'    => 'pr0-auth',
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
            'id'   => $user['identifier'],
            'name' => $user['name'],
        ]);
    }
}
