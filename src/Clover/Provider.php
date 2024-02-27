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
    const IDENTIFIER = 'CLOVER';

    /**
     * {@inheritdoc}
     */
    protected $scopes = [''];

    /**
     * Indicates if the session state should be utilized.
     *
     * @var bool
     */
    protected $stateless = true;

    protected function getApiDomain(): string
    {
        return match (true) {
            config('services.clover.sandbox-mode') => 'sandbox.dev.clover.com',
            default => 'www.clover.com',
        };
    }

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase('https://'.$this->getApiDomain().'/oauth/v2/authorize', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        $domain = match (true) {
            config('services.clover.sandbox-mode') => 'apisandbox.dev.clover.com',
            default => 'api.clover.com',
        };

        return 'https://'.$domain.'/oauth/token?'.Arr::query([
            'client_id' => config('services.clover.client_id'),
            'client_secret' => config('services.clover.client_secret'),
            'code' => $this->getCode(),
        ]);
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

        $response = $this->getHttpClient()->get('https://'.$this->getApiDomain().'/v3/merchants/'.$requestParams['merchant_id'].'/employees/'.$requestParams['employee_id'], [
            'headers' => [
                'Authorization' => 'Bearer '.$token,
            ],
        ]);

        return json_decode($response->getBody(), true);
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

    /**
     * Get the access token response for the given code.
     *
     * @param  string  $code
     * @return array
     */
    public function getAccessTokenResponse($code)
    {
        $response = $this->getHttpClient()->get($this->getTokenUrl(), [
            RequestOptions::HEADERS => $this->getTokenHeaders($code),
        ]);

        return json_decode($response->getBody(), true);
    }
}
