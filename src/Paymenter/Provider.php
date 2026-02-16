<?php

namespace SocialiteProviders\Paymenter;

use GuzzleHttp\RequestOptions;
use InvalidArgumentException;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'PAYMENTER';

    protected $scopes = ['profile'];

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

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase($this->getBaseUrl().'/oauth/authorize', $state);
    }

    protected function getTokenUrl(): string
    {
        return $this->getBaseUrl().'/api/oauth/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get($this->getBaseUrl().'/api/oauth/me', [
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
            'email'              => $user['email'],
            'email_verified'     => !empty($user['email_verified_at']),
            'name'               => $user['first_name'],
            'family_name'        => $user['last_name'],
            'groups'             => $user['role_id'],
            'id'                 => $user['id'],
        ]);
    }
}
