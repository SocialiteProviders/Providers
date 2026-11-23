<?php

namespace SocialiteProviders\Tuurio;

use GuzzleHttp\RequestOptions;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

/**
 * @see https://id.tuurio.com/en/public/developers
 */
class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'TUURIO';

    protected $scopes = ['openid', 'profile', 'email'];

    protected $scopeSeparator = ' ';

    /**
     * Tuurio ID rejects authorization requests without a S256 code challenge,
     * for confidential clients too.
     */
    protected $usesPKCE = true;

    public static function additionalConfigKeys(): array
    {
        return ['base_url'];
    }

    /**
     * The issuer of the tenant, e.g. https://<tenant>.id.tuurio.com or the
     * tenant's own domain. Every tenant is its own issuer, so there is no
     * shared default.
     */
    protected function getBaseUrl(): string
    {
        $baseUrl = rtrim((string) $this->getConfig('base_url'), '/');

        if ($baseUrl === '') {
            throw new InvalidArgumentException('Missing base_url');
        }

        if (! str_starts_with($baseUrl, 'https://')) {
            throw new InvalidArgumentException('base_url must use https');
        }

        return $baseUrl;
    }

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase($this->getBaseUrl().'/oauth2/authorize', $state);
    }

    protected function getTokenUrl(): string
    {
        return $this->getBaseUrl().'/oauth2/token';
    }

    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get($this->getBaseUrl().'/userinfo', [
            RequestOptions::HEADERS => [
                'Accept'        => 'application/json',
                'Authorization' => 'Bearer '.$token,
            ],
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user)->map([
            'id'             => Arr::get($user, 'sub'),
            'nickname'       => Arr::get($user, 'preferred_username'),
            'name'           => Arr::get($user, 'name'),
            'email'          => Arr::get($user, 'email'),
            'email_verified' => Arr::get($user, 'email_verified'),
            'locale'         => Arr::get($user, 'locale'),
        ]);
    }
}
