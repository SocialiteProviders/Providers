<?php

namespace SocialiteProviders\Lichess;

use Illuminate\Support\Arr;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    const IDENTIFIER = 'LICHESS';

    /**
     * The scopes being requested.
     *
     * @var array
     */
    protected $scopes = [
        'email:read',
    ];

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
     * @param string $token
     *
     * @return array $user
     */
    protected function getUserByToken($token)
    {
        $userUrl = 'https://lichess.org/api/account';

        $response = $this->getHttpClient()->get(
            $userUrl,
            $this->getRequestOptions($token)
        );

        $user = json_decode($response->getBody(), true);

        if (in_array('email:read', $this->scopes)) {
            $user = Arr::prepend($user, $this->getEmailByToken($token), 'email');
        }

        return $user;
    }

    /**
     * Get the default options for an HTTP request.
     *
     * @param string $token
     *
     * @return array
     */
    protected function getRequestOptions($token)
    {
        return [
            'headers' => [
                'Authorization' => 'Bearer '.$token,
            ],
        ];
    }

    /**
     * Get the email address for the user.
     *
     * @param string $token
     *
     * @return string
     */
    protected function getEmailByToken($token)
    {
        $url = 'https://lichess.org/api/account/email';

        $response = $this->getHttpClient()->get($url, [
            'headers' => [
                'Authorization' => 'Bearer '.$token,
            ],
        ]);

        $email = json_decode($response->getBody(), true);

        return Arr::get($email, 'email');
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'id'       => $user['id'],
            'username' => $user['username'],
            'email'    => $user['email'],
        ]);
    }

}
