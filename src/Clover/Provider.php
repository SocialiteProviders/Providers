<?php

namespace SocialiteProviders\Clover;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'clover';

    /**
     * {@inheritdoc}
     */
    protected $stateless = true;

    /**
     * {@inheritdoc}
     */
    public static function additionalConfigKeys(): array
    {
        return ['environment'];
    }

    /**
     * Determine if the current environment is sandbox.
     *
     * @return bool
     */
    protected function isSandbox(): bool
    {
        return $this->getConfig('environment') === 'sandbox';
    }

    /**
     * Get the API domain.
     *
     * @return string
     */
    protected function getApiDomain(): string
    {
        return $this->isSandbox() ? 'sandbox.dev.clover.com' : 'www.clover.com';
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
        $domain = $this->isSandbox() ? 'apisandbox.dev.clover.com' : 'api.clover.com';

        return sprintf('https://%s/oauth/token', $domain);
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
            $this->request->query('employee_id')
        ), [
            RequestOptions::HEADERS => [
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
            'id'       => $user['id'],
            'nickname' => $user['name'],
            'name'     => $user['name'],
            'email'    => $user['email'],
            'avatar'   => null,
        ]);
    }
}
