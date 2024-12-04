<?php

namespace SocialiteProviders\PropelAuth;

use GuzzleHttp\RequestOptions;
use InvalidArgumentException;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'PROPELAUTH';

    protected $scopes = ['email', 'profile'];

    protected $scopeSeparator = ' ';

    /**
     * Get the base URL.
     *
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    protected function getPropelAuthUrl()
    {
        $baseUrl = $this->getConfig('auth_url');

        if ($baseUrl === null) {
            throw new InvalidArgumentException('Missing Auth URL value.');
        }

        return $baseUrl;
    }

    public static function additionalConfigKeys(): array
    {
        return ['auth_url'];
    }

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase($this->getPropelAuthUrl().'/propelauth/oauth/authorize', $state);
    }

    protected function getTokenUrl(): string
    {
        return $this->getPropelAuthUrl().'/propelauth/oauth/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get($this->getPropelAuthUrl().'/propelauth/oauth/userinfo', [
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
        return (new PropelAuthUser)->setRaw($user)->map($user);
    }
}
