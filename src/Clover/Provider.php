<?php

namespace SocialiteProviders\Clover;

use GuzzleHttp\RequestOptions;
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

    public static function additionalConfigKeys()
    {
        return [
            'environment',
        ];
    }

    /**
     * Get the access token response for the given code.
     *
     * @param  string  $code
     * @return array
     */
    public function getAccessTokenResponse($code)
    {
        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            RequestOptions::HEADERS => $this->getTokenHeaders($code),
            RequestOptions::JSON => $this->getTokenFields($code),
        ]);

        return json_decode($response->getBody(), true);
    }

    protected function getApiDomain(): string
    {
        return match ($this->getConfig('environment')) {
            'sandbox' => 'sandbox.dev.clover.com',
            default => 'www.clover.com',
        };
    }

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase(
            sprintf('https://%s/oauth/v2/authorize', $this->getApiDomain()),
            $state
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        $domain = match ($this->getConfig('environment')) {
            'sandbox' => 'apisandbox.dev.clover.com',
            default => 'api.clover.com',
        };

        return sprintf('https://%s/oauth/v2/token', $domain);
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $requestParams = str(request()->fullUrl())
            ->after('?')
            ->explode('&')
            ->mapWithKeys(fn (string $keyAndValue) => [str($keyAndValue)->before('=')->toString() => str($keyAndValue)->after('=')->toString()]);

        $response = $this->getHttpClient()->get(sprintf(
            'https://%s/v3/merchants/%s/employees/%s',
            $this->getApiDomain(),
            $requestParams['merchant_id'],
            $requestParams['employee_id'],
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
        return (new User())->setRaw($user)->map([
            'id' => $user['id'],
            'nickname' => $user['name'],
            'name' => $user['name'],
            'email' => $user['email'],
            'avatar' => null,
        ]);
    }
}
