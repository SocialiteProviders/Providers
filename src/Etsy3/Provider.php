<?php

namespace SocialiteProviders\Etsy3;

use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    const IDENTIFIER = 'ETSY3';

    /**
     * List of all the scope permissions.
     */
    protected $scopes = [
        'address_r', 'address_w', 'billing_r', 'cart_r', 'cart_w', 'email_r',
        'favorites_r', 'favorites_w', 'feedback_r', 'listings_d', 'listings_r',
        'listings_w', 'profile_r', 'profile_w', 'recommend_r', 'recommend_w',
        'shops_r', 'shops_w', 'transactions_r', 'transactions_w'
    ];

    protected $scopeSeparator = ' ';

    protected $usesPKCE = true;

    /**
     * Produce the authorization URL so the application can redirect to it.
     * A requirement is that the PKCE needs to be enabled according to Etsy's
     * documentation.
     */
    protected function getAuthUrl($state)
    {
        return $this
            ->buildAuthUrlFromBase('https://www.etsy.com/oauth/connect', $state);
    }

    /**
     * The URL that returns the information for the access token.
     */
    protected function getTokenUrl()
    {
        return 'https://api.etsy.com/v3/public/oauth/token';
    }

    /**
     * Get information for the user depending on the authorized token.
     */
    protected function getUserByToken($token)
    {
        list($userId, ) = explode('.', $token);

        $response = $this->getHttpClient()->get(sprintf('https://openapi.etsy.com/v3/application/users/%s', $userId), [
            'headers' => [
                'x-api-key' => config('services.etsy3.client_id'),
                'Authorization' => sprintf('Bearer %s', $token)
            ],
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * Map user details to the user object.
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'id' => $user['user_id'],
            'nickname' => $user['first_name'],
            'name' => $user['login_name'],
            'email' => $user['primary_email'],
            'avatar' => $user['image_url_75x75'],
        ]);
    }

    /**
     * Get an array of the token fields.
     */
    protected function getTokenFields($code)
    {
        return array_merge(parent::getTokenFields($code), [
            'grant_type' => 'authorization_code'
        ]);
    }
}
