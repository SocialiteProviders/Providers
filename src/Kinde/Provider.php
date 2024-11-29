<?php

namespace SocialiteProviders\Kinde;

use GuzzleHttp\RequestOptions;
use InvalidArgumentException;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'KINDE';

    protected $scopes = ['address', 'email', 'offline', 'openid', 'phone', 'profile'];

    protected $scopeSeparator = ' ';

    /**
     * Get the domain (aka base URL)
     *
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    protected function getDomain()
    {
        $baseUrl = $this->getConfig('domain');

        if ($baseUrl === null) {
            throw new InvalidArgumentException('Missing Base URL value.');
        }

        return $baseUrl;
    }

    public static function additionalConfigKeys(): array
    {
        return ['domain'];
    }

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase($this->getDomain().'/oauth2/auth', $state);
    }

    protected function getTokenUrl(): string
    {
        return $this->getDomain().'/oauth2/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get($this->getDomain().'/oauth2/v2/user_profile', [
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer '.$token,
            ],
        ]);

        $user = json_decode((string) $response->getBody(), true);

        // Merge user details with org_code and permissions from the token
        // No need to validate the token because it is already validated
        // by succesful profile request.
        $payload = json_decode(base64_decode(explode('.', $token)[1]), true); // assuming valid JWT token

        return array_merge($user, [
            'org_code'    => $payload['org_code'] ?? null,
            'permissions' => $payload['permissions'] ?? [],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user)->map([
            'id'       => $user['id'],
            'nickname' => $user['preferred_username'],
            'avatar'   => $user['picture'],
            'name'     => $user['name'],
            'email'    => $user['email'],
        ]);
    }
}
