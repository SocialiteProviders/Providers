<?php

namespace SocialiteProviders\Nuvemshop;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

/**
 * @see https://tiendanube.github.io/api-documentation/authentication
 */
class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'NUVEMSHOP';

    /**
     * {@inheritdoc}
     */
    protected $scopes = ['read'];

    /**
     * The separating character for the requested scopes.
     *
     * @var string
     */
    protected $scopeSeparator = ' ';

    /**
     * Returns the configured user id that we're authenticating with
     *
     * @return string
     */
    private function getClientId()
    {
        return $this->getConfig('client_id');
    }

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase(
            sprintf(
                'https://www.tiendanube.com/apps/%s/authorize',
                $this->getClientId()
            ),
            $state
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://www.tiendanube.com/apps/authorize/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get(
            'https://api.nuvemshop.com.br/v1/store',
            [
                RequestOptions::HEADERS => [
                    'Authentication' => 'Bearer ' . $token
                ]
            ]
        );

        return json_decode((string)$response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user)->map([
            'id' => $user['user_id'],
            'nickname' => null,
            'name' => null,
            'email' => $user['email'],
            'avatar' => null
        ]);
    }

    /**
     * Acquire a new access token using the refresh token.
     *
     * @see https://dev.nuvemshop.com.br/es/docs/applications/authentication
     *
     * @param string $refreshToken
     * @return ResponseInterface
     *
     */
    public function refreshToken($refreshToken): ResponseInterface
    {
        $response = $this->getHttpClient()->post(
            $this->getTokenUrl(),
            [
                RequestOptions::HEADERS => [
                    'Accept' => 'application/json'
                ],
                RequestOptions::FORM_PARAMS => [
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'grant_type' => 'authorization_code',
                    'code' => $refreshToken
                ]
            ]
        );

        return json_decode((string)$response->getBody(), true);
    }
}
