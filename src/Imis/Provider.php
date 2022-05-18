<?php

namespace SocialiteProviders\Imis;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    public const IDENTIFIER = 'Imis';

    /**
     * Get the host Base URL.
     *
     * @return string
     */
    protected function getImisUrl()
    {
        return $this->getConfig('host');
    }

    /**
     * {@inheritdoc}
     * Get the login URL, Links to IMIS SSO Client Application.
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase($this->getImisUrl() . $this->getConfig('client_id').'.aspx', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl(): string
    {
        return $this->getImisUrl().'/token';
    }

    /**
     * {@inheritdoc}
     * IMIS does not support userInfo endpoints, so a custom query must be used.
     */
    protected function getUserByToken($token): array
    {
        $response = $this->getHttpClient()->get($this->getConfig('host').'/api/query?QueryName=$/OAuth2/userInfo', [
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer '.$token,
            ],
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     * Required fields to get the Bearer Token.
     */
    protected function getTokenFields($code)
    {
        return array_merge(parent::getTokenFields($code), [
            'grant_type'    => 'refresh_token',
            'client_id'     => $this->getConfig('client_id'),
            'client_secret' => $this->getConfig('client_secret'),
            'refresh_token' => $code,
        ]);
    }

    /**
     * Returned user array containing all available user attributes
     * Modified IMIS aliases to match standard OAuth2 claims - https://openid.net/specs/openid-connect-core-1_0.html#StandardClaims.
     *
     * Assert is used to cencel the creation of the user and allow a redirect to the login url.
     * https://www.php.net/manual/en/function.assert-options.php
     *
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        // No IMIS guest users allowed. Redirect to login.
        assert(isset($user['Items']['$values'][0]) && count($user['Items']['$values'][0]) > 0, 'Guest user is not allowed');

        $user = $user['Items']['$values'][0];

        return (new User())->setRaw($user)->map([
            'id'       => $user['sub'] ?? null,
            'nickname' => null,
            'name'     => trim(($user['given_name'] ?? '').' '.($user['family_name'] ?? '')),
            'email'    => $user['email'] ?? null,
            'avatar'   => null,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public static function additionalConfigKeys(): array
    {
        return [
            'host',
            'login_url',
            'client_id',
            'client_secret',
            'query_name',
            'redirect',
        ];
    }
}
