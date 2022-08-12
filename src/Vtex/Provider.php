<?php

namespace SocialiteProviders\Vtex;

use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\ProviderInterface;
use Laravel\Socialite\Two\User;

class Provider extends AbstractProvider implements ProviderInterface
{
    /**
     * Get the authentication URL for the provider.
     *
     * @param string $state
     * 
     * @return string
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase(
            $this->appendStorefrontDomain('/_v/oauth2/auth'),
            $state
        );
    }

    /**
     * Get the POST fields for the token request.
     *
     * @param string $code
     *
     * @return array
     */
    protected function getTokenFields($code)
    {
        return array_merge(
            parent::getTokenFields($code),
            [
                'response_type' => 'code',
            ]
        );
    }

    /**
     * Get the token URL for the provider.
     *
     * @return string
     */
    protected function getTokenUrl()
    {
        return $this->appendStorefrontDomain('/_v/oauth2/token');
    }

    /**
     * Get the raw user for the given access token.
     *
     * @param string $token
     *
     * @return array
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get(
            $this->appendStorefrontDomain('/_v/oauth2/introspect'),
            ['body' => 'token='.$token]
        );

        return json_decode($response->getBody(), true);
    }

    /**
     * Build a complete endpoint concatenating storefront domain from services
     * configuration and path.
     *
     * @param string $path
     *
     * @return string
     */
    private function appendStorefrontDomain(string $path): string
    {
        return $this->getConfig('storefront_domain').$path;
    }

    /**
     * Map the raw user array to a Socialite User instance.
     *
     * @param array $user
     *
     * @return \Laravel\Socialite\Two\User
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'id'       => $user['userId'],
            'email'    => $user['email'],
            'name'     => $user['username'],
        ]);
    }
}
