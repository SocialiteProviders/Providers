<?php

namespace SocialiteProviders\Kommo;

use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    public const IDENTIFIER = 'KOMMO';

    /**
     * Expose additional config keys supported by this provider.
     */
    public static function additionalConfigKeys(): array
    {
        return ['subdomain'];
    }

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
     * Get the subdomain strictly from config.
     *
     * @return string
     */
    protected function getSubdomain()
    {

        $subdomain = (string) $this->getConfig('subdomain');

        if ($subdomain === '') {
            throw new \InvalidArgumentException(
                'Missing Kommo subdomain configuration. Please set it in config/services.php [services.kommo.subdomain] or define the KOMMO_SUBDOMAIN environment variable.'
            );
        }

        return $subdomain;
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
