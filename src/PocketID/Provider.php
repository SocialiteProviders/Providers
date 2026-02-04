<?php

namespace SocialiteProviders\PocketID;

use GuzzleHttp\RequestOptions;
use InvalidArgumentException;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'POCKETID';

    protected $scopes = ['openid', 'profile', 'email'];

    protected $scopeSeparator = ' ';

    /**
     * {@inheritdoc}
     */
    public static function additionalConfigKeys()
    {
        return ['base_url', 'use_pkce'];
    }

  	protected function usesPKCE()
	{
		return $this->getConfig('use_pkce') == true ? true : false;
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
        return $this->buildAuthUrlFromBase($this->getBaseUrl().'/authorize', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return $this->getBaseUrl().'/api/oidc/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get($this->getBaseUrl().'/api/oidc/userinfo', [
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
            'id'                 => $user['sub'],
            'name'               => $user['name'] ?? null,
            'given_name'         => $user['given_name'] ?? null,
            'family_name'        => $user['family_name'] ?? null,
            'preferred_username' => $user['preferred_username'] ?? null,
            'email'              => $user['email'] ?? null,
            'email_verified'     => $user['email_verified'] ?? null,
            'picture'            => $user['picture'] ?? null,
        ]);
    }
}
