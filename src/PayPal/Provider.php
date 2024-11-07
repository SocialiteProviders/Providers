<?php

namespace SocialiteProviders\PayPal;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

/**
 * @see https://developer.paypal.com/docs/api/identity/v1/
 * @see https://developer.paypal.com/api/rest/authentication/
 */
class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'PAYPAL';

    /**
     * {@inheritdoc}
     */
    protected $scopes = ['openid', 'profile', 'email'];

    /**
     * {@inheritdoc}
     */
    protected $scopeSeparator = ' ';

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase('https://www.paypal.com/signin/authorize', $state);
    }

    protected function getTokenUrl(): string
    {
        return 'https://api-m.paypal.com/v1/oauth2/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get('https://api-m.paypal.com/v1/identity/oauth2/userinfo', [
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer '.$token,
            ],
            RequestOptions::QUERY => [
                'schema' => 'paypalv1.1',
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
            'id'       => str_replace('https://www.paypal.com/webapps/auth/identity/user/', '', $user['user_id']),
            'nickname' => null,
            'name'     => $user['name'] ?? null,
            'email'    => collect($user['emails'] ?? [])->firstWhere('primary')['value'] ?? null,
            'avatar'   => null,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessTokenResponse($code)
    {
        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            RequestOptions::HEADERS => [
                'Accept'        => 'application/json',
                'Authorization' => 'Basic '.base64_encode("{$this->clientId}:{$this->clientSecret}"),
            ],
            RequestOptions::FORM_PARAMS => $this->getTokenFields($code),
        ]);

        return json_decode((string) $response->getBody(), true);
    }
}
