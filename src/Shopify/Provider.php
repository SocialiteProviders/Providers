<?php

namespace SocialiteProviders\Shopify;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'SHOPIFY';

    private const API_VERSION = '2024-10';

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase($this->shopifyUrl('/admin/oauth/authorize'), $state);
    }

    protected function getTokenUrl(): string
    {
        return $this->shopifyUrl('/admin/oauth/access_token');
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->post($this->shopifyUrl('/admin/api/'.self::API_VERSION.'/graphql.json'), [
            RequestOptions::HEADERS => [
                'Accept'                 => 'application/json',
                'X-Shopify-Access-Token' => $token,
            ],
            RequestOptions::JSON => [
                'query' => '{
                    shop {
                        id
                        email
                        myshopifyDomain
                        shopOwnerName
                    }
                }'
            ]
        ]);

        return json_decode($response->getBody(), true)['data']['shop'];
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user)->map([
            'id'       => $user['id'],
            'nickname' => $user['myshopifyDomain'],
            'name'     => $user['shopOwnerName'],
            'email'    => $user['email'],
            'avatar'   => null,
        ]);
    }

    public static function additionalConfigKeys(): array
    {
        return ['subdomain'];
    }

    /**
     * Work out the shopify domain based on either the
     * `subdomain` config setting or the current request.
     *
     * @param  string  $uri  URI to append to the domain
     * @return string The fully qualified *.myshopify.com url
     */
    private function shopifyUrl($uri = null)
    {
        if (! empty($this->parameters['subdomain'])) {
            return 'https://'.$this->parameters['subdomain'].'.myshopify.com'.$uri;
        }
        if ($this->getConfig('subdomain')) {
            return "https://{$this->getConfig('subdomain')}.myshopify.com".$uri;
        }

        return 'https://'.$this->request->get('shop').$uri;
    }
}
