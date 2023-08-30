<?php

namespace SocialiteProviders\Imis;

use GuzzleHttp\RequestOptions;
use InvalidArgumentException;
use RuntimeException;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'IMIS';

    /**
     * Get the host Base URL.
     *
     * @return string
     *
     * @throws \RuntimeException
     */
    protected function getImisUrl(): string
    {
        $host = $this->getConfig('host', false);

        if ($host === false) {
            throw new RuntimeException('Missing Imis provider host config.');
        }

        return $host;
    }

    /**
     * {@inheritdoc}
     * Get the login URL, Links to IMIS SSO Client Application.
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase($this->getImisUrl().$this->clientId.'.aspx', $state);
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
        $response = $this->getHttpClient()->get($this->getConfig('host').'/api/query', [
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer '.$token,
            ],
            RequestOptions::QUERY => [
                'QueryName' => '$/OAuth2/userInfo',
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
            'refresh_token' => $code,
        ]);
    }

    /**
     * Returns a user array containing all available user attributes
     * Modify IMIS aliases to match standard OAuth2 claims - https://openid.net/specs/openid-connect-core-1_0.html#StandardClaims.
     *
     * You can catch InvalidArgumentException and use that to redirect the user back to IMIS.
     *
     * {@inheritdoc}
     *
     * @throws \InvalidArgumentException
     */
    protected function mapUserToObject(array $user)
    {
        // No IMIS guest users allowed. Throw an exception.
        if (! isset($user['Items']['$values'][0]) || (is_countable($user['Items']['$values'][0]) ? count($user['Items']['$values'][0]) : 0) < 1) {
            throw new InvalidArgumentException('Guest user is not allowed');
        }

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
        return ['host'];
    }
}
