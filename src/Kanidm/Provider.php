<?php

namespace SocialiteProviders\Kanidm;

use GuzzleHttp\RequestOptions;
use InvalidArgumentException;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'KANIDM';

    protected $scopes = [
        'email',
        'openid',
        'profile',
    ];

    protected $scopeSeparator = ' ';

    protected static array $additionalConfigKeys = ['base_url'];

    /**
     * Get the base URL.
     *
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    protected function getBaseUrl(): string
    {
        $baseUrl = $this->getConfig('base_url');

        if ($baseUrl === null) {
            throw new InvalidArgumentException('Missing base URL value.');
        }

        return $baseUrl;
    }

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase($this->getBaseUrl().'/ui/oauth2', $state);
    }

    protected function getTokenUrl(): string
    {
        return $this->getBaseUrl().'/oauth2/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $uri = "{$this->getBaseUrl()}/oauth2/openid/{$this->clientId}/userinfo";

        $response = $this->getHttpClient()->get($uri, [
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
            'id'       => $user['sub'],
            'nickname' => $user['preferred_username'],
            'name'     => trim(($user['given_name'] ?? '').' '.($user['family_name'] ?? '')),
            'email'    => $user['email'],
            'avatar'   => null,
        ]);
    }
}
