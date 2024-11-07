<?php

namespace SocialiteProviders\Stripe;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'STRIPE';

    protected $scopes = ['read_write'];

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase(
            'https://connect.stripe.com/oauth/authorize',
            $state
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://connect.stripe.com/oauth/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get(
            'https://api.stripe.com/v1/account',
            [
                RequestOptions::HEADERS => [
                    'Authorization' => 'Bearer '.$token,
                ],
            ]
        );

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        $nickname = null;
        if (isset($user['settings']['dashboard']['display_name'])) { // 2019-02-19 API change
            $nickname = $user['settings']['dashboard']['display_name'];
        } elseif (isset($user['display_name'])) { // original location
            $nickname = $user['display_name'];
        }

        return (new User)->setRaw($user)->map([
            'id'       => $user['id'],
            'nickname' => $nickname,
            'name'     => null,
            'email'    => $user['email'] ?? null,
            'avatar'   => null,
        ]);
    }
}
