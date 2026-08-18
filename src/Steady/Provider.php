<?php

namespace SocialiteProviders\Steady;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'STEADY';

    protected const BASE_URL = 'https://steadyhq.com';

    /**
     * The token exchange lives here too, not at the site root.
     */
    protected const API_URL = self::BASE_URL.'/api/v1';

    protected $scopeSeparator = ' ';

    protected $scopes = ['read'];

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase(self::BASE_URL.'/oauth/authorize', $state);
    }

    protected function getTokenUrl(): string
    {
        return self::API_URL.'/oauth/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get(self::API_URL.'/users/me', [
            RequestOptions::HEADERS => [
                'Accept'        => 'application/json',
                'Authorization' => 'Bearer '.$token,
            ],
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * The subscription the user holds for your publication, or null when they
     * have none.
     *
     * @return array<string, mixed>|null
     */
    public function getSubscriptionByToken(string $token): ?array
    {
        $response = $this->getHttpClient()->get(self::API_URL.'/subscriptions/me', [
            RequestOptions::HEADERS => [
                'Accept'        => 'application/json',
                'Authorization' => 'Bearer '.$token,
            ],
        ]);

        return json_decode((string) $response->getBody(), true)['data'] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        $attributes = $user['data']['attributes'] ?? [];

        $name = trim(($attributes['first-name'] ?? '').' '.($attributes['last-name'] ?? ''));

        return (new User)->setRaw($user)->map([
            'id'         => $user['data']['id'] ?? null,
            'nickname'   => null,
            'name'       => $name ?: null,
            'email'      => $attributes['email'] ?? null,
            'avatar'     => $attributes['avatar-url'] ?? null,
            'first_name' => $attributes['first-name'] ?? null,
            'last_name'  => $attributes['last-name'] ?? null,
        ]);
    }
}
