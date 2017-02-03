<?php

namespace SocialiteProviders\Shopify;

use Laravel\Socialite\Two\ProviderInterface;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider implements ProviderInterface
{
    /**
     * Unique Provider Identifier.
     */
    const IDENTIFIER = 'SHOPIFY';

    /**
     * {@inheritdoc}
     */
    protected $scopes = [
        'read_content', 'write_content',
        'read_themes', 'write_themes',
        'read_products', 'write_products',
        'read_customers', 'write_customers',
        'read_orders', 'write_orders',
        'read_script_tags', 'write_script_tags',
        'read_fulfillments', 'write_fulfillments',
        'read_shipping', 'write_shipping',
        'read_analytics',
        'read_users', 'write_users'
    ];

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase('https://' . $this->getSubdomain() . '.myshopify.com/admin/oauth/authorize', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://' . $this->getSubdomain() . '.myshopify.com/admin/oauth/access_token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get('https://' . $this->getSubdomain() . '.myshopify.com/admin/shop.json', [
            'headers' => [
                'Accept' => 'application/json'
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
            'id'       => $user['id'],
            'nickname' => $user['shopify_domain'],
            'name'     => null,
            'email'    => null,
            'avatar'   => null,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenFields($code)
    {
        return array_merge(parent::getTokenFields($code), [
            'grant_type' => 'authorization_code'
        ]);
    }

    private function getSubdomain()
    {
        return config('services.shopify.subdomain');
    }
}
