<?php

namespace SocialiteProviders\Envato;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'ENVATO';

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase('https://api.envato.com/authorization', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl(): string
    {
        return 'https://api.envato.com/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get('https://api.envato.com/v1/market/private/user/account.json', [
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer '.$token,
            ],
        ]);

        $response = json_decode((string) $response->getBody(), true)['account'];
        $response['email'] = $this->getEmailByToken($token);
        $response['username'] = $this->getUsernameByToken($token);

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user)->map([
            'id'    => null, 'nickname' => $user['username'],
            'name'  => $user['firstname'].' '.$user['surname'],
            'email' => $user['email'], 'avatar' => $user['image'],
        ]);
    }

    /**
     *  Get the account email of the current user.
     *
     * @param  string  $token
     * @return string
     */
    protected function getEmailByToken($token)
    {
        $response = $this->getHttpClient()->get('https://api.envato.com/v1/market/private/user/email.json', [
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer '.$token,
            ],
        ]);

        return json_decode((string) $response->getBody(), true)['email'];
    }

    /**
     *  Get the account username of the current user.
     *
     * @param  string  $token
     * @return string
     */
    protected function getUsernameByToken($token)
    {
        $response = $this->getHttpClient()->get('https://api.envato.com/v1/market/private/user/username.json', [
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer '.$token,
            ],
        ]);

        return json_decode((string) $response->getBody(), true)['username'];
    }
}
