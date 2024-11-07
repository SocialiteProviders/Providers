<?php

namespace SocialiteProviders\Lichess;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'LICHESS';

    protected $scopes = [
        'email:read',
    ];

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
        return $this->buildAuthUrlFromBase('https://lichess.org/oauth', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://lichess.org/api/token';
    }

    /**
     * Get profile of the logged in user.
     *
     * @param  string  $token
     * @return array $user
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get(
            'https://lichess.org/api/account',
            $this->getRequestOptions($token)
        );

        $user = json_decode((string) $response->getBody(), true);

        if (in_array('email:read', $this->scopes, true)) {
            $user['email'] = $this->getEmailByToken($token);
        }

        return $user;
    }

    /**
     * Get the default options for an HTTP request.
     *
     * @param  string  $token
     * @return array
     */
    protected function getRequestOptions($token)
    {
        return [
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer '.$token,
            ],
        ];
    }

    /**
     * Get the email address for the user.
     *
     * @param  string  $token
     * @return string
     */
    protected function getEmailByToken($token)
    {
        $response = $this->getHttpClient()->get(
            'https://lichess.org/api/account/email',
            $this->getRequestOptions($token)
        );

        return json_decode((string) $response->getBody(), true)['email'] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user)->map([
            'id'       => $user['id'],
            'username' => $user['username'],
            'email'    => $user['email'],
        ]);
    }
}
