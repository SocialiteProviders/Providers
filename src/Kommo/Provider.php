<?php

namespace SocialiteProviders\Kommo;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    public const string IDENTIFIER = 'KOMMO';

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase(
            'https://www.kommo.com/oauth',
            $state
        );
    }

    /**
     * Get the subdomain from config or request parameters
     */
    protected function getSubdomain()
    {
        // Try to get subdomain from request parameters first
        $subdomain = $this->request->get('subdomain');

        if ($subdomain) {
            return $subdomain;
        }

        // Fall back to config
        return $this->getConfig('subdomain', 'yayforms');
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        $subdomain = $this->getSubdomain();

        return "https://{$subdomain}.kommo.com/oauth2/access_token";
    }

    /**
     * {@inheritdoc}
     *
     * @throws GuzzleException
     */
    protected function getUserByToken($token)
    {
        $subdomain = $this->getSubdomain();
        $response = $this->getHttpClient()->get(
            "https://{$subdomain}.kommo.com/api/v4/account",
            [
                'headers' => [
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer '.$token,
                ],
            ]
        );

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user)->map([
            'id' => $user['id'] ?? null,
            'nickname' => $user['name'] ?? null,
            'name' => $user['name'] ?? null,
            'subdomain' => $user['subdomain'] ?? null,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenFields($code)
    {
        return array_merge(parent::getTokenFields($code), [
            'grant_type' => 'authorization_code',
        ]);
    }
}
