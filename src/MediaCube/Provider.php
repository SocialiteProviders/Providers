<?php

namespace SocialiteProviders\MediaCube;

use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    const IDENTIFIER = 'MEDIACUBE';

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase(
            'https://mediacube.id/oauth/authorize',
            $state
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://mediacube.id/oauth/token';
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
        $requestHeaders = [
            'Accept'        => 'application/json',
            'Authorization' => 'Bearer '.$token,
        ];

        $response = $this->getHttpClient()->get(
            'https://mediacube.id/oauth/user',
            [
                'headers' => $requestHeaders,
            ]
        );

        $userData = json_decode($response->getBody(), true);

        return [
            'id'         => $userData['id'],
            'first_name' => $userData['first_name'],
            'last_name'  => $userData['last_name'],
            'email'      => $userData['email'],
        ];
    }

    protected function getTokenFields($code)
    {
        return [
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
            'code'          => $code,
            'redirect_uri'  => $this->redirectUrl,
            'grant_type'    => 'authorization_code',
        ];
    }

    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map($user);
    }

    /**
     * Return all decoded data in order to retrieve additional params like 'email'.
     *
     * {@inheritdoc}
     */
    protected function parseAccessToken($body)
    {
        return \Arr::get($body, 'access_token');
    }
}
