<?php

namespace SocialiteProviders\Pr0gramm;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    public const IDENTIFIER = 'PR0GRAMM';

    private const URL = 'https://pr0gramm.com';

    /**
     * {@inheritdoc}
     */
    protected $scopes = [
        'user.me',
    ];

    /**
     * {@inheritdoc}
     */
    protected $encodingType = PHP_QUERY_RFC3986;

    /**
     * The separating character for the requested scopes.
     *
     * @var string
     */
    protected $scopeSeparator = ' ';

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase(self::URL.'/oauth/authorize', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl(): string
    {
        return self::URL.'/api/oauth/createAccessToken';
    }

    /**
     * {@inheritdoc}
     *
     * @throws GuzzleException
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get(self::URL.'/api/user/name', [
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer '.$token,
                'cache-control' => 'no-cache',
                'Content-Type'  => 'application/x-www-form-urlencoded',
                'User-Agent'    => 'pr0-auth',
            ],
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    protected function mapUserToObject(array $user): \Laravel\Socialite\Two\User|User
    {
        return (new User())->setRaw($user)->map([
            'name' => $user['name'],
        ]);
    }
}
