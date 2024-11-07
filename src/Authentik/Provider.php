<?php

namespace SocialiteProviders\Authentik;

use GuzzleHttp\RequestOptions;
use InvalidArgumentException;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'AUTHENTIK';

    protected $scopes = ['openid goauthentik.io/api profile email'];

    public static function additionalConfigKeys(): array
    {
        return ['base_url'];
    }

    protected function getBaseUrl()
    {
        $baseurl = $this->getConfig('base_url');
        if ($baseurl === null) {
            throw new InvalidArgumentException('Missing base_url');
        }

        return rtrim($baseurl, '/');
    }

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase($this->getBaseUrl().'/application/o/authorize/', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return $this->getBaseUrl().'/application/o/token/';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get($this->getBaseUrl().'/application/o/userinfo/', [
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer '.$token,
            ],
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    public function refreshToken($refreshToken)
    {
        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            RequestOptions::FORM_PARAMS => [
                'client_id'     => $this->clientId,
                'client_secret' => $this->clientSecret,
                'grant_type'    => 'refresh_token',
                'refresh_token' => $refreshToken,
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
            'email'              => $user['email'] ?? null,
            'email_verified'     => $user['email_verified'] ?? null,
            'name'               => $user['name'] ?? null,
            'given_name'         => $user['given_name'] ?? null,
            'family_name'        => $user['family_name'] ?? null,
            'preferred_username' => $user['preferred_username'] ?? null,
            'nickname'           => $user['nickname'] ?? null,
            'groups'             => $user['groups'] ?? null,
            'id'                 => $user['sub'],
        ]);
    }
}
