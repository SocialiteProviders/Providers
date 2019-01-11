<?php

namespace SocialiteProviders\Shopify;

use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    const IDENTIFIER = 'SHOPIFY';

    /**
     * {@inheritdoc}
     */
    protected $scopes = [];

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase($this->shopifyUrl('/admin/oauth/authorize'), $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return $this->shopifyUrl('/admin/oauth/access_token');
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get($this->shopifyUrl('/admin/shop.json'), [
            'headers' => [
                'Accept'                 => 'application/json',
                'X-Shopify-Access-Token' => $token,
            ],
        ]);

        return json_decode($response->getBody(), true)['shop'];
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'id'       => $user['id'],
            'nickname' => $user['myshopify_domain'],
            'name'     => $user['name'],
            'email'    => $user['email'],
            'avatar'   => null,
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

    /**
     * {@inheritdoc}
     */
    public static function additionalConfigKeys()
    {
        return ['subdomain'];
    }

    /**
     * Work out the shopify domain based on either the
     * `subdomain` config setting or the current request.
     *
     * @param string $uri URI to append to the domain
     *
     * @return string The fully qualified *.myshopify.com url
     */
    private function shopifyUrl($uri = null)
    {
        if (!empty($this->parameters['subdomain'])) {
            return 'https://'.$this->parameters['subdomain'].'.myshopify.com'.$uri;
        }
        if ($this->getConfig('subdomain')) {
            return "https://{$this->getConfig('subdomain')}.myshopify.com".$uri;
        }

        return 'https://'.$this->request->get('shop').$uri;
    }
}
