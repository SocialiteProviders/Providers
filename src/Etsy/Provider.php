<?php

namespace SocialiteProviders\Etsy;

use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    public const IDENTIFIER = 'ETSY';

    /**
     * {@inheritdoc}
     */
    protected $scopes = ['email_r'];

    /**
     * {@inheritdoc}
     */
    protected $scopeSeparator = ' ';

    /**
     * {@inheritdoc}
     */
    protected $usesPKCE = true;

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase('https://www.etsy.com/oauth/connect', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://openapi.etsy.com/v3/public/oauth/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $tokenData = explode('.', $token);
        $response = $this->getHttpClient()->get('https://openapi.etsy.com/v3/application/users/'.$tokenData[0], [
            'headers' => [
                'Authorization' => 'Bearer '.$token,
                'x-api-key'     => $this->clientId,
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
            'id'       => $user['user_id'],
            'nickname' => $user['login_name'],
            'name'     => $user['first_name'],
            'email'    => $user['primary_email'],
            'avatar'   => $user['image_url_75x75'],
        ]);
    }
}
