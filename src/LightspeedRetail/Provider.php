<?php

namespace SocialiteProviders\LightspeedRetail;

use GuzzleHttp\RequestOptions;
use Laravel\Socialite\Two\InvalidStateException;
use SocialiteProviders\Manager\Contracts\OAuth2\ProviderInterface;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider implements ProviderInterface
{
    const IDENTIFIER = 'LIGHTSPEEDRETAIL';

    protected $scopes = [];

    /**
     * The domain prefix for the current OAuth flow.
     *
     * @var string|null
     */
    protected $domainPrefix = null;

    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase('https://secure.retail.lightspeed.app/connect', $state);
    }

    /**
     * Get the token URL for the provider.
     * For the initial request, we must use the domain_prefix from the callback.
     *
     * @return string
     */
    protected function getTokenUrl()
    {
        // We must get the domain_prefix from the callback for the initial token request
        $domainPrefix = request()->input('domain_prefix', $this->domainPrefix);

        if (empty($domainPrefix)) {
            throw new \InvalidArgumentException(
                'Domain prefix is required to get the token URL. Make sure it is passed in the callback.'
            );
        }

        return "https://{$domainPrefix}.retail.lightspeed.app/api/1.0/token";
    }

    /**
     * Get the base API URL.
     *
     * @return string
     */
    protected function getBaseUrl()
    {
        return sprintf('https://%s.retail.lightspeed.app/api', $this->getDomainPrefix());
    }

    /**
     * Get the domain prefix.
     *
     * @return string
     */
    protected function getDomainPrefix()
    {
        if ($this->domainPrefix) {
            return $this->domainPrefix;
        }

        // For subsequent calls after token retrieval
        $configPrefix = $this->getConfig('domain_prefix');
        if (!empty($configPrefix)) {
            return $configPrefix;
        }

        throw new \InvalidArgumentException(
            'No domain_prefix found. It should be received from the token response ' .
            'or configured as a default in the config.'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function user()
    {
        if ($this->hasInvalidState()) {
            throw new InvalidStateException();
        }

        $response = $this->getAccessTokenResponse($this->getCode());

        // Store the domain_prefix from the token response
        if (isset($response['domain_prefix'])) {
            $this->domainPrefix = $response['domain_prefix'];
        }

        $userData = $this->getUserByToken(
            $token = $this->parseAccessToken($response)
        );

        // Add domain prefix to the raw user data
        $userData['domain_prefix'] = $this->domainPrefix;

        $user = $this->mapUserToObject($userData);

        return $user->setToken($token)
                    ->setRefreshToken($this->parseRefreshToken($response))
                    ->setExpiresIn($this->parseExpiresIn($response));
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get($this->getBaseUrl().'/2.0/user', [
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer '.$token,
            ],
        ]);

        return json_decode((string) $response->getBody(), true)['data'] ?? [];
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user)->map([
            'id'       => $user['id'],
            'nickname' => $user['display_name'],
            'name'     => $user['username'],
            'email'    => $user['email']
        ]);
    }

    /**
     * Set the domain prefix for API calls.
     *
     * @param string $domainPrefix
     * @return $this
     */
    public function setDomainPrefix($domainPrefix)
    {
        $this->domainPrefix = $domainPrefix;

        return $this;
    }

    /**
     * Get access token response from provider
     *
     * @param string $code
     * @return array
     */
    public function getAccessTokenResponse($code)
    {
        $response = parent::getAccessTokenResponse($code);

        // Store the domain_prefix from the token response
        if (isset($response['domain_prefix'])) {
            $this->domainPrefix = $response['domain_prefix'];
        }

        return $response;
    }

    /**
     * Refresh a token.
     *
     * @param string $refreshToken
     * @return array
     */
    public function refreshToken($refreshToken)
    {
        if (!$this->domainPrefix) {
            throw new \InvalidArgumentException(
                'Domain prefix must be set before refreshing tokens. Use setDomainPrefix() method.'
            );
        }

        $refreshTokenUrl = "https://{$this->domainPrefix}.retail.lightspeed.app/api/1.0/token";

        $response = $this->getHttpClient()->post($refreshTokenUrl, [
            'headers' => ['Accept' => 'application/json'],
            'form_params' => [
                'grant_type' => 'refresh_token',
                'refresh_token' => $refreshToken,
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
            ],
        ]);

        $refreshedToken = json_decode((string) $response->getBody(), true);

        // Update domain prefix in case it changed
        if (isset($refreshedToken['domain_prefix'])) {
            $this->domainPrefix = $refreshedToken['domain_prefix'];
        }

        return $refreshedToken;
    }
}
