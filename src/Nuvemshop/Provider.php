<?php

namespace SocialiteProviders\Nuvemshop;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'NUVEMSHOP';

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase($this->nuvemshopUrl('/admin/oauth/authorize'), $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return $this->nuvemshopUrl('/admin/oauth/access_token');
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get($this->nuvemshopUrl('/admin/shop.json'), [
            RequestOptions::HEADERS => [
                'Accept'                 => 'application/json',
                'X-Nuvemshop-Access-Token' => $token,
            ],
        ]);

        return json_decode((string) $response->getBody(), true)['shop'];
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user)->map([
            'id'       => $user['id'],
            'nickname' => $user['mynuvemshop_domain'],
            'name'     => $user['name'],
            'email'    => $user['email'],
            'avatar'   => null,
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
     * Work out the nuvemshop domain based on either the
     * `subdomain` config setting or the current request.
     *
     * @param  string  $uri  URI to append to the domain
     * @return string The fully qualified *.mynuvemshop.com url
     */
    private function nuvemshopUrl($uri = null)
    {
        if (! empty($this->parameters['subdomain'])) {
            return 'https://'.$this->parameters['subdomain'].'.mynuvemshop.com'.$uri;
        }
        if ($this->getConfig('subdomain')) {
            return "https://{$this->getConfig('subdomain')}.mynuvemshop.com".$uri;
        }

        return 'https://'.$this->request->get('shop').$uri;
    }
}
