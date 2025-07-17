<?php

namespace SocialiteProviders\RunSignup;

use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;
use GuzzleHttp\RequestOptions;
use InvalidArgumentException;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'RUNSIGNUP';

    /**
     * Get additional config keys
     *
     * @return array
     */
    public static function additionalConfigKeys(): array
    {
        return ['environment'];
    }

    /**
     * Get base URL
     *
     * @return string
     */
    protected function getBaseUrl()
    {
        $rsuEnv = $this->getConfig('environment', 'prod');
        if (! in_array($rsuEnv, ['prod', 'test'], true)) {
            throw new InvalidArgumentException('Invalid RSU environment value.');
        }
        return sprintf('https://%srunsignup.com', $rsuEnv === 'prod' ? '' : 'test.');
    }

    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase($this->getBaseUrl() . '/Profile/OAuth2/RequestGrant', $state);
    }

    /**
     * {@inheritDoc}
     */
    protected function getTokenUrl()
    {
        return $this->getBaseUrl() . '/Rest/v2/auth/auth-code-redemption.json';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get(
            $this->getBaseUrl() . '/Rest/User/Self',
            [
                RequestOptions::HEADERS => [
                    'Accept'        => 'application/json',
                    'Authorization' => 'Bearer '.$token
                ],
                RequestOptions::QUERY => [
                    'format' => 'json'
                ],
            ]
        );

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        $user = $user['user'];

        return (new User)->setRaw($user)->map([
            'id' => $user['user_id'] ?? null,
            'name' => $user['first_name'].' '.$user['last_name'],
            'email' => $user['email'],
            'avatar' => $user['profile_image_url'],
        ]);
    }
}
