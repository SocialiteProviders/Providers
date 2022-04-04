<?php

namespace SocialiteProviders\Starling;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    public const IDENTIFIER = 'STARLING';

    /**
     * Sandbox endpoint for testing
     */
    public const BASE_SANDBOX_URL = 'https://api-sandbox.starlingbank.com/api/v2';

    /**
     * Production endpoint
     */
    public const BASE_PRODUCTION_URL = 'https://api.starlingbank.com/api/v2';

    /**
     * Identity endpoint
     */
    public const IDENTITY_ENDPOINT = '/identity/individual';

    /**
     * {@inheritdoc}
     */
    protected $scopes = ['authorising-individual:read'];

    /**
     * {@inheritdoc}
     */
    protected $scopeSeparator = ' ';

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        if ($this->isSandbox()) {
            return $this->buildAuthUrlFromBase('https://oauth-sandbox.starlingbank.com', $state);
        }

        return $this->buildAuthUrlFromBase('https://oauth.starlingbank.com', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        if ($this->isSandbox()) {
            return 'https://api-sandbox.starlingbank.com/oauth/access-token';
        }

        return 'https://token-api.starlingbank.com/oauth/access-token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get($this->getBaseUrl().self::IDENTITY_ENDPOINT, [
            RequestOptions::HEADERS => [
                'Accept'        => 'application/json',
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
        return (new User())->setRaw($user)->map([
            'id'    => $user['id'] ?? '', // TODO: get from /api/v2/account-holder
            'name'  => trim(sprintf('%s %s %s', $user['title'], $user['firstName'], $user['lastName'])),
            'email' => $user['email'],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenFields($code)
    {
        return array_merge(parent::getTokenFields($code), [
            'grant_type' => 'authorization_code',
        ]);
    }

    protected function getBaseUrl()
    {
        if ($this->isSandbox()) {
            return self::BASE_SANDBOX_URL;
        }

        return self::BASE_PRODUCTION_URL;
    }

    protected function isSandbox()
    {
        return $this->getConfig('env', 'production') === 'sandbox';
    }

    /**
     * {@inheritdoc}
     */
    public static function additionalConfigKeys()
    {
        return ['env'];
    }
}
