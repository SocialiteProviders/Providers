<?php

namespace SocialiteProviders\Clover;

use GuzzleHttp\RequestOptions;
use Illuminate\Support\Arr;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    const IDENTIFIER = 'clover';

    /**
     * {@inheritdoc}
     */
    protected $scopes = [];

    /**
     * Indicates if the session state should be utilized.
     *
     * @var bool
     */
    protected $stateless = true;

    public static function additionalConfigKeys(): array
    {
        return [
            'environment',
        ];
    }

    /**
     * Get the access token response for the given code.
     *
     * @param  string  $code
     */
    public function getAccessTokenResponse($code): array
    {
        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            RequestOptions::HEADERS => $this->getTokenHeaders($code),
            RequestOptions::JSON    => $this->getTokenFields($code),
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    protected function getApiDomain(): string
    {
        return match ($this->getConfig('environment')) {
            'sandbox'       => 'sandbox.dev.clover.com',
            'europe'        => 'api.eu.clover.com',
            'latin_america' => 'api.la.clover.com',
            default         => 'api.clover.com',
        };
    }

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase(
            sprintf('https://%s/oauth/v2/authorize', $this->getApiDomain()),
            $state
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl(): string
    {
        $domain = match ($this->getConfig('environment')) {
            'sandbox'       => 'apisandbox.dev.clover.com',
            'europe'        => 'eu.clover.com',
            'latin_america' => 'la.clover.com',
            default         => 'api.clover.com',
        };

        return sprintf('https://%s/oauth/v2/token', $domain);
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get(sprintf(
            'https://%s/v3/merchants/%s/employees/%s',
            $this->getApiDomain(),
            $this->request->query('merchant_id'),
            $this->request->query('employee_id'),
        ), [
            'headers' => [
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
            'id'       => $user['id'],
            'nickname' => $user['name'],
            'name'     => $user['name'],
            'email'    => $user['email'],
            'avatar'   => null,
        ]);
    }

    /**
     * Get the expires in from the token response body.
     *
     * @param  array  $body
     * @return string
     */
    protected function parseExpiresIn($body)
    {
        return (string) (Arr::get($body, 'access_token_expiration') - time());
    }
}
