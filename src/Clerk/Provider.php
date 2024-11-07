<?php

namespace SocialiteProviders\Clerk;

use GuzzleHttp\RequestOptions;
use InvalidArgumentException;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'CLERK';

    /**
     * {@inheritdoc}
     */
    protected $scopes = ['email', 'profile'];

    /**
     * {@inheritdoc}
     */
    protected $scopeSeparator = ' ';

    /**
     * Get the base URL.
     *
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    protected function getBaseUrl()
    {
        $baseUrl = $this->getConfig('base_url');

        if ($baseUrl === null) {
            throw new InvalidArgumentException('Missing Base URL value.');
        }

        return $baseUrl;
    }

    /**
     * {@inheritdoc}
     */
    public static function additionalConfigKeys(): array
    {
        return ['base_url'];
    }

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase($this->getBaseUrl().'/oauth/authorize', $state);
    }

    protected function getTokenUrl(): string
    {
        return $this->getBaseUrl().'/oauth/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get($this->getBaseUrl().'/oauth/userinfo', [
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
        return (new User)->setRaw($user)->map([
            'id'       => $user['user_id'],
            'nickname' => $user['username'],
            'name'     => $user['name'],
            'email'    => $user['email'],
            'avatar'   => $user['picture'],
        ]);
    }
}
