<?php

namespace SocialiteProviders\Starling;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'STARLING';

    /**
     * Sandbox endpoint for testing.
     */
    public const BASE_SANDBOX_URL = 'https://api-sandbox.starlingbank.com/api/v2';

    /**
     * Production endpoint.
     */
    public const BASE_PRODUCTION_URL = 'https://api.starlingbank.com/api/v2';

    /**
     * Identity endpoint.
     */
    public const IDENTITY_ENDPOINT = '/identity/individual';

    /**
     * Token Identity endpoint.
     */
    public const TOKEN_IDENTITY_ENDPOINT = '/identity/token';

    protected $scopes = ['authorising-individual:read'];

    protected $scopeSeparator = ' ';

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        $url = $this->isSandbox() ? 'https://oauth-sandbox.starlingbank.com' : 'https://oauth.starlingbank.com';

        return $this->buildAuthUrlFromBase($url, $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        if ($this->useMTLS()) {
            return $this->isSandbox() ?
                'https://token-api-sandbox.starlingbank.com/oauth/access-token' :
                'https://token-api.starlingbank.com/oauth/access-token';
        }

        return $this->isSandbox() ?
            'https://api-sandbox.starlingbank.com/oauth/access-token' :
            'https://api.starlingbank.com/oauth/access-token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $options = [
            RequestOptions::HEADERS => [
                'Accept'        => 'application/json',
                'Authorization' => "Bearer $token",
            ],
        ];

        $identityResponse = $this->getHttpClient()->get($this->getBaseUrl().self::IDENTITY_ENDPOINT, $options);
        $user = json_decode((string) $identityResponse->getBody(), true);

        $tokenResponse = $this->getHttpClient()->get($this->getBaseUrl().self::TOKEN_IDENTITY_ENDPOINT, $options);
        $account = json_decode((string) $tokenResponse->getBody(), true);

        return array_merge_recursive($account, $user);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user)->map([
            'id'          => $user['accountHolderUid'],
            'name'        => trim(sprintf('%s %s %s', $user['title'], $user['firstName'], $user['lastName'])),
            'email'       => $user['email'],
            'phone'       => $user['phone'],
            'dateOfBirth' => $user['dateOfBirth'],
        ]);
    }

    /**
     * Get the base url.
     *
     * @return string
     */
    protected function getBaseUrl()
    {
        return $this->isSandbox() ? self::BASE_SANDBOX_URL : self::BASE_PRODUCTION_URL;
    }

    /**
     * Checks if the environment is sandbox.
     *
     * @return bool
     */
    protected function isSandbox()
    {
        return $this->getConfig('env', 'production') === 'sandbox';
    }

    /**
     * Checks if mTLS token endpoints should be used.
     *
     * @return bool
     */
    protected function useMTLS()
    {
        return $this->getConfig('use_mtls', false);
    }

    public static function additionalConfigKeys(): array
    {
        return ['env', 'use_mtls'];
    }
}
