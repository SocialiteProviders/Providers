<?php

namespace SocialiteProviders\Pixnet;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

/**
 * @see https://developer.pixnet.pro/ PIXNET API Developers website
 */
class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'PIXNET';

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase('https://emma.pixnet.cc/oauth2/authorize', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://emma.pixnet.cc/oauth2/grant';
    }

    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get(
            'https://emma.pixnet.cc/account',
            [
                RequestOptions::QUERY => ['access_token' => $token],
            ]
        );

        return json_decode((string) $response->getBody(), true);
    }

    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user)->map([
            'id'       => $user['account']['identity'],
            'nickname' => $user['account']['display_name'],
            'name'     => $user['account']['name'],
            'email'    => $user['account']['email'],
            'avatar'   => $user['account']['avatar'],
        ]);
    }
}
